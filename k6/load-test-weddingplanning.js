import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('weddingplanning_request_duration');
const requestCounter = new Counter('weddingplanning_requests');

export const options = {
  stages: [
    { duration: '1m', target: 10 },
    { duration: '5m', target: 25 },
    { duration: '5m', target: 10 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<400', 'p(99)<800'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const companies = [1, 2, 3, 4, 5];
const clients = Array.from({ length: 150 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const companyId = getRandomElement(companies);
  const clientId = getRandomElement(clients);
  const token = 'weddingplanning-token-' + companyId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': companyId.toString(),
  };

  group('Get Weddings', () => {
    let response = http.get(
      `${BASE_URL}/api/weddingplanning/weddings`,
      { headers, tags: { name: 'GetWeddings' } }
    );

    check(response, {
      'weddings status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Create Wedding', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/weddingplanning/weddings`, {
      client_id: clientId,
      date: new Date(Date.now() + 60*24*60*60*1000).toISOString(),
      guests: 200,
      budget: 50000,
    }, { headers, tags: { name: 'CreateWedding' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'create wedding status is 201': (r) => r.status === 201,
      'wedding has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
