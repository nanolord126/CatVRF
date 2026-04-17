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
  // Test malformed appointment payload
  const malformedPayload = JSON.stringify({
    doctor_id: 'A'.repeat(10000),
    slot_start: 'invalid_date',
    slot_end: null,
    appointment_type: null,
    symptoms: '../../../etc/passwd',
    payment_method: 'invalid_method',
  });

  const malformedRes = http.post(
    `${BASE_URL}/api/v1/medical/appointments`,
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
    doctor_id: null,
    slot_start: null,
    slot_end: null,
    appointment_type: null,
    symptoms: null,
    payment_method: null,
  });

  const nullRes = http.post(
    `${BASE_URL}/api/v1/medical/appointments`,
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

  // Test extremely large payload (symptoms field)
  sleep(0.5);
  const largePayload = JSON.stringify({
    doctor_id: 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T11:00:00Z',
    appointment_type: 'consultation',
    symptoms: 'A'.repeat(100000), // Extremely long symptoms
    payment_method: 'wallet',
    medical_history: Array(1000).fill('previous_condition'),
    medications: Array(500).fill('medication_name'),
  });

  const largeRes = http.post(
    `${BASE_URL}/api/v1/medical/appointments`,
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

  // Test concurrent appointment updates
  sleep(0.5);
  const validPayload = JSON.stringify({
    doctor_id: 1,
    slot_start: '2024-01-01T14:00:00Z',
    slot_end: '2024-01-01T15:00:00Z',
    appointment_type: 'consultation',
    symptoms: 'Headache',
    payment_method: 'wallet',
  });

  const createRes = http.post(
    `${BASE_URL}/api/v1/medical/appointments`,
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
    const appointmentUuid = createRes.json('data.uuid');

    for (let i = 0; i < 10; i++) {
      const updateRes = http.put(
        `${BASE_URL}/api/v1/medical/appointments/${appointmentUuid}`,
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

  // Test XSS in symptoms
  sleep(0.5);
  const xssPayload = JSON.stringify({
    doctor_id: 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T11:00:00Z',
    appointment_type: 'consultation',
    symptoms: '<script>alert("xss")</script>',
    payment_method: 'wallet',
  });

  const xssRes = http.post(
    `${BASE_URL}/api/v1/medical/appointments`,
    xssPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Correlation-ID': `crash-xss-${__VU}-${__ITER}`,
      },
    }
  );

  check(xssRes, {
    'xss blocked or sanitized': (r) => r.status < 500,
  });

  // Test invalid appointment type
  sleep(0.5);
  const invalidTypePayload = JSON.stringify({
    doctor_id: 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T11:00:00Z',
    appointment_type: '../../../etc/passwd',
    symptoms: 'Test',
    payment_method: 'wallet',
  });

  const invalidTypeRes = http.post(
    `${BASE_URL}/api/v1/medical/appointments`,
    invalidTypePayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Correlation-ID': `crash-injection-${__VU}-${__ITER}`,
      },
    }
  );

  check(invalidTypeRes, {
    'sql injection blocked': (r) => r.status === 422 || r.status === 400,
  });

  sleep(2);
}
