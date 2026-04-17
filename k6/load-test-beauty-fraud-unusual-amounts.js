import http from 'k6/http';
import { check } from 'k6';

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

export const options = {
  stages: [
    { duration: '30s', target: 50 },   // Ramp up
    { duration: '1m', target: 200 },   // Sustained load
    { duration: '30s', target: 0 },    // Stop
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],
    http_req_failed: ['rate<0.05'],
  },
};

export default function () {
  const userId = Math.floor(Math.random() * 1000) + 1;
  const correlationId = `unusual-amount-test-${__VU}-${__ITER}`;

  // 30% of requests with unusual amounts
  const useUnusualAmount = Math.random() < 0.3;
  let amount;

  if (useUnusualAmount) {
    // Unusual: very high or very low
    amount = Math.random() > 0.5 
      ? Math.floor(Math.random() * 100000) + 50000  // Very high: 50,000 - 150,000
      : Math.floor(Math.random() * 50) + 10;        // Very low: 10 - 60
  } else {
    // Normal: 1,000 - 10,000
    amount = Math.floor(Math.random() * 9000) + 1000;
  }

  const payload = {
    user_id: userId,
    action: 'payment',
    amount: amount,
    ip_address: `192.168.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`,
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
    'detects unusual_amount flag': (r) => {
      const data = JSON.parse(r.body);
      if (useUnusualAmount) {
        return data.flags.includes('unusual_amount');
      }
      return true;
    },
    'higher risk for unusual amounts': (r) => {
      const data = JSON.parse(r.body);
      if (useUnusualAmount) {
        return data.fraud_score > 0.3;
      }
      return true;
    },
  });
}
