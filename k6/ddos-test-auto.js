import http from 'k6/http';
import { check } from 'k6';

export const options = {
  stages: [
    { duration: '30s', target: 1000 },
    { duration: '1m', target: 5000 },
    { duration: '30s', target: 10000 },
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.5'],
  },
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

export default function () {
  const headers = {
    'Content-Type': 'application/json',
    'X-Tenant-ID': '1',
    'X-Correlation-ID': `ddos-${Date.now()}-${__VU}`,
  };

  const payload = JSON.stringify({
    vin: 'JTDKN3DU5A0123456',
    photo: 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
  });

  const responses = http.batch([
    ['POST', `${BASE_URL}/api/v1/auto/diagnostics/analyze`, payload, { headers }],
    ['POST', `${BASE_URL}/api/v1/auto/diagnostics/analyze`, payload, { headers }],
    ['POST', `${BASE_URL}/api/v1/auto/diagnostics/analyze`, payload, { headers }],
    ['POST', `${BASE_URL}/api/v1/auto/diagnostics/analyze`, payload, { headers }],
    ['POST', `${BASE_URL}/api/v1/auto/diagnostics/analyze`, payload, { headers }],
  ]);

  check(responses, {
    'some requests succeed': (r) => r.some((res) => res.status === 200 || res.status === 429),
    'no 500 errors': (r) => r.every((res) => res.status !== 500),
  });
}
