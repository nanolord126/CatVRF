import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const appointmentDuration = new Trend('medical_appointment_duration');
const requestCounter = new Counter('medical_requests');

export const options = {
  stages: [
    { duration: '1m', target: 20 },
    { duration: '5m', target: 50 },
    { duration: '5m', target: 20 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<350', 'p(99)<700'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const clinics = [1, 2, 3, 4, 5];
const patients = Array.from({ length: 300 }, (_, i) => i + 1);
const doctors = [10001, 10002, 10003, 10004, 10005];

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const clinicId = getRandomElement(clinics);
  const patientId = getRandomElement(patients);
  const token = 'medical-token-' + clinicId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': clinicId.toString(),
  };

  group('Get Doctors', () => {
    let response = http.get(
      `${BASE_URL}/api/medical/doctors`,
      { headers, tags: { name: 'GetDoctors' } }
    );

    check(response, {
      'doctors status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Create Appointment', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/medical/appointments`, {
      patient_id: patientId,
      doctor_id: getRandomElement(doctors),
      scheduled_at: new Date(Date.now() + 3*24*60*60*1000).toISOString(),
      reason: 'Checkup',
    }, { headers, tags: { name: 'CreateAppointment' } });

    appointmentDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'create appointment status is 201': (r) => r.status === 201,
      'appointment has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
