import http from 'k6/http';
import { check } from 'k6';

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

// Simulate DDoS attack: massive concurrent requests from different sources
export const options = {
  stages: [
    { duration: '5s', target: 100 },    // Immediate spike
    { duration: '30s', target: 1000 },  // Ramp to 1000 concurrent users
    { duration: '1m', target: 2000 },   // Sustained DDoS at 2000 concurrent
    { duration: '30s', target: 500 },   // Ramp down
    { duration: '10s', target: 0 },     // Stop
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'],  // Allow slower response during DDoS
    http_req_failed: ['rate<0.8'],      // Allow high failure rate during DDoS
  },
};

export default function () {
  const userId = Math.floor(Math.random() * 10000) + 1;
  const correlationId = `ddos-test-${__VU}-${__ITER}`;

  // Simulate distributed attack from different IPs
  const ipParts = Math.floor(Math.random() * 255);
  const payload = {
    user_id: userId,
    action: 'payment',
    amount: Math.floor(Math.random() * 100000) + 10000,
    ip_address: `10.${Math.floor(Math.random() * 255)}.${ipParts}.${Math.floor(Math.random() * 255)}`,
    user_agent: 'DDoSBot/2.0',
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
    'server responds': (r) => r.status >= 200 && r.status < 500,
    'blocks or rate limits': (r) => r.status === 200 || r.status === 429 || r.status === 503,
  });

  // No sleep - maximum throughput for DDoS simulation
}
