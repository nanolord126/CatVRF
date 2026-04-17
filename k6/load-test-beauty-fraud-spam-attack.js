import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

// Simulate spam attack: same user making rapid requests
export const options = {
  stages: [
    { duration: '10s', target: 1 },    // Warm up
    { duration: '30s', target: 50 },   // Rapid ramp up (spam)
    { duration: '1m', target: 500 },   // Sustained spam (500 concurrent users, same IP/user)
    { duration: '30s', target: 100 },  // Ramp down
    { duration: '10s', target: 0 },    // Cool down
  ],
  thresholds: {
    http_req_duration: ['p(95)<1000'], // Should still respond quickly
    http_req_failed: ['rate<0.5'],     // Allow higher failure rate during attack
  },
};

export default function () {
  const spamUserId = 999; // Same user for all requests (spam pattern)
  const correlationId = `spam-test-${__VU}-${__ITER}`;

  const payload = {
    user_id: spamUserId,
    action: 'appointment_booking',
    appointment_id: Math.floor(Math.random() * 1000) + 1,
    master_id: Math.floor(Math.random() * 50) + 1,
    amount: Math.floor(Math.random() * 5000) + 1000,
    ip_address: '192.168.1.100', // Same IP for all requests
    user_agent: 'Mozilla/5.0 (SpamBot/1.0)',
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
    'status is 200 or 429': (r) => r.status === 200 || r.status === 429, // Should block with 429
    'detects high fraud risk': (r) => {
      if (r.status === 200) {
        const data = JSON.parse(r.body);
        return data.fraud_score > 0.7; // Should detect spam as high risk
      }
      return true;
    },
    'action is block or manual_review': (r) => {
      if (r.status === 200) {
        const data = JSON.parse(r.body);
        return data.action_required === 'block' || data.action_required === 'manual_review';
      }
      return true;
    },
  });

  // No sleep - simulate rapid spam requests
}
