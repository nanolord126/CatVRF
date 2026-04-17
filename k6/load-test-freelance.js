import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('freelance_request_duration');
const requestCounter = new Counter('freelance_requests');

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
const platforms = [1, 2, 3, 4, 5];
const freelancers = Array.from({ length: 300 }, (_, i) => i + 1);
const clients = Array.from({ length: 300 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const platformId = getRandomElement(platforms);
  const freelancerId = getRandomElement(freelancers);
  const clientId = getRandomElement(clients);
  const token = 'freelance-token-' + platformId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': platformId.toString(),
  };

  group('Get Projects', () => {
    let response = http.get(
      `${BASE_URL}/api/freelance/projects`,
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

    let response = http.post(`${BASE_URL}/api/freelance/projects`, {
      client_id: clientId,
      freelancer_id: freelancerId,
      title: 'Web Development',
      budget: 5000,
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
