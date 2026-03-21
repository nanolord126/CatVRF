declare(strict_types=1);

describe('Fitness & Gym Management (Fitness Vertical)', () => {
  beforeEach(() => {
    cy.login({ email: 'business@fitness.test', password: 'password' });
    cy.visit('/app/fitness');
  });

  describe('Gym Membership Management', () => {
    it('Should create gym membership plans', () => {
      cy.get('button:contains("Add Plan")').click();
      cy.get('input[name="plan_name"]').type('Premium Membership');
      cy.get('input[name="duration_months"]').type('12');
      cy.get('input[name="price"]').type('9999');
      cy.get('button:contains("Save")').click();
      cy.contains('Plan created').should('be.visible');
    });

    it('Should enroll member in gym with payment hold', () => {
      cy.visit('/marketplace/fitness');
      cy.get('[data-test="gym-card"]').first().click();
      cy.get('button:contains("Join Now")').click();
      cy.get('select[name="membership_plan"]').select('Premium');
      cy.get('button:contains("Confirm Purchase")').click();
      cy.wait(500);
      cy.contains('Membership activated').should('be.visible');
    });
  });

  describe('Class Scheduling', () => {
    it('Should create fitness classes', () => {
      cy.get('button:contains("Add Class")').click();
      cy.get('input[name="class_name"]').type('HIIT Training');
      cy.get('input[name="capacity"]').type('20');
      cy.get('input[name="start_time"]').type('09:00');
      cy.get('button:contains("Save")').click();
      cy.contains('Class created').should('be.visible');
    });

    it('Should prevent overbooking of classes', () => {
      cy.get('[data-test="class-card"]').first().click();
      cy.get('button:contains("Book Class")').click();
      cy.contains('Class is full').should('be.visible');
    });
  });
});
