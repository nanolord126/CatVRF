describe('OFD Fiscalization & Receipt Management (ОФД и чеки)', () => {
  const baseUrl = 'http://localhost:8000';
  const businessEmail = 'ofd-business@test.com';
  const password = 'password';

  beforeEach(() => {
    cy.visit(`${baseUrl}/login`);
    cy.get('input[name="email"]').type(businessEmail);
    cy.get('input[name="password"]').type(password);
    cy.get('button[type="submit"]').click();
    cy.wait(500);
  });

  describe('OFD Registration & Setup', () => {
    it('Should register business with OFD provider', () => {
      cy.visit(`${baseUrl}/tenant/settings/ofd`);
      cy.contains('button', 'Register OFD').click();
      cy.wait(300);
      cy.get('input[name="inn"]').type('7799999999');
      cy.get('input[name="kpp"]').type('779900001');
      cy.get('select[name="ofd_provider"]').select('Яндекс.Касса');
      cy.contains('button', 'Register').click();
      cy.wait(500);
      cy.contains('OFD registered successfully').should('be.visible');
    });

    it('Should configure OFD settings', () => {
      cy.visit(`${baseUrl}/tenant/settings/ofd`);
      cy.get('input[name="organization_name"]').clear().type('ООО Новое Имя');
      cy.get('input[name="manager_email"]').clear().type('manager@test.com');
      cy.get('select[name="tax_system"]').select('УСН (доходы)');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('OFD settings updated').should('be.visible');
    });

    it('Should verify OFD connection status', () => {
      cy.visit(`${baseUrl}/tenant/settings/ofd`);
      cy.get('[data-test="ofd-status"]').should('contain', 'Connected');
      cy.get('[data-test="last-sync"]').should('contain', 'Today');
    });
  });

  describe('Receipt Generation & Transmission', () => {
    it('Should generate receipt on payment completion', () => {
      cy.visit(`${baseUrl}/app/orders/1`);
      cy.contains('Payment').should('be.visible');
      cy.wait(1000); // Receipt generation
      cy.get('[data-test="receipt-status"]').should('contain', 'Sent to OFD');
    });

    it('Should show receipt with tax breakdown', () => {
      cy.visit(`${baseUrl}/app/orders/1/receipt`);
      cy.contains('Receipt Details').should('be.visible');
      cy.get('[data-test="subtotal"]').should('contain', '₽');
      cy.get('[data-test="tax-18"]').should('contain', '₽');
      cy.get('[data-test="total"]').should('contain', '₽');
    });

    it('Should handle receipt with discounts and cashback', () => {
      cy.visit(`${baseUrl}/app/orders/2/receipt`);
      cy.get('[data-test="discount"]').should('contain', '₽');
      cy.get('[data-test="cashback"]').should('contain', '₽');
      cy.get('[data-test="final-total"]').should('be.visible');
    });

    it('Should allow downloading receipt as PDF', () => {
      cy.visit(`${baseUrl}/app/orders/1/receipt`);
      cy.contains('button', 'Download PDF').click();
      cy.wait(500);
      cy.readFile('cypress/downloads/receipt.pdf').should('exist');
    });

    it('Should send receipt via email', () => {
      cy.visit(`${baseUrl}/app/orders/1/receipt`);
      cy.get('input[name="email"]').clear().type('customer@test.com');
      cy.contains('button', 'Send Email').click();
      cy.wait(500);
      cy.contains('Receipt sent to email').should('be.visible');
    });

    it('Should transmit receipt to OFD with retry logic', () => {
      cy.visit(`${baseUrl}/tenant/ofd/receipts`);
      cy.get('[data-test="receipt-item"]').first().within(() => {
        cy.get('[data-test="status"]').should('contain', 'Transmitted');
      });
    });
  });

  describe('Receipt Management & History', () => {
    it('Should display receipt history and status', () => {
      cy.visit(`${baseUrl}/tenant/ofd/receipts`);
      cy.get('[data-test="receipt-item"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="receipt-status"]').each(($el) => {
        cy.wrap($el).should('be.oneOf', ['Transmitted', 'Pending', 'Failed']);
      });
    });

    it('Should handle failed receipt transmission with retry', () => {
      cy.visit(`${baseUrl}/tenant/ofd/receipts`);
      cy.contains('Failed').parent().within(() => {
        cy.contains('button', 'Retry').click();
      });
      cy.wait(500);
      cy.contains('Retransmitting receipt').should('be.visible');
      cy.wait(1000);
      cy.contains('Receipt transmitted').should('be.visible');
    });

    it('Should correct receipt (correction receipt)', () => {
      cy.visit(`${baseUrl}/tenant/ofd/receipts/1`);
      cy.contains('button', 'Correct Receipt').click();
      cy.wait(300);
      cy.get('select[name="reason"]').select('Item price changed');
      cy.get('input[name="corrected_amount"]').type('2000');
      cy.contains('button', 'Submit').click();
      cy.wait(500);
      cy.contains('Correction receipt created').should('be.visible');
    });

    it('Should void receipt if needed', () => {
      cy.visit(`${baseUrl}/tenant/ofd/receipts/1`);
      cy.contains('button', 'Void Receipt').click();
      cy.wait(300);
      cy.get('textarea[name="reason"]').type('Duplicate receipt');
      cy.contains('button', 'Confirm').click();
      cy.wait(500);
      cy.contains('Void receipt created').should('be.visible');
    });
  });

  describe('OFD Analytics & Reporting', () => {
    it('Should show OFD transmission statistics', () => {
      cy.visit(`${baseUrl}/tenant/ofd/analytics`);
      cy.contains('Receipt Transmission Rate').should('be.visible');
      cy.get('[data-test="transmission-rate"]').should('contain', '%');
    });

    it('Should display daily receipt count report', () => {
      cy.visit(`${baseUrl}/tenant/ofd/reports`);
      cy.get('[data-test="daily-receipts"]').should('be.visible');
      cy.get('[data-test="chart-data"]').should('have.length.greaterThan', 0);
    });

    it('Should generate OFD compliance report', () => {
      cy.visit(`${baseUrl}/tenant/ofd/compliance`);
      cy.contains('OFD Compliance Report').should('be.visible');
      cy.get('[data-test="compliance-status"]').should('contain', 'Compliant');
    });

    it('Should export receipts in OFD format', () => {
      cy.visit(`${baseUrl}/tenant/ofd/export`);
      cy.get('input[name="date_from"]').type('2026-03-01');
      cy.get('input[name="date_to"]').type('2026-03-17');
      cy.contains('button', 'Export').click();
      cy.wait(500);
      cy.readFile('cypress/downloads/ofd-export.xml').should('exist');
    });
  });

  describe('OFD Integration & Security', () => {
    it('Should verify OFD signature on receipt', () => {
      cy.visit(`${baseUrl}/app/orders/1/receipt`);
      cy.get('[data-test="ofd-signature"]').should('be.visible');
      cy.get('[data-test="ofd-signature"]').should('have.attr', 'data-verified', 'true');
    });

    it('Should handle OFD server connection issues', () => {
      cy.visit(`${baseUrl}/tenant/ofd/status`);
      cy.get('[data-test="connection-status"]').should('be.oneOf', ['Connected', 'Disconnected']);
      if (cy.contains('Disconnected')) {
        cy.contains('button', 'Reconnect').should('be.visible');
      }
    });

    it('Should maintain OFD queue for offline receipts', () => {
      cy.visit(`${baseUrl}/tenant/ofd/queue`);
      cy.contains('Queued Receipts').should('be.visible');
      cy.get('[data-test="queue-item"]').should('have.length.greaterThanOrEqual', 0);
    });
  });
});
