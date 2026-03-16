/// <reference types="cypress" />
/// <reference types="chai" />

import './commands'

// Disable uncaught exception handling for better debugging
Cypress.on('uncaught:exception', (err, runnable) => {
  // Return false to prevent Cypress from failing the test
  return false
})

// Reset database before each test suite
beforeEach(() => {
  cy.resetDatabase()
})

// Screenshot on failure
afterEach(() => {
  cy.screenshot()
})
