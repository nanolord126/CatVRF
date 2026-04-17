import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('wallet_request_duration');
const requestCounter = new Counter('wallet_requests');

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
const users = Array.from({ length: 600 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const userId = getRandomElement(users);
  const token = 'wallet-token-' + userId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': '1',
  };

  group('Get Balance', () => {
    let response = http.get(
      `${BASE_URL}/api/wallet/balance`,
      { headers, tags: { name: 'GetBalance' } }
    );

    check(response, {
      'balance status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.3);

  group('Get Transactions', () => {
    let start = new Date();

    let response = http.get(
      `${BASE_URL}/api/wallet/transactions`,
      { headers, tags: { name: 'GetTransactions' } }
    );

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'transactions status is 200': (r) => r.status === 200,
    });
  });

  sleep(0.5);
}
