declare(strict_types=1);

describe('Pet Services & Veterinary (PetServices Vertical)', () => {
  beforeEach(() => {
    cy.login({ email: 'business@petservices.test', password: 'password' });
    cy.visit('/app/petservices');
  });

  describe('Veterinary Services', () => {
    it('Should create veterinary clinic profile', () => {
      cy.get('button:contains("Create Clinic")').click();
      cy.get('input[name="clinic_name"]').type('Happy Paws Veterinary');
      cy.get('input[name="phone"]').type('+7-495-999-8888');
      cy.get('button:contains("Save")').click();
      cy.contains('Clinic created').should('be.visible');
    });

    it('Should book pet veterinary appointment', () => {
      cy.visit('/marketplace/petservices');
      cy.get('button:contains("Book Vet Visit")').click();
      cy.get('select[name="pet_type"]').select('dog');
      cy.get('input[name="date"]').type('2026-03-25');
      cy.get('textarea[name="symptoms"]').type('Limping on back leg');
      cy.get('button:contains("Confirm")').click();
      cy.wait(500);
      cy.contains('Appointment booked').should('be.visible');
    });
  });
});
