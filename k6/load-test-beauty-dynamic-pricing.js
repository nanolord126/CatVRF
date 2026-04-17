import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

export const options = {
  stages: [
    { duration: '30s', target: 20 },   // Ramp up to 20 users
    { duration: '1m', target: 100 },   // Ramp up to 100 users
    { duration: '2m', target: 200 },   // Ramp up to 200 users
    { duration: '1m', target: 50 },    // Ramp down to 50 users
    { duration: '30s', target: 0 },    // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<1000'], // 95% of requests must complete below 1s
    http_req_failed: ['rate<0.03'],    // Error rate must be less than 3%
  },
};

export default function () {
  const masterId = Math.floor(Math.random() * 50) + 1;
  const serviceId = Math.floor(Math.random() * 20) + 1;
  const correlationId = `test-${__VU}-${__ITER}`;

  const payload = {
    master_id: masterId,
    service_id: serviceId,
    base_price: Math.floor(Math.random() * 4000) + 1000,
    time_slot: new Date(Date.now() + Math.random() * 86400000).toISOString(),
  };

  const params = {
    headers: {
      'Content-Type': 'application/json',
      'X-Tenant-ID': '1',
      'X-Correlation-ID': correlationId,
      'Accept': 'application/json',
    },
  };

  const res = http.post(`${BASE_URL}/api/beauty/pricing/calculate`, JSON.stringify(payload), params);

  check(res, {
    'status is 200': (r) => r.status === 200,
    'has success': (r) => JSON.parse(r.body).success === true,
    'has final_price': (r) => JSON.parse(r.body).final_price !== undefined,
    'has surge_multiplier': (r) => JSON.parse(r.body).surge_multiplier !== undefined,
    'response time < 1s': (r) => r.timings.duration < 1000,
  });

  sleep(0.5);
}
