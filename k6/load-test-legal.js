import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('legal_request_duration');
const requestCounter = new Counter('legal_requests');

export const options = {
  stages: [
    { duration: '1m', target: 10 },
    { duration: '5m', target: 30 },
    { duration: '5m', target: 10 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<400', 'p(99)<800'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const firms = [1, 2, 3, 4, 5];
const clients = Array.from({ length: 200 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const firmId = getRandomElement(firms);
  const clientId = getRandomElement(clients);
  const token = 'legal-token-' + firmId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': firmId.toString(),
  };

  group('Get Cases', () => {
    let response = http.get(
      `${BASE_URL}/api/legal/cases`,
      { headers, tags: { name: 'GetCases' } }
    );

    check(response, {
      'cases status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Create Case', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/legal/cases`, {
      client_id: clientId,
      type: 'corporate',
      title: 'Contract Review',
    }, { headers, tags: { name: 'CreateCase' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'create case status is 201': (r) => r.status === 201,
      'case has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
