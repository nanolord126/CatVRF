declare(strict_types=1);

describe('Hotels & Accommodations Management (Hotels Vertical)', () => {
  beforeEach(() => {
    cy.login({ email: 'business@hotels.test', password: 'password' });
    cy.visit('/app/hotels');
  });

  describe('Hotel Setup & Management', () => {
    it('Should create hotel with basic information', () => {
      cy.get('button:contains("Add Hotel")').click();
      cy.get('input[name="hotel_name"]').type('Luxury Grand Hotel');
      cy.get('input[name="city"]').type('Moscow');
      cy.get('input[name="address"]').type('Red Square 1');
      cy.get('button:contains("Save")').click();
      cy.contains('Hotel created').should('be.visible');
    });

    it('Should add hotel rooms', () => {
      cy.get('[data-test="hotel-card"]').first().click();
      cy.get('button:contains("Add Room")').click();
      cy.get('input[name="room_number"]').type('101');
      cy.get('select[name="room_type"]').select('double');
      cy.get('input[name="price_per_night"]').type('5999');
      cy.get('button:contains("Save")').click();
      cy.contains('Room added').should('be.visible');
    });
  });

  describe('Booking & Reservations', () => {
    it('Should create booking with deposit hold', () => {
      cy.visit('/marketplace/hotels');
      cy.get('[data-test="hotel-card"]').first().click();
      cy.get('input[name="check_in"]').type('2026-04-01');
      cy.get('input[name="check_out"]').type('2026-04-05');
      cy.get('button:contains("Search")').click();
      cy.get('[data-test="room-option"]').first().click();
      cy.get('button:contains("Book Now")').click();
      cy.wait(500);
      cy.contains('Booking confirmed').should('be.visible');
    });

    it('Should prevent double-booking of rooms', () => {
      cy.visit('/marketplace/hotels');
      cy.get('[data-test="hotel-card"]').first().click();
      cy.get('input[name="check_in"]').type('2026-04-01');
      cy.get('input[name="check_out"]').type('2026-04-05');
      cy.get('button:contains("Search")').click();
      cy.get('[data-test="room-option"]').first().click();
      cy.contains('not available').should('be.visible');
    });
  });

  describe('Check-In & Check-Out', () => {
    it('Should verify guest during check-in', () => {
      cy.get('[data-test="active-booking"]').click();
      cy.get('button:contains("Check In")').click();
      cy.get('input[name="guest_id_type"]').select('passport');
      cy.get('button:contains("Confirm")').click();
      cy.contains('Check-in completed').should('be.visible');
    });

    it('Should settle payment at check-out', () => {
      cy.get('[data-test="booking-card"]').first().click();
      cy.get('button:contains("Check Out")').click();
      cy.get('[data-test="final-bill"]').should('be.visible');
      cy.get('button:contains("Process Payment")').click();
      cy.contains('Payment processed').should('be.visible');
    });
  });
});
