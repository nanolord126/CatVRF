import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');

// Test configuration
export const options = {
    stages: [
        { duration: '2m', target: 10 },   // Ramp up to 10 users
        { duration: '5m', target: 50 },   // Ramp up to 50 users
        { duration: '10m', target: 100 }, // Stay at 100 users
        { duration: '5m', target: 50 },   // Ramp down to 50 users
        { duration: '2m', target: 0 },    // Ramp down to 0 users
    ],
    thresholds: {
        http_req_duration: ['p(95)<500', 'p(99)<1000'], // 95% of requests < 500ms
        errors: ['rate<0.01'], // Error rate < 1%
    },
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';
const TENANT_ID = __ENV.TENANT_ID || '1';
const USER_ID = __ENV.USER_ID || '1';

// Helper function to get auth token
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
        'X-User-ID': USER_ID,
    };

    // Test 1: Get inventory items
    const inventoryRes = http.get(`${BASE_URL}/api/wms/inventory-items`, { headers });
    check(inventoryRes, {
        'inventory items status 200': (r) => r.status === 200,
    }) || errorRate.add(1);

    sleep(1);

    // Test 2: Get stock movements
    const movementsRes = http.get(`${BASE_URL}/api/wms/stock-movements`, { headers });
    check(movementsRes, {
        'stock movements status 200': (r) => r.status === 200,
    }) || errorRate.add(1);

    sleep(1);

    // Test 3: Get batches
    const batchesRes = http.get(`${BASE_URL}/api/wms/batches`, { headers });
    check(batchesRes, {
        'batches status 200': (r) => r.status === 200,
    }) || errorRate.add(1);

    sleep(1);

    // Test 4: Create stock movement (POST)
    const createMovementRes = http.post(`${BASE_URL}/api/wms/stock-movements`, JSON.stringify({
        inventory_item_id: 1,
        type: 'in',
        quantity: 100,
        reason: 'Load test receipt',
        source_type: 'supplier',
        source_id: 1,
    }), { headers });
    check(createMovementRes, {
        'create movement status 201': (r) => r.status === 201 || r.status === 200,
    }) || errorRate.add(1);

    sleep(1);

    // Test 5: Get compliance report
    const reportRes = http.get(`${BASE_URL}/api/wms/reports/compliance/152-fz`, { headers });
    check(reportRes, {
        'compliance report status 200': (r) => r.status === 200,
    }) || errorRate.add(1);

    sleep(1);

    // Test 6: Barcode lookup
    const barcodeRes = http.get(`${BASE_URL}/api/wms/barcode/lookup/4500123456789`, { headers });
    check(barcodeRes, {
        'barcode lookup status 200': (r) => r.status === 200 || r.status === 404,
    }) || errorRate.add(1);

    sleep(2);
}

export function handleSummary(data) {
    console.log('Test Summary:');
    console.log(`Total requests: ${data.metrics.http_reqs.values.count}`);
    console.log(`Average response time: ${data.metrics.http_req_duration.values.avg}ms`);
    console.log(`95th percentile: ${data.metrics.http_req_duration.values['p(95)']}ms`);
    console.log(`Error rate: ${data.metrics.errors.values.rate * 100}%`);
}
