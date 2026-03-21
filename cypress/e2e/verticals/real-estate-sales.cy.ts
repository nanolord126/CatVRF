/// <reference types="cypress" />

describe('Real Estate - Sales & Transaction Management', () => {
  const tenantId = 4;
  const agentId = 601;
  const propertyId = 501;
  const customerId = 3001;

  const salesTestData = {
    property: {
      address: 'СПб, Невский проспект, 28',
      type: 'apartment',
      area: 85,
      rooms: 3,
      floor: 5,
      totalFloors: 9,
      year: 2020,
      price: 12500000, // 125 млн копеек
      condition: 'excellent',
      features: ['балкон', 'паркинг', 'охрана'],
    },
    customer: {
      name: 'Александр Петров',
      phone: '+7-900-222-3333',
      email: 'alex@example.com',
    },
  };

  before(() => {
    cy.login('agent@example.com', 'password123');
  });

  describe('Sales Property Listing Creation', () => {
    it('Should create property with comprehensive details', () => {
      cy.visit(`/tenant/properties/create`);
      
      cy.get('select[name="type"]').select('sale');
      cy.get('input[name="address"]').type(salesTestData.property.address);
      cy.get('input[name="area"]').type(salesTestData.property.area.toString());
      cy.get('input[name="rooms"]').type(salesTestData.property.rooms.toString());
      cy.get('input[name="floor"]').type(salesTestData.property.floor.toString());
      cy.get('input[name="total_floors"]').type(salesTestData.property.totalFloors.toString());
      cy.get('input[name="year"]').type(salesTestData.property.year.toString());
      cy.get('input[name="price"]').type((salesTestData.property.price / 100).toString());
      cy.get('select[name="condition"]').select(salesTestData.property.condition);
      
      salesTestData.property.features.forEach(feature => {
        cy.get(`label[for="feature_${feature}"]`).click();
      });
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Объект создан');
    });

    it('Should upload property photos', () => {
      cy.visit(`/tenant/properties/${propertyId}/photos`);
      
      cy.get('input[name="photos"]').attachFile([
        'property-1.jpg',
        'property-2.jpg',
        'property-3.jpg',
      ]);
      
      cy.get('[data-testid="photo-preview"]').should('have.length', 3);
    });

    it('Should create 360-degree virtual tour', () => {
      cy.request({
        method: 'POST',
        url: `/api/properties/${propertyId}/virtual-tour`,
        body: {
          type: '360',
          images: ['tour-1.jpg', 'tour-2.jpg', 'tour-3.jpg'],
          hotspots: [
            { x: 50, y: 50, label: 'Кухня' },
            { x: 60, y: 40, label: 'Спальня' },
          ],
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('tour_url');
      });
    });

    it('Should auto-generate SEO-friendly URL', () => {
      cy.request(`/api/properties/${propertyId}`).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.seo_url).to.match(/^[\w-]+$/);
        expect(response.body.seo_url).to.include('nevskij-prospekt');
      });
    });
  });

  describe('Viewing Appointment Scheduling', () => {
    it('Should schedule viewing with customer', () => {
      cy.request({
        method: 'POST',
        url: `/api/properties/${propertyId}/viewings`,
        body: {
          customer_name: salesTestData.customer.name,
          customer_phone: salesTestData.customer.phone,
          viewing_date: '2026-03-25',
          viewing_time: '14:00',
          duration_minutes: 30,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('viewing_id');
        expect(response.body.status).to.equal('scheduled');
      });
    });

    it('Should verify idempotency for viewing creation', () => {
      const viewingData = {
        customer_phone: '+7-999-888-7777',
        viewing_date: '2026-03-26',
        viewing_time: '15:00',
      };

      cy.request({
        method: 'POST',
        url: `/api/properties/${propertyId}/viewings`,
        body: { ...viewingData, idempotency_key: 'view-001' },
      }).then(response => {
        const firstId = response.body.viewing_id;
        expect(response.status).to.equal(201);

        // Duplicate request
        cy.request({
          method: 'POST',
          url: `/api/properties/${propertyId}/viewings`,
          body: { ...viewingData, idempotency_key: 'view-001' },
        }).then(dupResponse => {
          expect(dupResponse.status).to.equal(200);
          expect(dupResponse.body.viewing_id).to.equal(firstId);
        });
      });
    });

    it('Should send appointment reminders', () => {
      const viewingId = 8001;
      
      cy.request({
        method: 'GET',
        url: `/api/viewings/${viewingId}/reminder-status`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('reminder_1_day_sent');
        expect(response.body).to.have.property('reminder_2_hours_sent');
      });
    });

    it('Should track viewing attendance and feedback', () => {
      const viewingId = 8001;
      
      cy.request({
        method: 'PATCH',
        url: `/api/viewings/${viewingId}/complete`,
        body: {
          attended: true,
          customer_rating: 4,
          feedback: 'Хорошая квартира, но нужна косметическая ремонт',
          interested: true,
        },
      }).then(response => {
        expect(response.status).to.equal(200);
      });
    });
  });

  describe('Offer & Negotiation Workflow', () => {
    it('Should create customer offer on property', () => {
      cy.request({
        method: 'POST',
        url: `/api/properties/${propertyId}/offers`,
        body: {
          customer_id: customerId,
          offered_price: 12000000, // 2% ниже
          proposed_closing_date: '2026-05-15',
          terms: 'Ready to move quickly',
          contingencies: ['Inspection', 'Financing'],
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('offer_id');
        expect(response.body.status).to.equal('pending');
      });
    });

    it('Should handle counter-offers in negotiation', () => {
      const offerId = 9001;
      
      // Agent counters
      cy.request({
        method: 'POST',
        url: `/api/offers/${offerId}/counter`,
        body: {
          counter_price: 12400000,
          counter_terms: 'Can close in 2 weeks',
          expires_at: '2026-03-23',
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('negotiation_round');
      });
    });

    it('Should track offer status transitions', () => {
      const offerId = 9001;
      
      // Offer accepted
      cy.request({
        method: 'PATCH',
        url: `/api/offers/${offerId}/accept`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('accepted');
      });
    });

    it('Should notify all parties of offer changes', () => {
      const offerId = 9001;
      
      cy.request({
        method: 'GET',
        url: `/api/offers/${offerId}/notifications`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.notifications).to.be.an('array');
        
        response.body.notifications.forEach(notif => {
          expect(['agent', 'customer', 'seller']).to.include(notif.recipient_type);
        });
      });
    });
  });

  describe('Deposit & Payment Management', () => {
    it('Should hold 10% deposit on accepted offer', () => {
      const offerId = 9001;
      
      cy.request({
        method: 'POST',
        url: `/api/offers/${offerId}/hold-deposit`,
        body: {
          amount: 1250000, // 10% от 125 млн
          payment_method: 'card',
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('transaction_id');
        expect(response.body.status).to.equal('held');
      });
    });

    it('Should refund deposit if deal falls through', () => {
      const transactionId = 'tx-10001';
      
      cy.request({
        method: 'POST',
        url: `/api/transactions/${transactionId}/refund`,
        body: {
          reason: 'Offer cancelled by buyer',
          correlation_id: 'corr-uuid-001',
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('refunded');
      });
    });

    it('Should capture deposit on deal completion', () => {
      const transactionId = 'tx-10001';
      
      cy.request({
        method: 'PATCH',
        url: `/api/transactions/${transactionId}/capture`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('captured');
      });
    });
  });

  describe('Commission & Agent Management', () => {
    it('Should calculate agent commission on sale', () => {
      const saleId = 11001;
      
      cy.request({
        method: 'GET',
        url: `/api/sales/${saleId}/commission`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('sale_price');
        expect(response.body).to.have.property('commission_percent');
        expect(response.body).to.have.property('commission_amount');
      });
    });

    it('Should distribute commission between agents', () => {
      const saleId = 11001;
      
      cy.request({
        method: 'GET',
        url: `/api/sales/${saleId}/commission-split`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.listing_agent).to.have.property('amount');
        expect(response.body.selling_agent).to.have.property('amount');
      });
    });

    it('Should track agent performance metrics', () => {
      cy.request({
        method: 'GET',
        url: `/api/agents/${agentId}/metrics`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('listings_active');
        expect(response.body).to.have.property('sales_completed');
        expect(response.body).to.have.property('average_time_to_sale');
        expect(response.body).to.have.property('average_commission');
      });
    });
  });

  describe('Legal Document Management', () => {
    it('Should prepare legal documents on accepted offer', () => {
      const saleId = 11001;
      
      cy.request({
        method: 'POST',
        url: `/api/sales/${saleId}/documents/generate`,
        body: {
          document_types: ['purchase_agreement', 'disclosure_form'],
          state: 'california', // для примера
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('document_ids');
      });
    });

    it('Should track document signature status', () => {
      const documentId = 'doc-12001';
      
      cy.request({
        method: 'GET',
        url: `/api/documents/${documentId}/signature-status`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('buyer_signed_at');
        expect(response.body).to.have.property('seller_signed_at');
      });
    });
  });

  describe('Closing Coordination', () => {
    it('Should schedule closing inspection', () => {
      const saleId = 11001;
      
      cy.request({
        method: 'POST',
        url: `/api/sales/${saleId}/closing-inspection`,
        body: {
          inspection_date: '2026-05-10',
          inspection_time: '10:00',
          inspector_id: 6001,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body.inspection_id).to.exist;
      });
    });

    it('Should track closing progress', () => {
      const saleId = 11001;
      
      cy.request({
        method: 'GET',
        url: `/api/sales/${saleId}/closing-timeline`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('inspection_completed');
        expect(response.body).to.have.property('appraisal_completed');
        expect(response.body).to.have.property('financing_approved');
        expect(response.body).to.have.property('title_cleared');
      });
    });

    it('Should finalize sale and release funds', () => {
      const saleId = 11001;
      
      cy.request({
        method: 'PATCH',
        url: `/api/sales/${saleId}/finalize`,
        body: {
          closing_date: '2026-05-15',
          funds_released: true,
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('closed');
      });
    });
  });

  describe('Post-Sale Analytics', () => {
    it('Should track sale performance', () => {
      cy.request({
        method: 'GET',
        url: `/api/properties/${propertyId}/sale-analytics`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('list_price');
        expect(response.body).to.have.property('sold_price');
        expect(response.body).to.have.property('price_reduction_percent');
        expect(response.body).to.have.property('days_on_market');
      });
    });

    it('Should compare to market trends', () => {
      cy.request({
        method: 'GET',
        url: `/api/properties/${propertyId}/market-comparison`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('price_per_sqm');
        expect(response.body).to.have.property('neighborhood_average_price_per_sqm');
        expect(response.body).to.have.property('price_above_below_market_percent');
      });
    });
  });
});
