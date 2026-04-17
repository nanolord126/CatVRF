import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

export const options = {
  stages: [
    { duration: '30s', target: 30 },   // Ramp up to 30 users
    { duration: '1m', target: 150 },   // Ramp up to 150 users
    { duration: '2m', target: 300 },   // Ramp up to 300 users
    { duration: '1m', target: 100 },   // Ramp down to 100 users
    { duration: '30s', target: 0 },    // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<800'],  // 95% of requests must complete below 800ms
    http_req_failed: ['rate<0.02'],    // Error rate must be less than 2%
  },
};

export default function () {
  const userId = Math.floor(Math.random() * 1000) + 1;
  const correlationId = `test-${__VU}-${__ITER}`;

  const actions = ['appointment_completed', 'review_left', 'video_call_completed', 'profile_completed'];
  const action = actions[Math.floor(Math.random() * actions.length)];

  const payload = {
    user_id: userId,
    action: action,
    appointment_id: Math.random() > 0.5 ? Math.floor(Math.random() * 1000) + 1 : null,
  };

  const params = {
    headers: {
      'Content-Type': 'application/json',
      'X-Tenant-ID': '1',
      'X-Correlation-ID': correlationId,
      'Accept': 'application/json',
    },
  };

  const res = http.post(`${BASE_URL}/api/beauty/loyalty/action`, JSON.stringify(payload), params);

  check(res, {
    'status is 200': (r) => r.status === 200,
    'has success': (r) => JSON.parse(r.body).success === true,
    'has points_earned': (r) => JSON.parse(r.body).points_earned !== undefined,
    'has total_points': (r) => JSON.parse(r.body).total_points !== undefined,
    'has streak_multiplier': (r) => JSON.parse(r.body).streak_multiplier !== undefined,
    'response time < 800ms': (r) => r.timings.duration < 800,
  });

  // Check loyalty status
  if (Math.random() > 0.8) {
    const statusRes = http.get(`${BASE_URL}/api/beauty/loyalty/status?user_id=${userId}`, params);

    check(statusRes, {
      'status status is 200': (r) => r.status === 200,
      'has tier': (r) => JSON.parse(r.body).tier !== undefined,
    });
  }

  sleep(0.3);
}
