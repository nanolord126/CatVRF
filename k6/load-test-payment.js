import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('payment_request_duration');
const requestCounter = new Counter('payment_requests');

export const options = {
  stages: [
    { duration: '1m', target: 40 },
    { duration: '5m', target: 100 },
    { duration: '5m', target: 40 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<300', 'p(99)<600'],
    'http_req_failed': ['rate<0.05'],
  },
};

const BASE_URL = 'http://localhost:8000';
const gateways = [1, 2, 3, 4, 5];
const users = Array.from({ length: 600 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const gatewayId = getRandomElement(gateways);
  const userId = getRandomElement(users);
  const token = 'payment-token-' + gatewayId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': gatewayId.toString(),
  };

  group('Get Transactions', () => {
    let response = http.get(
      `${BASE_URL}/api/payment/transactions`,
      { headers, tags: { name: 'GetTransactions' } }
    );

    check(response, {
      'transactions status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.3);

  group('Process Payment', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/payment/payments`, {
      user_id: userId,
      amount: 1000 + Math.floor(Math.random() * 9000),
      currency: 'USD',
      method: 'card',
    }, { headers, tags: { name: 'ProcessPayment' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'process payment status is 201': (r) => r.status === 201,
      'payment has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(0.5);
}
