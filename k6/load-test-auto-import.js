import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export const options = {
  stages: [
    { duration: '2m', target: 50 },
    { duration: '5m', target: 200 },
    { duration: '2m', target: 300 },
    { duration: '5m', target: 100 },
    { duration: '2m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<800', 'p(99)<2000'],
    errors: ['rate<0.02'],
  },
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

export default function () {
  const headers = {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${__ENV.API_TOKEN}`,
    'X-Tenant-ID': '1',
    'X-Correlation-ID': `import-test-${Date.now()}-${__VU}`,
  };

  const calculatePayload = JSON.stringify({
    vin: 'JTDKN3DU5A0123456',
    country: 'JP',
    declared_value: 15000,
    currency: 'eur',
    engine_type: 'petrol',
    engine_volume: 2.0,
    manufacture_year: 2020,
  });

  const calcRes = http.post(`${BASE_URL}/api/v1/auto/import/calculate-duties`, calculatePayload, { headers });
  
  const calcSuccess = check(calcRes, {
    'status is 200': (r) => r.status === 200,
    'has duties': (r) => r.json('total_duties') !== undefined,
    'has restrictions': (r) => Array.isArray(r.json('restrictions')),
  });

  errorRate.add(!calcSuccess);

  sleep(2);
}
