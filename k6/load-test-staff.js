import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('staff_request_duration');
const requestCounter = new Counter('staff_requests');

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

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const companyId = getRandomElement(companies);
  const token = 'staff-token-' + companyId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': companyId.toString(),
  };

  group('Get Employees', () => {
    let response = http.get(
      `${BASE_URL}/api/staff/employees`,
      { headers, tags: { name: 'GetEmployees' } }
    );

    check(response, {
      'employees status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Get Payroll', () => {
    let start = new Date();

    let response = http.get(
      `${BASE_URL}/api/staff/payroll`,
      { headers, tags: { name: 'GetPayroll' } }
    );

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'payroll status is 200': (r) => r.status === 200,
    });
  });

  sleep(1);
}
