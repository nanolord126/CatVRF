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
    hotel_id: 'invalid',
    room_id: null,
    check_in: 'invalid-date',
    check_out: null,
    guests: 'invalid',
  });

  const malformedRes = http.post(
    `${BASE_URL}/api/v1/hotels/bookings`,
    malformedPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': `crash-hotels-malformed-${__VU}-${__ITER}`,
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
    hotel_id: null,
    room_id: null,
    check_in: null,
    check_out: null,
    guests: null,
  });

  const nullRes = http.post(
    `${BASE_URL}/api/v1/hotels/bookings`,
    nullPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': `crash-hotels-null-${__VU}-${__ITER}`,
      },
    }
  );

  check(nullRes, {
    'null values handled': (r) => r.status === 422 || r.status === 400,
  });

  // Test extremely large payload
  sleep(0.5);
  const largePayload = JSON.stringify({
    hotel_id: 1,
    room_id: 1,
    check_in: new Date(Date.now() + 86400000).toISOString().split('T')[0],
    check_out: new Date(Date.now() + 259200000).toISOString().split('T')[0],
    guests: 2,
    special_requests: Array(10000).fill('large_data'),
  });

  const largeRes = http.post(
    `${BASE_URL}/api/v1/hotels/bookings`,
    largePayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': `crash-hotels-large-${__VU}-${__ITER}`,
      },
    }
  );

  check(largeRes, {
    'large payload handled': (r) => r.status < 500,
  });

  // Test invalid date ranges
  sleep(0.5);
  const invalidDatePayload = JSON.stringify({
    hotel_id: 1,
    room_id: 1,
    check_in: new Date(Date.now() + 259200000).toISOString().split('T')[0],
    check_out: new Date(Date.now() + 86400000).toISOString().split('T')[0],
    guests: 2,
  });

  const invalidDateRes = http.post(
    `${BASE_URL}/api/v1/hotels/bookings`,
    invalidDatePayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': `crash-hotels-invalid-date-${__VU}-${__ITER}`,
      },
    }
  );

  check(invalidDateRes, {
    'invalid date handled': (r) => r.status === 422 || r.status === 400,
  });

  sleep(2);
}
