import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('fitness_request_duration');
const requestCounter = new Counter('fitness_requests');

export const options = {
  stages: [
    { duration: '1m', target: 20 },
    { duration: '5m', target: 50 },
    { duration: '5m', target: 20 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<350', 'p(99)<700'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const gyms = [1, 2, 3, 4, 5];
const members = Array.from({ length: 300 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const gymId = getRandomElement(gyms);
  const memberId = getRandomElement(members);
  const token = 'fitness-token-' + gymId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': gymId.toString(),
  };

  group('Get Classes', () => {
    let response = http.get(
      `${BASE_URL}/api/fitness/classes`,
      { headers, tags: { name: 'GetClasses' } }
    );

    check(response, {
      'classes status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Book Class', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/fitness/bookings`, {
      member_id: memberId,
      class_id: getRandomElement([12001, 12002, 12003]),
      scheduled_at: new Date(Date.now() + 2*24*60*60*1000).toISOString(),
    }, { headers, tags: { name: 'BookClass' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'book class status is 201': (r) => r.status === 201,
      'booking has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
