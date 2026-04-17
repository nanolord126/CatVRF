import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('inventory_request_duration');
const requestCounter = new Counter('inventory_requests');

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
const warehouses = [1, 2, 3, 4, 5];

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const warehouseId = getRandomElement(warehouses);
  const token = 'inventory-token-' + warehouseId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': warehouseId.toString(),
  };

  group('Get Items', () => {
    let response = http.get(
      `${BASE_URL}/api/inventory/items`,
      { headers, tags: { name: 'GetItems' } }
    );

    check(response, {
      'items status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Update Stock', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/inventory/stock`, {
      item_id: getRandomElement([20001, 20002, 20003]),
      quantity: 100,
    }, { headers, tags: { name: 'UpdateStock' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'update stock status is 200 or 404': (r) => r.status === 200 || r.status === 404,
    });
  });

  sleep(1);
}
