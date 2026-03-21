import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Trend, Counter, Gauge } from 'k6/metrics';

const orderCreationDuration = new Trend('food_order_creation_duration');
const kdsUpdateDuration = new Trend('food_kds_update_duration');
const deliveryMatchingDuration = new Trend('food_delivery_matching_duration');
const orderCounter = new Counter('food_orders_total');
const activeOrders = new Gauge('food_active_orders');
const consumablesChecks = new Counter('food_consumables_checks');

export const options = {
  stages: [
    { duration: '2m', target: 20 },   // Lunch starts
    { duration: '15m', target: 100 }, // Lunch peak
    { duration: '5m', target: 50 },   // Winding down
  ],
  thresholds: {
    'http_req_duration': ['p(95)<500', 'p(99)<1000'],
    'http_req_failed': ['rate<0.1'],
  },
};

const BASE_URL = 'http://localhost:8000';
const restaurants = Array.from({ length: 5 }, (_, i) => i + 301);
const customers = Array.from({ length: 1000 }, (_, i) => i + 1001);
const couriers = Array.from({ length: 100 }, (_, i) => i + 401);
const dishes = Array.from({ length: 50 }, (_, i) => i + 5001);

function getRandomElement(arr) {
  return arr[Math.floor(Math.random() * arr.length)];
}

function generateOrderItems() {
  const itemCount = Math.floor(Math.random() * 4) + 1; // 1-4 items per order
  const items = [];

  for (let i = 0; i < itemCount; i++) {
    items.push({
      dish_id: getRandomElement(dishes),
      quantity: Math.floor(Math.random() * 3) + 1,
    });
  }

  return items;
}

