import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const paymentDuration = new Trend('payment_duration');
const fraudScoringDuration = new Trend('fraud_scoring_duration');
const authorizationDuration = new Trend('authorization_duration');
const databaseDuration = new Trend('database_duration');
const requestCounter = new Counter('requests_total');

export const options = {
  stages: [
    { duration: '2m', target: 10 },    // Ramp-up: 0 → 10 VUs over 2 min
    { duration: '5m', target: 50 },    // Ramp-up: 10 → 50 VUs over 5 min
    { duration: '10m', target: 100 },  // Ramp-up: 50 → 100 VUs over 10 min
    { duration: '5m', target: 50 },    // Ramp-down: 100 → 50 VUs over 5 min
    { duration: '2m', target: 0 },     // Ramp-down: 50 → 0 VUs over 2 min
  ],
  thresholds: {
    'http_req_duration': ['p(95)<500', 'p(99)<1000'], // 95% < 500ms, 99% < 1s
    'http_req_failed': ['rate<0.1'],  // <10% failed
    'errors': ['rate<0.05'],          // <5% errors
  },
};

const BASE_URL = 'http://localhost:8000';
const API_TOKEN = 'test-token-' + Math.random().toString(36).substring(7);

// Realistic user IDs and tenant IDs
const userIds = Array.from({ length: 100 }, (_, i) => i + 1);
const tenantIds = Array.from({ length: 10 }, (_, i) => i + 1);
const paymentIds = Array.from({ length: 1000 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

function generateIdempotencyKey() {
  return `test-${Date.now()}-${Math.random().toString(36).substring(7)}`;
}

export default function () {
  const userId = getRandomElement(userIds);
  const tenantId = getRandomElement(tenantIds);
  const idempotencyKey = generateIdempotencyKey();

  group('Authentication & Authorization', () => {
    let authStart = new Date();
    
    let response = http.post(`${BASE_URL}/api/login`, {
      email: `user${userId}@example.com`,
      password: 'password123',
    }, {
      headers: { 'Content-Type': 'application/json' },
      tags: { name: 'Login' },
    });

    authorizationDuration.add(new Date() - authStart);
    requestCounter.add(1);

    check(response, {
      'login status is 200': (r) => r.status === 200,
      'login response has token': (r) => r.body.includes('token'),
    }) || errorRate.add(1);

    const token = response.json('token');

    // Subsequent requests use this token
    const headers = {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
      'X-Tenant-ID': tenantId.toString(),
    };

    group('Payment Flow Load Test', () => {
      let paymentStart = new Date();

      // Step 1: Create payment transaction
      let paymentResponse = http.post(`${BASE_URL}/api/payments`, {
        amount: Math.floor(Math.random() * 10000) + 1000, // 1000-11000 копейки
        currency: 'RUB',
        description: 'Test payment',
        idempotency_key: idempotencyKey,
        tenant_id: tenantId,
      }, { headers, tags: { name: 'CreatePayment' } });

      paymentDuration.add(new Date() - paymentStart);
      requestCounter.add(1);

      check(paymentResponse, {
        'create payment status is 201': (r) => r.status === 201,
        'payment has ID': (r) => r.json('id') !== null,
        'payment status is pending': (r) => r.json('status') === 'pending',
      }) || errorRate.add(1);

      const paymentId = paymentResponse.json('id');

      sleep(0.5);

      // Step 2: Verify idempotency (duplicate request should return same result)
      let idempotencyResponse = http.post(`${BASE_URL}/api/payments`, {
        amount: Math.floor(Math.random() * 10000) + 1000,
        currency: 'RUB',
        description: 'Test payment',
        idempotency_key: idempotencyKey,
        tenant_id: tenantId,
      }, { headers, tags: { name: 'IdempotencyCheck' } });

      check(idempotencyResponse, {
        'idempotency check status is 200': (r) => r.status === 200,
        'returned same payment ID': (r) => r.json('id') === paymentId,
      }) || errorRate.add(1);

      sleep(0.3);

      // Step 3: Check payment status
      let statusResponse = http.get(`${BASE_URL}/api/payments/${paymentId}`, {
        headers,
        tags: { name: 'CheckPaymentStatus' },
      });

      check(statusResponse, {
        'get payment status is 200': (r) => r.status === 200,
        'payment status is authorized': (r) => r.json('status') === 'authorized',
      }) || errorRate.add(1);

      sleep(0.3);

      // Step 4: Capture payment
      let captureResponse = http.post(`${BASE_URL}/api/payments/${paymentId}/capture`, {}, {
        headers,
        tags: { name: 'CapturePayment' },
      });

      check(captureResponse, {
        'capture payment status is 200': (r) => r.status === 200,
        'payment status is captured': (r) => r.json('status') === 'captured',
      }) || errorRate.add(1);
    });

    sleep(1);

    group('Fraud Scoring Load Test', () => {
      let fraudStart = new Date();

      let fraudResponse = http.post(`${BASE_URL}/api/fraud-scoring`, {
        user_id: userId,
        operation_type: 'payment',
        amount: Math.floor(Math.random() * 100000) + 5000,
        ip_address: `192.168.1.${Math.floor(Math.random() * 255)}`,
        device_fingerprint: `device_${userId}_${Date.now()}`,
        location: { latitude: 55.7558, longitude: 37.6173 },
      }, { headers, tags: { name: 'FraudScoring' } });

      fraudScoringDuration.add(new Date() - fraudStart);
      requestCounter.add(1);

      check(fraudResponse, {
        'fraud scoring status is 200': (r) => r.status === 200,
        'fraud score is between 0-1': (r) => {
          const score = r.json('score');
          return score >= 0 && score <= 1;
        },
        'fraud decision exists': (r) => ['allow', 'block', 'review'].includes(r.json('decision')),
      }) || errorRate.add(1);
    });

    sleep(0.5);

    group('RBAC Authorization Load Test', () => {
      let authStart = new Date();

      let rbacResponse = http.get(`${BASE_URL}/api/auth/check-permission`, {
        headers: {
          ...headers,
          'X-Required-Role': 'owner',
          'X-Resource': 'tenant:' + tenantId,
        },
        tags: { name: 'RBACCheck' },
      });

      authorizationDuration.add(new Date() - authStart);
      requestCounter.add(1);

      check(rbacResponse, {
        'RBAC check status is 200 or 403': (r) => r.status === 200 || r.status === 403,
        'response has permission field': (r) => r.body.includes('permission') || r.body.includes('allowed'),
      });
    });

    sleep(0.5);

    group('Database Query Load Test', () => {
      let dbStart = new Date();

      let listResponse = http.get(`${BASE_URL}/api/payments?tenant_id=${tenantId}&limit=100`, {
        headers,
        tags: { name: 'ListPayments' },
      });

      databaseDuration.add(new Date() - dbStart);
      requestCounter.add(1);

      check(listResponse, {
        'list payments status is 200': (r) => r.status === 200,
        'list has data array': (r) => r.json('data') !== null,
        'pagination info exists': (r) => r.json('pagination') !== null,
      }) || errorRate.add(1);
    });

    sleep(0.5);

    group('Wishlist Service Load Test', () => {
      // Add to wishlist
      let addResponse = http.post(`${BASE_URL}/api/wishlist/add`, {
        item_id: Math.floor(Math.random() * 1000) + 1,
        item_type: 'product',
      }, { headers, tags: { name: 'AddToWishlist' } });

      check(addResponse, {
        'add to wishlist status is 201': (r) => r.status === 201,
      }) || errorRate.add(1);

      sleep(0.3);

      // Get wishlist
      let getResponse = http.get(`${BASE_URL}/api/wishlist`, {
        headers,
        tags: { name: 'GetWishlist' },
      });

      check(getResponse, {
        'get wishlist status is 200': (r) => r.status === 200,
        'wishlist is array': (r) => Array.isArray(r.json()),
      }) || errorRate.add(1);
    });

    sleep(1);
  });
}

export function handleSummary(data) {
  return {
    'stdout': textSummary(data, { indent: ' ', enableColors: true }),
    './load-test-results.json': JSON.stringify(data),
  };
}
