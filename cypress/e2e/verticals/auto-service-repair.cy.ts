/// <reference types="cypress" />

describe('Auto Service & Repair Shop Management', () => {
  const tenantId = 2;
  const serviceOrderId = 6001;
  const workerId = 251;

  const autoServiceTestData = {
    shop: {
      name: 'АвтоСервис Premium',
      address: 'Москва, Ленинградский пр., 45',
      phone: '+7-495-777-8888',
      specialization: ['техническое обслуживание', 'ремонт двигателя', 'кузовные работы'],
    },
    spareParts: [
      { sku: 'OIL-SHELL-5W30', name: 'Масло Shell 5W30', price: 150000, stock: 50 },
      { sku: 'FILTER-OIL-BOSCH', name: 'Фильтр масла Bosch', price: 25000, stock: 100 },
      { sku: 'BRAKE-PADS-ATE', name: 'Колодки тормоза ATE', price: 180000, stock: 30 },
    ],
    serviceOrder: {
      vehicleInfo: 'Toyota Camry, 2023, чёрный',
      issues: ['Стук в двигателе', 'Требуется техническое обслуживание'],
      estimatedTime: 360, // минут
      estimatedCost: 50000, // копейки
    },
  };

  before(() => {
    cy.login('mechanic@example.com', 'password123');
    cy.visitTenant('/tenant');
  });

  describe('Service Shop Setup', () => {
    it('Should create auto service shop with specializations', () => {
      cy.visit('/tenant/auto-services/create');
      
      cy.get('input[name="name"]').type(autoServiceTestData.shop.name);
      cy.get('input[name="address"]').type(autoServiceTestData.shop.address);
      cy.get('input[name="phone"]').type(autoServiceTestData.shop.phone);
      
      autoServiceTestData.shop.specialization.forEach(spec => {
        cy.get('label').contains(spec).click();
      });
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Сервис создан');
    });
  });

  describe('Spare Parts Inventory', () => {
    it('Should add spare parts to inventory', () => {
      cy.visit(`/tenant/auto-services/${tenantId}/parts`);
      
      autoServiceTestData.spareParts.forEach(part => {
        cy.get('button').contains('Добавить деталь').click();
        cy.get('input[name="sku"]').type(part.sku);
        cy.get('input[name="name"]').type(part.name);
        cy.get('input[name="price"]').type((part.price / 100).toString());
        cy.get('input[name="stock"]').type(part.stock.toString());
        cy.get('button').contains('Сохранить').click();
      });
      
      cy.get('.filament-notification').should('contain', 'детали добавлены');
    });

    it('Should track parts usage and deduct from inventory', () => {
      cy.request({
        method: 'POST',
        url: `/api/service-orders/${serviceOrderId}/use-parts`,
        body: {
          parts: [
            { sku: 'OIL-SHELL-5W30', quantity: 1 },
            { sku: 'FILTER-OIL-BOSCH', quantity: 1 },
          ],
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.parts_deducted).to.be.true;
      });
    });

    it('Should alert on low parts stock', () => {
      cy.request({
        method: 'GET',
        url: `/api/auto-services/${tenantId}/parts/low-stock`,
      }).then(response => {
        expect(response.status).to.equal(200);
        if (response.body.low_parts.length > 0) {
          cy.visit('/tenant/auto-services/parts');
          cy.get('[data-testid="low-stock-alert"]').should('be.visible');
        }
      });
    });
  });

  describe('Service Order Workflow', () => {
    it('Should create service order from customer request', () => {
      cy.logout();
      cy.login('customer@example.com', 'password123');
      cy.visit('/app/auto-services');
      
      cy.get('button').contains('Заказать диагностику').click();
      cy.get('input[name="vehicle_info"]').type(autoServiceTestData.serviceOrder.vehicleInfo);
      cy.get('textarea[name="issues"]').type(autoServiceTestData.serviceOrder.issues.join(', '));
      
      cy.get('button').contains('Отправить').click();
      cy.get('.filament-notification').should('contain', 'Заказ принят');
    });

    it('Should track service order through stages', () => {
      cy.login('mechanic@example.com', 'password123');
      cy.visit('/tenant/auto-services/orders');
      
      cy.get(`[data-order-id="${serviceOrderId}"]`).within(() => {
        // Diagnostics
        cy.get('button').contains('Диагностика').click();
        cy.get('textarea[name="findings"]').type('Требуется замена масла и фильтров');
        cy.get('input[name="estimated_cost"]').clear().type('500');
      });
    });

    it('Should hold payment before work starts', () => {
      const estimatedCost = autoServiceTestData.serviceOrder.estimatedCost;
      
      cy.request({
        method: 'POST',
        url: `/api/service-orders/${serviceOrderId}/hold-payment`,
        body: { amount: estimatedCost },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('AUTHORIZED');
      });
    });

    it('Should mark work as in progress', () => {
      cy.request({
        method: 'PATCH',
        url: `/api/service-orders/${serviceOrderId}`,
        body: { status: 'in_progress' },
      }).then(response => {
        expect(response.status).to.equal(200);
      });
    });

    it('Should capture payment on work completion', () => {
      cy.request({
        method: 'PATCH',
        url: `/api/service-orders/${serviceOrderId}`,
        body: { status: 'completed', final_amount: 48000 },
      }).then(response => {
        expect(response.status).to.equal(200);

        cy.request({
          method: 'POST',
          url: `/api/service-orders/${serviceOrderId}/capture-payment`,
        }).then(captureResponse => {
          expect(captureResponse.status).to.equal(200);
          expect(captureResponse.body.status).to.equal('CAPTURED');
        });
      });
    });
  });

  describe('Technician Management', () => {
    it('Should assign technician to order', () => {
      cy.request({
        method: 'PATCH',
        url: `/api/service-orders/${serviceOrderId}`,
        body: { assigned_technician_id: workerId },
      }).then(response => {
        expect(response.status).to.equal(200);
      });
    });

    it('Should track technician workload', () => {
      cy.request({
        method: 'GET',
        url: `/api/technicians/${workerId}/workload`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('current_orders');
        expect(response.body).to.have.property('estimated_completion');
      });
    });
  });

  describe('Service Rating & Quality Control', () => {
    it('Should allow customer to rate service', () => {
      cy.logout();
      cy.login('customer@example.com', 'password123');
      cy.visit(`/app/service-orders/${serviceOrderId}/rate`);
      
      cy.get('[data-testid="quality-rating"]').find('button').eq(4).click(); // 5 stars
      cy.get('textarea[name="comment"]').type('Отличная работа, всё быстро и качественно');
      cy.get('button').contains('Отправить').click();
    });

    it('Should track service quality metrics', () => {
      cy.request({
        method: 'GET',
        url: `/api/auto-services/${tenantId}/quality-metrics`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('average_rating');
        expect(response.body).to.have.property('completion_rate');
        expect(response.body).to.have.property('on_time_percentage');
      });
    });
  });
});
