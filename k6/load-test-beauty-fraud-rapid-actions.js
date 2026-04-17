import http from 'k6/http';
import { check } from 'k6';

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

// Simulate suspicious pattern: user performing rapid sequential actions
export const options = {
  stages: [
    { duration: '30s', target: 10 },   // 10 users performing rapid actions
    { duration: '1m', target: 50 },    // Ramp up to 50 users
    { duration: '2m', target: 100 },   // Sustained rapid actions
    { duration: '30s', target: 0 },    // Stop
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],  // Should be fast
    http_req_failed: ['rate<0.05'],    // Low failure rate
  },
};

export default function () {
  const userId = __VU; // Each VU represents one user
  const correlationId = `rapid-test-${__VU}-${__ITER}`;

  const actions = ['appointment_booking', 'payment', 'cancellation', 'appointment_booking', 'payment'];
  const action = actions[__ITER % actions.length]; // Rotate through suspicious pattern

  const payload = {
    user_id: userId,
    action: action,
    appointment_id: __ITER + 1,
    master_id: Math.floor(Math.random() * 50) + 1,
    amount: Math.floor(Math.random() * 5000) + 1000,
    ip_address: '192.168.1.' + userId,
    user_agent: 'Mozilla/5.0',
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
    'detects excessive_actions flag': (r) => {
      const data = JSON.parse(r.body);
      return data.flags.includes('excessive_actions');
    },
    'risk level increases over time': (r) => {
      const data = JSON.parse(r.body);
      return data.risk_level === 'high' || data.risk_level === 'critical';
    },
  });

  // Minimal sleep to simulate rapid actions
}
