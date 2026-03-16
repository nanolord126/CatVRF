/// <reference types="cypress" />

import './commands'

// Mount Vue components
import { mount } from 'cypress/vue'

Cypress.Commands.add('mount', mount)

declare global {
  namespace Cypress {
    interface Chainable {
      mount: typeof mount
    }
  }
}

export {}
