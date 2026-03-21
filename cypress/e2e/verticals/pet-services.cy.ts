declare(strict_types=1);

describe('Pet Services & Pet Shops (Pet Vertical)', () => {
  beforeEach(() => {
    cy.login({ email: 'business@pet.test', password: 'password' });
    cy.visit('/app/pet');
  });

  describe('Pet Shop Management', () => {
    it('Should add pet products to catalog', () => {
      cy.get('button:contains("Add Product")').click();
      cy.get('input[name="product_name"]').type('Premium Dog Food');
      cy.get('input[name="price"]').type('1999');
      cy.get('select[name="pet_type"]').select('dog');
      cy.get('button:contains("Save")').click();
      cy.contains('Product added').should('be.visible');
    });

    it('Should process pet product orders', () => {
      cy.visit('/marketplace/pet');
      cy.get('[data-test="product-card"]').first().click();
      cy.get('input[name="quantity"]').type('2');
      cy.get('button:contains("Add to Cart")').click();
      cy.get('button:contains("Checkout")').click();
      cy.wait(500);
      cy.contains('Order created').should('be.visible');
    });
  });

  describe('Pet Grooming Services', () => {
    it('Should book grooming appointment', () => {
      cy.visit('/marketplace/pet');
      cy.get('button:contains("Book Grooming")').click();
      cy.get('select[name="pet_type"]').select('dog');
      cy.get('input[name="date"]').type('2026-03-25');
      cy.get('button:contains("Confirm")').click();
      cy.wait(500);
      cy.contains('Grooming appointment confirmed').should('be.visible');
    });
  });
});
