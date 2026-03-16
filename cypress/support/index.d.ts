/// <reference types="cypress" />
/// <reference types="chai" />

declare global {
  namespace Cypress {
    interface Chainable {
      loginAs(email: string, password: string): Chainable<void>
      resetDatabase(): Chainable<void>
      seedDatabase(): Chainable<void>
      checkAccessibility(): Chainable<void>
      uploadFile(selector: string, filename: string, mimeType?: string): Chainable<void>
      waitForLoader(): Chainable<void>
      measurePerformance(label: string): Chainable<void>
      clock(): Chainable<Clock>
      intercept(method: string, url: string | RegExp, response?: any): Chainable<void>
    }

    interface Clock {
      tick(milliseconds: number): void
    }
  }
}

export {}
