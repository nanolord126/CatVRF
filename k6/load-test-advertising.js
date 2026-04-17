import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter } from 'k6/metrics';

const requestDuration = new Trend('advertising_request_duration');
const requestCounter = new Counter('advertising_requests');

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
const agencies = [1, 2, 3, 4, 5];
const clients = Array.from({ length: 200 }, (_, i) => i + 1);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

export default function () {
  const agencyId = getRandomElement(agencies);
  const clientId = getRandomElement(clients);
  const token = 'advertising-token-' + agencyId;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': agencyId.toString(),
  };

  group('Get Campaigns', () => {
    let response = http.get(
      `${BASE_URL}/api/advertising/campaigns`,
      { headers, tags: { name: 'GetCampaigns' } }
    );

    check(response, {
      'campaigns status is 200': (r) => r.status === 200,
    });

    requestCounter.add(1);
  });

  sleep(0.5);

  group('Create Campaign', () => {
    let start = new Date();

    let response = http.post(`${BASE_URL}/api/advertising/campaigns`, {
      client_id: clientId,
      name: 'Summer Sale',
      type: 'display',
      budget: 10000,
    }, { headers, tags: { name: 'CreateCampaign' } });

    requestDuration.add(new Date() - start);
    requestCounter.add(1);

    check(response, {
      'create campaign status is 201': (r) => r.status === 201,
      'campaign has ID': (r) => r.json('id') !== null,
    });
  });

  sleep(1);
}
