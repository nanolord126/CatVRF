declare(strict_types=1);

describe('Logistics & Delivery Management (Logistics Vertical)', () => {
  beforeEach(() => {
    cy.login({ email: 'business@logistics.test', password: 'password' });
    cy.visit('/app/logistics');
  });

  describe('Shipment Management', () => {
    it('Should create shipment with tracking', () => {
      cy.get('button:contains("Create Shipment")').click();
      cy.get('input[name="from_address"]').type('123 Warehouse St');
      cy.get('input[name="to_address"]').type('456 Delivery Ave');
      cy.get('input[name="weight_kg"]').type('5.5');
      cy.get('button:contains("Save")').click();
      cy.contains('Shipment created').should('be.visible');
    });

    it('Should track shipment real-time', () => {
      cy.get('[data-test="shipment-card"]').first().click();
      cy.get('[data-test="tracking-map"]').should('be.visible');
      cy.get('[data-test="current-status"]').should('contain', 'In Transit');
    });

    it('Should process delivery with signature', () => {
      cy.get('[data-test="shipment-card"]').first().click();
      cy.get('button:contains("Deliver")').click();
      cy.get('canvas[data-test="signature-pad"]').click();
      cy.get('button:contains("Confirm Delivery")').click();
      cy.contains('Delivery completed').should('be.visible');
    });
  });

  describe('Payment for Delivery', () => {
    it('Should charge delivery fee on completion', () => {
      cy.get('[data-test="transaction-row"]').first().within(() => {
        cy.get('[data-test="amount"]').contains('₽').should('be.visible');
      });
    });
  });
});
