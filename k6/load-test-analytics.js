import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('analytics_request_duration');
const requestCounter = new Counter('analytics_requests');

export const options = {
  stages: [
    { duration: '1m', target: 20 },
    { duration: '5m', target: 50 },
    { duration: '5m', target: 20 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<400', 'p(99)<800'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const organizations = [1, 2, 3, 4, 5];

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const organizationId = getRandomElement(organizations);
  const token = 'analytics-token-' + organizationId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': organizationId.toString(),
  };

  group('Get Metrics', () => {
    let response = http.get(
      `${BASE_URL}/api/analytics/metrics`,
      { headers, tags: { name: 'GetMetrics' } }
    );

    check(response, {
      'metrics status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Get Reports', () => {
    let start = new Date();

    let response = http.get(
      `${BASE_URL}/api/analytics/reports`,
      { headers, tags: { name: 'GetReports' } }
    );

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'reports status is 200': (r) => r.status === 200,
    });
  });

  sleep(1);
}