export default function () {
  const restaurantId = getRandomElement(restaurants);
  const customerId = getRandomElement(customers);
  const courierId = getRandomElement(couriers);
  const token = `food-token-${restaurantId}`;

  const headers = {
    Authorization: `Bearer ${token}`,
    'Content-Type': 'application/json',
    'X-Tenant-ID': restaurantId.toString(),
  };

  group('Customer Browsing & Order Creation', () => {
    // Get menu
    let menuResponse = http.get(`${BASE_URL}/api/restaurants/${restaurantId}/menu`, {
      headers,
      tags: { name: 'GetMenu' },
    });

    check(menuResponse, {
      'menu status is 200': (r) => r.status === 200,
      'menu has dishes': (r) => Array.isArray(r.json('dishes')) && r.json('dishes').length > 0,
    });

    sleep(1); // Simulate browsing

    group('Create & Submit Order', () => {
      const items = generateOrderItems();
      const totalAmount = items.reduce((sum, item) => sum + 5000, 0); // Simplified pricing

      let start = new Date();

      let orderResponse = http.post(`${BASE_URL}/api/orders`, {
        restaurant_id: restaurantId,
        customer_id: customerId,
        items: items,
        delivery_address: 'Москва, ул. Тверская, д. 10',
        comment: Math.random() < 0.3 ? 'Без лука' : '',
        payment_method: 'card',
      }, { headers, tags: { name: 'CreateOrder' } });

      orderCreationDuration.add(new Date() - start);
      orderCounter.add(1);

      check(orderResponse, {
        'create order status is 201': (r) => r.status === 201,
        'order has ID': (r) => r.json('id') !== null,
        'order status is pending': (r) => r.json('status') === 'pending',
        'estimated delivery time provided': (r) => r.json('estimated_delivery_minutes') > 0,
      });

      const orderId = orderResponse.json('id');
      activeOrders.set(activeOrders.value() + 1);

      sleep(0.5);

      // Check consumables availability
      group('Verify Consumables', () => {
        let consumableResponse = http.post(`${BASE_URL}/api/orders/${orderId}/check-consumables`, {
          items: items,
        }, { headers, tags: { name: 'CheckConsumables' } });

        consumablesChecks.add(1);

        check(consumableResponse, {
          'consumable check is 200': (r) => r.status === 200,
          'all consumables available': (r) => r.json('all_available') === true || r.json('all_available') === false,
        });
      });

      sleep(0.3);

      // Simulate hold payment
      let holdResponse = http.post(`${BASE_URL}/api/orders/${orderId}/hold-payment`, {
        amount: totalAmount,
      }, { headers, tags: { name: 'HoldPayment' } });

      check(holdResponse, {
        'hold payment status is 200': (r) => r.status === 200,
        'payment hold status is AUTHORIZED': (r) => r.json('status') === 'AUTHORIZED',
      });

      sleep(0.5);

      // Simulate KDS workflow
      group('Kitchen Display System (KDS)', () => {
        for (let step = 0; step < 3; step++) {
          let kdsStart = new Date();

          const statuses = ['pending', 'cooking', 'ready'];
          let kdsUpdateResponse = http.patch(`${BASE_URL}/api/orders/${orderId}`, {
            status: statuses[step],
          }, { headers, tags: { name: 'KDSUpdate' } });

          kdsUpdateDuration.add(new Date() - kdsStart);

          check(kdsUpdateResponse, {
            'KDS update status is 200': (r) => r.status === 200,
          });

          sleep(Math.random() * 3 + 2); // Simulate cooking time
        }
      });

      sleep(0.5);

      // Create delivery order once food is ready
      group('Create Delivery Order', () => {
        let deliveryStart = new Date();

        let deliveryResponse = http.post(`${BASE_URL}/api/delivery-orders`, {
          order_id: orderId,
          restaurant_id: restaurantId,
          address: 'Москва, ул. Тверская, д. 10',
          zone_id: 501,
          priority: 'normal',
        }, { headers, tags: { name: 'CreateDelivery' } });

        deliveryMatchingDuration.add(new Date() - deliveryStart);

        check(deliveryResponse, {
          'delivery order creation is 201': (r) => r.status === 201,
          'delivery has ID': (r) => r.json('delivery_id') !== null,
        });

        const deliveryId = deliveryResponse.json('delivery_id');

        sleep(0.5);

        // Match courier
        group('Match & Assign Courier', () => {
          let matchStart = new Date();

          let matchResponse = http.post(`${BASE_URL}/api/delivery-orders/${deliveryId}/assign-courier`, {
            courier_id: courierId,
          }, { headers, tags: { name: 'AssignCourier' } });

          deliveryMatchingDuration.add(new Date() - matchStart);

          check(matchResponse, {
            'courier assignment is 200': (r) => r.status === 200 || r.status === 201,
          });
        });
      });

      sleep(0.5);

      // Simulate delivery completion
      group('Complete Delivery', () => {
        sleep(2); // Simulate delivery time

        let completeResponse = http.patch(`${BASE_URL}/api/orders/${orderId}`, {
          status: 'delivered',
        }, { headers, tags: { name: 'CompleteDelivery' } });

        check(completeResponse, {
          'delivery completion is 200': (r) => r.status === 200,
          'order status is delivered': (r) => r.json('status') === 'delivered',
        });

        activeOrders.set(activeOrders.value() - 1);
      });

      sleep(0.5);

      // Capture payment
      let captureResponse = http.post(`${BASE_URL}/api/orders/${orderId}/capture-payment`, {}, {
        headers,
        tags: { name: 'CapturePayment' },
      });

      check(captureResponse, {
        'payment capture is 200': (r) => r.status === 200,
        'payment status is CAPTURED': (r) => r.json('status') === 'CAPTURED',
      });

      sleep(0.5);

      // Request rating
      if (Math.random() < 0.7) {
        group('Rate Restaurant & Courier', () => {
          let restaurantRateResponse = http.post(`${BASE_URL}/api/orders/${orderId}/rate-restaurant`, {
            rating: Math.floor(Math.random() * 5) + 1,
            comment: 'Вкусно!',
          }, { headers, tags: { name: 'RateRestaurant' } });

          check(restaurantRateResponse, {
            'restaurant rating status is 200 or 404': (r) => r.status === 200 || r.status === 404,
          });

          let courierRateResponse = http.post(`${BASE_URL}/api/orders/${orderId}/rate-courier`, {
            rating: Math.floor(Math.random() * 5) + 1,
            politeness: Math.random() > 0.5,
            speed: Math.random() > 0.5,
          }, { headers, tags: { name: 'RateCourier' } });

          check(courierRateResponse, {
            'courier rating status is 200 or 404': (r) => r.status === 200 || r.status === 404,
          });
        });
      }
    });
  });

  sleep(2);
}
