import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Counter, Trend, Gauge } from 'k6/metrics';

// Custom metrics
const crossVerticalDuration = new Trend('cross_vertical_duration');
const paymentConcurrencyErrors = new Counter('payment_concurrency_errors');
const fraudCheckConcurrencyErrors = new Counter('fraud_check_concurrency_errors');
const inventoryDeductionErrors = new Counter('inventory_deduction_errors');
const walletBalanceErrors = new Counter('wallet_balance_errors');
const concurrentOperations = new Gauge('concurrent_operations');

const API_BASE_URL = __ENV.API_BASE_URL || 'http://localhost:8000/api';
const TENANT_ID = parseInt(__ENV.TENANT_ID || '1');

export const options = {
  stages: [
    // Ramp up: 0 -> 50 VUs over 5 minutes
    { duration: '2m', target: 20 },
    { duration: '3m', target: 50 },
    // Stay at peak: 50 VUs for 10 minutes
    { duration: '10m', target: 50 },
    // Ramp down: 50 -> 0 VUs over 5 minutes
    { duration: '5m', target: 0 },
  ],
  thresholds: {
    'cross_vertical_duration': ['p(95)<5000'], // 5s for cross-vertical ops
    'payment_concurrency_errors': ['count<50'],
    'fraud_check_concurrency_errors': ['count<50'],
    'inventory_deduction_errors': ['count<10'],
    'wallet_balance_errors': ['count<5'],
    'http_req_duration': ['p(99)<8000'],
    'http_req_failed': ['rate<0.05'],
  },
};

/**
 * Cross-Vertical Integration Test
 * Simulates simultaneous operations across multiple verticals
 * to test system-wide performance under load
 */
