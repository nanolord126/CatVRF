import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

export const options = {
  stages: [
    { duration: '30s', target: 50 },   // Ramp up to 50 users
    { duration: '1m', target: 200 },   // Ramp up to 200 users
    { duration: '2m', target: 400 },   // Ramp up to 400 users
    { duration: '1m', target: 100 },   // Ramp down to 100 users
    { duration: '30s', target: 0 },    // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],  // 95% of requests must complete below 500ms
    http_req_failed: ['rate<0.01'],    // Error rate must be less than 1%
  },
};

export default function () {
  const userId = Math.floor(Math.random() * 1000) + 1;
  const correlationId = `test-${__VU}-${__ITER}`;

  const actions = ['appointment_booking', 'payment', 'cancellation', 'review', 'profile_update'];
  const action = actions[Math.floor(Math.random() * actions.length)];

  const payload = {
    user_id: userId,
    action: action,
    appointment_id: Math.random() > 0.5 ? Math.floor(Math.random() * 1000) + 1 : null,
    master_id: Math.random() > 0.5 ? Math.floor(Math.random() * 50) + 1 : null,
    amount: Math.random() > 0.5 ? Math.floor(Math.random() * 10000) + 500 : null,
    ip_address: `192.168.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`,
    user_agent: 'Mozilla/5.0 (Test User Agent)',
  };

  const params = {
    headers: {
      'Content-Type': 'application/json',
      'X-Tenant-ID': '1',
      'X-Correlation-ID': correlationId,
      'Accept': 'application/json',
    },
  };

  const res = http.post(`${BASE_URL}/api/beauty/fraud/analyze`, JSON.stringify(payload), params);

  check(res, {
    'status is 200': (r) => r.status === 200,
    'has success': (r) => JSON.parse(r.body).success === true,
    'has fraud_score': (r) => JSON.parse(r.body).fraud_score !== undefined,
    'has risk_level': (r) => JSON.parse(r.body).risk_level !== undefined,
    'has action_required': (r) => JSON.parse(r.body).action_required !== undefined,
    'fraud_score between 0 and 1': (r) => {
      const score = JSON.parse(r.body).fraud_score;
      return score >= 0 && score <= 1;
    },
    'response time < 500ms': (r) => r.timings.duration < 500,
  });

  sleep(0.2);
}
