import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export const options = {
    stages: [
        { duration: '1m', target: 5 },
        { duration: '3m', target: 20 },
        { duration: '5m', target: 50 },
        { duration: '3m', target: 20 },
        { duration: '1m', target: 0 },
    ],
    thresholds: {
        http_req_duration: ['p(95)<300'],
        errors: ['rate<0.02'],
    },
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';
const TENANT_ID = __ENV.TENANT_ID || '1';

function getAuthToken() {
    const loginRes = http.post(`${BASE_URL}/api/auth/login`, JSON.stringify({
        email: 'test@example.com',
        password: 'password123',
    }), {
        headers: { 'Content-Type': 'application/json' },
    });
    return loginRes.json('token') || 'test-token';
}

export default function () {
    const token = getAuthToken();
    const headers = {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'X-Tenant-ID': TENANT_ID,
    };

    // Test temperature reading recording
    const tempRes = http.post(`${BASE_URL}/api/wms/cold-chain/record-temperature`, JSON.stringify({
        warehouse_id: 1,
        zone_id: 1,
        temperature: 15.5,
        humidity: 45.0,
        sensor_id: `SENSOR-${__VU}-${__ITER}`,
    }), { headers });
    check(tempRes, {
        'temperature recorded': (r) => r.status === 201 || r.status === 200,
    }) || errorRate.add(1);

    sleep(0.5);

    // Test cold chain alerts retrieval
    const alertsRes = http.get(`${BASE_URL}/api/wms/cold-chain/alerts/1`, { headers });
    check(alertsRes, {
        'alerts retrieved': (r) => r.status === 200,
    }) || errorRate.add(1);

    sleep(0.5);

    // Test compliance report generation
    const reportRes = http.get(`${BASE_URL}/api/wms/cold-chain/compliance-report/1?start=2026-04-21&end=2026-04-28`, { headers });
    check(reportRes, {
        'compliance report generated': (r) => r.status === 200,
    }) || errorRate.add(1);

    sleep(1);
}
