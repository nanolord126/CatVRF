// K6 Load/Stress Test for RealEstate Vertical
// Tests: Property search, Scoring, Transactions, API endpoints under load

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
const propertySearchRate = new Rate('property_search_success_rate');
const scoringRate = new Rate('scoring_success_rate');
const transactionRate = new Rate('transaction_success_rate');
const apiRate = new Rate('api_success_rate');

// Test configuration
export const options = {
    stages: [
        { duration: '2m', target: 50 },   // Ramp up to 50 users
        { duration: '5m', target: 100 },  // Ramp up to 100 users
        { duration: '10m', target: 200 }, // Stay at 200 users
        { duration: '5m', target: 50 },   // Ramp down to 50 users
        { duration: '2m', target: 0 },    // Ramp down to 0
    ],
    thresholds: {
        'property_search_success_rate': ['rate>0.95'],
        'scoring_success_rate': ['rate>0.95'],
        'transaction_success_rate': ['rate>0.95'],
        'api_success_rate': ['rate>0.95'],
        'http_req_duration': ['p(95)<2000'], // 95% of requests under 2s
        'http_req_failed': ['rate<0.05'],     // Error rate under 5%
    },
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';
const AUTH_TOKEN = __ENV.AUTH_TOKEN || 'test-token';

// Test data
const propertyTypes = ['apartment', 'house', 'studio', 'penthouse', 'commercial'];
const priceRanges = [5000000, 10000000, 15000000, 20000000, 50000000];

export default function () {
    // Scenario 1: Property Search (High frequency read operation)
    const searchPayload = {
        q: randomString(10),
        type: randomItem(propertyTypes),
        min_price: randomItem(priceRanges),
        max_price: randomItem(priceRanges) + 10000000,
        page: Math.floor(Math.random() * 10) + 1,
    };

    const searchRes = http.post(
        `${BASE_URL}/api/real-estate/properties/search`,
        JSON.stringify(searchPayload),
        {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${AUTH_TOKEN}`,
                'X-Correlation-ID': randomUUID(),
            },
        }
    );

    propertySearchRate.add(searchRes.status === 200);
    check(searchRes, {
        'property search status 200': (r) => r.status === 200,
        'property search has data': (r) => r.json('data') !== undefined,
        'property search response time < 2s': (r) => r.timings.duration < 2000,
    });

    sleep(randomFloat(0.1, 0.5));

    // Scenario 2: Property Scoring (Compute-intensive operation)
    if (Math.random() > 0.7) {
        const scoringPayload = {
            property_id: Math.floor(Math.random() * 1000) + 1,
            deal_amount: randomItem(priceRanges),
            is_b2b: Math.random() > 0.8,
        };

        const scoringRes = http.post(
            `${BASE_URL}/api/real-estate/scoring`,
            JSON.stringify(scoringPayload),
            {
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${AUTH_TOKEN}`,
                    'X-Correlation-ID': randomUUID(),
                    'Idempotency-Key': randomUUID(),
                },
            }
        );

        scoringRate.add(scoringRes.status === 200);
        check(scoringRes, {
            'scoring status 200': (r) => r.status === 200,
            'scoring has overall_score': (r) => r.json('overall_score') !== undefined,
            'scoring has recommendation': (r) => r.json('recommendation') !== undefined,
            'scoring response time < 3s': (r) => r.timings.duration < 3000,
        });

        sleep(randomFloat(0.2, 0.8));
    }

    // Scenario 3: Property Detail (Read operation)
    const propertyId = Math.floor(Math.random() * 100) + 1;
    const detailRes = http.get(
        `${BASE_URL}/api/real-estate/properties/${propertyId}`,
        {
            headers: {
                'Authorization': `Bearer ${AUTH_TOKEN}`,
                'X-Correlation-ID': randomUUID(),
            },
        }
    );

    apiRate.add(detailRes.status === 200);
    check(detailRes, {
        'property detail status 200': (r) => r.status === 200 || r.status === 404,
        'property detail response time < 1s': (r) => r.timings.duration < 1000,
    });

    sleep(randomFloat(0.1, 0.3));

    // Scenario 4: Transaction Creation (Write operation - lower frequency)
    if (Math.random() > 0.85) {
        const transactionPayload = {
            property_id: Math.floor(Math.random() * 100) + 1,
            amount: randomItem(priceRanges),
            payment_method: 'wallet',
            is_b2b: Math.random() > 0.8,
        };

        const transactionRes = http.post(
            `${BASE_URL}/api/real-estate/transactions`,
            JSON.stringify(transactionPayload),
            {
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${AUTH_TOKEN}`,
                    'X-Correlation-ID': randomUUID(),
                    'Idempotency-Key': randomUUID(),
                },
            }
        );

        transactionRate.add(transactionRes.status === 200 || transactionRes.status === 409);
        check(transactionRes, {
            'transaction status 200 or 409': (r) => r.status === 200 || r.status === 409,
            'transaction has transaction_id': (r) => r.json('id') !== undefined || r.status === 409,
            'transaction response time < 5s': (r) => r.timings.duration < 5000,
        });

        sleep(randomFloat(0.5, 1.5));
    }

    // Scenario 5: Bulk Scoring (Batch operation - very low frequency)
    if (Math.random() > 0.95) {
        const propertyIds = Array.from({ length: 5 }, () => Math.floor(Math.random() * 100) + 1);
        const bulkScoringPayload = {
            property_ids: propertyIds,
            is_b2b: Math.random() > 0.8,
        };

        const bulkScoringRes = http.post(
            `${BASE_URL}/api/real-estate/scoring/bulk`,
            JSON.stringify(bulkScoringPayload),
            {
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${AUTH_TOKEN}`,
                    'X-Correlation-ID': randomUUID(),
                },
            }
        );

        apiRate.add(bulkScoringRes.status === 200);
        check(bulkScoringRes, {
            'bulk scoring status 200': (r) => r.status === 200,
            'bulk scoring has property_scores': (r) => r.json('property_scores') !== undefined,
            'bulk scoring response time < 10s': (r) => r.timings.duration < 10000,
        });

        sleep(randomFloat(1.0, 2.0));
    }

    // Scenario 6: User Eligibility Check (Frequent read operation)
    const eligibilityPayload = {
        requested_amount: randomItem(priceRanges),
        is_b2b: Math.random() > 0.8,
    };

    const eligibilityRes = http.post(
        `${BASE_URL}/api/real-estate/eligibility`,
        JSON.stringify(eligibilityPayload),
        {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${AUTH_TOKEN}`,
                'X-Correlation-ID': randomUUID(),
            },
        }
    );

    apiRate.add(eligibilityRes.status === 200);
    check(eligibilityRes, {
        'eligibility status 200': (r) => r.status === 200,
        'eligibility has eligibility_score': (r) => r.json('eligibility_score') !== undefined,
        'eligibility response time < 1s': (r) => r.timings.duration < 1000,
    });

    sleep(randomFloat(0.1, 0.4));
}

// Helper functions
function randomString(length) {
    const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

function randomUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

function randomItem(array) {
    return array[Math.floor(Math.random() * array.length)];
}

function randomFloat(min, max) {
    return Math.random() * (max - min) + min;
}
