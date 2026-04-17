import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const crashRate = new Rate('crashes');

export const options = {
  stages: [
    { duration: '10s', target: 10 },
    { duration: '20s', target: 30 },
    { duration: '10s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'],
    crashes: ['rate<0.1'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
  // Test malformed payload
  const malformedPayload = JSON.stringify({
    pickup_address: 'A'.repeat(10000),
    pickup_lat: 'invalid',
    pickup_lon: null,
    dropoff_address: null,
    dropoff_lat: null,
    dropoff_lon: null,
    payment_method: 'invalid_method',
  });

  const malformedRes = http.post(
    `${BASE_URL}/api/v1/taxi/orders`,
    malformedPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': `crash-malformed-${__VU}-${__ITER}`,
      },
    }
  );

  const crashHandled = check(malformedRes, {
    'malformed handled gracefully': (r) => r.status === 422 || r.status === 400,
  });

  crashRate.add(!crashHandled);

  // Test null values
  sleep(1);
  const nullPayload = JSON.stringify({
    pickup_address: null,
    pickup_lat: null,
    pickup_lon: null,
    dropoff_address: null,
    dropoff_lat: null,
    dropoff_lon: null,
    payment_method: null,
  });

  const nullRes = http.post(
    `${BASE_URL}/api/v1/taxi/orders`,
    nullPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': `crash-null-${__VU}-${__ITER}`,
      },
    }
  );

  check(nullRes, {
    'null values handled': (r) => r.status === 422 || r.status === 400,
  });

  // Test extremely large payload
  sleep(0.5);
  const largePayload = JSON.stringify({
    pickup_address: 'Moscow, Red Square',
    pickup_lat: 55.75396,
    pickup_lon: 37.62039,
    dropoff_address: 'Moscow, Kremlin',
    dropoff_lat: 55.7520,
    dropoff_lon: 37.6175,
    payment_method: 'wallet',
    device_type: 'mobile',
    app_version: '1.0.0',
    metadata: Array(10000).fill('large_data'),
  });

  const largeRes = http.post(
    `${BASE_URL}/api/v1/taxi/orders`,
    largePayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': `crash-large-${__VU}-${__ITER}`,
      },
    }
  );

  check(largeRes, {
    'large payload handled': (r) => r.status < 500,
  });

  // Test concurrent updates
  sleep(0.5);
  const validPayload = JSON.stringify({
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

  const createRes = http.post(
    `${BASE_URL}/api/v1/taxi/orders`,
    validPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': `crash-concurrent-${__VU}-${__ITER}`,
      },
    }
  );

  if (createRes.status === 201 && createRes.json('data.uuid')) {
    const rideUuid = createRes.json('data.uuid');

    for (let i = 0; i < 10; i++) {
      const updateRes = http.put(
        `${BASE_URL}/api/v1/taxi/orders/${rideUuid}`,
        JSON.stringify({ status: 'driver_assigned' }),
        {
          headers: {
            'Content-Type': 'application/json',
            'X-Correlation-ID': `crash-update-${__VU}-${__ITER}-${i}`,
          },
        }
      );

      check(updateRes, {
        'concurrent update handled': (r) => r.status < 500,
      });
    }
  }

  sleep(2);
}
