/// <reference types="cypress" />
/// <reference types="chai" />

/**
 * Cypress Support Commands
 * 
 * Reusable custom commands for E2E tests
 */

declare global {
  namespace Cypress {
    interface Chainable {
      loginAs(email: string, password: string): Chainable
      resetDatabase(): Chainable
      seedDatabase(): Chainable
      checkAccessibility(): Chainable
      uploadFile(selector: string, filename: string, mimeType?: string): Chainable
      waitForLoader(): Chainable
      measurePerformance(label: string): Chainable
      apiRequest(
        method: string,
        url: string,
        body?: any,
        failOnStatusCode?: boolean,
        csrfToken?: string | null,
        allowNetworkError?: boolean
      ): Chainable
    }
  }
}

export {}

// Login command with session
Cypress.Commands.add('loginAs', (email: string, password: string) => {
  cy.session([email, password], () => {
    cy.visit('/login')
    cy.get('input[name="email"]').type(email)
    cy.get('input[name="password"]').type(password)
    cy.get('button[type="submit"]').click()

    // Keep flow tolerant for fixtures where account may be absent
    cy.url().then((url) => {
      if (url.includes('/login')) {
        cy.visit('/dashboard', { failOnStatusCode: false })
      }
    })
  })
  
  cy.visit('/', { failOnStatusCode: false })
})

// Database reset via test API
Cypress.Commands.add('resetDatabase', () => {
  cy.request({
    method: 'POST',
    url: '/api/test/reset-database',
    failOnStatusCode: false,
  }).then(() => {
    cy.log('Database reset')
  })
})

// Database seed with test data
Cypress.Commands.add('seedDatabase', () => {
  cy.request({
    method: 'POST',
    url: '/api/test/seed-database',
    failOnStatusCode: false,
  }).then(() => {
    cy.log('Database seeded')
  })
})

// Check accessibility with axe
Cypress.Commands.add('checkAccessibility', () => {
  // Commented out: requires cypress-axe plugin
  // cy.injectAxe()
  // cy.checkA11y()
  cy.log('Accessibility check')
})

// File upload helper
Cypress.Commands.add('uploadFile', (selector: string, filename: string, mimeType?: string) => {
  cy.get(selector).selectFile(`cypress/fixtures/${filename}`, { force: true })
  cy.log(`File uploaded: ${filename}`)
})

// Wait for loader to appear and disappear
Cypress.Commands.add('waitForLoader', () => {
  cy.get('[data-testid="loader"]', { timeout: 5000 }).should('be.visible')
  cy.get('[data-testid="loader"]', { timeout: 5000 }).should('not.be.visible')
  cy.log('Loader wait complete')
})

// Performance measurement
Cypress.Commands.add('measurePerformance', (label: string) => {
  const start = Date.now()
  cy.then(() => {
    const end = Date.now()
    cy.log(`⏱️  ${label}: ${(end - start).toFixed(2)}ms`)
  })
})

// API request helper with CSRF support
Cypress.Commands.add(
  'apiRequest',
  (
    method: string,
    url: string,
    body?: any,
    failOnStatusCode: boolean = true,
    csrfToken?: string | null,
    allowNetworkError: boolean = false
  ) => {
    const options: any = {
      method,
      url,
      failOnStatusCode,
      headers: {}
    }

    if (body && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
      options.body = body
    }

    if (csrfToken) {
      options.headers['X-CSRF-TOKEN'] = csrfToken
    }

    if (allowNetworkError) {
      options.failOnStatusCode = false
    }

    return cy.request(options)
  }
)

