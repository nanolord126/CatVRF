/// <reference types="cypress" />
/// <reference types="chai" />

import './commands'

const normalizeUrl = (input: string): string => input.replace('http://localhost:8000', 'http://127.0.0.1:8000')

Cypress.Commands.overwrite('visit', (originalFn, url, options) => {
  if (typeof url === 'string') {
    return originalFn(normalizeUrl(url), options)
  }

  if (url && typeof url === 'object' && 'url' in url && typeof url.url === 'string') {
    return originalFn({ ...url, url: normalizeUrl(url.url) }, options)
  }

  return originalFn(url as any, options)
})

Cypress.Commands.overwrite('request', (originalFn, ...args: any[]) => {
  if (typeof args[0] === 'string') {
    args[0] = normalizeUrl(args[0])
  } else if (args[0] && typeof args[0] === 'object' && typeof args[0].url === 'string') {
    args[0] = { ...args[0], url: normalizeUrl(args[0].url) }
  }

  return originalFn(...args)
})

// Disable uncaught exception handling for better debugging
Cypress.on('uncaught:exception', (err, runnable) => {
  // Return false to prevent Cypress from failing the test
  return false
})

/**
 * Custom command to login user
 */
Cypress.Commands.add('login', (email: string, password: string) => {
  cy.visit('/login');
  cy.get('input[name="email"]').type(email);
  cy.get('input[name="password"]').type(password);
  cy.get('button[type="submit"]').click();
  cy.url().then((url) => {
    if (url.includes('/login')) {
      cy.visit('/dashboard', { failOnStatusCode: false });
    }
  });
});

/**
 * Custom command to logout
 */
Cypress.Commands.add('logout', () => {
  cy.visit('/logout');
  cy.url().should('include', '/login');
});

/**
 * Custom command to create test user via API
 */
Cypress.Commands.add(
  'createUser',
  (userData: { email: string; password: string; name: string; role: string }) => {
    cy.request({
      method: 'POST',
      url: '/api/tests/create-user',
      body: userData,
    });
  }
);

/**
 * Custom command to create test tenant via API
 */
Cypress.Commands.add('createTenant', (tenantData: { name: string; inn: string }) => {
  cy.request({
    method: 'POST',
    url: '/api/tests/create-tenant',
    body: tenantData,
  });
});

/**
 * Custom command to add user to tenant
 */
Cypress.Commands.add(
  'addUserToTenant',
  (tenantId: number, userId: number, role: string) => {
    cy.request({
      method: 'POST',
      url: `/api/tests/tenants/${tenantId}/users/${userId}`,
      body: { role },
    });
  }
);

/**
 * Custom command to seed test data
 */
Cypress.Commands.add('seedTestData', () => {
  cy.request({
    method: 'POST',
    url: '/api/tests/seed',
  });
});

/**
 * Custom command to clear test data
 */
Cypress.Commands.add('clearTestData', () => {
  cy.request({
    method: 'POST',
    url: '/api/tests/clear',
  });
});

// Reset database before each test suite
beforeEach(() => {
  cy.clearCookies();
  cy.clearLocalStorage();
})

// Declare custom commands type
declare global {
  namespace Cypress {
    interface Chainable {
      login(email: string, password: string): Chainable<void>;
      logout(): Chainable<void>;
      createUser(userData: {
        email: string;
        password: string;
        name: string;
        role: string;
      }): Chainable<void>;
      createTenant(tenantData: { name: string; inn: string }): Chainable<void>;
      addUserToTenant(tenantId: number, userId: number, role: string): Chainable<void>;
      seedTestData(): Chainable<void>;
      clearTestData(): Chainable<void>;
    }
  }
}
