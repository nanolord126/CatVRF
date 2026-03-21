/**
 * loadtest.js — k6 Load Test для CatVRF маркетплейса
 *
 * КОНФИГУРАЦИЯ:
 *   - Ramp-up:  0 → 1000 VU за 2 мин → 5000 VU за 3 мин → 10 000 VU за 5 мин
 *   - Spike:    10 000 → 50 000 VU за 30 с (пиковая атака)
 *   - Soak:     5 000 VU в течение 30 мин (стабильная нагрузка)
 *   - Cool-down: 5000 → 0 за 2 мин
 *
 * ПОРОГИ:
 *   - p(95) < 200 мс для wallet и recommendations
 *   - p(95) < 500 мс для payments
 *   - http_req_failed < 1%
 *   - rate limit 429 допускается
 *
 * ЗАПУСК:
 *   k6 run loadtest.js
 *   k6 run --vus 100 --duration 30s loadtest.js   (быстрый smoke)
 *   k6 run -e ENV=prod loadtest.js
 */

import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';
import { uuidv4 } from 'https://jslib.k6.io/k6-utils/1.4.0/index.js';

// ─── CUSTOM METRICS ────────────────────────────────────────────────────────
const paymentErrors      = new Counter('payment_errors');
const walletErrors       = new Counter('wallet_errors');
const fraudBlocked       = new Counter('fraud_blocked_requests');
const rateLimitHits      = new Counter('rate_limit_hits');
const walletLatency      = new Trend('wallet_latency');
const paymentLatency     = new Trend('payment_latency');
const recommendLatency   = new Trend('recommend_latency');
const searchLatency      = new Trend('search_latency');
const errorRate          = new Rate('error_rate');

// ─── ENVIRONMENT ───────────────────────────────────────────────────────────
const BASE_URL  = __ENV.BASE_URL  || 'http://localhost';
const API_TOKEN = __ENV.API_TOKEN || 'test-token-loadtest';
const TENANT_ID = __ENV.TENANT_ID || '1';

// ─── LOAD PROFILE ──────────────────────────────────────────────────────────
export const options = {
  scenarios: {
    // ── Ramp-up + Spike ─────────────────────────────────────────────────
    ramp_up_spike: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '2m',  target: 1_000  }, // Warm-up
        { duration: '3m',  target: 5_000  }, // Ramp-up
        { duration: '5m',  target: 10_000 }, // Heavy load
        { duration: '30s', target: 50_000 }, // SPIKE — 50k RPS
        { duration: '1m',  target: 10_000 }, // Post-spike recovery
        { duration: '2m',  target: 0      }, // Cool-down
      ],
      gracefulRampDown: '30s',
    },

    // ── Soak Test ────────────────────────────────────────────────────────
    soak: {
      executor: 'constant-vus',
      vus: 5_000,
      duration: '30m',
      startTime: '12m', // Start after ramp-up scenario peaks
    },

    // ── Fraud / Security Stress ──────────────────────────────────────────
    fraud_stress: {
      executor: 'constant-arrival-rate',
      rate: 100,         // 100 req/s malicious
      timeUnit: '1s',
      duration: '5m',
      preAllocatedVUs: 50,
      maxVUs: 200,
      startTime: '5m',
      exec: 'fraudScenario',
    },
  },

  thresholds: {
    // Global
    http_req_duration:   ['p(95)<500', 'p(99)<2000'],
    http_req_failed:     ['rate<0.01'],    // < 1% errors
    error_rate:          ['rate<0.01'],

    // Per-endpoint trends
    wallet_latency:      ['p(95)<200'],
    payment_latency:     ['p(95)<500'],
    recommend_latency:   ['p(95)<200'],
    search_latency:      ['p(95)<150'],
  },
};

// ─── HELPERS ───────────────────────────────────────────────────────────────
function headers() {
  return {
    'Content-Type':    'application/json',
    'Accept':          'application/json',
    'Authorization':   `Bearer ${API_TOKEN}`,
    'X-Tenant-ID':     TENANT_ID,
    'X-Correlation-ID': uuidv4(),
  };
}

function checkStatus(res, name, allowedCodes = [200, 201, 204]) {
  const ok = allowedCodes.includes(res.status);
  if (!ok) {
    if (res.status === 429) { rateLimitHits.add(1); }
    else if (res.status === 403 || res.status === 423) { fraudBlocked.add(1); }
    else { errorRate.add(1); }
  }
  return ok;
}

