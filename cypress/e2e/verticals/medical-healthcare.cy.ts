declare(strict_types=1);

describe('Medical Healthcare & Wellness (MedicalHealthcare Vertical)', () => {
  beforeEach(() => {
    cy.login({ email: 'business@medicalhealthcare.test', password: 'password' });
    cy.visit('/app/medicalhealthcare');
  });

  describe('Healthcare Services', () => {
    it('Should manage healthcare clinic profile', () => {
      cy.get('button:contains("Create Clinic")').click();
      cy.get('input[name="clinic_name"]').type('Modern Healthcare Center');
      cy.get('textarea[name="description"]').type('Full-service healthcare facility');
      cy.get('button:contains("Save")').click();
      cy.contains('Clinic created').should('be.visible');
    });

    it('Should book healthcare consultation', () => {
      cy.visit('/marketplace/medicalhealthcare');
      cy.get('button:contains("Book Consultation")').click();
      cy.get('select[name="service_type"]').select('General Checkup');
      cy.get('input[name="date"]').type('2026-03-25');
      cy.get('button:contains("Confirm")').click();
      cy.wait(500);
      cy.contains('Consultation booked').should('be.visible');
    });
  });
});
