import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('auto_request_duration');
const requestCounter = new Counter('auto_requests');

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
const workshops = [1, 2, 3, 4, 5];
const customers = Array.from({ length: 200 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const workshopId = getRandomElement(workshops);
  const customerId = getRandomElement(customers);
  const token = 'auto-token-' + workshopId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': workshopId.toString(),
  };

  group('Get Services', () => {
    let response = http.get(
      `${BASE_URL}/api/auto/services`,
      { headers, tags: { name: 'GetServices' } }
    );

    check(response, {
      'services status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Create Service Request', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/auto/requests`, {
      customer_id: customerId,
      service_type: 'maintenance',
      vehicle_make: 'Toyota',
      vehicle_model: 'Camry',
      notes: 'Regular maintenance',
    }, { headers, tags: { name: 'CreateServiceRequest' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'create request status is 201': (r) => r.status === 201,
      'request has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