// ─── SCENARIO 1: MAIN USER FLOW ────────────────────────────────────────────
export default function () {
  const correlationId = uuidv4();
  const h = headers();

  group('Wallet — Баланс', function () {
    const start = Date.now();
    const res = http.get(`${BASE_URL}/api/wallet/balance`, { headers: h });
    walletLatency.add(Date.now() - start);

    const ok = checkStatus(res, 'wallet_balance');
    if (!ok) walletErrors.add(1);

    check(res, {
      'wallet: status 200 or 401': (r) => [200, 401].includes(r.status),
      'wallet: has balance field':  (r) => r.status !== 200 || JSON.parse(r.body).balance !== undefined,
    });
  });

  sleep(0.1);

  group('Recommendations — Рекомендации', function () {
    const start = Date.now();
    const res = http.get(`${BASE_URL}/api/recommendations?vertical=beauty&limit=10`, { headers: h });
    recommendLatency.add(Date.now() - start);

    checkStatus(res, 'recommendations', [200, 204, 429]);

    check(res, {
      'recommend: not 500': (r) => r.status !== 500,
      'recommend: fast':    (r) => r.timings.duration < 500,
    });
  });

  sleep(0.1);

  group('Search — Поиск', function () {
    const queries = ['стрижка', 'такси', 'маникюр', 'доставка', 'гостиница'];
    const q = queries[Math.floor(Math.random() * queries.length)];

    const start = Date.now();
    const res = http.get(`${BASE_URL}/api/search?q=${encodeURIComponent(q)}&limit=20`, { headers: h });
    searchLatency.add(Date.now() - start);

    checkStatus(res, 'search', [200, 429]);

    check(res, {
      'search: not 500':  (r) => r.status !== 500,
      'search: fast':     (r) => r.timings.duration < 300,
    });
  });

  sleep(0.2);

  group('Payment Init — Инициация платежа', function () {
    const payload = JSON.stringify({
      amount:          1_000 + Math.floor(Math.random() * 50_000),
      currency:        'RUB',
      idempotency_key: uuidv4(),
      provider:        'tinkoff',
    });

    const start = Date.now();
    const res = http.post(`${BASE_URL}/api/payments/init`, payload, { headers: h });
    paymentLatency.add(Date.now() - start);

    const ok = checkStatus(res, 'payment_init', [200, 201, 202, 422, 429]);
    if (!ok) paymentErrors.add(1);

    check(res, {
      'payment: has correlation_id': (r) => {
        if (r.status === 200) {
          return JSON.parse(r.body).correlation_id !== undefined;
        }
        return true;
      },
    });
  });

  sleep(0.3);

  group('Wishlist — Вишлист', function () {
    const itemId  = Math.floor(Math.random() * 1000) + 1;
    const payload = JSON.stringify({ item_type: 'product', item_id: itemId });

    const res = http.post(`${BASE_URL}/api/wishlist/add`, payload, { headers: h });
    checkStatus(res, 'wishlist_add', [200, 201, 409, 422, 429]);
  });

  sleep(0.1);
}

// ─── SCENARIO 2: FRAUD STRESS ─────────────────────────────────────────────
export function fraudScenario() {
  const h = headers();

  // Replay attack — same idempotency key
  const replayKey = 'replay-stress-key-fixed';
  const payload   = JSON.stringify({ amount: 9_999_999, currency: 'RUB', idempotency_key: replayKey });

  const r1 = http.post(`${BASE_URL}/api/payments/init`, payload, { headers: h });
  const r2 = http.post(`${BASE_URL}/api/payments/init`, payload, { headers: h });

  // If both succeed, IDs must match (idempotency)
  if (r1.status === 200 && r2.status === 200) {
    const b1 = JSON.parse(r1.body);
    const b2 = JSON.parse(r2.body);
    check(null, {
      'idempotency: same id on replay': () => b1.id === b2.id,
    });
  }

  // Rate limit bypass attempt — rapid fire
  for (let i = 0; i < 5; i++) {
    http.post(`${BASE_URL}/api/payments/init`, payload, { headers: h });
  }

  sleep(0.05);

  // SQL injection probe
  const sqliRes = http.get(
    `${BASE_URL}/api/search?q=${encodeURIComponent("'; DROP TABLE users; --")}`,
    { headers: h }
  );
  check(sqliRes, {
    'sqli: no 500': (r) => r.status !== 500,
    'sqli: no sql leak': (r) => !r.body.includes('SQL') && !r.body.includes('syntax'),
  });

  sleep(0.1);
}
