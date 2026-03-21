describe('Test Transactions & Payment Testing (Тестовые транзакции)', () => {
  const baseUrl = 'http://localhost:8000';
  const businessEmail = 'test-payments@test.com';
  const password = 'password';

  beforeEach(() => {
    cy.visit(`${baseUrl}/login`);
    cy.get('input[name="email"]').type(businessEmail);
    cy.get('input[name="password"]').type(password);
    cy.get('button[type="submit"]').click();
    cy.wait(500);
  });

  describe('Test Payment Scenarios', () => {
    it('Should create test transaction without real payment', () => {
      cy.visit(`${baseUrl}/app/payments/test`);
      cy.get('input[name="amount"]').type('1000');
      cy.get('select[name="gateway"]').select('Tinkoff Test');
      cy.get('input[name="description"]').type('Test transaction');
      cy.contains('button', 'Create Test Payment').click();
      cy.wait(500);
      cy.contains('Test payment created').should('be.visible');
      cy.get('[data-test="payment-status"]').should('contain', 'Completed');
    });

    it('Should process test payment with different card statuses', () => {
      cy.visit(`${baseUrl}/app/payments/test`);
      cy.get('select[name="test_card"]').select('4111111111111111 - Success');
      cy.get('input[name="amount"]').type('5000');
      cy.contains('button', 'Pay').click();
      cy.wait(1000);
      cy.contains('Payment successful').should('be.visible');
    });

    it('Should simulate failed test payment', () => {
      cy.visit(`${baseUrl}/app/payments/test`);
      cy.get('select[name="test_card"]').select('4000002000000010 - Decline');
      cy.get('input[name="amount"]').type('1000');
      cy.contains('button', 'Pay').click();
      cy.wait(500);
      cy.contains('Payment declined').should('be.visible');
      cy.get('[data-test="error-code"]').should('contain', 'DECLINE');
    });

    it('Should handle 3DS test transactions', () => {
      cy.visit(`${baseUrl}/app/payments/test`);
      cy.get('select[name="test_card"]').select('4200000000000000 - 3DS Required');
      cy.get('input[name="amount"]').type('2000');
      cy.contains('button', 'Pay').click();
      cy.wait(500);
      cy.contains('3D Secure verification').should('be.visible');
      cy.get('input[name="otp"]').type('123456');
      cy.contains('button', 'Verify').click();
      cy.wait(500);
      cy.contains('Payment completed').should('be.visible');
    });

    it('Should test payment with hold and capture', () => {
      cy.visit(`${baseUrl}/app/payments/test`);
      cy.get('input[name="amount"]').type('3000');
      cy.get('input[name="description"]').type('Hold test');
      cy.get('input[name="hold"]').check();
      cy.contains('button', 'Create').click();
      cy.wait(500);
      cy.contains('Payment held').should('be.visible');
      cy.contains('button', 'Capture').click();
      cy.wait(500);
      cy.contains('Payment captured').should('be.visible');
    });

    it('Should test payment void/cancel', () => {
      cy.visit(`${baseUrl}/app/payments/test`);
      cy.get('input[name="amount"]').type('1500');
      cy.contains('button', 'Create').click();
      cy.wait(500);
      cy.contains('button', 'Void').click();
      cy.wait(300);
      cy.contains('Payment voided').should('be.visible');
    });
  });

  describe('Test Transaction Scenarios', () => {
    it('Should test recurring payment setup', () => {
      cy.visit(`${baseUrl}/app/payments/test/recurring`);
      cy.get('input[name="amount"]').type('500');
      cy.get('select[name="interval"]').select('Monthly');
      cy.contains('button', 'Setup Recurring').click();
      cy.wait(500);
      cy.contains('Recurring payment setup').should('be.visible');
    });

    it('Should test split payments between multiple parties', () => {
      cy.visit(`${baseUrl}/app/payments/test`);
      cy.get('input[name="amount"]').type('10000');
      cy.contains('button', 'Add Split').click();
      cy.wait(300);
      cy.get('input[name="recipient_1"]').type('recipient1@test.com');
      cy.get('input[name="amount_1"]').type('6000');
      cy.get('input[name="recipient_2"]').type('recipient2@test.com');
      cy.get('input[name="amount_2"]').type('4000');
      cy.contains('button', 'Create').click();
      cy.wait(500);
      cy.contains('Split payment created').should('be.visible');
    });

    it('Should validate idempotency of test payments', () => {
      cy.visit(`${baseUrl}/app/payments/test`);
      cy.get('input[name="amount"]').type('1000');
      const idempotencyKey = `test-${Date.now()}`;
      cy.get('input[name="idempotency_key"]').type(idempotencyKey);
      cy.contains('button', 'Create').click();
      cy.wait(500);
      cy.contains('Payment created').should('be.visible');
      const paymentId1 = cy.get('[data-test="payment-id"]').text();
      
      // Try same payment again
      cy.get('input[name="amount"]').type('1000');
      cy.get('input[name="idempotency_key"]').type(idempotencyKey);
      cy.contains('button', 'Create').click();
      cy.wait(500);
      const paymentId2 = cy.get('[data-test="payment-id"]').text();
      
      expect(paymentId1).to.equal(paymentId2);
    });
  });

  describe('Test Payment Logging & Audit', () => {
    it('Should log all test payment attempts', () => {
      cy.visit(`${baseUrl}/app/payments/test/log`);
      cy.contains('Test Payment Log').should('be.visible');
      cy.get('[data-test="log-entry"]').should('have.length.greaterThan', 0);
    });

    it('Should show test payment details and response', () => {
      cy.visit(`${baseUrl}/app/payments/test/log`);
      cy.get('[data-test="log-entry"]').first().click();
      cy.wait(300);
      cy.contains('Request:').should('be.visible');
      cy.contains('Response:').should('be.visible');
      cy.get('[data-test="response-code"]').should('contain', '200');
    });

    it('Should allow replaying test payments', () => {
      cy.visit(`${baseUrl}/app/payments/test/log`);
      cy.get('[data-test="log-entry"]').first().within(() => {
        cy.contains('button', 'Replay').click();
      });
      cy.wait(500);
      cy.contains('Payment replayed').should('be.visible');
    });
  });
});
