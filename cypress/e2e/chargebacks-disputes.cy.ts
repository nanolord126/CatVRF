describe('Chargebacks & Disputes (Чарджбеки и споры)', () => {
  const baseUrl = 'http://localhost:8000';
  const businessEmail = 'disputes-business@test.com';
  const customerEmail = 'disputes-customer@test.com';
  const password = 'password';

  describe('Chargeback Management', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should receive and display chargeback notification', () => {
      cy.visit(`${baseUrl}/tenant/chargebacks`);
      cy.get('[data-test="chargeback-notification"]').should('be.visible');
      cy.contains('Chargeback Received').should('be.visible');
      cy.get('[data-test="chargeback-amount"]').should('contain', '₽');
    });

    it('Should view chargeback details and reason', () => {
      cy.visit(`${baseUrl}/tenant/chargebacks`);
      cy.get('[data-test="chargeback-item"]').first().click();
      cy.wait(300);
      cy.contains('Chargeback Details').should('be.visible');
      cy.get('[data-test="reason-code"]').should('contain', '4855');
      cy.get('[data-test="dispute-deadline"]').should('be.visible');
    });

    it('Should upload evidence for chargeback defense', () => {
      cy.visit(`${baseUrl}/tenant/chargebacks/1`);
      cy.contains('button', 'Upload Evidence').click();
      cy.wait(300);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/delivery-proof.pdf', { force: true });
      cy.wait(500);
      cy.contains('button', 'Submit').click();
      cy.wait(500);
      cy.contains('Evidence uploaded').should('be.visible');
    });

    it('Should submit representment with documentation', () => {
      cy.visit(`${baseUrl}/tenant/chargebacks/1`);
      cy.contains('button', 'File Representment').click();
      cy.wait(300);
      cy.get('textarea[name="explanation"]').type('Customer received goods as ordered. Tracking shows delivery confirmation.');
      cy.get('input[type="file"]').selectFile('cypress/fixtures/invoice.pdf', { force: true });
      cy.wait(500);
      cy.contains('button', 'Submit').click();
      cy.wait(500);
      cy.contains('Representment submitted').should('be.visible');
    });

    it('Should track chargeback status progression', () => {
      cy.visit(`${baseUrl}/tenant/chargebacks/1`);
      cy.get('[data-test="status-timeline"]').should('be.visible');
      cy.get('[data-test="status-received"]').should('have.class', 'completed');
      cy.get('[data-test="status-evidence-due"]').should('be.visible');
    });

    it('Should set chargeback dispute category', () => {
      cy.visit(`${baseUrl}/tenant/chargebacks/1/edit`);
      cy.get('select[name="dispute_category"]').select('Authorization');
      cy.get('select[name="subcategory"]').select('Card Not Present');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Category updated').should('be.visible');
    });
  });

  describe('Customer Dispute Initiation', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(customerEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should allow customer to dispute transaction', () => {
      cy.visit(`${baseUrl}/app/orders/1`);
      cy.contains('button', 'Dispute Payment').click();
      cy.wait(300);
      cy.get('select[name="reason"]').select('Product Not Received');
      cy.get('textarea[name="description"]').type('Order never arrived');
      cy.contains('button', 'Submit Dispute').click();
      cy.wait(500);
      cy.contains('Dispute submitted').should('be.visible');
    });

    it('Should show dispute timeline to customer', () => {
      cy.visit(`${baseUrl}/app/disputes`);
      cy.get('[data-test="dispute-item"]').first().click();
      cy.wait(300);
      cy.contains('Dispute Timeline').should('be.visible');
      cy.get('[data-test="timeline-entry"]').should('have.length.greaterThan', 0);
    });
  });

  describe('Chargeback Reversal', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should show chargeback won notification', () => {
      cy.visit(`${baseUrl}/tenant/chargebacks`);
      cy.get('[data-test="chargeback-won"]').should('be.visible');
      cy.contains('Chargeback Reversal - Fund Credited').should('be.visible');
    });

    it('Should refund amount to business account after chargeback reversal', () => {
      cy.visit(`${baseUrl}/tenant/chargebacks/1`);
      cy.get('[data-test="status"]').should('contain', 'Won');
      cy.get('[data-test="refund-status"]').should('contain', 'Credited');
      cy.get('[data-test="refund-date"]').should('be.visible');
    });
  });

  describe('Dispute Prevention', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should show dispute prevention tips based on chargeback reasons', () => {
      cy.visit(`${baseUrl}/tenant/chargebacks/1`);
      cy.get('[data-test="prevention-tips"]').should('be.visible');
      cy.contains('Require signature on delivery').should('be.visible');
    });

    it('Should track chargeback metrics and trends', () => {
      cy.visit(`${baseUrl}/tenant/chargeback-analytics`);
      cy.contains('Chargeback Rate').should('be.visible');
      cy.get('[data-test="chargeback-rate"]').should('contain', '%');
      cy.get('[data-test="top-reason"]').should('be.visible');
    });
  });
});
