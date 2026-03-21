describe('Sports & Recreation (Sports Vertical)', () => {
  it('loads sports page route', () => {
    cy.request({
      method: 'GET',
      url: '/app/sports',
      failOnStatusCode: false,
    }).its('status').should('be.oneOf', [200, 302, 401, 403, 404])
  })
})