import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const bookingDuration = new Trend('travel_booking_duration');
const requestCounter = new Counter('travel_requests');

export const options = {
  stages: [
    { duration: '1m', target: 20 },
    { duration: '5m', target: 50 },
    { duration: '5m', target: 20 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<400', 'p(99)<800'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const agencies = [1, 2, 3, 4, 5];
const customers = Array.from({ length: 300 }, (_, i) => i + 1);
const destinations = [8001, 8002, 8003, 8004, 8005];

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const agencyId = getRandomElement(agencies);
  const customerId = getRandomElement(customers);
  const token = 'travel-token-' + agencyId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': agencyId.toString(),
  };

  group('Search Destinations', () => {
    let response = http.get(
      `${BASE_URL}/api/travel/destinations`,
      { headers, tags: { name: 'SearchDestinations' } }
    );

    check(response, {
      'destinations status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Create Booking', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/travel/bookings`, {
      customer_id: customerId,
      destination_id: getRandomElement(destinations),
      departure_date: new Date(Date.now() + 7*24*60*60*1000).toISOString(),
      passengers: 2,
    }, { headers, tags: { name: 'CreateBooking' } });

    bookingDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'create booking status is 201': (r) => r.status === 201,
      'booking has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
