import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const appointmentDuration = new Trend('beauty_appointment_duration');
const consumablesDuration = new Trend('beauty_consumables_deduct_duration');
const requestCounter = new Counter('beauty_requests');

export const options = {
  stages: [
    { duration: '1m', target: 20 },   // Salons booking appointments
    { duration: '5m', target: 50 },   // Peak hours
    { duration: '5m', target: 20 },   // Cool down
  ],
  thresholds: {
    'http_req_duration': ['p(95)<300', 'p(99)<600'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const salons = [1, 2, 3, 4, 5];
const masters = [101, 102, 103, 104, 105];
const clients = Array.from({ length: 500 }, (_, i) => i + 1);
const services = [5001, 5002, 5003, 5004, 5005];

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

function generateDateTime() {
  const date = new Date();
  date.setDate(date.getDate() + Math.floor(Math.random() * 7) + 1);
  date.setHours(Math.floor(Math.random() * 8) + 10, 0, 0, 0); // 10:00 - 18:00
  return date.toISOString();
}

export default function () {
  const salonId = getRandomElement(salons);
  const masterId = getRandomElement(masters);
  const clientId = getRandomElement(clients);
  const serviceId = getRandomElement(services);
  const token = 'beauty-token-' + salonId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': salonId.toString(),
  };

  group('Salon Availability Check', () => {
    let response = http.get(
      `${BASE_URL}/api/masters/${masterId}/availability?date=${new Date().toISOString().split('T')[0]}`,
      { headers, tags: { name: 'GetAvailability' } }
    );

    check(response, {
      'availability check status is 200': (r) => r.status === 200,
      'response has available_slots': (r) => r.json('available_slots') !== null,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Create Appointment with Idempotency', () => {
    const idempotencyKey = `appt-${Date.now()}-${Math.random().toString(36).substring(7)}`;
    const appointmentTime = generateDateTime();

    let start = new Date();

    let response = http.post(`${BASE_URL}/api/appointments`, {
      service_id: serviceId,
      master_id: masterId,
      client_id: clientId,
      datetime: appointmentTime,
      notes: 'Стрижка',
      idempotency_key: idempotencyKey,
    }, { headers, tags: { name: 'CreateAppointment' } });

    appointmentDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'create appointment status is 201': (r) => r.status === 201,
      'appointment has ID': (r) => r.json('id') !== null,
      'appointment status is pending': (r) => r.json('status') === 'pending',
    });

    const appointmentId = response.json('id');

    sleep(0.3);

    // Verify idempotency
    let dupResponse = http.post(`${BASE_URL}/api/appointments`, {
      service_id: serviceId,
      master_id: masterId,
      client_id: clientId,
      datetime: appointmentTime,
      notes: 'Стрижка',
      idempotency_key: idempotencyKey,
    }, { headers, tags: { name: 'IdempotencyCheck' } });

    check(dupResponse, {
      'duplicate request returns 200': (r) => r.status === 200,
      'same appointment ID': (r) => r.json('id') === appointmentId,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Deduct Consumables on Completion', () => {
    const appointmentId = Math.floor(Math.random() * 10000) + 9000;

    let start = new Date();

    let response = http.post(
      `${BASE_URL}/api/appointments/${appointmentId}/deduct-consumables`,
      {},
      { headers, tags: { name: 'DeductConsumables' } }
    );

    consumablesDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'deduct consumables is 200 or 404': (r) => r.status === 200 || r.status === 404,
    });
  });

  sleep(0.5);

  group('Rate Master & Service', () => {
    const appointmentId = Math.floor(Math.random() * 10000) + 9000;

    let response = http.post(`${BASE_URL}/api/appointments/${appointmentId}/review`, {
      rating: Math.floor(Math.random() * 5) + 1,
      comment: 'Отличная работа!',
    }, { headers, tags: { name: 'SubmitReview' } });

    check(response, {
      'review submission is 200 or 404': (r) => r.status === 200 || r.status === 404,
    });

    requestCounter.add(1);
  });

  sleep(1);
}
