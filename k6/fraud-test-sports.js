import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const fraudDetectedRate = new Rate('fraud_detected');

export const options = {
  stages: [
    { duration: '10s', target: 5 },
    { duration: '20s', target: 20 },
    { duration: '10s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'],
    fraud_detected: ['rate>0.8'], // Expect 80%+ fraud detection
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
  // Test 1: Rapid booking attempts (same facility, different times)
  const rapidPayload = JSON.stringify({
    facility_id: 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T12:00:00Z',
    sport_type: 'tennis',
    participants: 2,
    payment_method: 'wallet',
  });

  const rapidRes = http.post(
    `${BASE_URL}/api/v1/sports/bookings`,
    rapidPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Correlation-ID': `fraud-rapid-${__VU}-${__ITER}`,
      },
    }
  );

  const fraudDetected = check(rapidRes, {
    'rapid booking blocked or flagged': (r) => 
      r.status === 429 || 
      r.status === 403 || 
      (r.status === 422 && r.json('message')?.includes('fraud')) ||
      (r.json('data.fraud_score') && r.json('data.fraud_score') > 0.5),
  });

  fraudDetectedRate.add(fraudDetected);

  sleep(1);

  // Test 2: Fake booking with suspicious data
  const fakePayload = JSON.stringify({
    facility_id: 999999, // Non-existent facility
    slot_start: '2024-01-01T03:00:00Z', // Suspicious time (3 AM)
    slot_end: '2024-01-01T05:00:00Z',
    sport_type: 'tennis',
    participants: 50, // Unusually high for tennis
    payment_method: 'wallet',
    customer_notes: 'Urgent booking for VIP client',
  });

  const fakeRes = http.post(
    `${BASE_URL}/api/v1/sports/bookings`,
    fakePayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Correlation-ID': `fraud-fake-${__VU}-${__ITER}`,
        'X-Forwarded-For': '192.0.2.1', // Test IP
      },
    }
  );

  check(fakeRes, {
    'fake booking detected': (r) => 
      r.status === 422 || 
      r.status === 404 || 
      (r.json('data.fraud_score') && r.json('data.fraud_score') > 0.7),
  });

  sleep(1);

  // Test 3: Booking with invalid payment method
  const invalidPaymentPayload = JSON.stringify({
    facility_id: 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T12:00:00Z',
    sport_type: 'tennis',
    participants: 2,
    payment_method: 'stolen_card_12345',
  });

  const invalidPaymentRes = http.post(
    `${BASE_URL}/api/v1/sports/bookings`,
    invalidPaymentPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Correlation-ID': `fraud-payment-${__VU}-${__ITER}`,
      },
    }
  );

  check(invalidPaymentRes, {
    'invalid payment blocked': (r) => r.status === 422 || r.status === 400,
  });

  sleep(1);

  // Test 4: Multiple bookings for same slot (double booking attempt)
  const doubleBookPayload = JSON.stringify({
    facility_id: 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T12:00:00Z',
    sport_type: 'tennis',
    participants: 2,
    payment_method: 'wallet',
  });

  const doubleBookRes1 = http.post(
    `${BASE_URL}/api/v1/sports/bookings`,
    doubleBookPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Correlation-ID': `fraud-double1-${__VU}-${__ITER}`,
      },
    }
  );

  const doubleBookRes2 = http.post(
    `${BASE_URL}/api/v1/sports/bookings`,
    doubleBookPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Correlation-ID': `fraud-double2-${__VU}-${__ITER}`,
      },
    }
  );

  check(doubleBookRes2, {
    'double booking prevented': (r) => 
      r.status === 409 || 
      r.status === 422 || 
      (r.json('message')?.includes('already booked')),
  });

  sleep(1);

  // Test 5: Booking with suspicious user agent
  const suspiciousPayload = JSON.stringify({
    facility_id: 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T12:00:00Z',
    sport_type: 'tennis',
    participants: 2,
    payment_method: 'wallet',
  });

  const suspiciousRes = http.post(
    `${BASE_URL}/api/v1/sports/bookings`,
    suspiciousPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Correlation-ID': `fraud-suspicious-${__VU}-${__ITER}`,
        'User-Agent': 'Bot/1.0 (Suspicious Bot)',
      },
    }
  );

  check(suspiciousRes, {
    'suspicious user agent flagged': (r) => 
      r.status === 403 || 
      (r.json('data.fraud_score') && r.json('data.fraud_score') > 0.6),
  });

  sleep(2);
}
