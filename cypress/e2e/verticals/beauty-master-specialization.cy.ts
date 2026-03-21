/// <reference types="cypress" />

describe('Beauty - Master Management & Specialization', () => {
  const tenantId = 1;
  const salonId = 101;
  const masterId = 201;

  const masterTestData = {
    master: {
      firstName: 'Виктория',
      lastName: 'Петрова',
      phone: '+7-900-500-6666',
      email: 'victoria@salon.ru',
      specializations: ['стрижка', 'окрашивание', 'укладка'],
      experience: 12,
      rating: 4.8,
    },
    schedule: {
      workDays: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
      dayOffDays: ['saturday', 'sunday'],
      breakDuration: 60,
    },
  };

  before(() => {
    cy.login('salon_manager@example.com', 'password123');
  });

  describe('Master Registration & Onboarding', () => {
    it('Should create new master profile', () => {
      cy.visit(`/tenant/salons/${salonId}/masters/create`);
      
      cy.get('input[name="first_name"]').type(masterTestData.master.firstName);
      cy.get('input[name="last_name"]').type(masterTestData.master.lastName);
      cy.get('input[name="phone"]').type(masterTestData.master.phone);
      cy.get('input[name="email"]').type(masterTestData.master.email);
      cy.get('input[name="experience_years"]').type(masterTestData.master.experience.toString());
      
      masterTestData.master.specializations.forEach(spec => {
        cy.get(`label[for="spec_${spec}"]`).click();
      });
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Мастер добавлен');
    });

    it('Should upload master certificates and credentials', () => {
      cy.visit(`/tenant/salons/${salonId}/masters/${masterId}/credentials`);
      
      cy.get('input[name="certificates"]').attachFile([
        'certificate-1.pdf',
        'certificate-2.pdf',
      ]);
      
      cy.get('[data-testid="certificate-preview"]').should('have.length', 2);
      cy.get('button').contains('Сохранить').click();
    });

    it('Should verify master identity and background', () => {
      cy.request({
        method: 'POST',
        url: `/api/masters/${masterId}/verify`,
        body: {
          passport_number: '1234567890',
          verification_type: 'government_id',
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.verification_status).to.equal('pending');
      });
    });

    it('Should track verification completion', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/verification-status`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(['pending', 'verified', 'rejected']).to.include(response.body.status);
      });
    });
  });

  describe('Specialization & Skills Management', () => {
    it('Should add specializations to master profile', () => {
      cy.visit(`/tenant/salons/${salonId}/masters/${masterId}/specializations`);
      
      const newSpecs = ['мейкап', 'ламинирование_ресниц'];
      
      newSpecs.forEach(spec => {
        cy.get('button').contains('Добавить специализацию').click();
        cy.get(`label[for="spec_${spec}"]`).click();
      });
      
      cy.get('button').contains('Сохранить').click();
    });

    it('Should define service pricing per specialization', () => {
      cy.visit(`/tenant/salons/${salonId}/masters/${masterId}/pricing`);
      
      const pricingRules = [
        { specialization: 'стрижка', price: 80000 },
        { specialization: 'окрашивание', price: 150000 },
        { specialization: 'укладка', price: 50000 },
      ];
      
      pricingRules.forEach(rule => {
        cy.get(`input[name="price_${rule.specialization}"]`).type((rule.price / 100).toString());
      });
      
      cy.get('button').contains('Сохранить').click();
    });

    it('Should allow specialization-specific availability', () => {
      cy.request({
        method: 'PATCH',
        url: `/api/masters/${masterId}/specialization-availability`,
        body: {
          specialization: 'окрашивание',
          available: true,
          min_advance_booking_hours: 24,
          max_advance_booking_days: 60,
        },
      }).then(response => {
        expect(response.status).to.equal(200);
      });
    });

    it('Should track specialization skill level', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/skills`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.skills).to.be.an('array');
        
        response.body.skills.forEach(skill => {
          expect(['beginner', 'intermediate', 'advanced', 'expert']).to.include(skill.level);
        });
      });
    });
  });

  describe('Master Schedule Management', () => {
    it('Should set regular work schedule', () => {
      cy.visit(`/tenant/salons/${salonId}/masters/${masterId}/schedule`);
      
      masterTestData.schedule.workDays.forEach(day => {
        cy.get(`input[name="${day}_open"]`).type('09:00');
        cy.get(`input[name="${day}_close"]`).type('19:00');
      });
      
      // Days off
      masterTestData.schedule.dayOffDays.forEach(day => {
        cy.get(`input[name="${day}_open"]`).should('be.disabled');
      });
      
      cy.get('button').contains('Сохранить').click();
    });

    it('Should set lunch break time', () => {
      cy.request({
        method: 'PATCH',
        url: `/api/masters/${masterId}/breaks`,
        body: {
          break_start: '13:00',
          break_duration_minutes: 60,
          break_type: 'lunch',
        },
      }).then(response => {
        expect(response.status).to.equal(200);
      });
    });

    it('Should mark vacation days', () => {
      cy.request({
        method: 'POST',
        url: `/api/masters/${masterId}/time-off`,
        body: {
          start_date: '2026-07-01',
          end_date: '2026-07-14',
          reason: 'vacation',
          notes: 'Summer vacation',
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('time_off_id');
      });
    });

    it('Should manage ad-hoc unavailability', () => {
      cy.request({
        method: 'POST',
        url: `/api/masters/${masterId}/unavailable-slots`,
        body: {
          date: '2026-03-20',
          start_time: '11:00',
          end_time: '12:30',
          reason: 'Training session',
          is_paid: false,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
      });
    });

    it('Should calculate master utilization rate', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/utilization`,
        queryParams: { period: 'month', month: '2026-03' },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('scheduled_hours');
        expect(response.body).to.have.property('worked_hours');
        expect(response.body).to.have.property('utilization_percent');
      });
    });
  });

  describe('Master Performance Metrics', () => {
    it('Should track master average rating', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/rating`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('average_rating');
        expect(response.body).to.have.property('total_reviews');
        expect(response.body.average_rating).to.be.within(1, 5);
      });
    });

    it('Should show rating by specialization', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/rating-by-specialization`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.be.an('array');
        
        response.body.forEach(spec => {
          expect(spec).to.have.property('specialization');
          expect(spec).to.have.property('average_rating');
          expect(spec).to.have.property('review_count');
        });
      });
    });

    it('Should calculate master earnings', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/earnings`,
        queryParams: { month: '2026-03' },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('gross_earnings');
        expect(response.body).to.have.property('commission_deducted');
        expect(response.body).to.have.property('net_earnings');
      });
    });

    it('Should track no-show rate and cancellation rate', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/reliability-metrics`,
        queryParams: { period: 'quarter' },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('no_show_rate');
        expect(response.body).to.have.property('cancellation_rate');
        expect(response.body).to.have.property('on_time_rate');
      });
    });
  });

  describe('Master Portfolio Management', () => {
    it('Should create portfolio gallery', () => {
      cy.visit(`/tenant/salons/${salonId}/masters/${masterId}/portfolio`);
      
      cy.get('button').contains('Добавить работу').click();
      cy.get('input[name="title"]').type('Окрашивание омбре');
      cy.get('select[name="specialization"]').select('окрашивание');
      cy.get('textarea[name="description"]').type('Градиентное окрашивание на темную базу');
      
      cy.get('input[name="before_photo"]').attachFile('before.jpg');
      cy.get('input[name="after_photo"]').attachFile('after.jpg');
      
      cy.get('button').contains('Добавить').click();
    });

    it('Should track portfolio engagement metrics', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/portfolio-stats`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.be.an('array');
        
        response.body.forEach(item => {
          expect(item).to.have.property('views');
          expect(item).to.have.property('clicks');
          expect(item).to.have.property('booking_conversions');
        });
      });
    });
  });

  describe('Master Team Collaboration', () => {
    it('Should manage team collaborations and pairings', () => {
      const partnerId = 202;
      
      cy.request({
        method: 'POST',
        url: `/api/masters/${masterId}/collaborations`,
        body: {
          partner_master_id: partnerId,
          collaboration_type: 'referral',
          start_date: '2026-03-15',
        },
      }).then(response => {
        expect(response.status).to.equal(201);
      });
    });

    it('Should track referrals between masters', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/referral-stats`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('referrals_given');
        expect(response.body).to.have.property('referrals_received');
      });
    });

    it('Should handle team-based appointments', () => {
      const appointmentId = 3001;
      
      cy.request({
        method: 'GET',
        url: `/api/appointments/${appointmentId}/team`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.team_members).to.be.an('array');
        expect(response.body.team_members.length).to.be.greaterThan(1);
      });
    });
  });

  describe('Master Compliance & Training', () => {
    it('Should track certification validity', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/certifications`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.certifications).to.be.an('array');
        
        response.body.certifications.forEach(cert => {
          expect(cert).to.have.property('name');
          expect(cert).to.have.property('issue_date');
          expect(cert).to.have.property('expiry_date');
          expect(cert).to.have.property('status'); // active/expiring/expired
        });
      });
    });

    it('Should alert on upcoming certification expiry', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/certification-alerts`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.expiring_soon).to.be.an('array');
      });
    });

    it('Should track training and development', () => {
      cy.request({
        method: 'POST',
        url: `/api/masters/${masterId}/training-record`,
        body: {
          training_type: 'skill_development',
          course_name: 'Advanced Color Theory',
          completion_date: '2026-03-15',
          certification_file: 'training-cert.pdf',
        },
      }).then(response => {
        expect(response.status).to.equal(201);
      });
    });
  });

  describe('Master Content & Social Media', () => {
    it('Should allow masters to post showcase content', () => {
      cy.visit(`/tenant/salons/${salonId}/masters/${masterId}/content`);
      
      cy.get('textarea[name="content"]').type('Новая техника омбре, которую я только что выучила! 💄');
      cy.get('input[name="images"]').attachFile('showcase.jpg');
      cy.get('select[name="specialization"]').select('окрашивание');
      
      cy.get('button').contains('Опубликовать').click();
    });

    it('Should track master social engagement', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/social-metrics`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('followers');
        expect(response.body).to.have.property('posts');
        expect(response.body).to.have.property('engagement_rate');
      });
    });
  });

  describe('Master Income & Payments', () => {
    it('Should calculate master commission structure', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/commission-structure`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('base_commission_percent');
        expect(response.body).to.have.property('performance_bonus_percent');
        expect(response.body).to.have.property('loyalty_bonus_percent');
      });
    });

    it('Should process master payouts', () => {
      cy.request({
        method: 'POST',
        url: `/api/masters/${masterId}/request-payout`,
        body: {
          amount: 250000,
          payout_method: 'bank_transfer',
          month: '2026-03',
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body.status).to.equal('pending');
      });
    });

    it('Should track payout history', () => {
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/payout-history`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.payouts).to.be.an('array');
      });
    });
  });
});
