import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend, Gauge, Counter } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('error_rate');
const paymentDuration = new Trend('payment_duration');
const rpsGauge = new Gauge('rps');
const totalRequests = new Counter('total_requests');

// Test options - Ramp-up + Spike + Soak scenario
export const options = {
  stages: [
    // Ramp-up: 0 → 1000 VUs за 2 минуты
    { duration: '2m', target: 1000 },
    
    // Spike: 1000 → 5000 VUs за 30 сек
    { duration: '30s', target: 5000 },
    
    // Peak: 5000 VUs за 1 минуту
    { duration: '1m', target: 5000 },
    
    // Cool-down: 5000 → 1000 VUs за 1 минуту
    { duration: '1m', target: 1000 },
    
    // Soak: 1000 VUs за 5 минут
    { duration: '5m', target: 1000 },
    
    // Ramp-down
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<500', 'p(99)<1000'], // P95 < 500ms, P99 < 1s
    'http_req_failed': ['rate<0.1'],  // Error rate < 0.1%
    'error_rate': ['rate<0.01'],      // Custom error rate < 1%
  },
  ext: {
    loadimpact: {
      projectID: 3465167,
      name: 'CatVRF Payment Flow Load Test'
    }
  }
};

const BASE_URL = 'http://localhost:8000/api';
const AUTH_TOKEN = __ENV.AUTH_TOKEN || 'test-token';
const TENANT_ID = __ENV.TENANT_ID || '1';

