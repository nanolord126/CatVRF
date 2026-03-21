describe('Heatmap Analytics & Geographic Tests (Тепловые карты)', () => {
  const baseUrl = 'http://localhost:8000';
  const businessEmail = 'heatmap-business@test.com';
  const password = 'password';

  beforeEach(() => {
    cy.visit(`${baseUrl}/login`);
    cy.get('input[name="email"]').type(businessEmail);
    cy.get('input[name="password"]').type(password);
    cy.get('button[type="submit"]').click();
    cy.wait(500);
  });

  describe('Heatmap Visualization', () => {
    it('Should display geographic heatmap of customer activity', () => {
      cy.visit(`${baseUrl}/app/analytics/heatmap`);
      cy.get('[data-test="heatmap-container"]').should('be.visible');
      cy.get('[data-test="heatmap-map"]').should('have.attr', 'data-map-initialized', 'true');
      cy.contains('Customer Distribution').should('be.visible');
    });

    it('Should show density gradient colors (cool to hot)', () => {
      cy.visit(`${baseUrl}/app/analytics/heatmap`);
      cy.get('[data-test="heatmap-legend"]').should('be.visible');
      cy.get('[data-test="color-cool"]').should('have.css', 'background-color').and('include', 'blue');
      cy.get('[data-test="color-hot"]').should('have.css', 'background-color').and('include', 'red');
    });

    it('Should filter heatmap by date range', () => {
      cy.visit(`${baseUrl}/app/analytics/heatmap`);
      cy.get('input[name="date_from"]').type('2026-03-01');
      cy.get('input[name="date_to"]').type('2026-03-17');
      cy.wait(500);
      cy.get('[data-test="heatmap-data-points"]').should('have.length.greaterThan', 0);
    });

    it('Should filter heatmap by vertical', () => {
      cy.visit(`${baseUrl}/app/analytics/heatmap`);
      cy.get('select[name="vertical"]').select('Beauty');
      cy.wait(500);
      cy.contains('Beauty Heatmap').should('be.visible');
    });

    it('Should show city-level heatmap with zoom', () => {
      cy.visit(`${baseUrl}/app/analytics/heatmap`);
      cy.get('[data-test="zoom-level"]').should('contain', '1');
      cy.get('[data-test="zoom-in"]').click();
      cy.wait(300);
      cy.get('[data-test="zoom-level"]').should('contain', '2');
      cy.get('[data-test="heatmap-detail"]').should('contain', 'Moscow');
    });

    it('Should display hotspot clusters on heatmap', () => {
      cy.visit(`${baseUrl}/app/analytics/heatmap`);
      cy.wait(1000); // Wait for map initialization
      cy.get('[data-test="hotspot-cluster"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="hotspot-cluster"]').first().should('have.attr', 'data-intensity').and('match', /\d+/);
    });

    it('Should allow exporting heatmap data as CSV', () => {
      cy.visit(`${baseUrl}/app/analytics/heatmap`);
      cy.contains('button', 'Export').click();
      cy.wait(500);
      cy.readFile('cypress/downloads/heatmap.csv').should('exist');
    });

    it('Should display real-time heatmap updates', () => {
      cy.visit(`${baseUrl}/app/analytics/heatmap?real-time=true`);
      cy.wait(1000);
      cy.get('[data-test="heatmap-data-points"]').then(($points1) => {
        const count1 = $points1.length;
        cy.wait(3000); // Wait for new data
        cy.get('[data-test="heatmap-data-points"]').then(($points2) => {
          expect($points2.length).to.be.greaterThanOrEqual(count1);
        });
      });
    });
  });

  describe('Heatmap with Revenue Data', () => {
    it('Should display revenue heatmap by region', () => {
      cy.visit(`${baseUrl}/app/analytics/heatmap?type=revenue`);
      cy.contains('Revenue Distribution').should('be.visible');
      cy.get('[data-test="heatmap-value"]').each(($el) => {
        cy.wrap($el).should('contain', '₽');
      });
    });

    it('Should show top 10 regions by revenue in legend', () => {
      cy.visit(`${baseUrl}/app/analytics/heatmap?type=revenue`);
      cy.get('[data-test="top-regions"]').should('be.visible');
      cy.get('[data-test="region-item"]').should('have.length', 10);
    });

    it('Should compare revenue heatmaps between periods', () => {
      cy.visit(`${baseUrl}/app/analytics/heatmap?type=revenue&compare=true`);
      cy.get('[data-test="heatmap-current"]').should('be.visible');
      cy.get('[data-test="heatmap-previous"]').should('be.visible');
      cy.get('[data-test="comparison-diff"]').should('contain.text', '%');
    });
  });

  describe('Heatmap Performance & Permissions', () => {
    it('Should enforce tenant isolation on heatmap data', () => {
      cy.visit(`${baseUrl}/app/analytics/heatmap`);
      cy.window().then((win) => {
        const tenantId = win.localStorage.getItem('tenant_id');
        cy.get('[data-test="heatmap-tenant-id"]').should('contain', tenantId);
      });
    });

    it('Should cache heatmap data for performance', () => {
      cy.visit(`${baseUrl}/app/analytics/heatmap`);
      cy.wait(1000);
      cy.get('[data-test="cache-status"]').should('contain', 'Using cached data');
    });

    it('Should handle large datasets (10K+ points) without lag', () => {
      cy.visit(`${baseUrl}/app/analytics/heatmap?points=10000`);
      cy.wait(2000);
      cy.get('[data-test="render-time"]').then(($el) => {
        const renderTime = parseInt($el.text());
        expect(renderTime).to.be.lessThan(500); // < 500ms
      });
    });
  });
});
