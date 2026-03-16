import { defineConfig } from 'cypress'

export default defineConfig({
  projectId: 'catvrf-e2e',
  
  e2e: {
    baseUrl: 'http://localhost:8000',
    viewportWidth: 1280,
    viewportHeight: 720,
    defaultCommandTimeout: 10000,
    requestTimeout: 10000,
    responseTimeout: 10000,
    specPattern: 'cypress/e2e/**/*.cy.ts',
    supportFile: 'cypress/support/e2e.ts',
    screenshotOnRunFailure: true,
    video: true,
    videoCompression: 32,
    videosFolder: 'cypress/videos',
    screenshotsFolder: 'cypress/screenshots',
    downloadsFolder: 'cypress/downloads',

    env: {
      apiUrl: 'http://localhost:8000/api',
      adminUser: 'admin@kotvrf.ru',
      adminPassword: 'password123',
      managerUser: 'manager@kotvrf.ru',
      managerPassword: 'password123',
      viewerUser: 'viewer@kotvrf.ru',
      viewerPassword: 'password123',
    },

    setupNodeEvents(on: Cypress.PluginEvents, config: Cypress.PluginConfigOptions) {
      // Database reset plugin
      on('task', {
        resetDatabase() {
          // Reset database before each test suite
          return null
        },
        seedDatabase() {
          // Seed test data
          return null
        },
      })

      return config
    },
  },

  component: {
    devServer: {
      framework: 'vue',
      bundler: 'vite',
    },
    specPattern: 'cypress/component/**/*.cy.ts',
    supportFile: 'cypress/support/component.ts',
  },
})
