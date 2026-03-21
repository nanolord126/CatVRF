declare(strict_types=1);

describe('Home Services & Maintenance (HomeServices Vertical)', () => {
  beforeEach(() => {
    cy.login({ email: 'business@homeservices.test', password: 'password' });
    cy.visit('/app/homeservices');
  });

  describe('Service Provider Management', () => {
    it('Should register service provider', () => {
      cy.get('button:contains("Become Provider")').click();
      cy.get('input[name="company_name"]').type('Elite Plumbing Co');
      cy.get('input[name="service_type"]').select('plumbing');
      cy.get('button:contains("Register")').click();
      cy.contains('Registration submitted').should('be.visible');
    });
  });

  describe('Service Request', () => {
    it('Should create service request', () => {
      cy.visit('/marketplace/homeservices');
      cy.get('button:contains("Request Service")').click();
      cy.get('select[name="service_type"]').select('plumbing');
      cy.get('textarea[name="issue_description"]').type('Leaking faucet');
      cy.get('input[name="address"]').type('123 Main St, Moscow');
      cy.get('button:contains("Submit Request")').click();
      cy.contains('Request created').should('be.visible');
    });

    it('Should show nearby available providers', () => {
      cy.contains('Available Providers').within(() => {
        cy.get('[data-test="provider-card"]').should('have.length.greaterThan', 0);
      });
    });
  });

  describe('Payment & Billing', () => {
    it('Should process payment after service completion', () => {
      cy.get('[data-test="service-card"]').first().click();
      cy.contains('Service completed').within(() => {
        cy.get('button:contains("Pay Now")').click();
      });
      cy.wait(1000);
      cy.contains('Payment successful').should('be.visible');
    });
  });
});
