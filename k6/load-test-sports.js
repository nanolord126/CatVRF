import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('sports_request_duration');
const requestCounter = new Counter('sports_requests');

export const options = {
  stages: [
    { duration: '1m', target: 15 },
    { duration: '5m', target: 40 },
    { duration: '5m', target: 15 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<350', 'p(99)<700'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const clubs = [1, 2, 3, 4, 5];
const athletes = Array.from({ length: 200 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const clubId = getRandomElement(clubs);
  const athleteId = getRandomElement(athletes);
  const token = 'sports-token-' + clubId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': clubId.toString(),
  };

  group('Get Trainings', () => {
    let response = http.get(
      `${BASE_URL}/api/sports/trainings`,
      { headers, tags: { name: 'GetTrainings' } }
    );

    check(response, {
      'trainings status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Register for Training', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/sports/registrations`, {
      athlete_id: athleteId,
      training_id: getRandomElement([13001, 13002, 13003]),
    }, { headers, tags: { name: 'RegisterTraining' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'register status is 201': (r) => r.status === 201,
      'registration has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
