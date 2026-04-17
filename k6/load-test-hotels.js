import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const bookingDuration = new Trend('hotels_booking_duration');
const requestCounter = new Counter('hotels_requests');

export const options = {
  stages: [
    { duration: '1m', target: 25 },
    { duration: '5m', target: 70 },
    { duration: '5m', target: 25 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<400', 'p(99)<800'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const hotels = [1, 2, 3, 4, 5];
const guests = Array.from({ length: 400 }, (_, i) => i + 1);
const rooms = [9001, 9002, 9003, 9004, 9005];

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const hotelId = getRandomElement(hotels);
  const guestId = getRandomElement(guests);
  const token = 'hotels-token-' + hotelId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': hotelId.toString(),
  };

  group('Search Rooms', () => {
    let response = http.get(
      `${BASE_URL}/api/hotels/rooms`,
      { headers, tags: { name: 'SearchRooms' } }
    );

    check(response, {
      'rooms status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Create Booking', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/hotels/bookings`, {
      guest_id: guestId,
      room_id: getRandomElement(rooms),
      check_in: new Date(Date.now() + 7*24*60*60*1000).toISOString(),
      check_out: new Date(Date.now() + 14*24*60*60*1000).toISOString(),
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
