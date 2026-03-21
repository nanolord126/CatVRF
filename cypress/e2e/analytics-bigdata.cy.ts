describe('Analytics & BigData Tests (Аналитика и BigData)', () => {
  const baseUrl = 'http://localhost:8000';
  const businessEmail = 'analytics-business@test.com';
  const adminEmail = 'admin@test.com';
  const password = 'password';

  describe('Real-time Analytics Dashboard', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should display real-time sales metrics', () => {
      cy.visit(`${baseUrl}/tenant/analytics/dashboard`);
      cy.contains('Today\'s Revenue').should('be.visible');
      cy.get('[data-test="revenue-today"]').should('contain', '₽');
      cy.get('[data-test="revenue-auto-update"]').should('have.attr', 'data-updating', 'true');
    });

    it('Should show real-time conversion rate', () => {
      cy.visit(`${baseUrl}/tenant/analytics/dashboard`);
      cy.contains('Conversion Rate').should('be.visible');
      cy.get('[data-test="conversion-rate"]').should('contain', '%');
    });

    it('Should display active users count', () => {
      cy.visit(`${baseUrl}/tenant/analytics/dashboard`);
      cy.contains('Active Users').should('be.visible');
      cy.get('[data-test="active-users"]').then(($el) => {
        const count = parseInt($el.text());
        expect(count).to.be.greaterThan(0);
      });
    });

    it('Should show real-time order stream', () => {
      cy.visit(`${baseUrl}/tenant/analytics/orders-live`);
      cy.get('[data-test="order-item"]').should('have.length.greaterThan', 0);
      cy.wait(3000);
      cy.get('[data-test="order-item"]').should('have.length.greaterThan', 0);
    });
  });

  describe('Historical Analytics & Reports', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should generate daily revenue report', () => {
      cy.visit(`${baseUrl}/tenant/reports/revenue?period=daily`);
      cy.contains('Daily Revenue Report').should('be.visible');
      cy.get('[data-test="report-data"]').should('have.length.greaterThan', 0);
    });

    it('Should show weekly analytics with trend', () => {
      cy.visit(`${baseUrl}/tenant/analytics/weekly`);
      cy.get('[data-test="trend-arrow"]').should('be.visible');
      cy.get('[data-test="trend-percentage"]').should('contain', '%');
    });

    it('Should display monthly analytics with YoY comparison', () => {
      cy.visit(`${baseUrl}/tenant/analytics/monthly`);
      cy.contains('Year-over-Year').should('be.visible');
      cy.get('[data-test="yoy-comparison"]').should('contain', '%');
    });

    it('Should allow custom date range analytics', () => {
      cy.visit(`${baseUrl}/tenant/analytics`);
      cy.get('input[name="date_from"]').type('2026-01-01');
      cy.get('input[name="date_to"]').type('2026-03-17');
      cy.contains('button', 'Generate Report').click();
      cy.wait(500);
      cy.contains('Report Generated').should('be.visible');
    });
  });

  describe('Cohort Analysis & Retention', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should display cohort analysis table', () => {
      cy.visit(`${baseUrl}/tenant/analytics/cohorts`);
      cy.contains('Cohort Analysis').should('be.visible');
      cy.get('[data-test="cohort-row"]').should('have.length.greaterThan', 0);
    });

    it('Should show customer retention rates', () => {
      cy.visit(`${baseUrl}/tenant/analytics/retention`);
      cy.get('[data-test="retention-day-1"]').should('contain', '%');
      cy.get('[data-test="retention-day-7"]').should('contain', '%');
      cy.get('[data-test="retention-day-30"]').should('contain', '%');
    });

    it('Should display churn rate analysis', () => {
      cy.visit(`${baseUrl}/tenant/analytics/churn`);
      cy.contains('Churn Rate').should('be.visible');
      cy.get('[data-test="churn-percentage"]').should('contain', '%');
    });

    it('Should calculate Customer Lifetime Value (LTV)', () => {
      cy.visit(`${baseUrl}/tenant/analytics/ltv`);
      cy.contains('Customer Lifetime Value').should('be.visible');
      cy.get('[data-test="ltv-value"]').should('contain', '₽');
    });
  });

  describe('BigData Export & Integration', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should export data to CSV', () => {
      cy.visit(`${baseUrl}/tenant/analytics/dashboard`);
      cy.contains('button', 'Export').click();
      cy.wait(300);
      cy.get('select[name="format"]').select('CSV');
      cy.contains('button', 'Download').click();
      cy.wait(500);
      cy.readFile('cypress/downloads/analytics.csv').should('exist');
    });

    it('Should export data to Excel', () => {
      cy.visit(`${baseUrl}/tenant/analytics/dashboard`);
      cy.contains('button', 'Export').click();
      cy.wait(300);
      cy.get('select[name="format"]').select('Excel');
      cy.contains('button', 'Download').click();
      cy.wait(500);
      cy.readFile('cypress/downloads/analytics.xlsx').should('exist');
    });

    it('Should stream data to BigQuery', () => {
      cy.visit(`${baseUrl}/tenant/settings/integrations/bigquery`);
      cy.contains('button', 'Connect BigQuery').click();
      cy.wait(500);
      cy.contains('Connected to BigQuery').should('be.visible');
    });

    it('Should sync data to ClickHouse', () => {
      cy.visit(`${baseUrl}/tenant/settings/integrations/clickhouse`);
      cy.get('[data-test="sync-status"]').should('contain', 'Syncing');
      cy.wait(2000);
      cy.get('[data-test="sync-status"]').should('contain', 'Synced');
    });
  });

  describe('Custom Metrics & KPIs', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should create custom metric', () => {
      cy.visit(`${baseUrl}/tenant/analytics/metrics`);
      cy.contains('button', 'Create Metric').click();
      cy.wait(300);
      cy.get('input[name="name"]').type('Average Order Value');
      cy.get('textarea[name="formula"]').type('SUM(revenue) / COUNT(orders)');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Metric created').should('be.visible');
    });

    it('Should track KPI progress to goal', () => {
      cy.visit(`${baseUrl}/tenant/analytics/kpis`);
      cy.get('[data-test="kpi-progress"]').should('be.visible');
      cy.get('[data-test="progress-bar"]').should('have.attr', 'data-value');
    });

    it('Should alert when KPI threshold is crossed', () => {
      cy.visit(`${baseUrl}/tenant/alerts`);
      cy.get('[data-test="kpi-alert"]').should('have.length.greaterThan', 0);
    });
  });

  describe('Advanced Analytics & Segmentation', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should create customer segment', () => {
      cy.visit(`${baseUrl}/tenant/analytics/segments`);
      cy.contains('button', 'Create Segment').click();
      cy.wait(300);
      cy.get('input[name="name"]').type('High-Value Customers');
      cy.get('select[name="criteria"]').select('LTV > 100000');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Segment created').should('be.visible');
    });

    it('Should analyze segment behavior', () => {
      cy.visit(`${baseUrl}/tenant/analytics/segments/1`);
      cy.contains('Segment Analysis').should('be.visible');
      cy.get('[data-test="segment-size"]').should('contain', 'customers');
      cy.get('[data-test="segment-revenue"]').should('contain', '₽');
    });

    it('Should run funnel analysis', () => {
      cy.visit(`${baseUrl}/tenant/analytics/funnel`);
      cy.contains('Funnel Analysis').should('be.visible');
      cy.get('[data-test="funnel-stage"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="funnel-conversion"]').should('contain', '%');
    });

    it('Should perform attribution analysis', () => {
      cy.visit(`${baseUrl}/tenant/analytics/attribution`);
      cy.contains('Attribution Model').should('be.visible');
      cy.get('select[name="model"]').select('Multi-touch');
      cy.wait(500);
      cy.get('[data-test="attribution-result"]').should('be.visible');
    });
  });

  describe('Analytics Performance & Caching', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should cache analytics data for performance', () => {
      cy.visit(`${baseUrl}/tenant/analytics/dashboard`);
      cy.wait(1000);
      cy.visit(`${baseUrl}/tenant/analytics/dashboard`);
      cy.get('[data-test="cache-status"]').should('contain', 'cached');
    });

    it('Should handle large dataset analytics efficiently', () => {
      cy.visit(`${baseUrl}/tenant/analytics?dataset=large`);
      cy.wait(3000);
      cy.get('[data-test="load-time"]').then(($el) => {
        const time = parseFloat($el.text());
        expect(time).to.be.lessThan(5000); // < 5 seconds
      });
    });
  });
});
