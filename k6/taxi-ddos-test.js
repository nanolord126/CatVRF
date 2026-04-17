import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export let options = {
  stages: [
    { duration: '10s', target: 100 },
    { duration: '20s', target: 500 },
    { duration: '10s', target: 1000 },
    { duration: '20s', target: 2000 },
    { duration: '10s', target: 0 },
  ],
  thresholds: {
    errors: ['rate<0.5'],
    http_req_duration: ['p(95)<1000'],
  },
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

export default function () {
  let endpoints = [
    '/api/taxi/rides',
    '/api/taxi/ai/analyze-route',
    '/api/taxi/ai/predict-surge',
  ];

  let endpoint = endpoints[Math.floor(Math.random() * endpoints.length)];

  let payload = JSON.stringify({
    tenant_id: 1,
    passenger_id: Math.floor(Math.random() * 1000) + 1,
    pickup_latitude: 55.7558 + (Math.random() * 0.01),
    pickup_longitude: 37.6173 + (Math.random() * 0.01),
    dropoff_latitude: 55.7520 + (Math.random() * 0.01),
    dropoff_longitude: 37.6150 + (Math.random() * 0.01),
    pickup_address: 'DDoS Test',
    dropoff_address: 'DDoS Test',
    estimated_price_kopeki: 15000,
    correlation_id: `ddos-${Date.now()}-${__VU}-${Math.random()}`,
  });

  let params = {
    headers: {
      'Content-Type': 'application/json',
      'X-Correlation-ID': `ddos-${Date.now()}-${__VU}`,
    },
  };

  let response = http.post(`${BASE_URL}${endpoint}`, payload, params);

  errorRate.add(response.status >= 500);

  sleep(0.1);
}

export function handleSummary(data) {
  return {
    'stdout': `
      DDoS Test Summary:
      - Total Requests: ${data.metrics.http_reqs.values.count}
      - Error Rate: ${(data.metrics.errors.values.rate * 100).toFixed(2)}%
      - RPS: ${data.metrics.http_reqs.values.count / (data.metrics.http_req_duration.values.count / 1000)}
      - P95 Response Time: ${data.metrics.http_req_duration.values['p(95)']}ms
    `,
  };
}
