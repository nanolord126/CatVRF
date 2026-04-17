import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('eventplanning_request_duration');
const requestCounter = new Counter('eventplanning_requests');

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
const customers = Array.from({ length: 200 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const companyId = getRandomElement(companies);
  const customerId = getRandomElement(customers);
  const token = 'eventplanning-token-' + companyId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': companyId.toString(),
  };

  group('Get Events', () => {
    let response = http.get(
      `${BASE_URL}/api/eventplanning/events`,
      { headers, tags: { name: 'GetEvents' } }
    );

    check(response, {
      'events status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Create Event', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/eventplanning/events`, {
      customer_id: customerId,
      name: 'Wedding Reception',
      date: new Date(Date.now() + 30*24*60*60*1000).toISOString(),
      guests: 150,
    }, { headers, tags: { name: 'CreateEvent' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'create event status is 201': (r) => r.status === 201,
      'event has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
