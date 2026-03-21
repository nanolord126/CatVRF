describe('ML & AI Services Tests (ML и AI сервисы)', () => {
  const baseUrl = 'http://localhost:8000';
  const businessEmail = 'ml-ai-business@test.com';
  const customerEmail = 'ml-ai-customer@test.com';
  const password = 'password';

  describe('Recommendation Service ML', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(customerEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should generate personalized recommendations', () => {
      cy.visit(`${baseUrl}/app`);
      cy.wait(1000);
      cy.contains('Recommended for you').should('be.visible');
      cy.get('[data-test="recommendation-item"]').should('have.length.greaterThan', 0);
    });

    it('Should display recommendation confidence score', () => {
      cy.visit(`${baseUrl}/app`);
      cy.get('[data-test="recommendation-item"]').first().within(() => {
        cy.get('[data-test="confidence-score"]').should('be.visible');
        cy.get('[data-test="confidence-score"]').then(($el) => {
          const score = parseFloat($el.text());
          expect(score).to.be.within(0, 100);
        });
      });
    });

    it('Should use location-based recommendations', () => {
      cy.visit(`${baseUrl}/app?geo=true`);
      cy.wait(500);
      cy.contains('Nearby').should('be.visible');
      cy.get('[data-test="recommendation-item"]').each(($el) => {
        cy.wrap($el).should('contain', 'km away');
      });
    });

    it('Should improve recommendations based on user behavior', () => {
      cy.visit(`${baseUrl}/app`);
      cy.get('[data-test="recommendation-item"]').first().click();
      cy.wait(300);
      cy.visit(`${baseUrl}/app`);
      cy.wait(500);
      cy.get('[data-test="recommendation-item"]').should('have.length.greaterThan', 0);
    });

    it('Should provide cross-vertical recommendations', () => {
      cy.visit(`${baseUrl}/app/services/beauty`);
      cy.contains('You might also like').should('be.visible');
      cy.get('[data-test="cross-vertical-recommendation"]').should('contain.text', 'Restaurant');
    });
  });

  describe('Fraud Detection ML Service', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should calculate fraud score on payment', () => {
      cy.visit(`${baseUrl}/app/checkout`);
      cy.wait(500);
      cy.get('[data-test="fraud-score"]').should('be.visible');
      cy.get('[data-test="fraud-score"]').then(($el) => {
        const score = parseFloat($el.text());
        expect(score).to.be.within(0, 1);
      });
    });

    it('Should display fraud risk level', () => {
      cy.visit(`${baseUrl}/app/checkout`);
      cy.get('[data-test="fraud-risk"]').should('be.oneOf', ['Low', 'Medium', 'High']);
    });

    it('Should trigger 3DS on high fraud score', () => {
      cy.visit(`${baseUrl}/app/checkout`);
      cy.wait(500);
      cy.get('[data-test="fraud-score"]').then(($el) => {
        const score = parseFloat($el.text());
        if (score > 0.7) {
          cy.contains('3D Secure verification').should('be.visible');
        }
      });
    });

    it('Should log fraud attempt features', () => {
      cy.visit(`${baseUrl}/tenant/fraud/logs`);
      cy.get('[data-test="fraud-log-entry"]').first().click();
      cy.wait(300);
      cy.contains('Features Used').should('be.visible');
      cy.get('[data-test="feature-item"]').should('have.length.greaterThan', 0);
    });
  });

  describe('Demand Forecast Service', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should display demand forecast chart', () => {
      cy.visit(`${baseUrl}/tenant/inventory/forecast`);
      cy.contains('Demand Forecast').should('be.visible');
      cy.get('[data-test="forecast-chart"]').should('be.visible');
    });

    it('Should show forecast with confidence interval', () => {
      cy.visit(`${baseUrl}/tenant/inventory/forecast`);
      cy.get('[data-test="forecast-upper-bound"]').should('be.visible');
      cy.get('[data-test="forecast-lower-bound"]').should('be.visible');
      cy.get('[data-test="confidence-level"]').should('contain', '%');
    });

    it('Should generate 30-day demand forecast', () => {
      cy.visit(`${baseUrl}/tenant/inventory/forecast`);
      cy.get('[data-test="forecast-date"]').each(($el) => {
        cy.wrap($el).should('be.visible');
      });
    });

    it('Should provide restock recommendations', () => {
      cy.visit(`${baseUrl}/tenant/inventory/forecast`);
      cy.contains('Restock Recommendations').should('be.visible');
      cy.get('[data-test="recommended-item"]').should('have.length.greaterThan', 0);
    });
  });

  describe('Price Optimization ML', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should suggest optimized prices based on demand', () => {
      cy.visit(`${baseUrl}/tenant/products/1/price-optimization`);
      cy.contains('Price Suggestion').should('be.visible');
      cy.get('[data-test="suggested-price"]').should('contain', '₽');
    });

    it('Should show revenue impact of price change', () => {
      cy.visit(`${baseUrl}/tenant/products/1/price-optimization`);
      cy.get('[data-test="revenue-impact"]').should('be.visible');
      cy.get('[data-test="impact-percentage"]').should('contain', '%');
    });

    it('Should display competitor price data', () => {
      cy.visit(`${baseUrl}/tenant/products/1/price-optimization`);
      cy.contains('Competitor Prices').should('be.visible');
      cy.get('[data-test="competitor-price"]').should('have.length.greaterThan', 0);
    });
  });

  describe('AI Anomaly Detection', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should detect anomalies in sales pattern', () => {
      cy.visit(`${baseUrl}/tenant/analytics/anomalies`);
      cy.contains('Anomaly Detection').should('be.visible');
      cy.get('[data-test="anomaly-alert"]').should('have.length.greaterThan', 0);
    });

    it('Should alert on unusual transaction patterns', () => {
      cy.visit(`${baseUrl}/tenant/alerts`);
      cy.get('[data-test="alert-type"]').each(($el) => {
        cy.wrap($el).should('be.oneOf', ['Anomaly', 'Fraud', 'Warning']);
      });
    });

    it('Should provide anomaly explanation', () => {
      cy.visit(`${baseUrl}/tenant/analytics/anomalies`);
      cy.get('[data-test="anomaly-item"]').first().click();
      cy.wait(300);
      cy.contains('Reason:').should('be.visible');
      cy.get('[data-test="anomaly-reason"]').should('not.be.empty');
    });
  });

  describe('ML Model Versions & Management', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should display current ML model versions', () => {
      cy.visit(`${baseUrl}/tenant/ai/models`);
      cy.contains('Model Versions').should('be.visible');
      cy.get('[data-test="model-version"]').should('have.length.greaterThan', 0);
    });

    it('Should show model performance metrics', () => {
      cy.visit(`${baseUrl}/tenant/ai/models`);
      cy.get('[data-test="model-accuracy"]').should('contain', '%');
      cy.get('[data-test="model-precision"]').should('contain', '%');
    });

    it('Should allow toggling model versions', () => {
      cy.visit(`${baseUrl}/tenant/ai/models`);
      cy.get('[data-test="model-version"]').first().within(() => {
        cy.contains('button', 'Use').click();
      });
      cy.wait(500);
      cy.contains('Model switched').should('be.visible');
    });
  });
});
