import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const fraudRate = new Rate('fraud_detected');

export const options = {
  stages: [
    { duration: '20s', target: 20 },
    { duration: '40s', target: 50 },
    { duration: '20s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],
    fraud_detected: ['rate<0.1'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
  // Test suspicious device fingerprint
  const suspiciousPayload = JSON.stringify({
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

  const suspiciousParams = {
    headers: {
      'Content-Type': 'application/json',
      'X-Correlation-ID': `fraud-test-${__VU}-${__ITER}`,
      'X-Device-Fingerprint': 'suspicious-known-fraud-device',
    },
  };

  const suspiciousRes = http.post(
    `${BASE_URL}/api/v1/taxi/orders`,
    suspiciousPayload,
    suspiciousParams
  );

  const fraudDetected = check(suspiciousRes, {
    'fraud blocked': (r) => r.status === 403,
  });

  fraudRate.add(fraudDetected);

  // Test high value order
  sleep(1);
  const highValuePayload = JSON.stringify({
    pickup_address: 'Moscow, Red Square',
    pickup_lat: 55.75396,
    pickup_lon: 37.62039,
    dropoff_address: 'Saint Petersburg',
    dropoff_lat: 59.9343,
    dropoff_lon: 30.3351,
    payment_method: 'wallet',
    device_type: 'mobile',
    app_version: '1.0.0',
  });

  const highValueParams = {
    headers: {
      'Content-Type': 'application/json',
      'X-Correlation-ID': `fraud-high-value-${__VU}-${__ITER}`,
    },
  };

  const highValueRes = http.post(
    `${BASE_URL}/api/v1/taxi/orders`,
    highValuePayload,
    highValueParams
  );

  check(highValueRes, {
    'high value processed': (r) => r.status === 201 || r.status === 403,
  });

  // Test rapid payment switching
  sleep(0.5);
  for (let i = 0; i < 5; i++) {
    const rapidPayload = JSON.stringify({
      pickup_address: 'Moscow, Red Square',
      pickup_lat: 55.75396,
      pickup_lon: 37.62039,
      dropoff_address: 'Moscow, Kremlin',
      dropoff_lat: 55.7520,
      dropoff_lon: 37.6175,
      payment_method: ['wallet', 'card', 'cash'][i % 3],
      device_type: 'mobile',
      app_version: '1.0.0',
    });

    const rapidRes = http.post(
      `${BASE_URL}/api/v1/taxi/orders`,
      rapidPayload,
      {
        headers: {
          'Content-Type': 'application/json',
          'X-Correlation-ID': `fraud-rapid-${__VU}-${__ITER}-${i}`,
        },
      }
    );

    check(rapidRes, {
      'rapid switching handled': (r) => r.status < 500,
    });
  }

  sleep(2);
}
