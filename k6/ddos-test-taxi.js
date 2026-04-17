import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export const options = {
  stages: [
    { duration: '10s', target: 100 },
    { duration: '20s', target: 500 },
    { duration: '10s', target: 1000 },
    { duration: '20s', target: 500 },
    { duration: '10s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<1000'],
    errors: ['rate<0.5'], // Allow higher error rate during DDOS test
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
  const payload = JSON.stringify({
    pickup_address: 'Moscow, Red Square',
    pickup_lat: 55.75396,
    pickup_lon: 37.62039,
    dropoff_address: 'Moscow, Kremlin',
    dropoff_lat: 55.7520,
    dropoff_lon: 37.6175,
    payment_method: 'wallet',
    device_type: 'mobile',
    app_version: '1.0.0',
  });

  const params = {
    headers: {
      'Content-Type': 'application/json',
      'X-Correlation-ID': `ddos-test-${__VU}-${__ITER}`,
      'X-Forwarded-For': `192.168.1.${__VU % 255}`,
    },
  };

  const res = http.post(
    `${BASE_URL}/api/v1/taxi/orders`,
    payload,
    params
  );

  const success = check(res, {
    'request completed': (r) => r.status < 500,
    'rate limited': (r) => r.status === 429 || r.status === 403,
  });

  errorRate.add(!success);

  sleep(0.1);
}
