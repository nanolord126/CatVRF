import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('education_request_duration');
const requestCounter = new Counter('education_requests');

export const options = {
  stages: [
    { duration: '1m', target: 25 },
    { duration: '5m', target: 60 },
    { duration: '5m', target: 25 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<400', 'p(99)<800'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const institutions = [1, 2, 3, 4, 5];
const students = Array.from({ length: 400 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const institutionId = getRandomElement(institutions);
  const studentId = getRandomElement(students);
  const token = 'education-token-' + institutionId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': institutionId.toString(),
  };

  group('Get Courses', () => {
    let response = http.get(
      `${BASE_URL}/api/education/courses`,
      { headers, tags: { name: 'GetCourses' } }
    );

    check(response, {
      'courses status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Enroll Student', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/education/enrollments`, {
      student_id: studentId,
      course_id: getRandomElement([15001, 15002, 15003]),
    }, { headers, tags: { name: 'EnrollStudent' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'enroll status is 201': (r) => r.status === 201,
      'enrollment has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