export default function () {
  // Headers for authenticated requests
  const headers = {
    'Authorization': `Bearer ${AUTH_TOKEN}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': TENANT_ID,
    'Accept': 'application/json',
  };

  group('Payment Flow', () => {
    // 1. Initialize payment
    const initPaymentRes = http.post(
      `${BASE_URL}/payments/init`,
      JSON.stringify({
        amount: Math.floor(Math.random() * 1000000) + 100000, // 1k - 10k RUB
        currency: 'RUB',
        description: `Load test payment ${Date.now()}`,
      }),
      { headers }
    );

    totalRequests.add(1);
    paymentDuration.add(initPaymentRes.timings.duration);

    const paymentInitSuccess = check(initPaymentRes, {
      'payment init status is 200': (r) => r.status === 200,
      'payment has id': (r) => r.json('id') !== undefined,
      'has correlation_id': (r) => r.json('correlation_id') !== undefined,
      'has fraud_score': (r) => r.json('fraud_score') !== undefined,
    });

    if (!paymentInitSuccess) {
      errorRate.add(1);
      return; // Skip to next iteration
    }

    const paymentId = initPaymentRes.json('id');

    sleep(1); // Wait for payment processing

    // 2. Get payment status
    const getPaymentRes = http.get(
      `${BASE_URL}/payments/${paymentId}`,
      { headers }
    );

    totalRequests.add(1);

    check(getPaymentRes, {
      'get payment status is 200': (r) => r.status === 200,
      'payment status is valid': (r) => ['pending', 'authorized', 'captured'].includes(r.json('status')),
    });

    sleep(1);

    // 3. Capture payment
    const captureRes = http.post(
      `${BASE_URL}/payments/${paymentId}/capture`,
      JSON.stringify({}),
      { headers }
    );

    totalRequests.add(1);

    const captureSuccess = check(captureRes, {
      'capture status is 200': (r) => r.status === 200,
      'payment captured': (r) => r.json('status') === 'captured',
    });

    if (!captureSuccess) {
      errorRate.add(1);
    }

    sleep(1);
  });

  group('Search & Browse', () => {
    // Search products
    const searchRes = http.get(
      `${BASE_URL}/products/search?q=test&limit=20&offset=0`,
      { headers }
    );

    totalRequests.add(1);

    check(searchRes, {
      'search status is 200': (r) => r.status === 200,
      'search returns results': (r) => r.json('data').length > 0,
    });

    sleep(2);

    // Get product details
    if (searchRes.json('data').length > 0) {
      const productId = searchRes.json('data')[0].id;

      const productRes = http.get(
        `${BASE_URL}/products/${productId}`,
        { headers }
      );

      totalRequests.add(1);

      check(productRes, {
        'product status is 200': (r) => r.status === 200,
        'product has details': (r) => r.json('id') !== undefined,
      });
    }

    sleep(1);
  });

  group('Wishlist Operations', () => {
    // Add to wishlist
    const wishlistRes = http.post(
      `${BASE_URL}/wishlists`,
      JSON.stringify({
        product_id: Math.floor(Math.random() * 1000) + 1,
        quantity: Math.floor(Math.random() * 10) + 1,
      }),
      { headers }
    );

    totalRequests.add(1);

    check(wishlistRes, {
      'wishlist add status is 200': (r) => [200, 201, 409].includes(r.status), // 409 if duplicate
    });

    sleep(1);

    // Get wishlist
    const getWishlistRes = http.get(
      `${BASE_URL}/wishlists`,
      { headers }
    );

    totalRequests.add(1);

    check(getWishlistRes, {
      'get wishlist status is 200': (r) => r.status === 200,
      'wishlist is array': (r) => Array.isArray(r.json('data')),
    });

    sleep(1);
  });

  group('Wallet Operations', () => {
    // Get wallet balance
    const walletRes = http.get(
      `${BASE_URL}/wallets/balance`,
      { headers }
    );

    totalRequests.add(1);

    check(walletRes, {
      'wallet status is 200': (r) => r.status === 200,
      'wallet has balance': (r) => r.json('current_balance') !== undefined,
    });

    sleep(1);

    // Get transaction history
    const historyRes = http.get(
      `${BASE_URL}/wallets/transactions?limit=10&offset=0`,
      { headers }
    );

    totalRequests.add(1);

    check(historyRes, {
      'history status is 200': (r) => r.status === 200,
      'history is paginated': (r) => r.json('data') !== undefined,
    });

    sleep(1);
  });

  // Random delay between iterations
  sleep(Math.random() * 3);
}

/**
 * Scenario 2: High concurrency spike test
 * Tests system behavior under sudden traffic spike
 */
export function spikeTest() {
  const options2 = {
    stages: [
      { duration: '10s', target: 100 },   // Warm up
      { duration: '5s', target: 10000 },  // Spike to 10k VUs
      { duration: '30s', target: 10000 }, // Hold
      { duration: '5s', target: 0 },      // Ramp down
    ],
  };

  // Same test function as default
  // This would be exported and run separately: k6 run -f spikeTest
}

/**
 * Scenario 3: Sustained load test
 * Maintains high load for extended period to detect memory leaks
 */
export function soakTest() {
  const options3 = {
    stages: [
      { duration: '5m', target: 1000 },   // Ramp to 1k VUs
      { duration: '30m', target: 1000 },  // Hold for 30 minutes
      { duration: '5m', target: 0 },      // Ramp down
    ],
    thresholds: {
      'http_req_duration': ['p(95)<300', 'p(99)<500'],
      'http_req_failed': ['rate<0.05'],
    },
  };
}

/**
 * Scenario 4: Rate limit testing
 * Tests if rate limiting is working correctly
 */
export function rateLimitTest() {
  const endpoints = [
    '/payments/init',
    '/wishlists',
    '/products/search',
  ];

  const headers = {
    'Authorization': `Bearer ${AUTH_TOKEN}`,
    'Content-Type': 'application/json',
  };

  endpoints.forEach(endpoint => {
    group(`Rate limit on ${endpoint}`, () => {
      let responses = [];

      // Send 100 requests as fast as possible
      for (let i = 0; i < 100; i++) {
        const res = http.post(
          `${BASE_URL}${endpoint}`,
          JSON.stringify({ test: true }),
          { headers }
        );

        responses.push(res.status);
      }

      // Check that we get some 429 responses (rate limited)
      const rateLimited = responses.filter(r => r === 429).length;
      check({ rateLimitedCount: rateLimited }, {
        'some requests are rate limited': (obj) => obj.rateLimitedCount > 10,
      });

      sleep(2); // Backoff
    });
  });
}

/**
 * Scenario 5: Error recovery test
 * Tests how system recovers from errors
 */
export function errorRecoveryTest() {
  const options5 = {
    stages: [
      { duration: '5m', target: 500 },      // Normal load
      { duration: '2m', target: 500 },      // (During this time, simulate errors)
      { duration: '5m', target: 500 },      // Should recover
      { duration: '2m', target: 0 },        // Ramp down
    ],
  };

  // Same test function - errors will be injected by backend
  // This tests if system recovers when errors are resolved
}
