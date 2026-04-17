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
    errors: ['rate<0.5'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
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
      'X-Correlation-ID': `ddos-hotels-${__VU}-${__ITER}`,
      'X-Forwarded-For': `192.168.1.${__VU % 255}`,
    },
  };

  const res = http.post(
    `${BASE_URL}/api/v1/hotels/bookings`,
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