export default function () {
  const startTime = new Date().getTime();
  concurrentOperations.add(1);

  try {
    group('Parallel Beauty + Food Operations', () => {
      // Scenario 1: Beauty salon appointment + payment
      // Scenario 2: Restaurant order + kitchen workflow
      // Running both simultaneously to test system stress

      const beautyStart = new Date().getTime();
      
      // Beauty: Create appointment
      const appointmentRes = http.post(
        `${API_BASE_URL}/salons/101/appointments`,
        JSON.stringify({
          master_id: 201,
          service_id: 401,
          start_time: new Date().toISOString(),
          duration_minutes: 60,
          client_phone: `+7-900-${Math.floor(Math.random() * 9000000) + 1000000}`,
          idempotency_key: `beauty-appt-${Date.now()}-${Math.random()}`,
        }),
        {
          headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${__ENV.API_TOKEN || ''}`,
            'X-Tenant-ID': TENANT_ID.toString(),
          },
        }
      );

      check(appointmentRes, {
        'Beauty appointment created': (r) => r.status === 201,
        'Appointment has ID': (r) => r.json('id') !== undefined,
      }) || paymentConcurrencyErrors.add(1);

      if (appointmentRes.status === 201) {
        const appointmentId = appointmentRes.json('id');

        // Beauty: Fraud check on appointment
        const fraudCheckRes = http.post(
          `${API_BASE_URL}/fraud/check`,
          JSON.stringify({
            operation_type: 'appointment_booking',
            user_id: 1001,
            amount: 50000,
            ip_address: `192.168.${Math.floor(Math.random() * 255)}.${Math.floor(Math.random() * 255)}`,
            device_fingerprint: `device-${Math.random()}`,
          }),
          {
            headers: {
              'Content-Type': 'application/json',
              'X-Tenant-ID': TENANT_ID.toString(),
            },
          }
        );

        check(fraudCheckRes, {
          'Fraud check completed': (r) => r.status === 200,
          'Fraud score available': (r) => r.json('score') !== undefined,
        }) || fraudCheckConcurrencyErrors.add(1);

        // Beauty: Hold payment for appointment
        const paymentHoldRes = http.post(
          `${API_BASE_URL}/payments/hold`,
          JSON.stringify({
            appointment_id: appointmentId,
            amount: 50000,
            payment_method: 'card',
            idempotency_key: `payment-hold-${appointmentId}`,
          }),
          {
            headers: {
              'Content-Type': 'application/json',
              'X-Tenant-ID': TENANT_ID.toString(),
            },
          }
        );

        check(paymentHoldRes, {
          'Payment hold successful': (r) => r.status === 201,
          'Hold transaction created': (r) => r.json('transaction_id') !== undefined,
        }) || paymentConcurrencyErrors.add(1);

        // Beauty: Deduct consumables
        const consumableDeductRes = http.post(
          `${API_BASE_URL}/inventory/deduct`,
          JSON.stringify({
            items: [
              { consumable_id: 5001, quantity: 3 }, // краска
              { consumable_id: 5002, quantity: 1 }, // ножницы (durability)
            ],
            appointment_id: appointmentId,
            reason: 'appointment_completion',
          }),
          {
            headers: {
              'Content-Type': 'application/json',
              'X-Tenant-ID': TENANT_ID.toString(),
            },
          }
        );

        check(consumableDeductRes, {
          'Consumables deducted': (r) => r.status === 200,
          'Inventory updated': (r) => r.json('deductions_applied') !== undefined,
        }) || inventoryDeductionErrors.add(1);
      }

      const beautyEnd = new Date().getTime();
      crossVerticalDuration.add(beautyEnd - beautyStart, { operation: 'beauty_full_flow' });

      sleep(0.5); // Small delay between vertical operations
    });

    group('Parallel Food + Delivery Operations', () => {
      const foodStart = new Date().getTime();

      // Food: Create order
      const orderRes = http.post(
        `${API_BASE_URL}/restaurants/301/orders`,
        JSON.stringify({
          items: [
            { dish_id: 5001, quantity: 2 },
            { dish_id: 5002, quantity: 1 },
          ],
          client_phone: `+7-900-${Math.floor(Math.random() * 9000000) + 1000000}`,
          client_address: 'СПб, Невский проспект, 1',
          idempotency_key: `food-order-${Date.now()}-${Math.random()}`,
        }),
        {
          headers: {
            'Content-Type': 'application/json',
            'X-Tenant-ID': TENANT_ID.toString(),
          },
        }
      );

      check(orderRes, {
        'Food order created': (r) => r.status === 201,
        'Order has ID': (r) => r.json('id') !== undefined,
      }) || paymentConcurrencyErrors.add(1);

      if (orderRes.status === 201) {
        const orderId = orderRes.json('id');

        // Food: Check consumables availability
        const inventoryCheckRes = http.post(
          `${API_BASE_URL}/restaurants/301/check-availability`,
          JSON.stringify({
            items: [
              { dish_id: 5001, quantity: 2 },
              { dish_id: 5002, quantity: 1 },
            ],
          }),
          {
            headers: {
              'Content-Type': 'application/json',
              'X-Tenant-ID': TENANT_ID.toString(),
            },
          }
        );

        check(inventoryCheckRes, {
          'Inventory check passed': (r) => r.status === 200,
          'All items available': (r) => r.json('all_available') === true,
        }) || inventoryDeductionErrors.add(1);

        // Food: Hold payment
        const foodPaymentHoldRes = http.post(
          `${API_BASE_URL}/payments/hold`,
          JSON.stringify({
            order_id: orderId,
            amount: 120000,
            payment_method: 'card',
            idempotency_key: `payment-food-${orderId}`,
          }),
          {
            headers: {
              'Content-Type': 'application/json',
              'X-Tenant-ID': TENANT_ID.toString(),
            },
          }
        );

        check(foodPaymentHoldRes, {
          'Food payment hold successful': (r) => r.status === 201,
        }) || paymentConcurrencyErrors.add(1);

        // Food: Update KDS status (order in preparation)
        const kdsUpdateRes = http.patch(
          `${API_BASE_URL}/orders/${orderId}/kds-status`,
          JSON.stringify({
            status: 'in_progress',
            estimated_ready_minutes: 20,
          }),
          {
            headers: {
              'Content-Type': 'application/json',
              'X-Tenant-ID': TENANT_ID.toString(),
            },
          }
        );

        check(kdsUpdateRes, {
          'KDS status updated': (r) => r.status === 200,
        });

        // Food: Deduct consumables when KDS marks ready
        sleep(1); // Simulate prep time

        const foodConsumableDeductRes = http.post(
          `${API_BASE_URL}/restaurants/301/deduct-consumables`,
          JSON.stringify({
            order_id: orderId,
            items: [
              { ingredient_id: 9001, quantity: 150 }, // грамм
              { ingredient_id: 9002, quantity: 100 },
            ],
          }),
          {
            headers: {
              'Content-Type': 'application/json',
              'X-Tenant-ID': TENANT_ID.toString(),
            },
          }
        );

        check(foodConsumableDeductRes, {
          'Food consumables deducted': (r) => r.status === 200,
        }) || inventoryDeductionErrors.add(1);

        // Food: Create delivery order
        const deliveryRes = http.post(
          `${API_BASE_URL}/deliveries`,
          JSON.stringify({
            order_id: orderId,
            restaurant_id: 301,
            pickup_address: 'СПб, Невский проспект, 100',
            delivery_address: 'СПб, Невский проспект, 1',
            client_phone: `+7-900-${Math.floor(Math.random() * 9000000) + 1000000}`,
          }),
          {
            headers: {
              'Content-Type': 'application/json',
              'X-Tenant-ID': TENANT_ID.toString(),
            },
          }
        );

        check(deliveryRes, {
          'Delivery created': (r) => r.status === 201,
        });
      }

      const foodEnd = new Date().getTime();
      crossVerticalDuration.add(foodEnd - foodStart, { operation: 'food_delivery_flow' });

      sleep(0.5);
    });

    group('Parallel Auto + Real Estate Operations', () => {
      const autoStart = new Date().getTime();

      // Auto: Create ride request
      const rideRes = http.post(
        `${API_BASE_URL}/rides`,
        JSON.stringify({
          pickup_latitude: 59.9311,
          pickup_longitude: 30.3609,
          dropoff_latitude: 59.9386,
          dropoff_longitude: 30.3197,
          ride_type: 'comfort',
        }),
        {
          headers: {
            'Content-Type': 'application/json',
            'X-Tenant-ID': TENANT_ID.toString(),
          },
        }
      );

      check(rideRes, {
        'Ride request created': (r) => r.status === 201,
      });

      // Real Estate: Concurrent property search
      const searchRes = http.get(
        `${API_BASE_URL}/properties?city=spb&type=apartment&price_min=5000000&price_max=25000000&area_min=40&area_max=150&limit=20`,
        {
          headers: {
            'X-Tenant-ID': TENANT_ID.toString(),
          },
        }
      );

      check(searchRes, {
        'Real estate search successful': (r) => r.status === 200,
        'Properties returned': (r) => r.json('count') > 0,
      });

      const autoEnd = new Date().getTime();
      crossVerticalDuration.add(autoEnd - autoStart, { operation: 'auto_realestate_flow' });

      sleep(0.5);
    });

    group('Wallet Balance Validation Across Verticals', () => {
      const walletStart = new Date().getTime();

      // Check wallet balance after multiple operations
      const walletRes = http.get(
        `${API_BASE_URL}/wallets/tenant-${TENANT_ID}`,
        {
          headers: {
            'X-Tenant-ID': TENANT_ID.toString(),
          },
        }
      );

      check(walletRes, {
        'Wallet balance readable': (r) => r.status === 200,
        'Current balance exists': (r) => r.json('current_balance') !== undefined,
        'Hold amount exists': (r) => r.json('hold_amount') !== undefined,
      }) || walletBalanceErrors.add(1);

      const walletEnd = new Date().getTime();
      crossVerticalDuration.add(walletEnd - walletStart, { operation: 'wallet_validation' });
    });

  } finally {
    concurrentOperations.add(-1);
  }

  const totalDuration = new Date().getTime() - startTime;
  crossVerticalDuration.add(totalDuration, { operation: 'total_iteration' });
  
  sleep(1);
}
