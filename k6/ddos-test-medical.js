import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const errorRate = new Rate('errors');
const responseTime = new Trend('response_time');

export const options = {
  stages: [
    { duration: '30s', target: 100 },  // Ramp up to 100 users
    { duration: '1m', target: 500 },    // Ramp up to 500 users
    { duration: '30s', target: 1000 },  // Spike to 1000 users
    { duration: '1m', target: 1000 },   // Stay at 1000 users
    { duration: '30s', target: 0 },     // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<3000'],
    errors: ['rate<0.5'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
  // DDOS attack on appointments endpoint
  const start = new Date().getTime();

  const appointmentPayload = JSON.stringify({
    doctor_id: Math.floor(Math.random() * 100) + 1,
    slot_start: '2024-01-01T10:00:00Z',
    slot_end: '2024-01-01T11:00:00Z',
    appointment_type: ['consultation', 'checkup', 'followup'][Math.floor(Math.random() * 3)],
    symptoms: 'Test symptoms',
    payment_method: 'wallet',
  });

  const res = http.post(
    `${BASE_URL}/api/v1/medical/appointments`,
    appointmentPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Request-ID': `ddos-${__VU}-${__ITER}-${Date.now()}`,
        'User-Agent': `DDOS-Test-${__VU}`,
      },
    }
  );

  const end = new Date().getTime();
  responseTime.add(end - start);

  const success = check(res, {
    'status is not 5xx': (r) => r.status < 500,
    'status is rate limited or success': (r) => r.status === 429 || r.status === 201 || r.status === 422,
  });

  errorRate.add(!success);

  // Parallel attack on doctors endpoint
  const doctorsRes = http.get(
    `${BASE_URL}/api/v1/medical/doctors`,
    {
      headers: {
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Request-ID': `ddos-doctors-${__VU}-${__ITER}-${Date.now()}`,
      },
    }
  );

  check(doctorsRes, {
    'doctors endpoint survives': (r) => r.status < 500,
  });

  // Attack on availability check
  const availabilityPayload = JSON.stringify({
    doctor_id: Math.floor(Math.random() * 100) + 1,
    date: '2024-01-01',
  });

  const availabilityRes = http.post(
    `${BASE_URL}/api/v1/medical/availability/check`,
    availabilityPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Request-ID': `ddos-availability-${__VU}-${__ITER}-${Date.now()}`,
      },
    }
  );

  check(availabilityRes, {
    'availability check survives': (r) => r.status < 500,
  });

  // Attack on diagnostic endpoint
  const diagnosticPayload = JSON.stringify({
    symptoms: 'Headache and fever',
    age: 30,
    gender: 'male',
  });

  const diagnosticRes = http.post(
    `${BASE_URL}/api/v1/medical/diagnostic/ai`,
    diagnosticPayload,
    {
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${__ENV.TOKEN || 'test_token'}`,
        'X-Request-ID': `ddos-diagnostic-${__VU}-${__ITER}-${Date.now()}`,
      },
    }
  );

  check(diagnosticRes, {
    'diagnostic endpoint survives': (r) => r.status < 500,
  });

  sleep(Math.random() * 2);
}
