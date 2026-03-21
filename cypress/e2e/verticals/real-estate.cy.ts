/// <reference types="cypress" />

describe('Real Estate & Rentals Vertical', () => {
  const tenantId = 4;
  const propertyId = 401;
  const agentId = 501;
  const viewingId = 9001;
  const saleListingId = 10001;

  const realEstateTestData = {
    property: {
      address: 'Москва, ул. Большая Якиманка, д. 14, кв. 25',
      type: 'apartment',
      area: 125,
      rooms: 3,
      floor: 5,
      totalFloors: 12,
      buildYear: 2018,
      condition: 'renovated',
      geoPoint: { lat: 55.7426, lng: 37.6085 },
    },
    sale: {
      price: 1250000000, // копейки (12.5M)
      pricePerSqm: 10000000, // копейки (100K/м²)
      commission: 2.0, // процент
      minOffer: 1200000000,
      condition: 'ready',
    },
    rental: {
      monthlyPrice: 250000, // копейки (2500$/месяц)
      deposit: 250000, // копейки
      minLeaseTerm: 12, // месяцев
      utilities: 'included',
    },
    agent: {
      fullName: 'Иван Александров',
      phone: '+7-900-123-4567',
      email: 'agent@realest.ru',
      licenseNumber: 'REA-7701-2024-001',
      specialization: 'residential',
    },
  };

  before(() => {
    cy.login('agent@example.com', 'password123');
  });

  describe('Property Listing Creation', () => {
    it('Should create sale listing with full details', () => {
      cy.visit('/tenant/properties/create');
      
      cy.get('input[name="address"]').type(realEstateTestData.property.address);
      cy.get('select[name="type"]').select(realEstateTestData.property.type);
      cy.get('input[name="area"]').clear().type(realEstateTestData.property.area.toString());
      cy.get('input[name="rooms"]').clear().type(realEstateTestData.property.rooms.toString());
      cy.get('input[name="floor"]').clear().type(realEstateTestData.property.floor.toString());
      cy.get('input[name="total_floors"]').clear().type(realEstateTestData.property.totalFloors.toString());
      cy.get('input[name="build_year"]').clear().type(realEstateTestData.property.buildYear.toString());
      
      // Upload photos
      cy.get('input[name="photos"]').selectFile([
        'cypress/fixtures/property-1.jpg',
        'cypress/fixtures/property-2.jpg',
        'cypress/fixtures/property-3.jpg',
      ]);
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Свойство создано');
    });

    it('Should create sale listing for property', () => {
      cy.visit(`/tenant/properties/${propertyId}/sale-listing/create`);
      
      cy.get('input[name="sale_price"]').clear().type((realEstateTestData.sale.price / 100000000).toString());
      cy.get('input[name="price_per_sqm"]').clear().type((realEstateTestData.sale.pricePerSqm / 100000).toString());
      cy.get('input[name="commission_percent"]').clear().type(realEstateTestData.sale.commission.toString());
      cy.get('input[name="min_offer"]').clear().type((realEstateTestData.sale.minOffer / 100000000).toString());
      
      cy.get('select[name="condition"]').select(realEstateTestData.sale.condition);
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Объект выставлен на продажу');
    });

    it('Should create rental listing for property', () => {
      cy.visit(`/tenant/properties/${propertyId}/rental-listing/create`);
      
      cy.get('input[name="monthly_price"]').clear().type((realEstateTestData.rental.monthlyPrice / 100000).toString());
      cy.get('input[name="deposit"]').clear().type((realEstateTestData.rental.deposit / 100000).toString());
      cy.get('input[name="min_lease_months"]').clear().type(realEstateTestData.rental.minLeaseTerm.toString());
      cy.get('select[name="utilities"]').select(realEstateTestData.rental.utilities);
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Объект выставлен в аренду');
    });

    it('Should verify listing in database', () => {
      cy.request(`/api/properties/${propertyId}`).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.address).to.equal(realEstateTestData.property.address);
        expect(response.body.type).to.equal(realEstateTestData.property.type);
        expect(response.body.sale_listing).to.exist;
        expect(response.body.rental_listing).to.exist;
      });
    });

    it('Should generate listing URL with SEO-friendly slug', () => {
      cy.request(`/api/properties/${propertyId}/listing`).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('seo_url');
        expect(response.body.seo_url).to.include('moscow');
        expect(response.body.seo_url).to.include(propertyId.toString());
      });
    });
  });

  describe('Virtual Tours & Photos', () => {
    it('Should upload 360-degree photos', () => {
      cy.visit(`/tenant/properties/${propertyId}/photos`);
      
      cy.get('input[name="photo_type"]').select('360');
      cy.get('input[name="photo_file"]').selectFile('cypress/fixtures/property-360.jpg');
      cy.get('input[name="room_name"]').type('Гостиная');
      
      cy.get('button').contains('Загрузить').click();
      cy.get('.filament-notification').should('contain', '360-фото добавлено');
    });

    it('Should display interactive tour on listing', () => {
      cy.logout();
      cy.visit(`/app/properties/${propertyId}`);
      
      cy.get('[data-testid="virtual-tour-button"]').click();
      cy.get('[data-testid="tour-viewer"]').should('be.visible');
      cy.get('[data-testid="room-selector"]').should('be.visible');
    });
  });

  describe('Viewing Appointments', () => {
    it('Should display available viewing slots', () => {
      cy.logout();
      cy.login('customer@example.com', 'password123');
      cy.visit(`/app/properties/${propertyId}`);
      
      cy.get('button').contains('Запросить просмотр').click();
      cy.get('[data-testid="date-picker"]').click();
      cy.get('[data-date="2026-03-20"]').click();
      
      cy.get('[data-testid="time-slot"]').should('have.length.greaterThan', 0);
    });

    it('Should book viewing appointment with idempotency', () => {
      const idempotencyKey = `viewing-${Date.now()}`;
      
      cy.request({
        method: 'POST',
        url: '/api/viewings',
        body: {
          property_id: propertyId,
          client_id: 3001,
          datetime: '2026-03-20T14:00:00',
          agent_id: agentId,
          idempotency_key: idempotencyKey,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        const viewingId = response.body.id;

        // Duplicate request
        cy.request({
          method: 'POST',
          url: '/api/viewings',
          body: {
            property_id: propertyId,
            client_id: 3001,
            datetime: '2026-03-20T14:00:00',
            agent_id: agentId,
            idempotency_key: idempotencyKey,
          },
        }).then(dupResponse => {
          expect(dupResponse.status).to.equal(200); // Already created
          expect(dupResponse.body.id).to.equal(viewingId);
        });
      });
    });

    it('Should send reminder emails before viewing', () => {
      const viewingId = 9001;
      
      cy.request({
        method: 'POST',
        url: `/api/viewings/${viewingId}/send-reminder`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.emails_sent).to.equal(2); // Client + Agent
      });
    });

    it('Should record viewing attendance', () => {
      cy.request({
        method: 'PATCH',
        url: `/api/viewings/${viewingId}`,
        body: {
          status: 'attended',
          notes: 'Интересуется предложением',
          duration_minutes: 30,
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.attended_at).to.exist;
      });
    });
  });

  describe('Agent Management', () => {
    it('Should register agent with license verification', () => {
      cy.visit('/tenant/agents/register');
      
      cy.get('input[name="full_name"]').type(realEstateTestData.agent.fullName);
      cy.get('input[name="phone"]').type(realEstateTestData.agent.phone);
      cy.get('input[name="email"]').type(realEstateTestData.agent.email);
      cy.get('input[name="license_number"]').type(realEstateTestData.agent.licenseNumber);
      cy.get('input[name="license_photo"]').selectFile('cypress/fixtures/license-copy.pdf');
      
      cy.get('button[type="submit"]').click();
      cy.get('.filament-notification').should('contain', 'Агент зарегистрирован');
    });

    it('Should display agent profile on property listing', () => {
      cy.visit(`/app/properties/${propertyId}`);
      
      cy.get('[data-testid="agent-card"]').should('be.visible');
      cy.get('[data-testid="agent-rating"]').should('contain', realEstateTestData.agent.fullName);
      cy.get('[data-testid="agent-phone"]').should('contain', realEstateTestData.agent.phone);
    });

    it('Should track agent performance metrics', () => {
      cy.request({
        method: 'GET',
        url: `/api/agents/${agentId}/performance`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('listings_count');
        expect(response.body).to.have.property('viewings_count');
        expect(response.body).to.have.property('sales_count');
        expect(response.body).to.have.property('average_commission');
      });
    });
  });

  describe('Price Analytics & Heatmap', () => {
    it('Should display interactive price heatmap', () => {
      cy.visit('/app/real-estate/analytics?city=moscow');
      
      cy.get('[data-testid="price-heatmap"]').should('be.visible');
      cy.get('[data-testid="map-legend"]').should('be.visible');
    });

    it('Should calculate price per sqm analytics', () => {
      cy.request({
        method: 'GET',
        url: '/api/properties/analytics/price-per-sqm',
        queryParams: { city: 'moscow', property_type: 'apartment' },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('average_price');
        expect(response.body).to.have.property('median_price');
        expect(response.body).to.have.property('price_trends');
      });
    });

    it('Should suggest optimal price based on market analysis', () => {
      cy.request({
        method: 'POST',
        url: `/api/properties/${propertyId}/price-suggestion`,
        body: {
          market_analysis: true,
          comparable_properties: 10,
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('suggested_price');
        expect(response.body).to.have.property('confidence_level');
      });
    });
  });

  describe('Mortgage & Financing Integration', () => {
    it('Should calculate mortgage options', () => {
      cy.visit(`/app/properties/${propertyId}`);
      
      cy.get('button').contains('Рассчитать ипотеку').click();
      cy.get('input[name="down_payment_percent"]').clear().type('20');
      cy.get('input[name="loan_term_years"]').clear().type('20');
      
      cy.get('button').contains('Рассчитать').click();
      cy.get('[data-testid="monthly-payment"]').should('be.visible');
    });

    it('Should integrate with bank APIs for pre-approval', () => {
      cy.request({
        method: 'POST',
        url: '/api/financing/pre-approval-check',
        body: {
          annual_income: 500000,
          existing_debts: 0,
          property_price: realEstateTestData.sale.price,
          down_payment: Math.floor(realEstateTestData.sale.price * 0.2),
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('pre_approval_eligible');
        expect(response.body).to.have.property('max_loan_amount');
      });
    });
  });

  describe('Transaction Management', () => {
    it('Should record offer from buyer', () => {
      cy.logout();
      cy.login('buyer@example.com', 'password123');
      
      cy.request({
        method: 'POST',
        url: `/api/properties/${propertyId}/make-offer`,
        body: {
          offered_price: realEstateTestData.sale.minOffer,
          comment: 'Интересуюсь данным объектом',
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('offer_id');
        expect(response.body.status).to.equal('pending');
      });
    });

    it('Should notify seller of offer', () => {
      cy.login('seller@example.com', 'password123');
      
      cy.visit('/tenant/properties/offers');
      cy.get('[data-testid="offer-notification"]').should('be.visible');
    });

    it('Should track offer negotiation', () => {
      const offerId = 11001;
      
      cy.request({
        method: 'PATCH',
        url: `/api/offers/${offerId}`,
        body: {
          status: 'accepted',
          accepted_price: realEstateTestData.sale.price,
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.accepted_at).to.exist;
      });
    });

    it('Should hold deposit after offer acceptance', () => {
      const offerId = 11001;
      
      cy.request({
        method: 'POST',
        url: `/api/offers/${offerId}/hold-deposit`,
        body: {
          deposit_amount: Math.floor(realEstateTestData.sale.price * 0.1), // 10%
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.hold_status).to.equal('AUTHORIZED');
      });
    });
  });

  describe('Rental Management', () => {
    it('Should list rental properties separately', () => {
      cy.logout();
      cy.visit('/app/real-estate?listing_type=rental');
      
      cy.get('[data-testid="property-card"]').first().within(() => {
        cy.get('[data-testid="monthly-price"]').should('contain', '₽');
        cy.get('[data-testid="lease-terms"]').should('be.visible');
      });
    });

    it('Should track rental application workflow', () => {
      const rentalListingId = 10001;
      
      cy.request({
        method: 'POST',
        url: `/api/rental-applications`,
        body: {
          rental_listing_id: rentalListingId,
          tenant_id: 3001,
          move_in_date: '2026-04-15',
          lease_term_months: 12,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('application_id');
      });
    });

    it('Should schedule property inspection before move-in', () => {
      const applicationId = 12001;
      
      cy.request({
        method: 'POST',
        url: `/api/rental-applications/${applicationId}/schedule-inspection`,
        body: {
          inspection_date: '2026-04-14',
          inspector_id: agentId,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body.inspection_scheduled).to.be.true;
      });
    });
  });

  describe('Listings API', () => {
    it('Should filter properties by multiple criteria', () => {
      cy.request({
        method: 'GET',
        url: '/api/properties',
        queryParams: {
          city: 'moscow',
          property_type: 'apartment',
          min_price: 1000000000,
          max_price: 1500000000,
          min_area: 100,
          max_area: 150,
          rooms: 3,
          listing_type: 'sale',
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.data).to.be.an('array');
        
        response.body.data.forEach(property => {
          expect(property.type).to.equal('apartment');
          expect(property.rooms).to.equal(3);
        });
      });
    });

    it('Should return results with proper pagination', () => {
      cy.request({
        method: 'GET',
        url: '/api/properties',
        queryParams: { page: 1, per_page: 20 },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('pagination');
        expect(response.body.pagination).to.have.property('current_page');
        expect(response.body.pagination).to.have.property('total_pages');
      });
    });
  });

  describe('Listing Lifecycle', () => {
    it('Should change listing status from active to sold', () => {
      cy.login('agent@example.com', 'password123');
      
      cy.request({
        method: 'PATCH',
        url: `/api/sale-listings/${saleListingId}`,
        body: {
          status: 'sold',
          sold_price: realEstateTestData.sale.price,
          sold_date: new Date().toISOString(),
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('sold');
      });
    });

    it('Should remove listing after marked as sold', () => {
      cy.request({
        method: 'GET',
        url: '/api/properties',
        queryParams: { status: 'sold' },
      }).then(response => {
        expect(response.status).to.equal(200);
        // Sold listings might be hidden from public search
      });
    });
  });
});
