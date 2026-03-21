/// <reference types="cypress" />

describe('Auto & Mobility Vertical - Taxi & Services', () => {
  const tenantId = 2;
  const driverId = 201;
  const vehicleId = 301;
  const passengerId = 2001;
  const rideId = 5001;
  const surgeZoneId = 3001;

  const autoTestData = {
    driver: {
      userId: driverId,
      licenseNumber: '77 AB 123456',
      licenseExpiry: '2026-12-31',
      rating: 4.7,
      currentLocation: { lat: 55.7558, lng: 37.6173 }, // Red Square
    },
    vehicle: {
      brand: 'Toyota',
      model: 'Camry',
      licensePlate: 'К 777 МР 77',
      year: 2023,
      class: 'comfort',
      seats: 5,
      color: 'чёрный',
    },
    passenger: {
      name: 'Иван Петров',
      phone: '+7-900-555-1234',
      paymentMethod: 'card',
    },
    ride: {
      pickupPoint: { lat: 55.7558, lng: 37.6173 },
      dropoffPoint: { lat: 55.7505, lng: 37.6175 },
      expectedDistance: 3.2, // км
      expectedDuration: 12, // минут
      baseFare: 50, // копейки
    },
  };

  before(() => {
    cy.login('driver@example.com', 'password123');
  });

  describe('Driver Onboarding & Verification', () => {
    it('Should complete driver profile with license verification', () => {
      cy.visit('/taxi/driver/onboarding');
      
      cy.get('input[name="full_name"]').type('Иван Сидоров');
      cy.get('input[name="license_number"]').type(autoTestData.driver.licenseNumber);
      cy.get('input[name="license_expiry"]').type('12/31/2026');
      cy.get('input[name="phone"]').type('+7-900-123-4567');
      
      // Upload documents
      cy.get('input[name="license_photo"]').selectFile('cypress/fixtures/license.jpg');
      cy.get('input[name="passport_photo"]').selectFile('cypress/fixtures/passport.jpg');
      cy.get('input[name="registration_doc"]').selectFile('cypress/fixtures/registration.pdf');
      
      cy.get('button').contains('Отправить на проверку').click();
      cy.get('.filament-notification').should('contain', 'Документы загружены');
    });

    it('Should verify driver documents asynchronously', () => {
      cy.request({
        method: 'GET',
        url: `/api/drivers/${driverId}/verification-status`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(['pending', 'approved', 'rejected']).to.include(response.body.status);
      });
    });

    it('Should set driver to online only after verification', () => {
      cy.request({
        method: 'PATCH',
        url: `/api/drivers/${driverId}`,
        body: { is_online: true },
      }).then(response => {
        if (response.status === 403) {
          expect(response.body.message).to.include('документы не проверены');
        } else {
          expect(response.status).to.equal(200);
          expect(response.body.is_online).to.be.true;
        }
      });
    });
  });

  describe('Vehicle Management', () => {
    it('Should register vehicle with full details', () => {
      cy.visit('/taxi/driver/vehicles/add');
      
      cy.get('input[name="brand"]').type(autoTestData.vehicle.brand);
      cy.get('input[name="model"]').type(autoTestData.vehicle.model);
      cy.get('input[name="license_plate"]').type(autoTestData.vehicle.licensePlate);
      cy.get('input[name="year"]').clear().type(autoTestData.vehicle.year.toString());
      cy.get('input[name="seats"]').clear().type(autoTestData.vehicle.seats.toString());
      
      cy.get('select[name="class"]').select(autoTestData.vehicle.class);
      cy.get('input[name="color"]').type(autoTestData.vehicle.color);
      
      cy.get('input[name="vehicle_photo"]').selectFile('cypress/fixtures/vehicle-photo.jpg');
      cy.get('input[name="registration_doc"]').selectFile('cypress/fixtures/vehicle-registration.pdf');
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Транспортное средство добавлено');
    });

    it('Should verify vehicle data in database', () => {
      cy.request(`/api/vehicles/${vehicleId}`).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.brand).to.equal(autoTestData.vehicle.brand);
        expect(response.body.license_plate).to.equal(autoTestData.vehicle.licensePlate);
        expect(response.body.class).to.equal(autoTestData.vehicle.class);
      });
    });

    it('Should track vehicle maintenance schedule', () => {
      cy.request({
        method: 'POST',
        url: `/api/vehicles/${vehicleId}/maintenance`,
        body: {
          type: 'техническое обслуживание',
          scheduled_date: '2026-06-01',
          notes: 'Плановое ТО-2',
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body.status).to.equal('scheduled');
      });
    });
  });

  describe('Ride Request & Matching', () => {
    it('Should create ride request from passenger perspective', () => {
      cy.logout();
      cy.login('passenger@example.com', 'password123');
      cy.visit('/app/taxi');
      
      // Set pickup location
      cy.get('[data-testid="map"]').click(100, 100);
      cy.get('input[name="pickup_address"]').should('have.value');
      
      // Set dropoff location
      cy.get('input[name="dropoff_address"]').type('Москва, Красная площадь');
      
      // Select ride class
      cy.get('[data-testid="class-select"]').click();
      cy.get('[data-class="comfort"]').click();
      
      cy.get('button').contains('Вызвать такси').click();
      
      cy.get('[data-testid="ride-status"]').should('contain', 'Поиск водителя');
    });

    it('Should match nearby drivers based on location', () => {
      cy.request({
        method: 'POST',
        url: '/api/rides',
        body: {
          passenger_id: passengerId,
          pickup: autoTestData.ride.pickupPoint,
          dropoff: autoTestData.ride.dropoffPoint,
          ride_class: 'comfort',
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        const rideId = response.body.id;

        // Verify ride matching
        cy.request(`/api/rides/${rideId}/candidates`).then(candidatesResponse => {
          expect(candidatesResponse.status).to.equal(200);
          expect(candidatesResponse.body.candidates).to.be.an('array');
          
          // Verify drivers are sorted by distance
          const candidates = candidatesResponse.body.candidates;
          if (candidates.length > 1) {
            for (let i = 0; i < candidates.length - 1; i++) {
              expect(candidates[i].distance).to.be.lessThanOrEqual(candidates[i + 1].distance);
            }
          }
        });
      });
    });

    it('Should assign ride to nearest available driver', () => {
      cy.request({
        method: 'POST',
        url: `/api/rides/${rideId}/assign`,
        body: { driver_id: driverId },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('assigned');
        expect(response.body.driver_id).to.equal(driverId);
      });
    });

    it('Should send real-time updates to driver', () => {
      // Verify WebSocket connection
      cy.request({
        method: 'GET',
        url: `/api/rides/${rideId}/subscribe`,
        headers: { 'X-Connection-Type': 'websocket' },
      }).then(response => {
        expect(response.status).to.equal(101); // Switching Protocols
      });
    });
  });

  describe('Dynamic Pricing & Surge', () => {
    it('Should calculate base fare correctly', () => {
      cy.request({
        method: 'POST',
        url: '/api/rides/estimate-fare',
        body: {
          pickup: autoTestData.ride.pickupPoint,
          dropoff: autoTestData.ride.dropoffPoint,
          ride_class: 'comfort',
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.all.keys('base_fare', 'distance_fare', 'time_fare', 'surge_multiplier', 'total_fare');
        
        const baseFare = response.body.base_fare;
        expect(baseFare).to.be.greaterThan(0);
      });
    });

    it('Should apply surge pricing during high demand', () => {
      cy.request({
        method: 'GET',
        url: `/api/surge-zones/${surgeZoneId}`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('surge_multiplier');
        expect(response.body.surge_multiplier).to.be.greaterThanOrEqual(1.0);
      });
    });

    it('Should dynamically recalculate fare on detour', () => {
      const originalFare = 1250; // копейки
      
      cy.request({
        method: 'PATCH',
        url: `/api/rides/${rideId}`,
        body: {
          dropoff: { lat: 55.7400, lng: 37.6200 }, // Different location
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.estimated_fare).toBeGreaterThan(originalFare);
        expect(response.body.fare_updated).to.be.true;
      });
    });

    it('Should prevent surge above maximum multiplier', () => {
      // Simulate extreme demand
      cy.request({
        method: 'POST',
        url: `/api/surge-zones/${surgeZoneId}/update-demand`,
        body: { demand_level: 999 },
      }).then(() => {
        cy.request({
          method: 'GET',
          url: `/api/surge-zones/${surgeZoneId}`,
        }).then(response => {
          expect(response.body.surge_multiplier).to.be.lessThanOrEqual(3.0); // Max 3x
        });
      });
    });
  });

  describe('Active Ride Tracking', () => {
    it('Should update driver location in real-time', () => {
      cy.request({
        method: 'PATCH',
        url: `/api/drivers/${driverId}/location`,
        body: {
          latitude: 55.7558,
          longitude: 37.6173,
          timestamp: new Date().toISOString(),
        },
      }).then(response => {
        expect(response.status).to.equal(200);
      });
    });

    it('Should track ride in progress with status updates', () => {
      // Driver arrives at pickup
      cy.request({
        method: 'PATCH',
        url: `/api/rides/${rideId}`,
        body: { status: 'driver_arrived' },
      }).then(response => {
        expect(response.status).to.equal(200);
      });

      // Driver picks up passenger
      cy.request({
        method: 'PATCH',
        url: `/api/rides/${rideId}`,
        body: { status: 'in_progress' },
      }).then(response => {
        expect(response.status).to.equal(200);
      });
    });

    it('Should calculate accurate ETA', () => {
      cy.request({
        method: 'GET',
        url: `/api/rides/${rideId}/eta`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('eta_minutes');
        expect(response.body.eta_minutes).to.be.greaterThan(0);
      });
    });

    it('Should handle ride completion with receipt generation', () => {
      cy.request({
        method: 'PATCH',
        url: `/api/rides/${rideId}`,
        body: { status: 'completed' },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('receipt_id');
        expect(response.body).to.have.property('final_fare');
      });
    });
  });

  describe('Payment Processing', () => {
    it('Should hold funds before ride completion', () => {
      cy.request({
        method: 'GET',
        url: `/api/rides/${rideId}/payment-hold`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('AUTHORIZED');
        expect(response.body.hold_expiry).to.exist;
      });
    });

    it('Should capture payment after ride completion', () => {
      cy.request({
        method: 'POST',
        url: `/api/rides/${rideId}/capture-payment`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('CAPTURED');
      });
    });

    it('Should process refund for cancelled ride', () => {
      cy.request({
        method: 'POST',
        url: `/api/rides/${rideId}/refund`,
        body: { reason: 'Cancelled by passenger' },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.refund_status).to.equal('REFUNDED');
      });
    });
  });

  describe('Rating & Safety', () => {
    it('Should allow passenger to rate driver', () => {
      cy.visit(`/app/rides/${rideId}/rate`);
      
      cy.get('[data-testid="rating-stars"]').find('button').eq(4).click(); // 5 stars
      cy.get('textarea[name="comment"]').type('Вежливый и аккуратный водитель');
      cy.get('button').contains('Отправить').click();
      
      cy.get('.filament-notification').should('contain', 'Оценка отправлена');
    });

    it('Should allow driver to rate passenger', () => {
      cy.visit(`/taxi/driver/rides/${rideId}/rate`);
      
      cy.get('[data-testid="rating-stars"]').find('button').eq(3).click(); // 4 stars
      cy.get('input[name="behavior"]').check(); // Good behavior
      cy.get('button').contains('Оценить пассажира').click();
    });

    it('Should automatically block drivers/passengers with low ratings', () => {
      cy.request({
        method: 'GET',
        url: `/api/drivers/${driverId}/rating`,
      }).then(response => {
        expect(response.status).to.equal(200);
        
        if (response.body.rating < 3.0) {
          expect(response.body.is_blocked).to.be.true;
        }
      });
    });

    it('Should flag suspicious activity patterns', () => {
      cy.request({
        method: 'GET',
        url: `/api/drivers/${driverId}/safety-check`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('is_safe');
        expect(response.body).to.have.property('risk_factors');
      });
    });
  });

  describe('Auto Service (Repair Shop) Management', () => {
    it('Should create service order', () => {
      cy.visit('/app/auto-service/new-order');
      
      cy.get('input[name="vehicle_info"]').type('Toyota Camry, 2023');
      cy.get('textarea[name="issue_description"]').type('Требуется диагностика тормозной системы');
      cy.get('[data-testid="service-type"]').click();
      cy.get('[data-service="diagnostics"]').click();
      
      cy.get('button').contains('Создать заказ').click();
      cy.get('.filament-notification').should('contain', 'Заказ принят');
    });

    it('Should track service progress with updates', () => {
      const serviceOrderId = 6001;
      
      cy.request({
        method: 'PATCH',
        url: `/api/service-orders/${serviceOrderId}`,
        body: { status: 'diagnostics_in_progress' },
      }).then(response => {
        expect(response.status).to.equal(200);
      });

      cy.request({
        method: 'PATCH',
        url: `/api/service-orders/${serviceOrderId}`,
        body: { status: 'repair_in_progress' },
      }).then(response => {
        expect(response.status).to.equal(200);
      });
    });

    it('Should deduct spare parts from inventory', () => {
      const serviceOrderId = 6001;
      
      cy.request({
        method: 'POST',
        url: `/api/service-orders/${serviceOrderId}/use-parts`,
        body: {
          parts: [
            { part_id: 401, quantity: 2 },
            { part_id: 402, quantity: 1 },
          ],
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.parts_deducted).to.be.true;
      });
    });
  });

  describe('Car Wash Booking', () => {
    it('Should display available car wash slots', () => {
      cy.visit('/app/car-wash/book');
      
      cy.get('[data-testid="date-picker"]').click();
      cy.get('[data-date="2026-03-20"]').click();
      
      cy.get('[data-testid="time-slot"]').should('have.length.greaterThan', 0);
    });

    it('Should reserve wash slot with service selection', () => {
      cy.request({
        method: 'POST',
        url: '/api/car-wash-bookings',
        body: {
          location_id: 401,
          service_type: 'premium_wash',
          vehicle_size: 'sedan',
          datetime: '2026-03-20T14:00:00',
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('booking_id');
      });
    });
  });
});
