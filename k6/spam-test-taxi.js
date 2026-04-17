import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const spamRate = new Rate('spam_blocked');

export const options = {
  stages: [
    { duration: '15s', target: 50 },
    { duration: '30s', target: 100 },
    { duration: '15s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],
    spam_blocked: ['rate>0.3'], // Expect 30%+ to be blocked
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
  // Test multiple orders from same IP
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
      'X-Correlation-ID': `spam-taxi-${__VU}-${__ITER}`,
      'X-Forwarded-For': '192.168.1.100', // Same IP for all
    },
  };

  const res = http.post(
    `${BASE_URL}/api/v1/taxi/orders`,
    payload,
    params
  );

  const blocked = check(res, {
    'spam blocked': (r) => r.status === 429 || r.status === 403,
  });

  spamRate.add(blocked);

  // Test rapid price estimation
  sleep(0.2);
  const pricePayload = JSON.stringify({
    pickup_lat: 55.75396,
    pickup_lon: 37.62039,
    dropoff_lat: 55.7520,
    dropoff_lon: 37.6175,
    vehicle_class: 'economy',
  });

  const priceRes = http.post(
    `${BASE_URL}/api/v1/taxi/estimate-price`,
    pricePayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': `spam-price-${__VU}-${__ITER}`,
      },
    }
  );

  check(priceRes, {
    'price spam handled': (r) => r.status === 429 || r.status === 200,
  });

  sleep(0.5);
}
