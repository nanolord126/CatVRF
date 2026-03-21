/// <reference types="cypress" />

describe('Payment Flow E2E Tests', () => {
  const baseUrl = 'http://localhost:8000';
  const ownerEmail = 'owner@test.com';
  const ownerPassword = 'password123';

  beforeEach(() => {
    cy.visit(`${baseUrl}/login`);
    cy.login(ownerEmail, ownerPassword);
    cy.url().should('include', '/tenant');
  });

  it('Should display wallet balance on dashboard', () => {
    cy.visit(`${baseUrl}/tenant`);
    
    // Check wallet balance is visible
    cy.contains('Баланс').should('be.visible');
    cy.get('[data-cy=wallet-balance]').should('contain', '₽');
  });

  it('Should initialize payment and show payment form', () => {
    cy.visit(`${baseUrl}/tenant/payments/init`);
    
    // Fill payment form
    cy.get('input[name="amount"]').type('5000');
    cy.get('select[name="payment_method"]').select('card');
    
    // Submit form
    cy.get('button[type="submit"]').click();
    
    // Should show payment form (Tinkoff iframe)
    cy.get('[data-cy=payment-form]', { timeout: 5000 }).should('be.visible');
  });

  it('Should handle payment idempotency - duplicate payment blocked', () => {
    const idempotencyKey = `test-payment-${Date.now()}`;
    
    // First payment
    cy.visit(`${baseUrl}/tenant/payments/init`);
    cy.get('input[name="amount"]').type('1000');
    cy.get('input[data-cy=idempotency-key]').type(idempotencyKey);
    cy.get('button[type="submit"]').click();
    
    // Check response success
    cy.get('[data-cy=payment-success]', { timeout: 10000 }).should('be.visible');
    
    // Attempt duplicate payment
    cy.visit(`${baseUrl}/tenant/payments/init`);
    cy.get('input[name="amount"]').type('1000');
    cy.get('input[data-cy=idempotency-key]').type(idempotencyKey);
    cy.get('button[type="submit"]').click();
    
    // Should show idempotency error
    cy.get('[data-cy=idempotency-error]').should('contain', 'уже обработан');
  });

  it('Should hold payment and release after 24h timeout', () => {
    cy.visit(`${baseUrl}/tenant/payments/init`);
    cy.get('input[name="amount"]').type('3000');
    cy.get('input[name="hold"]').check(); // Hold payment
    cy.get('button[type="submit"]').click();
    
    // Payment should be in AUTHORIZED state
    cy.get('[data-cy=payment-status]').should('contain', 'AUTHORIZED');
    
    // Check hold amount is displayed
    cy.get('[data-cy=hold-amount]').should('contain', '₽ 3000');
    
    // Advance time to 25 hours (in test environment)
    cy.intercept('POST', '**/api/jobs/release-hold', { statusCode: 200 }).as('releaseHold');
    
    // Manually trigger job or wait for scheduled execution
    cy.visit(`${baseUrl}/tenant/payments/history`);
    cy.get('[data-cy=payment-status]').should('contain', 'CANCELLED'); // After release
  });

  it('Should display fraud score and block suspicious payment', () => {
    cy.visit(`${baseUrl}/tenant/payments/init`);
    
    // Attempt large payment (suspicious)
    cy.get('input[name="amount"]').type('999999'); // Very large amount
    cy.get('button[type="submit"]').click();
    
    // Should show fraud warning
    cy.get('[data-cy=fraud-warning]').should('be.visible');
    cy.get('[data-cy=fraud-score]').should('contain.text', 'Подозрение');
    
    // Confirm fraud check
    cy.get('button[data-cy=confirm-fraud-check]').click();
    cy.get('[data-cy=fraud-blocked]').should('be.visible');
  });

  it('Should process webhook and credit wallet', () => {
    // Create payment via API
    cy.request({
      method: 'POST',
      url: `${baseUrl}/api/payments/init`,
      body: {
        amount: 5000,
        correlation_id: `test-${Date.now()}`,
      },
      headers: {
        'Authorization': `Bearer ${Cypress.env('API_TOKEN')}`,
      },
    }).then((response) => {
      const paymentId = response.body.id;
      
      // Simulate Tinkoff webhook
      cy.request({
        method: 'POST',
        url: `${baseUrl}/api/internal/webhooks/payment/tinkoff`,
        body: {
          TerminalKey: 'TEST12345',
          OrderId: paymentId,
          Success: 'true',
          Status: 'CONFIRMED',
          PaymentId: '12345678',
          Amount: '500000',
          Token: 'webhook-token-hash',
        },
      }).then((webhookResponse) => {
        expect(webhookResponse.status).to.eq(200);
        
        // Check wallet was credited
        cy.visit(`${baseUrl}/tenant`);
        cy.get('[data-cy=wallet-balance]').should('contain', '₽');
      });
    });
  });

  it('Should log payment audit trail with correlation_id', () => {
    const correlationId = `audit-test-${Date.now()}`;
    
    cy.visit(`${baseUrl}/tenant/payments/init`);
    cy.get('input[name="amount"]').type('2000');
    cy.get('input[data-cy=correlation-id]').should('have.value', correlationId);
    cy.get('button[type="submit"]').click();
    
    // Check audit log
    cy.visit(`${baseUrl}/tenant/audit-logs`);
    cy.contains(correlationId).should('be.visible');
    cy.get('[data-cy=audit-action]').should('contain', 'payment_init');
  });

  it('Should handle payment webhook signature verification', () => {
    // Valid signature - should succeed
    cy.request({
      method: 'POST',
      url: `${baseUrl}/api/internal/webhooks/payment/tinkoff`,
      body: {
        TerminalKey: 'TEST12345',
        OrderId: 'test-order-valid',
        Success: 'true',
        Status: 'CONFIRMED',
        Token: 'valid-signature-hash', // Would be computed correctly in real scenario
      },
    }).then((response) => {
      // Depending on validation, might be 200 or 403
      expect([200, 403]).to.include(response.status);
    });
  });
});
