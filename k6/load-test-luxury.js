import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('luxury_request_duration');
const requestCounter = new Counter('luxury_requests');

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
const boutiques = [1, 2, 3, 4, 5];
const clients = Array.from({ length: 150 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const boutiqueId = getRandomElement(boutiques);
  const clientId = getRandomElement(clients);
  const token = 'luxury-token-' + boutiqueId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': boutiqueId.toString(),
  };

  group('Get Products', () => {
    let response = http.get(
      `${BASE_URL}/api/luxury/products`,
      { headers, tags: { name: 'GetProducts' } }
    );

    check(response, {
      'products status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Book Experience', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/luxury/bookings`, {
      client_id: clientId,
      experience_id: getRandomElement([14001, 14002, 14003]),
      scheduled_at: new Date(Date.now() + 7*24*60*60*1000).toISOString(),
    }, { headers, tags: { name: 'BookExperience' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'book status is 201': (r) => r.status === 201,
      'booking has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
