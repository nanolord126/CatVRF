import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export const options = {
  stages: [
    { duration: '2m', target: 100 },
    { duration: '5m', target: 500 },
    { duration: '2m', target: 1000 },
    { duration: '5m', target: 500 },
    { duration: '2m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<500', 'p(99)<1000'],
    errors: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

export default function () {
  const headers = {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${__ENV.API_TOKEN}`,
    'X-Tenant-ID': '1',
    'X-Correlation-ID': `test-${Date.now()}-${__VU}`,
  };

  const payload = JSON.stringify({
    vin: 'JTDKN3DU5A0123456',
    photo: 'data:image/jpeg;base64,/9j/4AAQSkZJRg==',
    latitude: 55.7558,
    longitude: 37.6173,
  });

  const diagnoseRes = http.post(`${BASE_URL}/api/v1/auto/diagnostics/analyze`, payload, { headers });
  
  const success = check(diagnoseRes, {
    'status is 200': (r) => r.status === 200,
    'has response body': (r) => r.body.length > 0,
    'has correlation_id': (r) => r.json('correlation_id') !== undefined,
  });

  errorRate.add(!success);

  sleep(1);
}
