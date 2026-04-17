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
    spam_blocked: ['rate>0.3'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
  // Test multiple bookings from same IP
  const payload = JSON.stringify({
    hotel_id: 1,
    room_id: 1,
    check_in: new Date(Date.now() + 86400000).toISOString().split('T')[0],
    check_out: new Date(Date.now() + 259200000).toISOString().split('T')[0],
    guests: 2,
  });

  const params = {
    headers: {
      'Content-Type': 'application/json',
      'X-Correlation-ID': `spam-hotels-${__VU}-${__ITER}`,
      'X-Forwarded-For': '192.168.1.100',
    },
  };

  const res = http.post(
    `${BASE_URL}/api/v1/hotels/bookings`,
    payload,
    params
  );

  const blocked = check(res, {
    'spam blocked': (r) => r.status === 429 || r.status === 403,
  });

  spamRate.add(blocked);

  // Test rapid price calculations
  sleep(0.2);
  const pricePayload = JSON.stringify({
    hotel_id: 1,
    room_id: 1,
    check_in: new Date(Date.now() + 86400000).toISOString().split('T')[0],
    check_out: new Date(Date.now() + 259200000).toISOString().split('T')[0],
    guests: 2,
  });

  const priceRes = http.post(
    `${BASE_URL}/api/v1/hotels/calculate-price`,
    pricePayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': `spam-price-hotels-${__VU}-${__ITER}`,
      },
    }
  );

  check(priceRes, {
    'price spam handled': (r) => r.status === 429 || r.status === 200,
  });

  sleep(0.5);
}
