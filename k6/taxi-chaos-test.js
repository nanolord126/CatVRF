import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export let options = {
  stages: [
    { duration: '30s', target: 10 },
    { duration: '1m', target: 50 },
    { duration: '30s', target: 100 },
    { duration: '1m', target: 200 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    errors: ['rate<0.1'],
    http_req_duration: ['p(95)<500'],
  },
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

export default function () {
  let payload = JSON.stringify({
    tenant_id: 1,
    passenger_id: Math.floor(Math.random() * 1000) + 1,
    pickup_latitude: 55.7558 + (Math.random() * 0.01),
    pickup_longitude: 37.6173 + (Math.random() * 0.01),
    dropoff_latitude: 55.7520 + (Math.random() * 0.01),
    dropoff_longitude: 37.6150 + (Math.random() * 0.01),
    pickup_address: 'Test Pickup',
    dropoff_address: 'Test Dropoff',
    estimated_price_kopeki: 15000,
    correlation_id: `k6-test-${Date.now()}-${__VU}`,
    idempotency_key: `k6-key-${Date.now()}-${__VU}`,
  });

  let params = {
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${__ENV.API_TOKEN || 'test-token'}`,
      'X-Correlation-ID': `k6-${Date.now()}-${__VU}`,
    },
  };

  let response = http.post(`${BASE_URL}/api/taxi/rides`, payload, params);

  let success = check(response, {
    'status is 201': (r) => r.status === 201,
    'has success': (r) => r.json('success') === true,
    'has data': (r) => r.json('data') !== undefined,
  });

  errorRate.add(!success);

  sleep(Math.random() * 2);
}

export function handleSummary(data) {
  return {
    'stdout': `
      Test Summary:
      - Total Requests: ${data.metrics.http_reqs.values.count}
      - Error Rate: ${(data.metrics.errors.values.rate * 100).toFixed(2)}%
      - P95 Response Time: ${data.metrics.http_req_duration.values['p(95)']}ms
      - P99 Response Time: ${data.metrics.http_req_duration.values['p(99)']}ms
    `,
  };
}
