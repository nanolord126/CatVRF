import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const crashRate = new Rate('crashes');

export const options = {
  stages: [
    { duration: '10s', target: 10 },
    { duration: '20s', target: 50 },
    { duration: '10s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'],
    crashes: ['rate<0.1'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
  // Test malformed booking payload
  const malformedPayload = JSON.stringify({
    facility_id: 'A'.repeat(10000),
    slot_start: 'invalid_date',
    slot_end: null,
    sport_type: null,
    participants: -5,
    payment_method: 'invalid_method',
  });

  const malformedRes = http.post(
    `${BASE_URL}/api/v1/sports/bookings`,
    malformedPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
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
    facility_id: null,
    slot_start: null,
    slot_end: null,
    sport_type: null,
    participants: null,
    payment_method: null,
  });

  const nullRes = http.post(
    `${BASE_URL}/api/v1/sports/bookings`,
    nullPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
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
    facility_id: 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T12:00:00Z',
    sport_type: 'tennis',
    participants: 2,
    payment_method: 'wallet',
    metadata: Array(10000).fill('large_data'),
    equipment: Array(1000).fill({ name: 'racket', quantity: 1 }),
  });

  const largeRes = http.post(
    `${BASE_URL}/api/v1/sports/bookings`,
    largePayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Correlation-ID': `crash-large-${__VU}-${__ITER}`,
      },
    }
  );

  check(largeRes, {
    'large payload handled': (r) => r.status < 500,
  });

  // Test concurrent booking updates
  sleep(0.5);
  const validPayload = JSON.stringify({
    facility_id: 1,
    slot_start: '2024-01-01T14:00:00Z',
    slot_end: '2024-01-01T16:00:00Z',
    sport_type: 'football',
    participants: 10,
    payment_method: 'wallet',
  });

  const createRes = http.post(
    `${BASE_URL}/api/v1/sports/bookings`,
    validPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Correlation-ID': `crash-concurrent-${__VU}-${__ITER}`,
      },
    }
  );

  if (createRes.status === 201 && createRes.json('data.uuid')) {
    const bookingUuid = createRes.json('data.uuid');

    for (let i = 0; i < 10; i++) {
      const updateRes = http.put(
        `${BASE_URL}/api/v1/sports/bookings/${bookingUuid}`,
        JSON.stringify({ status: 'confirmed' }),
        {
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
            'X-Correlation-ID': `crash-update-${__VU}-${__ITER}-${i}`,
          },
        }
      );

      check(updateRes, {
        'concurrent update handled': (r) => r.status < 500,
      });
    }
  }

  // Test invalid sport type
  sleep(0.5);
  const invalidSportPayload = JSON.stringify({
    facility_id: 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T12:00:00Z',
    sport_type: '../../../etc/passwd',
    participants: 2,
    payment_method: 'wallet',
  });

  const invalidSportRes = http.post(
    `${BASE_URL}/api/v1/sports/bookings`,
    invalidSportPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Correlation-ID': `crash-injection-${__VU}-${__ITER}`,
      },
    }
  );

  check(invalidSportRes, {
    'sql injection blocked': (r) => r.status === 422 || r.status === 400,
  });

  sleep(2);
}
