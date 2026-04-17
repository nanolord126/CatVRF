import http from 'k6/http';
import { check, sleep } from 'k6';
import { SharedArray } from 'k6/data';

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

// Simulate photo upload with base64
const generateBase64Photo = () => {
  // Simulate a small photo (1KB)
  const dummyPhoto = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
  return dummyPhoto;
};

export const options = {
  stages: [
    { duration: '30s', target: 10 },   // Ramp up to 10 users
    { duration: '1m', target: 50 },    // Ramp up to 50 users
    { duration: '2m', target: 100 },   // Ramp up to 100 users
    { duration: '1m', target: 50 },    // Ramp down to 50 users
    { duration: '30s', target: 0 },    // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'], // 95% of requests must complete below 2s
    http_req_failed: ['rate<0.05'],    // Error rate must be less than 5%
  },
};

export default function () {
  const userId = Math.floor(Math.random() * 1000) + 1;
  const correlationId = `test-${__VU}-${__ITER}`;

  const payload = {
    user_id: userId,
    photo: generateBase64Photo(),
    service_type: ['haircut', 'coloring', 'styling', 'makeup'][Math.floor(Math.random() * 4)],
    preferred_gender: ['male', 'female', ''][Math.floor(Math.random() * 3)],
    min_rating: Math.floor(Math.random() * 2) + 3,
    price_min: Math.floor(Math.random() * 2000) + 1000,
    price_max: Math.floor(Math.random() * 3000) + 5000,
  };

  const params = {
    headers: {
      'Content-Type': 'application/json',
      'X-Tenant-ID': '1',
      'X-Correlation-ID': correlationId,
      'Accept': 'application/json',
    },
  };

  const res = http.post(`${BASE_URL}/api/beauty/masters/match-by-photo`, JSON.stringify(payload), params);

  check(res, {
    'status is 200': (r) => r.status === 200,
    'has success': (r) => JSON.parse(r.body).success === true,
    'has matched_masters': (r) => JSON.parse(r.body).matched_masters !== undefined,
    'has analysis': (r) => JSON.parse(r.body).analysis !== undefined,
    'response time < 2s': (r) => r.timings.duration < 2000,
  });

  sleep(1);
}
