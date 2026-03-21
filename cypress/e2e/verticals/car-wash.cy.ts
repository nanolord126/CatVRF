/// <reference types="cypress" />

describe('Car Wash Booking & Service Management', () => {
  const tenantId = 2;
  const locationId = 2701;
  const washerId = 2801;
  const bookingId = 5501;

  const carWashTestData = {
    location: {
      name: 'АвтоМойка Express',
      address: 'Москва, Проспект Мира, 88',
      phone: '+7-495-888-9999',
      bays: 6,
      operatingHours: { open: '08:00', close: '22:00' },
    },
    services: [
      { name: 'Быстрая мойка', duration: 15, price: 29900, types: ['sedan', 'suv'] },
      { name: 'Стандартная мойка', duration: 25, price: 49900, types: ['sedan', 'suv', 'truck'] },
      { name: 'Премиум мойка', duration: 40, price: 79900, types: ['sedan', 'suv', 'truck'] },
      { name: 'Полировка', duration: 60, price: 149900, types: ['sedan', 'suv'] },
    ],
    booking: {
      vehicleType: 'sedan',
      serviceType: 'premium',
      dateTime: '2026-03-20T14:00:00',
      bayNumber: 3,
    },
  };

  before(() => {
    cy.login('washer@example.com', 'password123');
  });

  describe('Car Wash Location Setup', () => {
    it('Should create car wash location', () => {
      cy.visit('/tenant/car-washes/create');
      
      cy.get('input[name="name"]').type(carWashTestData.location.name);
      cy.get('input[name="address"]').type(carWashTestData.location.address);
      cy.get('input[name="phone"]').type(carWashTestData.location.phone);
      cy.get('input[name="bays"]').clear().type(carWashTestData.location.bays.toString());
      cy.get('input[name="opening_time"]').type(carWashTestData.location.operatingHours.open);
      cy.get('input[name="closing_time"]').type(carWashTestData.location.operatingHours.close);
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Мойка создана');
    });

    it('Should configure wash bays', () => {
      cy.visit(`/tenant/car-washes/${locationId}/bays`);
      
      for (let i = 1; i <= carWashTestData.location.bays; i++) {
        cy.get(`input[name="bay_${i}_type"]`).select('automated');
        cy.get(`input[name="bay_${i}_status"]`).select('operational');
      }
      
      cy.get('button').contains('Сохранить').click();
      cy.get('.filament-notification').should('contain', 'Боксы обновлены');
    });
  });

  describe('Service Menu Management', () => {
    it('Should add wash services', () => {
      cy.visit(`/tenant/car-washes/${locationId}/services`);
      
      carWashTestData.services.forEach(service => {
        cy.get('button').contains('Добавить услугу').click();
        cy.get('input[name="name"]').type(service.name);
        cy.get('input[name="duration"]').type(service.duration.toString());
        cy.get('input[name="price"]').type((service.price / 100).toString());
        
        service.types.forEach(type => {
          cy.get(`label[for="type_${type}"]`).click();
        });
        
        cy.get('button').contains('Сохранить').click();
      });
      
      cy.get('.filament-notification').should('contain', 'услуг добавлено');
    });

    it('Should verify services in database', () => {
      cy.request(`/api/car-washes/${locationId}/services`).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.services.length).to.equal(carWashTestData.services.length);
      });
    });
  });

  describe('Booking System', () => {
    it('Should display available time slots', () => {
      cy.logout();
      cy.visit(`/app/car-wash/${locationId}`);
      
      cy.get('[data-testid="date-picker"]').click();
      cy.get('[data-date="2026-03-20"]').click();
      
      cy.get('[data-testid="time-slot"]').should('have.length.greaterThan', 0);
    });

    it('Should book wash service with idempotency', () => {
      const idempotencyKey = `carwash-${Date.now()}`;
      
      cy.request({
        method: 'POST',
        url: '/api/car-wash-bookings',
        body: {
          location_id: locationId,
          service_id: 6101,
          vehicle_type: carWashTestData.booking.vehicleType,
          datetime: carWashTestData.booking.dateTime,
          customer_id: 3501,
          idempotency_key: idempotencyKey,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        const bookingId = response.body.id;

        // Verify idempotency
        cy.request({
          method: 'POST',
          url: '/api/car-wash-bookings',
          body: {
            location_id: locationId,
            service_id: 6101,
            vehicle_type: carWashTestData.booking.vehicleType,
            datetime: carWashTestData.booking.dateTime,
            customer_id: 3501,
            idempotency_key: idempotencyKey,
          },
        }).then(dupResponse => {
          expect(dupResponse.status).to.equal(200);
          expect(dupResponse.body.id).to.equal(bookingId);
        });
      });
    });

    it('Should hold payment at booking', () => {
      const servicePrice = carWashTestData.services[2].price; // Премиум мойка
      
      cy.request({
        method: 'POST',
        url: `/api/car-wash-bookings/${bookingId}/hold-payment`,
        body: { amount: servicePrice },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('AUTHORIZED');
      });
    });

    it('Should prevent double-booking of bay', () => {
      cy.request({
        method: 'POST',
        url: '/api/car-wash-bookings',
        body: {
          location_id: locationId,
          service_id: 6101,
          vehicle_type: 'suv',
          datetime: carWashTestData.booking.dateTime,
          bay_number: carWashTestData.booking.bayNumber,
          customer_id: 3502,
        },
      }).then(response => {
        expect(response.status).to.equal(409); // Conflict
        expect(response.body.message).to.include('занят');
      });
    });
  });

  describe('Wash Operations', () => {
    it('Should start wash service', () => {
      cy.login('washer@example.com', 'password123');
      cy.visit('/tenant/car-washes/operations');
      
      cy.get(`[data-booking-id="${bookingId}"]`).within(() => {
        cy.get('button').contains('Начать').click();
      });
      
      cy.get('.filament-notification').should('contain', 'Мойка началась');
    });

    it('Should track wash progress in real-time', () => {
      cy.request({
        method: 'GET',
        url: `/api/car-wash-bookings/${bookingId}/progress`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('status');
        expect(response.body).to.have.property('elapsed_time');
        expect(response.body).to.have.property('estimated_remaining');
      });
    });

    it('Should complete wash and capture payment', () => {
      cy.request({
        method: 'PATCH',
        url: `/api/car-wash-bookings/${bookingId}`,
        body: { status: 'completed', actual_duration: 42 },
      }).then(response => {
        expect(response.status).to.equal(200);

        cy.request({
          method: 'POST',
          url: `/api/car-wash-bookings/${bookingId}/capture-payment`,
        }).then(captureResponse => {
          expect(captureResponse.status).to.equal(200);
          expect(captureResponse.body.status).to.equal('CAPTURED');
        });
      });
    });

    it('Should allow refund if cancelled before start', () => {
      // Create new booking
      cy.request({
        method: 'POST',
        url: '/api/car-wash-bookings',
        body: {
          location_id: locationId,
          service_id: 6101,
          vehicle_type: 'sedan',
          datetime: new Date(Date.now() + 3600000).toISOString(), // 1 hour from now
          customer_id: 3501,
        },
      }).then(createResponse => {
        const testBookingId = createResponse.body.id;

        // Cancel immediately
        cy.request({
          method: 'PATCH',
          url: `/api/car-wash-bookings/${testBookingId}`,
          body: { status: 'cancelled_by_customer' },
        }).then(cancelResponse => {
          expect(cancelResponse.status).to.equal(200);

          // Verify refund
          cy.request(`/api/car-wash-bookings/${testBookingId}`).then(statusResponse => {
            expect(statusResponse.body.payment_status).to.equal('REFUNDED');
          });
        });
      });
    });
  });

  describe('Equipment Management', () => {
    it('Should track equipment maintenance schedules', () => {
      cy.login('manager@example.com', 'password123');
      cy.visit(`/tenant/car-washes/${locationId}/equipment`);
      
      cy.get('button').contains('Добавить оборудование').click();
      cy.get('input[name="name"]').type('Система верхней мойки');
      cy.get('input[name="maintenance_interval_days"]').type('90');
      cy.get('button').contains('Добавить').click();
    });

    it('Should alert on maintenance needed', () => {
      cy.request({
        method: 'GET',
        url: `/api/car-washes/${locationId}/maintenance-alerts`,
      }).then(response => {
        expect(response.status).to.equal(200);
        if (response.body.alerts.length > 0) {
          cy.visit(`/tenant/car-washes/${locationId}/equipment`);
          cy.get('[data-testid="maintenance-alert"]').should('be.visible');
        }
      });
    });
  });

  describe('Revenue & Analytics', () => {
    it('Should calculate daily revenue', () => {
      cy.request({
        method: 'GET',
        url: `/api/car-washes/${locationId}/analytics/daily-revenue`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('total_revenue');
        expect(response.body).to.have.property('booking_count');
        expect(response.body).to.have.property('average_rating');
      });
    });

    it('Should track bay utilization', () => {
      cy.request({
        method: 'GET',
        url: `/api/car-washes/${locationId}/analytics/bay-utilization`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('utilization_percentage');
        expect(response.body).to.have.property('busy_hours');
      });
    });
  });

  describe('Customer Ratings', () => {
    it('Should collect customer satisfaction feedback', () => {
      cy.logout();
      cy.login('customer@example.com', 'password123');
      cy.visit(`/app/car-wash-bookings/${bookingId}/rate`);
      
      cy.get('[data-testid="rating-stars"]').find('button').eq(4).click(); // 5 stars
      cy.get('input[name="cleanliness"]').select('excellent');
      cy.get('input[name="speed"]').select('excellent');
      cy.get('input[name="staff_politeness"]').select('good');
      cy.get('textarea[name="comment"]').type('Отличная мойка!');
      
      cy.get('button').contains('Отправить').click();
    });
  });
});
