/**
 * Warehouse Load Testing Script
 * 
 * Tests warehouse operations under load:
 * - Warehouse CRUD operations
 * - Zone management
 * - Product catalog operations
 * - Stock movements
 * - Real-time monitoring
 * 
 * @author CatVRF Team
 * @version 2026.04.28
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');

// Test configuration
export const options = {
    stages: [
        { duration: '2m', target: 50 },   // Ramp up to 50 users
        { duration: '5m', target: 50 },   // Stay at 50 users
        { duration: '2m', target: 100 },  // Ramp up to 100 users
        { duration: '5m', target: 100 },  // Stay at 100 users
        { duration: '2m', target: 200 },  // Ramp up to 200 users
        { duration: '5m', target: 200 },  // Stay at 200 users
        { duration: '2m', target: 0 },    // Ramp down to 0
    ],
    thresholds: {
        http_req_duration: ['p(95)<2000'], // 95% of requests must complete below 2s
        http_req_failed: ['rate<0.05'],    // Error rate must be below 5%
        errors: ['rate<0.05'],
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const API_TOKEN = __ENV.API_TOKEN || 'test-token';

// Test data
const testWarehouses = [];
const testZones = [];
const testProducts = [];

// Helper functions
function getRandomString(length) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

function createHeaders() {
    return {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${API_TOKEN}`,
    };
}

// Test scenarios
export function setup() {
    // Login and get token
    const loginRes = http.post(`${BASE_URL}/api/login`, JSON.stringify({
        email: 'test@catvrf.com',
        password: 'password',
    }), {
        headers: { 'Content-Type': 'application/json' },
    });

    if (loginRes.status === 200) {
        const token = loginRes.json('token');
        __ENV.API_TOKEN = token;
    }

    // Create initial test data
    for (let i = 0; i < 10; i++) {
        const warehouseRes = http.post(`${BASE_URL}/api/warehouses`, JSON.stringify({
            name: `Load Test Warehouse ${i}`,
            address: `${i} Test Street, Test City`,
            branch_id: `branch-load-${i}`,
            type: 'central',
            capacity: 100000,
        }), {
            headers: createHeaders(),
        });

        if (warehouseRes.status === 201) {
            testWarehouses.push(warehouseRes.json('id'));
        }
    }

    return { testWarehouses };
}

export default function (data) {
    const headers = createHeaders();

    // Scenario 1: Read warehouses (70% of requests)
    if (Math.random() < 0.7) {
        const warehouseId = testWarehouses[Math.floor(Math.random() * testWarehouses.length)];
        const res = http.get(`${BASE_URL}/api/warehouses/${warehouseId}`, {
            headers,
        });

        const success = check(res, {
            'warehouse read status 200': (r) => r.status === 200,
            'warehouse has id': (r) => r.json('id') !== undefined,
            'warehouse has name': (r) => r.json('name') !== undefined,
        });

        errorRate.add(!success);
    }

    // Scenario 2: List all warehouses (20% of requests)
    else if (Math.random() < 0.9) {
        const res = http.get(`${BASE_URL}/api/warehouses`, {
            headers,
        });

        const success = check(res, {
            'warehouse list status 200': (r) => r.status === 200,
            'warehouse list is array': (r) => Array.isArray(r.json()),
        });

        errorRate.add(!success);
    }

    // Scenario 3: Create warehouse (5% of requests)
    else if (Math.random() < 0.95) {
        const res = http.post(`${BASE_URL}/api/warehouses`, JSON.stringify({
            name: `Dynamic Warehouse ${getRandomString(8)}`,
            address: `${Math.floor(Math.random() * 1000)} Dynamic Street`,
            branch_id: `branch-dynamic-${getRandomString(4)}`,
            type: ['central', 'regional', 'local'][Math.floor(Math.random() * 3)],
            capacity: Math.floor(Math.random() * 50000) + 1000,
        }), {
            headers,
        });

        const success = check(res, {
            'warehouse create status 201': (r) => r.status === 201,
            'warehouse has id': (r) => r.json('id') !== undefined,
        });

        errorRate.add(!success);

        if (res.status === 201) {
            testWarehouses.push(res.json('id'));
        }
    }

    // Scenario 4: Update stock (3% of requests)
    else if (Math.random() < 0.98) {
        const warehouseId = testWarehouses[Math.floor(Math.random() * testWarehouses.length)];
        const res = http.put(`${BASE_URL}/api/warehouses/${warehouseId}/stock`, JSON.stringify({
            quantity: Math.floor(Math.random() * 1000),
        }), {
            headers,
        });

        const success = check(res, {
            'stock update status 200': (r) => r.status === 200,
        });

        errorRate.add(!success);
    }

    // Scenario 5: Get zones for warehouse (2% of requests)
    else {
        const warehouseId = testWarehouses[Math.floor(Math.random() * testWarehouses.length)];
        const res = http.get(`${BASE_URL}/api/zones/warehouse/${warehouseId}`, {
            headers,
        });

        const success = check(res, {
            'zones list status 200': (r) => r.status === 200,
            'zones list is array': (r) => Array.isArray(r.json()),
        });

        errorRate.add(!success);
    }

    sleep(Math.random() * 2 + 1); // Random sleep between 1-3 seconds
}

export function teardown(data) {
    // Cleanup test data
    const headers = createHeaders();

    for (const warehouseId of testWarehouses) {
        http.delete(`${BASE_URL}/api/warehouses/${warehouseId}`, {
            headers,
        });
    }
}

// Advanced scenario: Stress test with concurrent stock movements
export function stressTestStockMovements() {
    const headers = createHeaders();
    const warehouseId = testWarehouses[0];

    // Create zone
    const zoneRes = http.post(`${BASE_URL}/api/zones`, JSON.stringify({
        warehouse_id: warehouseId,
        name: 'Stress Test Zone',
        type: 'storage',
        capacity: 10000,
    }), { headers });

    if (zoneRes.status !== 201) return;

    const zoneId = zoneRes.json('id');

    // Create inventory item
    const inventoryRes = http.post(`${BASE_URL}/api/inventory-items`, JSON.stringify({
        warehouse_id: warehouseId,
        zone_id: zoneId,
        product_sku: `STRESS-${getRandomString(6)}`,
        quantity: 0,
    }), { headers });

    if (inventoryRes.status !== 201) return;

    const inventoryItemId = inventoryRes.json('id');

    // Perform rapid stock movements
    const movementPromises = [];
    for (let i = 0; i < 50; i++) {
        const promise = http.postAsync(`${BASE_URL}/api/movements`, JSON.stringify({
            warehouse_id: warehouseId,
            from_zone_id: null,
            to_zone_id: zoneId,
            inventory_item_id: inventoryItemId,
            product_sku: `STRESS-${getRandomString(6)}`,
            quantity: Math.floor(Math.random() * 100) + 1,
            movement_type: 'receipt',
            reason: 'Stress test movement',
        }), { headers });

        movementPromises.push(promise);
    }

    const results = Promise.all(movementPromises);
    const successCount = results.filter(r => r.status === 201).length;

    check(results, {
        'stress test success rate > 90%': () => successCount / results.length > 0.9,
    });
}

// Scenario: Real-time monitoring load
export function realTimeMonitoringLoad() {
    const headers = createHeaders();

    // Simulate multiple users monitoring warehouses
    const monitoringUsers = 20;
    const requests = [];

    for (let i = 0; i < monitoringUsers; i++) {
        const warehouseId = testWarehouses[Math.floor(Math.random() * testWarehouses.length)];
        const request = http.getAsync(`${BASE_URL}/api/warehouses/${warehouseId}`, {
            headers,
        });
        requests.push(request);
    }

    const results = Promise.all(requests);

    check(results, {
        'real-time monitoring p95 < 500ms': (r) => r.timings.duration < 500,
        'real-time monitoring error rate < 1%': () => results.filter(r => r.status === 200).length / results.length > 0.99,
    });
}

// Scenario: Bulk operations test
export function bulkOperationsTest() {
    const headers = createHeaders();

    // Create multiple products in bulk
    const products = [];
    for (let i = 0; i < 100; i++) {
        products.push({
            sku: `BULK-${getRandomString(8)}`,
            name: `Bulk Product ${i}`,
            unit: 'шт',
            weight: Math.random() * 10,
        });
    }

    const startTime = new Date();
    const promises = products.map(product => 
        http.postAsync(`${BASE_URL}/api/products`, JSON.stringify(product), { headers })
    );

    const results = Promise.all(promises);
    const endTime = new Date();
    const duration = endTime - startTime;

    const successCount = results.filter(r => r.status === 201).length;

    check(results, {
        'bulk operations success rate > 95%': () => successCount / results.length > 0.95,
        'bulk operations avg time < 100ms per request': () => duration / results.length < 100,
    });
}
