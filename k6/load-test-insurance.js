import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('insurance_request_duration');
const requestCounter = new Counter('insurance_requests');

export const options = {
  stages: [
    { duration: '1m', target: 15 },
    { duration: '5m', target: 40 },
    { duration: '5m', target: 15 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<400', 'p(99)<800'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const companies = [1, 2, 3, 4, 5];
const customers = Array.from({ length: 400 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const companyId = getRandomElement(companies);
  const customerId = getRandomElement(customers);
  const token = 'insurance-token-' + companyId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': companyId.toString(),
  };

  group('Get Policies', () => {
    let response = http.get(
      `${BASE_URL}/api/insurance/policies`,
      { headers, tags: { name: 'GetPolicies' } }
    );

    check(response, {
      'policies status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Create Policy', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/insurance/policies`, {
      customer_id: customerId,
      type: 'health',
      coverage: 100000,
    }, { headers, tags: { name: 'CreatePolicy' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'create policy status is 201': (r) => r.status === 201,
      'policy has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
