/// <reference types="cypress" />

describe('Food & Delivery Vertical - Restaurants', () => {
  const tenantId = 3;
  const restaurantId = 301;
  const orderId = 7001;
  const courierId = 401;
  const menuId = 401;
  const dishId = 5001;

  const foodTestData = {
    restaurant: {
      name: 'Ресторан Ташир',
      address: 'Москва, Арбат ул., 15',
      phone: '+7-495-123-4567',
      cuisineType: ['кавказская', 'европейская'],
      operatingHours: {
        monday: { open: '11:00', close: '23:00' },
        sunday: { open: '12:00', close: '23:00' },
      },
    },
    menu: {
      name: 'Основное меню',
      dishes: [
        {
          name: 'Люля-кебаб',
          price: 45000, // копейки
          calories: 450,
          allergens: ['глютен'],
          cookingTime: 20,
          consumables: [
            { name: 'Фарш', quantity: 0.2 },
            { name: 'Салат', quantity: 0.1 },
          ],
        },
        {
          name: 'Плов с бараниной',
          price: 52000,
          calories: 580,
          allergens: [],
          cookingTime: 25,
          consumables: [
            { name: 'Рис', quantity: 0.3 },
            { name: 'Баранина', quantity: 0.25 },
          ],
        },
      ],
    },
    order: {
      items: [
        { dish_id: dishId, quantity: 2 },
        { dish_id: 5002, quantity: 1 },
      ],
      comment: 'Без лука, побольше специй',
      deliveryAddress: 'Москва, Тверская ул., 20',
      paymentMethod: 'card',
    },
  };

  before(() => {
    cy.login('restaurant@example.com', 'password123');
  });

  describe('Restaurant Setup & Menu Management', () => {
    it('Should create restaurant with full details', () => {
      cy.visit('/tenant/restaurants/create');
      
      cy.get('input[name="name"]').type(foodTestData.restaurant.name);
      cy.get('input[name="address"]').type(foodTestData.restaurant.address);
      cy.get('input[name="phone"]').type(foodTestData.restaurant.phone);
      
      foodTestData.restaurant.cuisineType.forEach(cuisine => {
        cy.get('label').contains(cuisine).click();
      });
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Ресторан создан');
    });

    it('Should create menu with dishes', () => {
      cy.visit(`/tenant/restaurants/${restaurantId}/menus/create`);
      
      cy.get('input[name="name"]').type(foodTestData.menu.name);
      
      foodTestData.menu.dishes.forEach((dish, index) => {
        if (index > 0) cy.get('button').contains('Добавить блюдо').click();
        
        cy.get(`input[name="dishes[${index}].name"]`).type(dish.name);
        cy.get(`input[name="dishes[${index}].price"]`).clear().type((dish.price / 100).toString());
        cy.get(`input[name="dishes[${index}].calories"]`).clear().type(dish.calories.toString());
        cy.get(`input[name="dishes[${index}].cooking_time"]`).clear().type(dish.cookingTime.toString());
      });
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Меню создано');
    });

    it('Should add consumables to dishes', () => {
      cy.visit(`/tenant/restaurants/${restaurantId}/menus/${menuId}/edit`);
      
      cy.get(`button[data-dish-id="${dishId}"]`).contains('Добавить расходник').click();
      cy.get('input[name="consumable_name"]').type('Салат');
      cy.get('input[name="consumable_quantity"]').type('0.1');
      cy.get('button').contains('Подтвердить').click();
      
      cy.get('.filament-notification').should('contain', 'Расходник добавлен');
    });

    it('Should update restaurant schedule', () => {
      cy.visit(`/tenant/restaurants/${restaurantId}/settings/schedule`);
      
      cy.get('input[name="monday_open"]').clear().type('11:00');
      cy.get('input[name="monday_close"]').clear().type('23:00');
      cy.get('input[name="sunday_open"]').clear().type('12:00');
      cy.get('input[name="sunday_close"]').clear().type('23:00');
      
      cy.get('button').contains('Сохранить').click();
      cy.get('.filament-notification').should('contain', 'Расписание обновлено');
    });

    it('Should verify menu in database with correct structure', () => {
      cy.request(`/api/restaurants/${restaurantId}/menu`).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.dishes).to.be.an('array');
        expect(response.body.dishes.length).to.equal(foodTestData.menu.dishes.length);
        
        response.body.dishes.forEach(dish => {
          expect(dish).to.have.all.keys('id', 'name', 'price', 'calories', 'cooking_time', 'consumables');
        });
      });
    });
  });

  describe('Order Management & KDS Integration', () => {
    it('Should accept order from customer', () => {
      cy.logout();
      cy.login('customer@example.com', 'password123');
      cy.visit(`/app/restaurants/${restaurantId}`);
      
      cy.get(`[data-dish-id="${dishId}"]`).click();
      cy.get('input[name="quantity"]').clear().type('2');
      cy.get('button').contains('В корзину').click();
      
      cy.get('[data-testid="cart-items"]').should('contain', '2');
      
      cy.get('button').contains('Оформить заказ').click();
      cy.get('textarea[name="comment"]').type(foodTestData.order.comment);
      cy.get('button').contains('Подтвердить').click();
      
      cy.get('.filament-notification').should('contain', 'Заказ принят');
    });

    it('Should send order to KDS immediately', () => {
      cy.request({
        method: 'POST',
        url: '/api/orders',
        body: {
          restaurant_id: restaurantId,
          items: foodTestData.order.items,
          comment: foodTestData.order.comment,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        const orderId = response.body.id;

        // Verify KDS received order
        cy.request(`/api/kds-orders/${restaurantId}`).then(kdsResponse => {
          expect(kdsResponse.status).to.equal(200);
          const kdsOrder = kdsResponse.body.orders.find(o => o.order_id === orderId);
          expect(kdsOrder).to.exist;
          expect(kdsOrder.status).to.equal('pending');
        });
      });
    });

    it('Should track order through KDS workflow', () => {
      cy.login('restaurant@example.com', 'password123');
      cy.visit('/tenant/kds');
      
      cy.get(`[data-order-id="${orderId}"]`).should('be.visible');
      cy.get(`[data-order-id="${orderId}"]`).within(() => {
        cy.get('button').contains('Готовится').click();
      });
      
      cy.request({
        method: 'PATCH',
        url: `/api/orders/${orderId}`,
        body: { status: 'cooking' },
      }).then(response => {
        expect(response.status).to.equal(200);
      });
    });

    it('Should notify kitchen about allergens', () => {
      cy.visit('/tenant/kds');
      
      cy.get(`[data-order-id="${orderId}"]`).within(() => {
        cy.get('[data-testid="allergen-alert"]').should('contain', 'глютен');
      });
    });

    it('Should mark order as ready', () => {
      cy.request({
        method: 'PATCH',
        url: `/api/orders/${orderId}`,
        body: { status: 'ready' },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.ready_at).to.exist;
      });
    });

    it('Should auto-close order if not picked up within 30 min', () => {
      // Create ready order 30+ minutes ago
      const oldReadyTime = new Date(Date.now() - 31 * 60 * 1000).toISOString();
      
      cy.request({
        method: 'POST',
        url: '/api/orders',
        body: {
          restaurant_id: restaurantId,
          items: foodTestData.order.items,
          ready_at: oldReadyTime,
          status: 'ready',
        },
      }).then(response => {
        const testOrderId = response.body.id;

        // Trigger cleanup job
        cy.request({
          method: 'POST',
          url: '/api/orders/cleanup-expired',
        }).then(cleanupResponse => {
          expect(cleanupResponse.status).to.equal(200);

          // Verify order was closed
          cy.request(`/api/orders/${testOrderId}`).then(orderResponse => {
            expect(['closed', 'cancelled']).to.include(orderResponse.body.status);
          });
        });
      });
    });
  });

  describe('Consumables Management', () => {
    it('Should deduct consumables from inventory on order preparation', () => {
      cy.request({
        method: 'POST',
        url: `/api/orders/${orderId}/deduct-consumables`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.consumables_deducted).to.be.an('array');
        expect(response.body.consumables_deducted.length).to.be.greaterThan(0);
      });
    });

    it('Should alert on low stock of critical ingredients', () => {
      cy.request({
        method: 'GET',
        url: `/api/restaurants/${restaurantId}/inventory/low-stock`,
      }).then(response => {
        expect(response.status).to.equal(200);
        
        if (response.body.low_items.length > 0) {
          cy.visit('/tenant/restaurants/inventory');
          cy.get('.filament-notification').should('contain', 'низкий уровень');
        }
      });
    });

    it('Should predict ingredient needs based on demand forecast', () => {
      cy.request({
        method: 'GET',
        url: `/api/restaurants/${restaurantId}/forecast/ingredient-needs`,
        queryParams: { days_ahead: 7 },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('forecasted_items');
        expect(response.body.forecasted_items).to.be.an('array');
      });
    });
  });

  describe('Delivery Management', () => {
    it('Should create delivery order after food is ready', () => {
      cy.request({
        method: 'PATCH',
        url: `/api/orders/${orderId}`,
        body: { status: 'ready_for_delivery' },
      }).then(response => {
        expect(response.status).to.equal(200);

        // Create delivery
        cy.request({
          method: 'POST',
          url: '/api/delivery-orders',
          body: {
            order_id: orderId,
            address: foodTestData.order.deliveryAddress,
            zone_id: 501,
          },
        }).then(deliveryResponse => {
          expect(deliveryResponse.status).to.equal(201);
          expect(deliveryResponse.body).to.have.property('delivery_id');
        });
      });
    });

    it('Should match courier based on location', () => {
      cy.request({
        method: 'POST',
        url: `/api/delivery-orders/find-courier`,
        body: {
          restaurant_id: restaurantId,
          delivery_zone: 501,
          priority: 'normal',
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('courier_id');
      });
    });

    it('Should apply zone-based surge pricing', () => {
      cy.request({
        method: 'GET',
        url: `/api/delivery-zones/501/surge`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('surge_multiplier');
        expect(response.body.surge_multiplier).to.be.greaterThanOrEqual(1.0);
      });
    });

    it('Should track delivery in real-time', () => {
      const deliveryId = 8001;
      
      cy.request({
        method: 'PATCH',
        url: `/api/delivery-orders/${deliveryId}/location`,
        body: {
          latitude: 55.7558,
          longitude: 37.6173,
          timestamp: new Date().toISOString(),
        },
      }).then(response => {
        expect(response.status).to.equal(200);
      });
    });
  });

  describe('Payment Processing', () => {
    it('Should hold payment for order', () => {
      const totalAmount = foodTestData.order.items.reduce((sum, item) => sum + 50000, 0);
      
      cy.request({
        method: 'POST',
        url: `/api/orders/${orderId}/hold-payment`,
        body: { amount: totalAmount },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('AUTHORIZED');
      });
    });

    it('Should capture payment after delivery confirmation', () => {
      cy.request({
        method: 'POST',
        url: `/api/orders/${orderId}/capture-payment`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('CAPTURED');
      });
    });

    it('Should issue fiscal receipt (54-ФЗ)', () => {
      cy.request({
        method: 'GET',
        url: `/api/orders/${orderId}/receipt`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('receipt_id');
        expect(response.body).to.have.property('fiscal_number');
      });
    });

    it('Should refund if delivery takes > 45 min', () => {
      // Create order with old timestamp
      const oldOrderTime = new Date(Date.now() - 46 * 60 * 1000).toISOString();
      
      cy.request({
        method: 'POST',
        url: '/api/orders',
        body: {
          restaurant_id: restaurantId,
          items: foodTestData.order.items,
          created_at: oldOrderTime,
          status: 'delivered',
        },
      }).then(response => {
        const testOrderId = response.body.id;

        // Trigger refund job
        cy.request({
          method: 'POST',
          url: '/api/orders/check-late-deliveries',
        }).then(checkResponse => {
          expect(checkResponse.status).to.equal(200);

          // Verify refund was issued
          cy.request(`/api/orders/${testOrderId}/payment`).then(paymentResponse => {
            if (paymentResponse.body.delivery_time_minutes > 45) {
              expect(paymentResponse.body.status).to.equal('REFUNDED');
            }
          });
        });
      });
    });
  });

  describe('Rating & Feedback', () => {
    it('Should collect customer rating for restaurant', () => {
      cy.logout();
      cy.login('customer@example.com', 'password123');
      cy.visit(`/app/orders/${orderId}/rate-restaurant`);
      
      cy.get('[data-testid="rating-stars"]').find('button').eq(4).click(); // 5 stars
      cy.get('textarea[name="comment"]').type('Вкусная еда, быстрая доставка!');
      cy.get('button').contains('Отправить').click();
      
      cy.get('.filament-notification').should('contain', 'Спасибо за оценку');
    });

    it('Should collect rating for courier', () => {
      cy.visit(`/app/orders/${orderId}/rate-courier`);
      
      cy.get('[data-testid="rating-stars"]').find('button').eq(4).click(); // 5 stars
      cy.get('input[name="politeness"]').check();
      cy.get('input[name="speed"]').check();
      cy.get('button').contains('Оценить').click();
    });

    it('Should update restaurant rating aggregate', () => {
      cy.request({
        method: 'GET',
        url: `/api/restaurants/${restaurantId}`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('rating');
        expect(response.body).to.have.property('review_count');
      });
    });
  });

  describe('Online Aggregator Integration', () => {
    it('Should sync menu to aggregators (Yandex Eats, Delivery Club)', () => {
      cy.request({
        method: 'POST',
        url: `/api/restaurants/${restaurantId}/sync-to-aggregators`,
        body: {
          platforms: ['yandex_eats', 'delivery_club'],
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.synced_platforms).to.include('yandex_eats');
      });
    });

    it('Should receive orders from aggregators', () => {
      // Simulate webhook from Yandex Eats
      cy.request({
        method: 'POST',
        url: '/api/webhooks/yandex-eats-order',
        body: {
          order_id: 'yandex_' + Date.now(),
          restaurant_id: restaurantId,
          items: foodTestData.order.items,
          customer_address: foodTestData.order.deliveryAddress,
          signature: 'SHA256_SIGNATURE',
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.order_created).to.be.true;
      });
    });
  });

  describe('Table Booking (Optional)', () => {
    it('Should reserve table with QR menu', () => {
      cy.logout();
      cy.login('customer@example.com', 'password123');
      cy.visit(`/app/restaurants/${restaurantId}/book-table`);
      
      cy.get('input[name="date"]').type('2026-03-20');
      cy.get('input[name="time"]').type('19:00');
      cy.get('input[name="guests"]').clear().type('4');
      
      cy.get('button').contains('Зарезервировать').click();
      cy.get('.filament-notification').should('contain', 'Стол зарезервирован');
    });

    it('Should display QR menu for seated customer', () => {
      cy.visit(`/app/table-menu?table_id=T_001&restaurant_id=${restaurantId}`);
      
      cy.get('[data-testid="menu-section"]').should('be.visible');
      cy.get('[data-testid="dish-card"]').should('have.length.greaterThan', 0);
    });
  });
});
