import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('delivery_request_duration');
const requestCounter = new Counter('delivery_requests');

export const options = {
  stages: [
    { duration: '1m', target: 30 },
    { duration: '5m', target: 80 },
    { duration: '5m', target: 30 },
  ],
  thresholds: {
    'http_req_duration': ['p(95)<350', 'p(99)<700'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const providers = [1, 2, 3, 4, 5];
const customers = Array.from({ length: 500 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const providerId = getRandomElement(providers);
  const customerId = getRandomElement(customers);
  const token = 'delivery-token-' + providerId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': providerId.toString(),
  };

  group('Track Order', () => {
    let response = http.get(
      `${BASE_URL}/api/delivery/orders/${Math.floor(Math.random() * 1000)}`,
      { headers, tags: { name: 'TrackOrder' } }
    );

    check(response, {
      'track status is 200 or 404': (r) => r.status === 200 || r.status === 404,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Create Delivery Order', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/delivery/orders`, {
      customer_id: customerId,
      pickup_address: 'Pickup Address',
      delivery_address: 'Delivery Address',
      package_weight: 5,
    }, { headers, tags: { name: 'CreateOrder' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'create order status is 201': (r) => r.status === 201,
      'order has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
