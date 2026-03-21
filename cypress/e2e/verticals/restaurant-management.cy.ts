/// <reference types="cypress" />

describe.skip('Restaurant Management & KDS System', () => {
  const tenantId = 3;
  const restaurantId = 301;
  const menuId = 401;
  const kdsId = 8001;

  const restaurantTestData = {
    restaurant: {
      name: 'Ресторан Три Слона',
      address: 'Москва, Красная площадь, 3',
      phone: '+7-495-111-2222',
      seatingCapacity: 120,
      cuisineType: ['европейская', 'русская'],
    },
    menu: {
      name: 'Основное меню',
      categories: [
        {
          name: 'Закуски',
          dishes: [
            { name: 'Салат Цезарь', price: 35000, calories: 320 },
            { name: 'Брускетта', price: 28000, calories: 280 },
          ],
        },
        {
          name: 'Основные блюда',
          dishes: [
            { name: 'Стейк Рибай', price: 95000, calories: 850 },
            { name: 'Паста Болоньезе', price: 52000, calories: 680 },
          ],
        },
      ],
    },
  };

  before(() => {
    cy.login('restaurant@example.com', 'password123');
  });

  describe('Restaurant Setup & Configuration', () => {
    it('Should complete restaurant profile with legal info', () => {
      cy.visit('/tenant/restaurants/profile');
      
      cy.get('input[name="name"]').type(restaurantTestData.restaurant.name);
      cy.get('input[name="address"]').type(restaurantTestData.restaurant.address);
      cy.get('input[name="phone"]').type(restaurantTestData.restaurant.phone);
      cy.get('input[name="seating_capacity"]').clear().type(restaurantTestData.restaurant.seatingCapacity.toString());
      
      restaurantTestData.restaurant.cuisineType.forEach(cuisine => {
        cy.get(`label[for="cuisine_${cuisine}"]`).click();
      });
      
      // Legal info
      cy.get('input[name="legal_name"]').type('ООО Три Слона');
      cy.get('input[name="inn"]').type('7701234567');
      cy.get('input[name="kpp"]').type('770101001');
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Профиль обновлен');
    });

    it('Should configure restaurant hours by day', () => {
      cy.visit(`/tenant/restaurants/${restaurantId}/hours`);
      
      const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
      days.forEach((day, idx) => {
        const isWeekend = idx >= 5;
        const openTime = isWeekend ? '12:00' : '11:00';
        const closeTime = isWeekend ? '23:30' : '23:00';
        
        cy.get(`input[name="${day}_open"]`).type(openTime);
        cy.get(`input[name="${day}_close"]`).type(closeTime);
      });
      
      cy.get('button').contains('Сохранить').click();
    });

    it('Should verify restaurant is active and visible', () => {
      cy.request(`/api/restaurants/${restaurantId}`).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('active');
        expect(response.body.name).to.equal(restaurantTestData.restaurant.name);
      });
    });
  });

  describe('Menu Management & Categories', () => {
    it('Should create multi-category menu', () => {
      cy.visit(`/tenant/restaurants/${restaurantId}/menus/create`);
      
      cy.get('input[name="name"]').type(restaurantTestData.menu.name);
      cy.get('input[name="is_active"]').check();
      
      restaurantTestData.menu.categories.forEach((category, catIdx) => {
        if (catIdx > 0) cy.get('button').contains('Добавить категорию').click();
        
        cy.get(`input[name="categories[${catIdx}].name"]`).type(category.name);
        
        category.dishes.forEach((dish, dishIdx) => {
          if (dishIdx > 0) cy.get(`button[data-category="${catIdx}"]`).contains('Блюдо').click();
          
          cy.get(`input[name="categories[${catIdx}].dishes[${dishIdx}].name"]`).type(dish.name);
          cy.get(`input[name="categories[${catIdx}].dishes[${dishIdx}].price"]`).type((dish.price / 100).toString());
          cy.get(`input[name="categories[${catIdx}].dishes[${dishIdx}].calories"]`).type(dish.calories.toString());
        });
      });
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Меню создано');
    });

    it('Should support menu versioning', () => {
      cy.request({
        method: 'POST',
        url: `/api/restaurants/${restaurantId}/menus/duplicate`,
        body: { from_menu_id: menuId, version_name: 'Летнее меню' },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('id');
        expect(response.body.version_name).to.equal('Летнее меню');
      });
    });

    it('Should allow quick menu updates (availability)', () => {
      cy.visit(`/tenant/restaurants/${restaurantId}/menu-status`);
      
      // Mark some dishes as unavailable
      cy.get('[data-dish-id="5001"]').within(() => {
        cy.get('input[name="is_available"]').uncheck();
      });
      
      cy.get('button').contains('Сохранить').click();
      cy.get('.filament-notification').should('contain', 'Обновлено');
    });

    it('Should verify menu structure in database', () => {
      cy.request(`/api/restaurants/${restaurantId}/menu`).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.categories).to.be.an('array');
        expect(response.body.categories.length).to.equal(restaurantTestData.menu.categories.length);
      });
    });
  });

  describe('Kitchen Display System (KDS)', () => {
    it('Should display KDS main screen', () => {
      cy.visit(`/tenant/restaurants/${restaurantId}/kds`);
      
      cy.get('[data-testid="kds-screen"]').should('be.visible');
      cy.get('[data-testid="order-queue"]').should('be.visible');
      cy.get('[data-testid="ready-orders"]').should('be.visible');
    });

    it('Should show orders grouped by type', () => {
      cy.visit(`/tenant/restaurants/${restaurantId}/kds`);
      
      // Orders should be grouped by station (drinks, hot, cold, etc.)
      cy.get('[data-testid="station-selector"]').should('exist');
      cy.get('[data-station-name]').should('have.length.greaterThan', 0);
    });

    it('Should handle order status transitions', () => {
      const orderId = 7001;
      
      // Order arrives at KDS
      cy.request({
        method: 'PATCH',
        url: `/api/orders/${orderId}/kds-status`,
        body: { status: 'received' },
      }).then(response => {
        expect(response.status).to.equal(200);
      });

      // Order in progress
      cy.request({
        method: 'PATCH',
        url: `/api/orders/${orderId}/kds-status`,
        body: { status: 'in_progress' },
      }).then(response => {
        expect(response.status).to.equal(200);
      });

      // Order ready
      cy.request({
        method: 'PATCH',
        url: `/api/orders/${orderId}/kds-status`,
        body: { status: 'ready', ready_at: new Date().toISOString() },
      }).then(response => {
        expect(response.status).to.equal(200);
      });
    });

    it('Should highlight priority/rush orders', () => {
      cy.request({
        method: 'GET',
        url: `/api/restaurants/${restaurantId}/kds/orders`,
      }).then(response => {
        expect(response.status).to.equal(200);
        const orders = response.body.orders;
        
        // Check if priority field exists
        orders.forEach(order => {
          expect(order).to.have.property('is_priority');
          expect(order).to.have.property('wait_time_minutes');
        });
      });
    });

    it('Should display preparation time estimates', () => {
      cy.visit(`/tenant/restaurants/${restaurantId}/kds`);
      
      cy.get('[data-order-id]').first().within(() => {
        cy.get('[data-testid="prep-time"]').should('be.visible');
        cy.get('[data-testid="elapsed-time"]').should('be.visible');
      });
    });

    it('Should allow staff to request help/urgent items', () => {
      const orderId = 7001;
      
      cy.request({
        method: 'POST',
        url: `/api/orders/${orderId}/request-assistance`,
        body: { reason: 'Missing ingredient', notes: 'Need extra basil' },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('assistance_id');
      });
    });
  });

  describe('Table Management (Dine-In)', () => {
    it('Should manage table reservations', () => {
      cy.visit(`/tenant/restaurants/${restaurantId}/tables`);
      
      cy.get('button').contains('Создать столик').click();
      cy.get('input[name="table_number"]').type('1');
      cy.get('input[name="capacity"]').type('4');
      cy.get('select[name="location"]').select('window');
      
      cy.get('button').contains('Добавить').click();
    });

    it('Should track table status in real-time', () => {
      cy.request({
        method: 'GET',
        url: `/api/restaurants/${restaurantId}/tables/status`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.tables).to.be.an('array');
        
        response.body.tables.forEach(table => {
          expect(['empty', 'occupied', 'reserved', 'cleaning']).to.include(table.status);
        });
      });
    });

    it('Should manage table orders separately', () => {
      const tableId = 'T_001';
      
      cy.request({
        method: 'POST',
        url: `/api/restaurants/${restaurantId}/tables/${tableId}/order`,
        body: {
          items: [
            { dish_id: 5001, quantity: 2 },
            { dish_id: 5002, quantity: 1 },
          ],
          waiter_id: 2501,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('order_id');
        expect(response.body).to.have.property('table_id');
      });
    });
  });

  describe('Staff Management', () => {
    it('Should manage kitchen and service staff', () => {
      cy.visit(`/tenant/restaurants/${restaurantId}/staff`);
      
      cy.get('button').contains('Добавить сотрудника').click();
      cy.get('input[name="name"]').type('Иван Повар');
      cy.get('input[name="phone"]').type('+7-900-111-2222');
      cy.get('select[name="role"]').select('cook');
      cy.get('select[name="station"]').select('hot');
      
      cy.get('button').contains('Добавить').click();
    });

    it('Should track staff schedules', () => {
      cy.request({
        method: 'GET',
        url: `/api/restaurants/${restaurantId}/staff/schedule`,
        queryParams: { date: '2026-03-20' },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.staff).to.be.an('array');
        
        response.body.staff.forEach(staff => {
          expect(staff).to.have.property('shift_start');
          expect(staff).to.have.property('shift_end');
        });
      });
    });
  });

  describe('Restaurant Analytics', () => {
    it('Should calculate average order preparation time', () => {
      cy.request({
        method: 'GET',
        url: `/api/restaurants/${restaurantId}/analytics/prep-time`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('average_prep_time');
        expect(response.body).to.have.property('by_dish_type');
      });
    });

    it('Should track order completion rate and speed', () => {
      cy.request({
        method: 'GET',
        url: `/api/restaurants/${restaurantId}/analytics/order-metrics`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('total_orders');
        expect(response.body).to.have.property('completed_on_time_percent');
        expect(response.body).to.have.property('average_wait_minutes');
      });
    });

    it('Should show peak hours and demand patterns', () => {
      cy.request({
        method: 'GET',
        url: `/api/restaurants/${restaurantId}/analytics/demand-pattern`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('peak_hours');
        expect(response.body).to.have.property('hourly_distribution');
      });
    });
  });

  describe('Menu Performance', () => {
    it('Should track dish popularity', () => {
      cy.request({
        method: 'GET',
        url: `/api/restaurants/${restaurantId}/analytics/popular-dishes`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.dishes).to.be.an('array');
        
        response.body.dishes.forEach(dish => {
          expect(dish).to.have.property('order_count');
          expect(dish).to.have.property('revenue');
          expect(dish).to.have.property('profit_margin');
        });
      });
    });

    it('Should identify underperforming dishes', () => {
      cy.request({
        method: 'GET',
        url: `/api/restaurants/${restaurantId}/analytics/underperforming-dishes`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('low_popularity_items');
        expect(response.body).to.have.property('high_return_rate_items');
      });
    });
  });
});
