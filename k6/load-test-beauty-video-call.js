import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

export const options = {
  stages: [
    { duration: '30s', target: 10 },   // Ramp up to 10 users
    { duration: '1m', target: 30 },    // Ramp up to 30 users
    { duration: '2m', target: 50 },    // Ramp up to 50 users
    { duration: '1m', target: 20 },    // Ramp down to 20 users
    { duration: '30s', target: 0 },    // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<1500'], // 95% of requests must complete below 1.5s
    http_req_failed: ['rate<0.05'],    // Error rate must be less than 5%
  },
};

const activeCalls = new Map();

export default function () {
  const userId = Math.floor(Math.random() * 1000) + 1;
  const masterId = Math.floor(Math.random() * 50) + 1;
  const correlationId = `test-${__VU}-${__ITER}`;

  // Initiate call
  const payload = {
    user_id: userId,
    master_id: masterId,
    duration_minutes: Math.floor(Math.random() * 10) + 5,
  };

  const params = {
    headers: {
      'Content-Type': 'application/json',
      'X-Tenant-ID': '1',
      'X-Correlation-ID': correlationId,
      'Accept': 'application/json',
    },
  };

  const res = http.post(`${BASE_URL}/api/beauty/video-calls/initiate`, JSON.stringify(payload), params);

  check(res, {
    'status is 200': (r) => r.status === 200,
    'has success': (r) => JSON.parse(r.body).success === true,
    'has call_id': (r) => JSON.parse(r.body).call_id !== undefined,
    'has token': (r) => JSON.parse(r.body).token !== undefined,
    'has room_name': (r) => JSON.parse(r.body).room_name !== undefined,
    'response time < 1.5s': (r) => r.timings.duration < 1500,
  });

  // Simulate ending some calls
  if (Math.random() > 0.7 && activeCalls.size > 0) {
    const [callId] = activeCalls.keys();
    const endPayload = {
      call_id: callId,
      duration_seconds: Math.floor(Math.random() * 600) + 60,
      reason: 'user_ended',
    };

    const endRes = http.post(`${BASE_URL}/api/beauty/video-calls/end`, JSON.stringify(endPayload), params);

    check(endRes, {
      'end status is 200': (r) => r.status === 200,
    });

    activeCalls.delete(callId);
  }

  sleep(1);
}
