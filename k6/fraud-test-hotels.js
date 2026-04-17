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
  // Test high value booking (30 days)
  const highValuePayload = JSON.stringify({
    hotel_id: 1,
    room_id: 1,
    check_in: new Date(Date.now() + 86400000).toISOString().split('T')[0],
    check_out: new Date(Date.now() + 2592000000).toISOString().split('T')[0],
    guests: 2,
  });

  const highValueParams = {
    headers: {
      'Content-Type': 'application/json',
      'X-Correlation-ID': `fraud-hotels-high-${__VU}-${__ITER}`,
    },
  };

  const highValueRes = http.post(
    `${BASE_URL}/api/v1/hotels/bookings`,
    highValuePayload,
    highValueParams
  );

  const fraudDetected = check(highValueRes, {
    'high value processed': (r) => r.status === 201 || r.status === 403,
  });

  fraudRate.add(fraudDetected);

  // Test suspicious device
  sleep(1);
  const suspiciousPayload = JSON.stringify({
    hotel_id: 1,
    room_id: 1,
    check_in: new Date(Date.now() + 86400000).toISOString().split('T')[0],
    check_out: new Date(Date.now() + 259200000).toISOString().split('T')[0],
    guests: 2,
  });

  const suspiciousRes = http.post(
    `${BASE_URL}/api/v1/hotels/bookings`,
    suspiciousPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': `fraud-hotels-suspicious-${__VU}-${__ITER}`,
        'X-Device-Fingerprint': 'suspicious-known-fraud-device',
      },
    }
  );

  check(suspiciousRes, {
    'suspicious device handled': (r) => r.status === 403 || r.status === 201,
  });

  // Test rapid bookings
  sleep(0.5);
  for (let i = 0; i < 5; i++) {
    const rapidPayload = JSON.stringify({
      hotel_id: 1,
      room_id: (i % 10) + 1,
      check_in: new Date(Date.now() + 86400000).toISOString().split('T')[0],
      check_out: new Date(Date.now() + 259200000).toISOString().split('T')[0],
      guests: 2,
    });

    const rapidRes = http.post(
      `${BASE_URL}/api/v1/hotels/bookings`,
      rapidPayload,
      {
        headers: {
          'Content-Type': 'application/json',
          'X-Correlation-ID': `fraud-hotels-rapid-${__VU}-${__ITER}-${i}`,
        },
      }
    );

    check(rapidRes, {
      'rapid booking handled': (r) => r.status < 500,
    });
  }

  sleep(2);
}
