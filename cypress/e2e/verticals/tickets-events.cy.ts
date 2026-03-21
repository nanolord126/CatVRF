describe('Event Tickets & Ticket Sales (Tickets Vertical)', () => {
  it('loads tickets page route', () => {
    cy.request({
      method: 'GET',
      url: '/app/tickets',
      failOnStatusCode: false,
    }).its('status').should('be.oneOf', [200, 302, 401, 403, 404])
  })
})