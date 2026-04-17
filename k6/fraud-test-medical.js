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
  // Test 1: Rapid appointment attempts (same doctor, different times)
  const rapidPayload = JSON.stringify({
    doctor_id: 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T11:00:00Z',
    appointment_type: 'consultation',
    symptoms: 'Test symptoms',
    payment_method: 'wallet',
  });

  const rapidRes = http.post(
    `${BASE_URL}/api/v1/medical/appointments`,
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
    'rapid appointment blocked or flagged': (r) => 
      r.status === 429 || 
      r.status === 403 || 
      (r.status === 422 && r.json('message')?.includes('fraud')) ||
      (r.json('data.fraud_score') && r.json('data.fraud_score') > 0.5),
  });

  fraudDetectedRate.add(fraudDetected);

  sleep(1);

  // Test 2: Fake appointment with suspicious data
  const fakePayload = JSON.stringify({
    doctor_id: 999999, // Non-existent doctor
    slot_start: '2024-01-01T03:00:00Z', // Suspicious time (3 AM)
    slot_end: '2024-01-01T04:00:00Z',
    appointment_type: 'surgery', // Complex procedure without proper context
    symptoms: 'I need surgery immediately, very urgent, VIP patient',
    payment_method: 'cash', // Suspicious for medical
  });

  const fakeRes = http.post(
    `${BASE_URL}/api/v1/medical/appointments`,
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
    'fake appointment detected': (r) => 
      r.status === 422 || 
      r.status === 404 || 
      (r.json('data.fraud_score') && r.json('data.fraud_score') > 0.7),
  });

  sleep(1);

  // Test 3: Appointment with PII in symptoms (compliance test)
  const piiPayload = JSON.stringify({
    doctor_id: 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T11:00:00Z',
    appointment_type: 'consultation',
    symptoms: 'My SSN is 123-45-6789 and my credit card is 4111-1111-1111-1111',
    payment_method: 'wallet',
  });

  const piiRes = http.post(
    `${BASE_URL}/api/v1/medical/appointments`,
    piiPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Correlation-ID': `fraud-pii-${__VU}-${__ITER}`,
      },
    }
  );

  check(piiRes, {
    'PII detected and blocked/anonymized': (r) => 
      r.status === 422 || 
      (r.json('data.symptoms') && !r.json('data.symptoms').includes('123-45-6789')),
  });

  sleep(1);

  // Test 4: Multiple appointments for same slot (double booking attempt)
  const doubleBookPayload = JSON.stringify({
    doctor_id: 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T11:00:00Z',
    appointment_type: 'consultation',
    symptoms: 'Test symptoms',
    payment_method: 'wallet',
  });

  const doubleBookRes1 = http.post(
    `${BASE_URL}/api/v1/medical/appointments`,
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
    `${BASE_URL}/api/v1/medical/appointments`,
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

  // Test 5: Appointment with suspicious user agent
  const suspiciousPayload = JSON.stringify({
    doctor_id: 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T11:00:00Z',
    appointment_type: 'consultation',
    symptoms: 'Test symptoms',
    payment_method: 'wallet',
  });

  const suspiciousRes = http.post(
    `${BASE_URL}/api/v1/medical/appointments`,
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

  sleep(1);

  // Test 6: Emergency abuse (fake emergency)
  const emergencyPayload = JSON.stringify({
    doctor_id: 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T11:00:00Z',
    appointment_type: 'emergency',
    symptoms: 'EMERGENCY!!! I am dying!!! Need immediate attention!!!',
    payment_method: 'wallet',
    is_emergency: true,
  });

  const emergencyRes = http.post(
    `${BASE_URL}/api/v1/medical/appointments`,
    emergencyPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Correlation-ID': `fraud-emergency-${__VU}-${__ITER}`,
      },
    }
  );

  check(emergencyRes, {
    'emergency abuse detected': (r) => 
      r.status === 422 || 
      (r.json('data.fraud_score') && r.json('data.fraud_score') > 0.8),
  });

  sleep(2);
}
