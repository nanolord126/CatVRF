import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('tickets_request_duration');
const requestCounter = new Counter('tickets_requests');

export const options = {
  stages: [
    { duration: '1m', target: 25 },
    { duration: '5m', target: 60 },
    { duration: '5m', target: 25 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<350', 'p(99)<700'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const venues = [1, 2, 3, 4, 5];
const customers = Array.from({ length: 400 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const venueId = getRandomElement(venues);
  const customerId = getRandomElement(customers);
  const token = 'tickets-token-' + venueId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': venueId.toString(),
  };

  group('Get Events', () => {
    let response = http.get(
      `${BASE_URL}/api/tickets/events`,
      { headers, tags: { name: 'GetEvents' } }
    );

    check(response, {
      'events status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Book Tickets', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/tickets/bookings`, {
      customer_id: customerId,
      event_id: getRandomElement([21001, 21002, 21003]),
      quantity: 2,
    }, { headers, tags: { name: 'BookTickets' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'book tickets status is 201': (r) => r.status === 201,
      'booking has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
