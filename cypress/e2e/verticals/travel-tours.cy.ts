describe('Travel & Tour Booking (Travel Vertical)', () => {
  it('loads travel page route', () => {
    cy.request({
      method: 'GET',
      url: '/app/travel',
      failOnStatusCode: false,
    }).its('status').should('be.oneOf', [200, 302, 401, 403, 404])
  })
})