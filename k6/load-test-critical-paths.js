import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// Custom metrics for critical path monitoring
const errorRate = new Rate('errors');
const paymentCreationDuration = new Trend('payment_creation_duration');
const walletDepositDuration = new Trend('wallet_deposit_duration');
const walletWithdrawalDuration = new Trend('wallet_withdrawal_duration');
const fraudCheckDuration = new Trend('fraud_check_duration');
const apiResponseDuration = new Trend('api_response_duration');
const requestCounter = new Counter('requests_total');
const successfulPayments = new Counter('successful_payments');
const failedPayments = new Counter('failed_payments');

export const options = {
  stages: [
    { duration: '2m', target: 10 },   // Warm-up: 0 → 10 VUs
    { duration: '5m', target: 50 },   // Ramp-up: 10 → 50 VUs
    { duration: '10m', target: 100 }, // Peak load: 50 → 100 VUs
    { duration: '5m', target: 200 },  // Stress test: 100 → 200 VUs
    { duration: '5m', target: 100 },  // Ramp-down: 200 → 100 VUs
    { duration: '3m', target: 0 },    // Cooldown: 100 → 0 VUs
  ],
  thresholds: {
    'http_req_duration': ['p(95)<500', 'p(99)<1000', 'p(99.9)<2000'],
    'http_req_failed': ['rate<0.1'],
    'errors': ['rate<0.05'],
    'payment_creation_duration': ['p(95)<300', 'p(99)<600'],
    'wallet_deposit_duration': ['p(95)<200', 'p(99)<400'],
    'fraud_check_duration': ['p(95)<100', 'p(99)<200'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const API_VERSION = 'v1';

// Test data pools
const userIds = Array.from({ length: 1000 }, (_, i) => i + 1);
const tenantIds = Array.from({ length: 50 }, (_, i) => i + 1);
const paymentMethods = ['card', 'yookassa', 'tinkoff', 'sbp'];
const currencies = ['RUB', 'USD', 'EUR'];

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

function generateIdempotencyKey() {
  return `load-test-${Date.now()}-${Math.random().toString(36).substring(7)}`;
}

function generateCorrelationId() {
  return `corr-${Date.now()}-${Math.random().toString(36).substring(7)}`;
}

function getRandomAmount(min = 1000, max = 100000) {
  return Math.floor(Math.random() * (max - min)) + min;
}

export default function () {
  const userId = getRandomElement(userIds);
  const tenantId = getRandomElement(tenantId);
  const idempotencyKey = generateIdempotencyKey();
  const correlationId = generateCorrelationId();

  // Get auth token
  const authResponse = http.post(`${BASE_URL}/api/${API_VERSION}/auth/login`, JSON.stringify({
    email: `user${userId}@example.com`,
    password: 'test_password',
  }), {
    headers: { 'Content-Type': 'application/json' },
    tags: { name: 'AuthLogin' },
  });

  const token = authResponse.json('data.token') || 'test-token';
  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': tenantId.toString(),
    'X-Correlation-ID': correlationId,
  };

  group('Payment Creation Flow', () => {
    const startTime = new Date();

    const paymentResponse = http.post(
      `${BASE_URL}/api/${API_VERSION}/payments`,
      JSON.stringify({
        amount: getRandomAmount(1000, 50000),
        currency: getRandomElement(currencies),
        payment_method: getRandomElement(paymentMethods),
        metadata: {
          order_id: Math.floor(Math.random() * 100000),
          source: 'load-test',
        },
        idempotency_key: idempotencyKey,
      }),
      { headers, tags: { name: 'CreatePayment' } }
    );

    paymentCreationDuration.add(new Date() - startTime);
    requestCounter.add(1);

    const isPaymentSuccess = check(paymentResponse, {
      'payment creation status is 201': (r) => r.status === 201,
      'payment has ID': (r) => r.json('data.id') !== undefined,
      'payment status is pending': (r) => r.json('data.status') === 'pending',
      'response time < 500ms': (r) => r.timings.duration < 500,
    });

    if (isPaymentSuccess) {
      successfulPayments.add(1);
    } else {
      failedPayments.add(1);
      errorRate.add(1);
    }

    sleep(Math.random() * 0.5 + 0.1);
  });

  group('Wallet Operations Flow', () => {
    // Deposit operation
    const depositStart = new Date();

    const depositResponse = http.post(
      `${BASE_URL}/api/${API_VERSION}/wallet/deposit`,
      JSON.stringify({
        amount: getRandomAmount(1000, 100000),
        currency: 'RUB',
        description: 'Load test deposit',
      }),
      { headers, tags: { name: 'WalletDeposit' } }
    );

    walletDepositDuration.add(new Date() - depositStart);
    requestCounter.add(1);

    check(depositResponse, {
      'deposit status is 201': (r) => r.status === 201,
      'deposit has transaction ID': (r) => r.json('data.id') !== undefined,
      'response time < 200ms': (r) => r.timings.duration < 200,
    }) || errorRate.add(1);

    sleep(Math.random() * 0.3 + 0.1);

    // Withdrawal operation (30% of requests)
    if (Math.random() < 0.3) {
      const withdrawStart = new Date();

      const withdrawResponse = http.post(
        `${BASE_URL}/api/${API_VERSION}/wallet/withdraw`,
        JSON.stringify({
          amount: getRandomAmount(1000, 10000),
          currency: 'RUB',
          description: 'Load test withdrawal',
        }),
        { headers, tags: { name: 'WalletWithdraw' } }
      );

      walletWithdrawalDuration.add(new Date() - withdrawStart);
      requestCounter.add(1);

      check(withdrawResponse, {
        'withdrawal status is 200': (r) => r.status === 200,
        'response time < 300ms': (r) => r.timings.duration < 300,
      }) || errorRate.add(1);

      sleep(Math.random() * 0.3 + 0.1);
    }
  });

  group('Fraud Detection Flow', () => {
    const fraudStart = new Date();

    const fraudResponse = http.post(
      `${BASE_URL}/api/${API_VERSION}/fraud/check`,
      JSON.stringify({
        user_id: userId,
        operation_type: 'payment',
        amount: getRandomAmount(1000, 100000),
        ip_address: `192.168.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`,
        device_fingerprint: `device-${userId}-${Date.now()}`,
        location: {
          latitude: 55.7558 + (Math.random() - 0.5) * 0.1,
          longitude: 37.6173 + (Math.random() - 0.5) * 0.1,
        },
      }),
      { headers, tags: { name: 'FraudCheck' } }
    );

    fraudCheckDuration.add(new Date() - fraudStart);
    requestCounter.add(1);

    check(fraudResponse, {
      'fraud check status is 200': (r) => r.status === 200,
      'fraud score is valid': (r) => {
        const score = r.json('data.score');
        return score >= 0 && score <= 1;
      },
      'fraud decision exists': (r) => ['allow', 'block', 'review'].includes(r.json('data.decision')),
      'response time < 100ms': (r) => r.timings.duration < 100,
    }) || errorRate.add(1);

    sleep(Math.random() * 0.2 + 0.05);
  });

  group('API Health Check', () => {
    const healthStart = new Date();

    const healthResponse = http.get(`${BASE_URL}/api/health`, {
      headers,
      tags: { name: 'HealthCheck' },
    });

    apiResponseDuration.add(new Date() - healthStart);
    requestCounter.add(1);

    check(healthResponse, {
      'health check status is 200': (r) => r.status === 200,
      'health check is fast': (r) => r.timings.duration < 50,
    }) || errorRate.add(1);

    sleep(Math.random() * 0.1);
  });

  group('List Payments - Pagination Test', () => {
    const listStart = new Date();

    const listResponse = http.get(
      `${BASE_URL}/api/${API_VERSION}/payments?page=1&per_page=20`,
      { headers, tags: { name: 'ListPayments' } }
    );

    apiResponseDuration.add(new Date() - listStart);
    requestCounter.add(1);

    check(listResponse, {
      'list payments status is 200': (r) => r.status === 200,
      'response has data array': (r) => Array.isArray(r.json('data')),
      'pagination metadata exists': (r) => r.json('meta') !== undefined,
      'response time < 300ms': (r) => r.timings.duration < 300,
    }) || errorRate.add(1);

    sleep(Math.random() * 0.2);
  });

  // Random sleep to simulate realistic user behavior
  sleep(Math.random() * 2 + 1);
}

export function handleSummary(data) {
  return {
    'stdout': textSummary(data, { indent: ' ', enableColors: true }),
    './k6-results/critical-paths-summary.json': JSON.stringify(data, null, 2),
    './k6-results/critical-paths-summary.txt': textSummary(data, { indent: ' ', enableColors: false }),
  };
}
