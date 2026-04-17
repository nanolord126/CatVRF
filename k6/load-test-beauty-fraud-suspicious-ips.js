import http from 'k6/http';
import { check } from 'k6';

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

// Known suspicious IPs list
const SUSPICIOUS_IPS = [
  '192.168.1.100',
  '192.168.1.101',
  '10.0.0.50',
  '10.0.0.51',
  '172.16.0.10',
  '172.16.0.11',
];

export const options = {
  stages: [
    { duration: '20s', target: 20 },   // Ramp up
    { duration: '1m', target: 100 },   // Sustained from suspicious IPs
    { duration: '30s', target: 0 },    // Stop
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],
    http_req_failed: ['rate<0.1'],     // Should block suspicious IPs
  },
};

export default function () {
  const userId = Math.floor(Math.random() * 1000) + 1;
  const correlationId = `suspicious-ip-test-${__VU}-${__ITER}`;

  // Use suspicious IP 50% of the time
  const useSuspiciousIP = Math.random() > 0.5;
  const ipAddress = useSuspiciousIP 
    ? SUSPICIOUS_IPS[__VU % SUSPICIOUS_IPS.length] 
    : `192.168.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`;

  const payload = {
    user_id: userId,
    action: 'payment',
    amount: Math.floor(Math.random() * 10000) + 1000,
    ip_address: ipAddress,
    user_agent: useSuspiciousIP ? 'MaliciousBot/1.0' : 'Mozilla/5.0',
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
    'detects suspicious_ip flag': (r) => {
      const data = JSON.parse(r.body);
      if (useSuspiciousIP) {
        return data.flags.includes('suspicious_ip');
      }
      return true;
    },
    'higher risk for suspicious IPs': (r) => {
      const data = JSON.parse(r.body);
      if (useSuspiciousIP) {
        return data.fraud_score > 0.5;
      }
      return true;
    },
  });
}
