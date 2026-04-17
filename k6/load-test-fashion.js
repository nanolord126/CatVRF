import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const orderDuration = new Trend('fashion_order_duration');
const requestCounter = new Counter('fashion_requests');

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
const stores = [1, 2, 3, 4, 5];
const customers = Array.from({ length: 400 }, (_, i) => i + 1);
const products = [7001, 7002, 7003, 7004, 7005];

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const storeId = getRandomElement(stores);
  const customerId = getRandomElement(customers);
  const token = 'fashion-token-' + storeId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': storeId.toString(),
  };

  group('Get Products', () => {
    let response = http.get(
      `${BASE_URL}/api/fashion/products`,
      { headers, tags: { name: 'GetProducts' } }
    );

    check(response, {
      'products status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Create Order', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/fashion/orders`, {
      customer_id: customerId,
      items: [{ product_id: getRandomElement(products), quantity: 2 }],
      shipping_address: 'Test Address',
    }, { headers, tags: { name: 'CreateOrder' } });

    orderDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'create order status is 201': (r) => r.status === 201,
      'order has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
