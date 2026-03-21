declare(strict_types=1);

describe('Entertainment & Events Platform (Entertainment Vertical)', () => {
  beforeEach(() => {
    cy.login({ email: 'business@entertainment.test', password: 'password' });
    cy.visit('/app/entertainment');
  });

  describe('Event Creation & Management', () => {
    it('Should create event with title, date, venue, capacity', () => {
      cy.get('button:contains("Create Event")').click();
      cy.get('input[name="title"]').type('Summer Music Festival 2026');
      cy.get('textarea[name="description"]').type('Three-day outdoor music festival');
      cy.get('input[name="event_date"]').type('2026-06-15');
      cy.get('input[name="capacity"]').type('5000');
      cy.get('input[name="venue_name"]').type('Central Park Amphitheater');
      cy.get('input[name="ticket_price"]').type('2999');
      cy.get('button:contains("Save")').click();
      cy.contains('Event created successfully').should('be.visible');
    });

    it('Should display event in marketplace', () => {
      cy.visit('/marketplace/entertainment');
      cy.get('[data-test="event-card"]').should('be.visible');
      cy.contains('Summer Music Festival').should('be.visible');
    });

    it('Should track available and sold tickets', () => {
      cy.get('[data-test="event-card"]').first().click();
      cy.get('[data-test="available-tickets"]').contains(/\d+/).should('be.visible');
      cy.get('[data-test="ticket-progress"]').should('be.visible');
    });
  });

  describe('Ticket Booking', () => {
    it('Should purchase event tickets with payment hold', () => {
      cy.visit('/marketplace/entertainment');
      cy.get('[data-test="event-card"]').first().click();
      cy.get('button:contains("Buy Tickets")').click();
      cy.get('input[name="quantity"]').type('2');
      cy.get('button:contains("Confirm Purchase")').click();
      cy.wait(500);
      cy.contains('Payment processing').should('be.visible');
      cy.contains('Tickets purchased').should('be.visible');
    });

    it('Should generate QR codes for tickets', () => {
      cy.get('[data-test="ticket-card"]').first().click();
      cy.get('[data-test="ticket-qr"]').should('be.visible');
    });
  });
});
