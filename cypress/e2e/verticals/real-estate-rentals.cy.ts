/// <reference types="cypress" />

describe('Real Estate - Rental Management & Leases', () => {
  const tenantId = 4;
  const propertyId = 502;
  const rentalListingId = 5001;
  const tenantCustomerId = 3002;

  const rentalTestData = {
    property: {
      address: 'СПб, Петродворцовая ул., 45/2',
      type: 'apartment',
      area: 65,
      rooms: 2,
      floor: 3,
      totalFloors: 5,
      furnished: true,
      utilities: ['интернет', 'отопление', 'водопровод'],
    },
    rental: {
      monthlyPrice: 75000, // 750 тыс копеек
      minTerm: 6, // months
      maxTerm: 36,
      depositMonths: 1, // 1 month of rent
      cancellationDays: 30,
    },
  };

  before(() => {
    cy.login('landlord@example.com', 'password123');
  });

  describe('Rental Listing Creation', () => {
    it('Should create rental listing with lease terms', () => {
      cy.visit(`/tenant/properties/${propertyId}/rental-listing`);
      
      cy.get('input[name="monthly_price"]').type((rentalTestData.rental.monthlyPrice / 100).toString());
      cy.get('input[name="min_lease_term"]').type(rentalTestData.rental.minTerm.toString());
      cy.get('input[name="max_lease_term"]').type(rentalTestData.rental.maxTerm.toString());
      cy.get('input[name="deposit_months"]').type(rentalTestData.rental.depositMonths.toString());
      cy.get('input[name="notice_period_days"]').type(rentalTestData.rental.cancellationDays.toString());
      
      rentalTestData.property.utilities.forEach(util => {
        cy.get(`label[for="utility_${util}"]`).click();
      });
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Объект выставлен');
    });

    it('Should list rental with occupation dates', () => {
      cy.request({
        method: 'POST',
        url: `/api/rental-listings`,
        body: {
          property_id: propertyId,
          available_from: '2026-04-01',
          available_until: '2027-12-31',
          monthly_price: rentalTestData.rental.monthlyPrice,
          deposit_required: rentalTestData.rental.monthlyPrice * rentalTestData.rental.depositMonths,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('listing_id');
        expect(response.body.status).to.equal('active');
      });
    });

    it('Should auto-generate rental SEO-friendly URL', () => {
      cy.request(`/api/rental-listings/${rentalListingId}`).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.seo_url).to.match(/^[\w-]+$/);
      });
    });
  });

  describe('Tenant Application & Screening', () => {
    it('Should accept rental applications from tenants', () => {
      cy.request({
        method: 'POST',
        url: `/api/rental-listings/${rentalListingId}/applications`,
        body: {
          applicant_name: 'Иван Попов',
          applicant_phone: '+7-900-333-4444',
          applicant_email: 'ivan@example.com',
          move_in_date: '2026-04-15',
          employment_status: 'employed',
          monthly_income: 300000,
          references: [
            { name: 'Maria', phone: '+7-900-111-1111', relationship: 'employer' },
          ],
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('application_id');
        expect(response.body.status).to.equal('pending');
      });
    });

    it('Should verify tenant creditworthiness', () => {
      const applicationId = 'app-13001';
      
      cy.request({
        method: 'POST',
        url: `/api/applications/${applicationId}/credit-check`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('credit_score');
        expect(response.body).to.have.property('recommendation'); // approved/denied
      });
    });

    it('Should request documentation from tenant', () => {
      const applicationId = 'app-13001';
      
      cy.request({
        method: 'POST',
        url: `/api/applications/${applicationId}/request-documents`,
        body: {
          document_types: ['id', 'proof_of_income', 'reference_letter'],
          deadline: '2026-03-22',
        },
      }).then(response => {
        expect(response.status).to.equal(201);
      });
    });

    it('Should track document upload status', () => {
      const applicationId = 'app-13001';
      
      cy.request({
        method: 'GET',
        url: `/api/applications/${applicationId}/documents`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.documents).to.be.an('array');
        
        response.body.documents.forEach(doc => {
          expect(['pending', 'uploaded', 'verified']).to.include(doc.status);
        });
      });
    });

    it('Should approve or deny application', () => {
      const applicationId = 'app-13001';
      
      cy.request({
        method: 'PATCH',
        url: `/api/applications/${applicationId}/approve`,
        body: { approved: true, start_lease_process: true },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('approved');
      });
    });
  });

  describe('Lease Agreement Management', () => {
    it('Should generate lease agreement from template', () => {
      const applicationId = 'app-13001';
      
      cy.request({
        method: 'POST',
        url: `/api/applications/${applicationId}/generate-lease`,
        body: {
          template_id: 'standard_residential',
          terms_override: {
            early_termination_fee: 15000, // 150K копеек
            pet_policy: 'not_allowed',
          },
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('lease_id');
        expect(response.body).to.have.property('lease_url');
      });
    });

    it('Should track lease signature status', () => {
      const leaseId = 'lease-14001';
      
      cy.request({
        method: 'GET',
        url: `/api/leases/${leaseId}/signature-status`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('landlord_signed_at');
        expect(response.body).to.have.property('tenant_signed_at');
        expect(response.body).to.have.property('all_signed_at');
      });
    });

    it('Should collect security deposit before move-in', () => {
      const leaseId = 'lease-14001';
      
      cy.request({
        method: 'POST',
        url: `/api/leases/${leaseId}/collect-deposit`,
        body: {
          amount: rentalTestData.rental.monthlyPrice * rentalTestData.rental.depositMonths,
          payment_method: 'card',
          due_date: '2026-03-30',
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('transaction_id');
        expect(response.body.status).to.equal('pending');
      });
    });

    it('Should verify idempotency of deposit collection', () => {
      const leaseId = 'lease-14001';
      const depositPayload = {
        amount: rentalTestData.rental.monthlyPrice * rentalTestData.rental.depositMonths,
        payment_method: 'card',
        idempotency_key: 'deposit-001',
      };

      cy.request({
        method: 'POST',
        url: `/api/leases/${leaseId}/collect-deposit`,
        body: depositPayload,
      }).then(response => {
        const firstTxId = response.body.transaction_id;
        expect(response.status).to.equal(201);

        // Duplicate request
        cy.request({
          method: 'POST',
          url: `/api/leases/${leaseId}/collect-deposit`,
          body: depositPayload,
        }).then(dupResponse => {
          expect(dupResponse.status).to.equal(200);
          expect(dupResponse.body.transaction_id).to.equal(firstTxId);
        });
      });
    });
  });

  describe('Rent Payment Management', () => {
    it('Should set up automatic rent collection', () => {
      const leaseId = 'lease-14001';
      
      cy.request({
        method: 'POST',
        url: `/api/leases/${leaseId}/auto-payment`,
        body: {
          payment_day: 1,
          payment_amount: rentalTestData.rental.monthlyPrice,
          payment_method: 'bank_transfer',
          starts: '2026-05-01',
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('recurring_id');
      });
    });

    it('Should track rent payment history', () => {
      const leaseId = 'lease-14001';
      
      cy.request({
        method: 'GET',
        url: `/api/leases/${leaseId}/rent-payments`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.payments).to.be.an('array');
        
        response.body.payments.forEach(payment => {
          expect(['pending', 'paid', 'late', 'overdue']).to.include(payment.status);
          expect(payment).to.have.property('due_date');
          expect(payment).to.have.property('paid_date');
        });
      });
    });

    it('Should handle late rent payment notifications', () => {
      const leaseId = 'lease-14001';
      
      cy.request({
        method: 'GET',
        url: `/api/leases/${leaseId}/payment-alerts`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.alerts).to.be.an('array');
        
        response.body.alerts.forEach(alert => {
          expect(['reminder', 'overdue_notice', 'legal_notice']).to.include(alert.alert_type);
        });
      });
    });

    it('Should calculate late fees on overdue rent', () => {
      const paymentId = 'pmt-15001';
      
      cy.request({
        method: 'GET',
        url: `/api/payments/${paymentId}/late-fee`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('days_overdue');
        expect(response.body).to.have.property('late_fee_amount');
        expect(response.body).to.have.property('total_due');
      });
    });
  });

  describe('Maintenance & Repair Requests', () => {
    it('Should allow tenant to submit maintenance requests', () => {
      const leaseId = 'lease-14001';
      
      cy.request({
        method: 'POST',
        url: `/api/leases/${leaseId}/maintenance-requests`,
        body: {
          title: 'Утечка в ванной комнате',
          category: 'plumbing',
          description: 'Вода капает из крана',
          urgency: 'high',
          photos: ['leak-1.jpg'],
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('request_id');
        expect(response.body.status).to.equal('submitted');
      });
    });

    it('Should track maintenance request status', () => {
      const requestId = 'maint-16001';
      
      cy.request({
        method: 'GET',
        url: `/api/maintenance-requests/${requestId}`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(['submitted', 'acknowledged', 'in_progress', 'completed', 'closed']).to.include(response.body.status);
      });
    });

    it('Should schedule maintenance access to property', () => {
      const requestId = 'maint-16001';
      
      cy.request({
        method: 'POST',
        url: `/api/maintenance-requests/${requestId}/schedule`,
        body: {
          maintenance_date: '2026-03-22',
          maintenance_time: '14:00',
          estimated_duration_hours: 2,
        },
      }).then(response => {
        expect(response.status).to.equal(200);
      });
    });
  });

  describe('Lease Renewal & Termination', () => {
    it('Should send lease renewal offer before expiration', () => {
      const leaseId = 'lease-14001';
      
      cy.request({
        method: 'POST',
        url: `/api/leases/${leaseId}/renewal-offer`,
        body: {
          new_monthly_price: 78000, // 2% increase
          renewal_period_months: 12,
          offer_expires_at: '2027-02-01',
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('renewal_id');
      });
    });

    it('Should track renewal acceptance', () => {
      const renewalId = 'renewal-17001';
      
      cy.request({
        method: 'GET',
        url: `/api/renewals/${renewalId}/status`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(['pending', 'accepted', 'declined', 'expired']).to.include(response.body.status);
      });
    });

    it('Should process lease termination with notice', () => {
      const leaseId = 'lease-14001';
      
      cy.request({
        method: 'POST',
        url: `/api/leases/${leaseId}/terminate`,
        body: {
          termination_date: '2027-04-30',
          reason: 'Tenant moving out',
          initiated_by: 'tenant',
          notice_given_date: '2027-03-31',
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('termination_pending');
      });
    });

    it('Should process early termination with penalty', () => {
      const leaseId = 'lease-14001';
      
      cy.request({
        method: 'POST',
        url: `/api/leases/${leaseId}/early-terminate`,
        body: {
          termination_date: '2027-01-15', // 2.5 months early
          agreed_penalty: 37500, // 0.5 month penalty
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('early_termination_fee');
      });
    });
  });

  describe('Deposit Return & Move-Out', () => {
    it('Should schedule move-out inspection', () => {
      const leaseId = 'lease-14001';
      
      cy.request({
        method: 'POST',
        url: `/api/leases/${leaseId}/move-out-inspection`,
        body: {
          inspection_date: '2027-04-30',
          inspection_time: '10:00',
          inspector_id: 6002,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('inspection_id');
      });
    });

    it('Should document move-out condition', () => {
      const inspectionId = 'insp-18001';
      
      cy.request({
        method: 'POST',
        url: `/api/inspections/${inspectionId}/report`,
        body: {
          overall_condition: 'good',
          damages: [
            { location: 'Living room wall', damage_type: 'paint_scratch', repair_cost: 5000 },
          ],
          cleanliness: 'acceptable',
          utilities_working: true,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
      });
    });

    it('Should calculate deposit deductions', () => {
      const inspectionId = 'insp-18001';
      
      cy.request({
        method: 'GET',
        url: `/api/inspections/${inspectionId}/deposit-deductions`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('original_deposit');
        expect(response.body).to.have.property('deductions');
        expect(response.body).to.have.property('amount_to_return');
      });
    });

    it('Should process deposit refund', () => {
      const leaseId = 'lease-14001';
      
      cy.request({
        method: 'POST',
        url: `/api/leases/${leaseId}/refund-deposit`,
        body: {
          refund_amount: 75000,
          refund_date: '2027-05-15',
          deductions_explanation: 'Wall damage repair cost',
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('refund_transaction_id');
      });
    });
  });

  describe('Rental Analytics & Reporting', () => {
    it('Should track rental property occupancy rate', () => {
      cy.request({
        method: 'GET',
        url: `/api/rental-listings/${rentalListingId}/occupancy-stats`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('occupancy_rate');
        expect(response.body).to.have.property('average_days_vacant');
      });
    });

    it('Should calculate rental yield and ROI', () => {
      cy.request({
        method: 'GET',
        url: `/api/rental-listings/${rentalListingId}/financial-analysis`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('annual_gross_rent');
        expect(response.body).to.have.property('operating_expenses');
        expect(response.body).to.have.property('net_rental_income');
        expect(response.body).to.have.property('cap_rate');
      });
    });

    it('Should show tenant payment compliance', () => {
      const leaseId = 'lease-14001';
      
      cy.request({
        method: 'GET',
        url: `/api/leases/${leaseId}/payment-compliance`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('on_time_payment_rate');
        expect(response.body).to.have.property('late_payments_count');
      });
    });
  });
});
