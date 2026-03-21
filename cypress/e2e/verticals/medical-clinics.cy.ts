declare(strict_types=1);

describe('Medical Services & Clinics (Medical Vertical)', () => {
  beforeEach(() => {
    cy.login({ email: 'business@medical.test', password: 'password' });
    cy.visit('/app/medical');
  });

  describe('Doctor Appointments', () => {
    it('Should schedule doctor appointment', () => {
      cy.visit('/marketplace/medical');
      cy.get('button:contains("Book Doctor")').click();
      cy.get('select[name="specialty"]').select('cardiology');
      cy.get('input[name="date"]').type('2026-03-25');
      cy.get('input[name="time"]').type('10:00');
      cy.get('button:contains("Confirm")').click();
      cy.wait(500);
      cy.contains('Appointment confirmed').should('be.visible');
    });

    it('Should verify doctor credentials', () => {
      cy.get('[data-test="doctor-card"]').first().within(() => {
        cy.get('[data-test="license-badge"]').should('be.visible');
        cy.get('[data-test="qualification"]').should('be.visible');
      });
    });
  });

  describe('Payment for Medical Services', () => {
    it('Should process payment for consultation', () => {
      cy.get('[data-test="appointment-card"]').first().click();
      cy.get('button:contains("Pay for Consultation")').click();
      cy.wait(500);
      cy.contains('Payment successful').should('be.visible');
    });
  });
});
