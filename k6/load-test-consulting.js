import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('consulting_request_duration');
const requestCounter = new Counter('consulting_requests');

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
const firms = [1, 2, 3, 4, 5];
const clients = Array.from({ length: 150 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const firmId = getRandomElement(firms);
  const clientId = getRandomElement(clients);
  const token = 'consulting-token-' + firmId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': firmId.toString(),
  };

  group('Get Projects', () => {
    let response = http.get(
      `${BASE_URL}/api/consulting/projects`,
      { headers, tags: { name: 'GetProjects' } }
    );

    check(response, {
      'projects status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Create Project', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/consulting/projects`, {
      client_id: clientId,
      name: 'Strategy Review',
      type: 'strategy',
    }, { headers, tags: { name: 'CreateProject' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'create project status is 201': (r) => r.status === 201,
      'project has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
