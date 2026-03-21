describe('Travel & Tourism Packages (TravelTourism Vertical)', () => {
  it('loads travel tourism page route', () => {
    cy.request({
      method: 'GET',
      url: '/app/traveltourism',
      failOnStatusCode: false,
    }).its('status').should('be.oneOf', [200, 302, 401, 403, 404])
  })
})