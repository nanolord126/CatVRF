/// <reference types="cypress" />

describe('Beauty & Wellness Vertical - Full Workflow', () => {
  const tenantId = 1;
  const masterId = 101;
  const clientId = 1001;
  const serviceId = 5001;
  const appointmentId = 9001;
  
  const beautyTestData = {
    salon: {
      name: 'Салон Красоты Premium',
      address: 'Москва, Тверская ул., 12',
      phone: '+7-900-123-4567',
      specialization: ['стрижка', 'окрашивание', 'макияж'],
      schedule: {
        monday: { open: '10:00', close: '20:00' },
        tuesday: { open: '10:00', close: '20:00' },
      },
    },
    master: {
      fullName: 'Анна Петрова',
      specialization: ['стрижка', 'окрашивание'],
      experienceYears: 8,
      rating: 4.8,
    },
    service: {
      name: 'Стрижка + укладка',
      durationMinutes: 60,
      price: 2500, // копейки × 100
      consumables: [
        { name: 'Профессиональный шампунь', quantity: 1 },
        { name: 'Кондиционер', quantity: 1 },
        { name: 'Лак для волос', quantity: 0.5 },
      ],
    },
    appointment: {
      serviceId: serviceId,
      masterId: masterId,
      clientId: clientId,
      dateTime: '2026-03-20T14:00:00',
      notes: 'Короткая стрижка, осветление концов',
    },
  };

  before(() => {
    cy.login('business@example.com', 'password123');
    cy.visitTenant(`/tenant`);
  });

  describe('Salon Setup & Management', () => {
    it('Should create salon with all required fields', () => {
      cy.visit('/tenant/salons/create');
      
      cy.get('input[name="name"]').type(beautyTestData.salon.name);
      cy.get('input[name="address"]').type(beautyTestData.salon.address);
      cy.get('input[name="phone"]').type(beautyTestData.salon.phone);
      
      beautyTestData.salon.specialization.forEach(spec => {
        cy.get('label').contains(spec).click();
      });
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Салон создан успешно');
      cy.url().should('include', '/tenant/salons');
    });

    it('Should set salon schedule', () => {
      cy.visit(`/tenant/salons/${tenantId}/schedule`);
      
      cy.get('input[name="schedule.monday.open"]').clear().type('10:00');
      cy.get('input[name="schedule.monday.close"]').clear().type('20:00');
      cy.get('input[name="schedule.tuesday.open"]').clear().type('10:00');
      cy.get('input[name="schedule.tuesday.close"]').clear().type('20:00');
      
      cy.get('button').contains('Сохранить').click();
      cy.get('.filament-notification').should('contain', 'Расписание обновлено');
    });

    it('Should verify salon stored in database', () => {
      cy.request({
        method: 'GET',
        url: `/api/salons/${tenantId}`,
        headers: { Authorization: `Bearer ${window.localStorage.getItem('token')}` },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.name).to.equal(beautyTestData.salon.name);
        expect(response.body.address).to.equal(beautyTestData.salon.address);
      });
    });
  });

  describe('Master Management', () => {
    it('Should add master to salon', () => {
      cy.visit(`/tenant/salons/${tenantId}/masters/create`);
      
      cy.get('input[name="full_name"]').type(beautyTestData.master.fullName);
      cy.get('input[name="experience_years"]').clear().type(beautyTestData.master.experienceYears.toString());
      
      beautyTestData.master.specialization.forEach(spec => {
        cy.get('label').contains(spec).click();
      });
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Мастер добавлен');
    });

    it('Should update master profile', () => {
      cy.visit(`/tenant/salons/${tenantId}/masters/${masterId}/edit`);
      
      cy.get('input[name="full_name"]')
        .clear()
        .type(`${beautyTestData.master.fullName} - Обновлено`);
      
      cy.get('button').contains('Сохранить').click();
      cy.get('.filament-notification').should('contain', 'Мастер обновлён');
    });

    it('Should retrieve master with correct specialization', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.specialization).to.include('стрижка');
        expect(response.body.specialization).to.include('окрашивание');
        expect(response.body.experience_years).to.equal(beautyTestData.master.experienceYears);
      });
    });
  });

  describe('Service & Consumables Management', () => {
    it('Should create service with consumables list', () => {
      cy.visit(`/tenant/salons/${tenantId}/services/create`);
      
      cy.get('input[name="name"]').type(beautyTestData.service.name);
      cy.get('input[name="duration_minutes"]').clear().type(beautyTestData.service.durationMinutes.toString());
      cy.get('input[name="price"]').clear().type((beautyTestData.service.price / 100).toString());
      
      // Add consumables
      beautyTestData.service.consumables.forEach((consumable, index) => {
        if (index > 0) cy.get('button').contains('Добавить расходник').click();
        cy.get(`input[name="consumables[${index}].name"]`).type(consumable.name);
        cy.get(`input[name="consumables[${index}].quantity"]`).type(consumable.quantity.toString());
      });
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Услуга создана');
    });

    it('Should deduct consumables on appointment completion', () => {
      // Create appointment
      cy.request({
        method: 'POST',
        url: '/api/appointments',
        body: {
          service_id: serviceId,
          master_id: masterId,
          client_id: clientId,
          datetime: beautyTestData.appointment.dateTime,
          notes: beautyTestData.appointment.notes,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        const appointmentIdCreated = response.body.id;

        // Check inventory before completion
        cy.request('/api/inventory/check').then(invResponse => {
          const shampooQtyBefore = invResponse.body.items.find(i => i.name.includes('шампунь')).quantity;

          // Complete appointment
          cy.request({
            method: 'PATCH',
            url: `/api/appointments/${appointmentIdCreated}`,
            body: { status: 'completed' },
          });

          // Verify consumables deducted
          cy.request('/api/inventory/check').then(invAfter => {
            const shampooQtyAfter = invAfter.body.items.find(i => i.name.includes('шампунь')).quantity;
            expect(shampooQtyBefore - shampooQtyAfter).to.equal(1);
          });
        });
      });
    });

    it('Should alert on low consumable stock', () => {
      cy.request({
        method: 'POST',
        url: `/api/inventory/${serviceId}/check-low-stock`,
        body: { min_threshold: 50 },
      }).then(response => {
        expect(response.status).to.equal(200);
        if (response.body.is_low) {
          cy.get('.filament-notification').should('contain', 'Низкий уровень запасов');
        }
      });
    });
  });

  describe('Appointment Booking & Management', () => {
    it('Should display available master slots', () => {
      cy.visit(`/app/beauty/${tenantId}/book`);
      
      cy.get('[data-testid="date-picker"]').click();
      cy.get('[data-date="2026-03-20"]').click();
      
      cy.get('[data-testid="master-select"]').should('be.visible');
      cy.get('[data-testid="master-select"]').click();
      cy.get(`[data-master-id="${masterId}"]`).click();
      
      // Verify available time slots
      cy.get('[data-testid="time-slot"]').should('have.length.greaterThan', 0);
    });

    it('Should reserve slot with idempotency', () => {
      const idempotencyKey = `appointment-${Date.now()}`;
      
      // First request
      cy.request({
        method: 'POST',
        url: '/api/appointments',
        body: {
          service_id: serviceId,
          master_id: masterId,
          client_id: clientId,
          datetime: beautyTestData.appointment.dateTime,
          idempotency_key: idempotencyKey,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        const appointmentId = response.body.id;

        // Duplicate request should return same result
        cy.request({
          method: 'POST',
          url: '/api/appointments',
          body: {
            service_id: serviceId,
            master_id: masterId,
            client_id: clientId,
            datetime: beautyTestData.appointment.dateTime,
            idempotency_key: idempotencyKey,
          },
        }).then(duplicateResponse => {
          expect(duplicateResponse.status).to.equal(200); // Already created
          expect(duplicateResponse.body.id).to.equal(appointmentId);
        });
      });
    });

    it('Should hold payment for appointment', () => {
      cy.request({
        method: 'POST',
        url: '/api/appointments',
        body: {
          service_id: serviceId,
          master_id: masterId,
          client_id: clientId,
          datetime: beautyTestData.appointment.dateTime,
        },
      }).then(response => {
        const appointmentId = response.body.id;

        // Verify hold was created
        cy.request(`/api/appointments/${appointmentId}/payment-hold`).then(holdResponse => {
          expect(holdResponse.status).to.equal(200);
          expect(holdResponse.body.status).to.equal('AUTHORIZED');
          expect(holdResponse.body.amount).to.equal(beautyTestData.service.price);
        });
      });
    });

    it('Should send appointment reminders', () => {
      cy.request({
        method: 'POST',
        url: `/api/appointments/${appointmentId}/send-reminder`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.reminder_sent).to.be.true;
      });
    });
  });

  describe('Payment Processing', () => {
    it('Should process payment after service completion', () => {
      cy.request({
        method: 'PATCH',
        url: `/api/appointments/${appointmentId}`,
        body: { status: 'completed' },
      }).then(response => {
        expect(response.status).to.equal(200);

        // Verify payment was captured
        cy.request(`/api/appointments/${appointmentId}/payment`).then(paymentResponse => {
          expect(paymentResponse.body.status).to.equal('CAPTURED');
          expect(paymentResponse.body.amount).to.equal(beautyTestData.service.price);
        });
      });
    });

    it('Should refund if appointment cancelled within 2 hours', () => {
      // Create new appointment in near future
      const futureTime = new Date(Date.now() + 60 * 60 * 1000).toISOString();
      
      cy.request({
        method: 'POST',
        url: '/api/appointments',
        body: {
          service_id: serviceId,
          master_id: masterId,
          client_id: clientId,
          datetime: futureTime,
        },
      }).then(createResponse => {
        const newAppointmentId = createResponse.body.id;

        // Cancel within 2 hours
        cy.request({
          method: 'PATCH',
          url: `/api/appointments/${newAppointmentId}`,
          body: { status: 'cancelled_by_client' },
        }).then(cancelResponse => {
          expect(cancelResponse.status).to.equal(200);

          // Verify refund initiated
          cy.request(`/api/appointments/${newAppointmentId}/payment`).then(paymentResponse => {
            expect(paymentResponse.body.status).to.equal('REFUNDED');
          });
        });
      });
    });
  });

  describe('Rating & Review System', () => {
    it('Should submit review after appointment completion', () => {
      cy.visit(`/app/appointments/${appointmentId}`);
      
      cy.get('[data-testid="review-form"]').should('be.visible');
      cy.get('[data-testid="rating-stars"]').then($stars => {
        cy.wrap($stars).find('button').eq(4).click(); // 5 stars
      });
      
      cy.get('textarea[name="comment"]').type('Отличная работа! Очень доволен результатом');
      cy.get('button').contains('Отправить отзыв').click();
      
      cy.get('.filament-notification').should('contain', 'Отзыв отправлен');
    });

    it('Should update master rating after review', () => {
      cy.request({
        method: 'POST',
        url: `/api/appointments/${appointmentId}/review`,
        body: {
          rating: 5,
          comment: 'Отличная работа',
        },
      }).then(() => {
        cy.request(`/api/masters/${masterId}`).then(response => {
          expect(response.body.rating).to.be.greaterThan(0);
          expect(response.body.review_count).to.be.greaterThan(0);
        });
      });
    });
  });

  describe('Portfolio & Before/After', () => {
    it('Should upload portfolio images', () => {
      cy.visit(`/tenant/masters/${masterId}/portfolio`);
      
      cy.get('input[type="file"]').selectFile('cypress/fixtures/before-after-1.jpg');
      cy.get('input[name="description"]').type('Окрашивание + укладка');
      cy.get('input[name="service_type"]').type('окрашивание');
      
      cy.get('button').contains('Загрузить').click();
      cy.get('.filament-notification').should('contain', 'Фото добавлено в портфолио');
    });

    it('Should display portfolio on master profile', () => {
      cy.visit(`/app/masters/${masterId}`);
      
      cy.get('[data-testid="portfolio-section"]').should('be.visible');
      cy.get('[data-testid="portfolio-image"]').should('have.length.greaterThan', 0);
    });
  });

  describe('Schedule & Availability', () => {
    it('Should calculate available slots correctly', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/availability?date=2026-03-20`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.available_slots).to.be.an('array');
        
        // Verify no overlapping slots
        const slots = response.body.available_slots;
        for (let i = 0; i < slots.length - 1; i++) {
          expect(new Date(slots[i].end) <= new Date(slots[i + 1].start)).to.be.true;
        }
      });
    });

    it('Should block slots for maintenance', () => {
      cy.request({
        method: 'POST',
        url: `/api/masters/${masterId}/block-slot`,
        body: {
          start_time: '2026-03-20T13:00:00',
          end_time: '2026-03-20T14:00:00',
          reason: 'Техническое обслуживание',
        },
      }).then(response => {
        expect(response.status).to.equal(201);

        // Verify slot is blocked
        cy.request(`/api/masters/${masterId}/availability?date=2026-03-20`).then(availResponse => {
          const blockedSlot = availResponse.body.available_slots.find(s => 
            s.start === '2026-03-20T13:00:00'
          );
          expect(blockedSlot).to.be.undefined;
        });
      });
    });
  });

  describe('Multi-Master Coordination', () => {
    it('Should distribute clients across multiple masters', () => {
      const master2Id = 102;
      
      cy.request({
        method: 'GET',
        url: `/api/salons/${tenantId}/masters/load-distribution`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('masters');
        expect(response.body.masters).to.be.an('array');
        expect(response.body.masters.length).to.be.greaterThan(1);
      });
    });
  });
});
