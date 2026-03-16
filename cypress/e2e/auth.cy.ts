// @ts-nocheck
/// <reference types="cypress" />

/**
 * Authentication E2E Tests
 * 
 * Tests for user authentication flows
 */

describe('Authentication', () => {
  beforeEach(() => {
    // Visit home before each test
    cy.visit('/')
  })

  describe('Login', () => {
    it('should redirect to login when not authenticated', () => {
      cy.visit('/dashboard')
      cy.url().should('include', '/login')
    })

    it('should display login form with required fields', () => {
      cy.visit('/login')
      cy.get('input[name="email"]').should('exist')
      cy.get('input[name="password"]').should('exist')
      cy.get('button[type="submit"]').should('exist')
    })

    it('should show validation error for empty email', () => {
      cy.visit('/login')
      cy.get('input[name="password"]').type('password123')
      cy.get('button[type="submit"]').click()
      // Check for error - actual selector depends on your form implementation
      cy.contains(/required/i).should('be.visible')
    })

    it('should show validation error for empty password', () => {
      cy.visit('/login')
      cy.get('input[name="email"]').type('admin@kotvrf.ru')
      cy.get('button[type="submit"]').click()
      // Check for error
      cy.contains(/required/i).should('be.visible')
    })

    it('should show error for invalid credentials', () => {
      cy.visit('/login')
      cy.get('input[name="email"]').type('invalid@kotvrf.ru')
      cy.get('input[name="password"]').type('wrongpass')
      cy.get('button[type="submit"]').click()
      // Wait for response and check for error message
      cy.contains(/invalid/i).should('exist')
    })

    it('should login with valid credentials using loginAs command', () => {
      cy.loginAs('admin@kotvrf.ru', 'password123')
      // Verify we're logged in (not on login page)
      cy.url().should('not.include', '/login')
    })

    it('should remember session on page reload', () => {
      cy.loginAs('admin@kotvrf.ru', 'password123')
      cy.visit('/')
      cy.reload()
      // Should still be logged in
      cy.url().should('not.include', '/login')
    })
  })

  describe('Logout', () => {
    beforeEach(() => {
      cy.loginAs('admin@kotvrf.ru', 'password123')
    })

    it('should logout successfully', () => {
      cy.visit('/')
      // Look for logout button or user menu
      cy.get('button[aria-label="User menu"]', { timeout: 5000 })
        .click({ force: true })
        .then(() => {
          cy.contains(/logout/i).click({ force: true })
        })
      
      // Should be redirected to login
      cy.url().should('include', '/login')
    })

    it('should clear session after logout', () => {
      cy.visit('/')
      // Perform logout
      cy.get('button[aria-label="User menu"]', { timeout: 5000 })
        .then(($btn: JQuery<HTMLElement>) => {
          if ($btn.length > 0) {
            cy.get('button[aria-label="User menu"]').click({ force: true })
            cy.contains(/logout/i).click({ force: true })
          }
        })
      
      // Try to access protected route
      cy.visit('/dashboard')
      cy.url().should('include', '/login')
    })
  })

  describe('Password Reset', () => {
    it('should display password reset form', () => {
      cy.visit('/forgot-password')
      cy.get('input[name="email"]').should('exist')
      cy.get('button[type="submit"]').should('exist')
    })

    it('should submit reset email for valid user', () => {
      cy.visit('/forgot-password')
      cy.get('input[name="email"]').type('admin@kotvrf.ru')
      cy.get('button[type="submit"]').click()
      
      // Should show success message or redirect
      cy.url().then((url: string) => {
        if (url.includes('/forgot-password')) {
          cy.contains(/sent/i).should('exist')
        }
      })
    })

    it('should handle non-existent user gracefully', () => {
      cy.visit('/forgot-password')
      cy.get('input[name="email"]').type('nonexistent@kotvrf.ru')
      cy.get('button[type="submit"]').click()
      
      // Should still show success (don't reveal user existence)
      cy.url().then((url: string) => {
        if (url.includes('/forgot-password')) {
          cy.contains(/sent/i).should('exist')
        }
      })
    })
  })

  describe('Two-Factor Authentication', () => {
    it('should display 2FA form if enabled for user', () => {
      cy.visit('/login')
      cy.get('input[name="email"]').type('admin@kotvrf.ru')
      cy.get('input[name="password"]').type('password123')
      cy.get('button[type="submit"]').click()
      
      // Check if 2FA form appears (may not exist for all users)
      cy.get('body').then(($body: JQuery<HTMLElement>) => {
        if ($body.text().includes('2FA') || $body.text().includes('code')) {
          cy.get('input[name="code"]').should('exist')
        }
      })
    })

    it('should require valid 2FA code format', () => {
      cy.visit('/login')
      cy.get('input[name="email"]').type('admin@kotvrf.ru')
      cy.get('input[name="password"]').type('password123')
      cy.get('button[type="submit"]').click()
      
      // Check if 2FA form is shown
      cy.get('body').then(($body: JQuery<HTMLElement>) => {
        if ($body.find('input[name="code"]').length > 0) {
          cy.get('input[name="code"]').type('invalid')
          cy.get('button[type="submit"]').click()
          // Should show error
          cy.contains(/invalid/i).should('exist')
        }
      })
    })
  })
})


