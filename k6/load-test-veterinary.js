import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('veterinary_request_duration');
const requestCounter = new Counter('veterinary_requests');

export const options = {
  stages: [
    { duration: '1m', target: 15 },
    { duration: '5m', target: 40 },
    { duration: '5m', target: 15 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<400', 'p(99)<800'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const clinics = [1, 2, 3, 4, 5];
const owners = Array.from({ length: 200 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const clinicId = getRandomElement(clinics);
  const ownerId = getRandomElement(owners);
  const token = 'veterinary-token-' + clinicId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': clinicId.toString(),
  };

  group('Get Patients', () => {
    let response = http.get(
      `${BASE_URL}/api/veterinary/patients`,
      { headers, tags: { name: 'GetPatients' } }
    );

    check(response, {
      'patients status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Create Appointment', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/veterinary/appointments`, {
      owner_id: ownerId,
      patient_id: getRandomElement([23001, 23002, 23003]),
      scheduled_at: new Date(Date.now() + 3*24*60*60*1000).toISOString(),
      reason: 'Checkup',
    }, { headers, tags: { name: 'CreateAppointment' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'create appointment status is 201': (r) => r.status === 201,
      'appointment has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
