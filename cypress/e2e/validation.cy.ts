// @ts-nocheck
/// <reference types="cypress" />
/// <reference types="chai" />

/**
 * Data Validation E2E Tests
 * 
 * Tests for input validation, data integrity, and error handling
 */

describe('Data Validation', () => {
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
    cy.loginAs('admin@kotvrf.ru', 'password123')
  })

  describe('Required Field Validation', () => {
    it('should validate required fields on form submission', () => {
      cy.visit('/admin/b2b/inventory/create')
      
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-name"]').should('contain', /required|обязательное/i)
      cy.get('[data-testid="error-sku"]').should('contain', /required|обязательное/i)
      cy.get('[data-testid="error-quantity"]').should('contain', /required|обязательное/i)
    })

    it('should prevent form submission with empty required fields', () => {
      cy.visit('/admin/b2b/hr/create')
      
      cy.get('[data-testid="submit-button"]').click()
      
      cy.url().should('include', '/create')
    })

    it('should clear errors when fields are filled', () => {
      cy.visit('/admin/b2b/inventory/create')
      
      cy.get('[data-testid="submit-button"]').click()
      cy.get('[data-testid="error-name"]').should('be.visible')
      
      cy.get('[data-testid="input-name"]').type('Test Item')
      cy.get('[data-testid="error-name"]').should('not.be.visible')
    })
  })

  describe('Email Validation', () => {
    it('should validate email format', () => {
      cy.visit('/admin/b2b/hr/create')
      
      cy.get('[data-testid="input-email"]').type('invalid-email')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-email"]').should('contain', /invalid|некорректный/i)
    })

    it('should reject duplicate email addresses', () => {
      cy.visit('/admin/b2b/hr/create')
      
      cy.get('[data-testid="input-first-name"]').type('John')
      cy.get('[data-testid="input-last-name"]').type('Doe')
      cy.get('[data-testid="input-email"]').type('admin@kotvrf.ru')
      cy.get('[data-testid="input-phone"]').type('+7 999 123-45-67')
      cy.get('[data-testid="select-position"]').click()
      cy.get('[data-testid="position-manager"]').click()
      cy.get('[data-testid="select-department"]').click()
      cy.get('[data-testid="department-hr"]').click()
      cy.get('[data-testid="input-hire-date"]').type('2026-03-01')
      cy.get('[data-testid="input-salary"]').type('50000')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-email"]').should('contain', /duplicate|уже существует/i)
    })

    it('should validate email for business domain requirement', () => {
      cy.visit('/admin/b2b/hr/create')
      
      cy.get('[data-testid="input-email"]').type('user@gmail.com')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-email"]').should('contain', /corporate|рабочий/i)
    })
  })

  describe('Phone Number Validation', () => {
    it('should validate phone number format', () => {
      cy.visit('/admin/b2b/hr/create')
      
      cy.get('[data-testid="input-phone"]').type('12345')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-phone"]').should('contain', /invalid|некорректный/i)
    })

    it('should accept international phone formats', () => {
      cy.visit('/admin/marketplace/beauty/bookings/create')
      
      cy.get('[data-testid="input-client-phone"]').type('+1 555 123 4567')
      cy.get('[data-testid="error-phone"]').should('not.exist')
    })

    it('should validate phone for duplicate booking prevention', () => {
      cy.visit('/admin/marketplace/beauty/bookings/create')
      
      cy.get('[data-testid="input-client-phone"]').type('+7 999 123-45-67')
      cy.get('[data-testid="submit-button"]').click()
      
      // Try duplicate booking at same time
      cy.visit('/admin/marketplace/beauty/bookings/create')
      cy.get('[data-testid="input-client-phone"]').type('+7 999 123-45-67')
      cy.get('[data-testid="select-salon"]').click()
      cy.get('[data-testid="salon-option"]').first().click()
      cy.get('[data-testid="select-service"]').click()
      cy.get('[data-testid="service-option"]').first().click()
      cy.get('[data-testid="input-booking-date"]').type('2026-03-20')
      cy.get('[data-testid="select-time-slot"]').click()
      cy.get('[data-testid="time-option"]').first().click()
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/already booked|уже забронирован/i).should('be.visible')
    })
  })

  describe('Numeric Validation', () => {
    it('should validate quantity as positive integer', () => {
      cy.visit('/admin/b2b/inventory/create')
      
      cy.get('[data-testid="input-quantity"]').type('-10')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-quantity"]').should('contain', /positive|положительное/i)
    })

    it('should validate price as positive decimal', () => {
      cy.visit('/admin/b2b/inventory/create')
      
      cy.get('[data-testid="input-unit-price"]').type('-99.99')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-price"]').should('contain', /positive|положительное/i)
    })

    it('should validate salary within acceptable range', () => {
      cy.visit('/admin/b2b/hr/create')
      
      cy.get('[data-testid="input-salary"]').type('0')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-salary"]').should('contain', /minimum|минимум/i)
    })

    it('should validate percentage values between 0-100', () => {
      cy.visit('/admin/b2b/inventory')
      cy.get('[data-testid="inventory-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      cy.get('[data-testid="pricing-tab"]').click()
      
      cy.get('[data-testid="input-discount-percent"]').type('150')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-discount"]').should('contain', /0-100|0 до 100/i)
    })
  })

  describe('Date Validation', () => {
    it('should validate date format', () => {
      cy.visit('/admin/b2b/inventory/create')
      
      cy.get('[data-testid="input-date"]').type('invalid-date')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-date"]').should('contain', /invalid|некорректный/i)
    })

    it('should prevent selecting past dates for future events', () => {
      cy.visit('/admin/b2b/communications/create')
      
      cy.get('[data-testid="input-send-date"]').type('2026-01-01')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-date"]').should('contain', /future|будущий/i)
    })

    it('should validate date ranges for leave requests', () => {
      cy.visit('/admin/b2b/hr')
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="request-leave-button"]').click()
      
      cy.get('[data-testid="input-start-date"]').type('2026-03-22')
      cy.get('[data-testid="input-end-date"]').type('2026-03-15')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-date-range"]').should('contain', /end after start|конец после начала/i)
    })

    it('should prevent overlapping leave requests', () => {
      cy.visit('/admin/b2b/hr')
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="request-leave-button"]').click()
      
      cy.get('[data-testid="select-leave-type"]').click()
      cy.get('[data-testid="leave-annual"]').click()
      cy.get('[data-testid="input-start-date"]').type('2026-03-15')
      cy.get('[data-testid="input-end-date"]').type('2026-03-22')
      cy.get('[data-testid="submit-button"]').click()
      
      // Try overlapping leave
      cy.visit('/admin/b2b/hr')
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="request-leave-button"]').click()
      cy.get('[data-testid="select-leave-type"]').click()
      cy.get('[data-testid="leave-annual"]').click()
      cy.get('[data-testid="input-start-date"]').type('2026-03-20')
      cy.get('[data-testid="input-end-date"]').type('2026-03-25')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-overlap"]').should('contain', /overlap|пересек/i)
    })
  })

  describe('Text Validation', () => {
    it('should validate text length requirements', () => {
      cy.visit('/admin/b2b/inventory/create')
      
      cy.get('[data-testid="input-name"]').type('AB')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-name"]').should('contain', /minimum|минимум/i)
    })

    it('should enforce text length maximums', () => {
      cy.visit('/admin/b2b/inventory/create')
      
      const longText = 'a'.repeat(300)
      cy.get('[data-testid="input-name"]').type(longText)
      
      cy.get('[data-testid="char-count"]').should('contain', /300/)
    })

    it('should sanitize HTML/script tags in text input', () => {
      cy.visit('/admin/b2b/communications/create')
      
      cy.get('[data-testid="textarea-content"]').type('<script>alert("xss")</script>')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.visit('/admin/b2b/communications')
      cy.get('[data-testid="newsletter-row"]').last().click()
      
      // XSS should be sanitized
      cy.get('[data-testid="content-display"]').should('not.contain', '<script>')
    })

    it('should validate against SQL injection patterns', () => {
      cy.visit('/admin/b2b/inventory/create')
      
      cy.get('[data-testid="input-name"]').type("'; DROP TABLE inventory; --")
      cy.get('[data-testid="submit-button"]').click()
      
      // Should sanitize or reject
      cy.contains(/invalid|не допускается/i).should('be.visible')
    })
  })

  describe('Conditional Validation', () => {
    it('should validate dependent fields', () => {
      cy.visit('/admin/b2b/communications/create')
      
      cy.get('[data-testid="checkbox-schedule"]').click()
      cy.get('[data-testid="input-send-date"]').should('be.visible')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-send-date"]').should('contain', /required|обязательное/i)
    })

    it('should validate price tier selection for services', () => {
      cy.visit('/admin/marketplace/beauty/services/create')
      
      cy.get('[data-testid="select-pricing-type"]').click()
      cy.get('[data-testid="pricing-tiered"]').click()
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-tiers"]').should('contain', /required|обязательное/i)
    })

    it('should validate based on user role', () => {
      cy.loginAs('manager@kotvrf.ru', 'password123')
      cy.visit('/admin/b2b/inventory/create')
      
      // Manager cannot set cost price
      cy.get('[data-testid="input-cost-price"]').should('be.disabled')
    })
  })

  describe('Batch Validation', () => {
    it('should validate CSV import data', () => {
      cy.visit('/admin/b2b/inventory')
      cy.get('[data-testid="import-button"]').click()
      cy.get('[data-testid="file-input"]').selectFile('cypress/fixtures/inventory-invalid.csv')
      cy.get('[data-testid="preview-button"]').click()
      
      cy.get('[data-testid="validation-error"]').should('have.length.greaterThan', 0)
    })

    it('should report all validation errors in batch', () => {
      cy.visit('/admin/b2b/inventory')
      cy.get('[data-testid="import-button"]').click()
      cy.get('[data-testid="file-input"]').selectFile('cypress/fixtures/inventory-invalid.csv')
      cy.get('[data-testid="preview-button"]').click()
      
      cy.get('[data-testid="validation-summary"]').should('contain', /errors/)
      cy.get('[data-testid="validation-error"]').each(($error) => {
        cy.wrap($error).should('contain', /row/)
      })
    })

    it('should allow partial import with error skipping', () => {
      cy.visit('/admin/b2b/inventory')
      cy.get('[data-testid="import-button"]').click()
      cy.get('[data-testid="file-input"]').selectFile('cypress/fixtures/inventory-mixed.csv')
      cy.get('[data-testid="preview-button"]').click()
      
      cy.get('[data-testid="skip-invalid-rows"]').click()
      cy.get('[data-testid="confirm-import"]').click()
      
      cy.contains(/imported|импортировано/i).should('be.visible')
    })
  })

  describe('Real-time Validation', () => {
    it('should validate email in real-time', () => {
      cy.visit('/admin/b2b/hr/create')
      
      cy.get('[data-testid="input-email"]').type('invalid')
      cy.get('[data-testid="error-email"]').should('be.visible')
      
      cy.get('[data-testid="input-email"]').clear().type('valid@company.com')
      cy.get('[data-testid="error-email"]').should('not.be.visible')
    })

    it('should validate SKU availability in real-time', () => {
      cy.visit('/admin/b2b/inventory/create')
      
      cy.get('[data-testid="input-sku"]').type('existing-sku-001')
      cy.wait(500)
      cy.get('[data-testid="sku-available"]').should('contain', /unavailable|недоступен/i)
    })

    it('should calculate dependent fields in real-time', () => {
      cy.visit('/admin/b2b/inventory/create')
      
      cy.get('[data-testid="input-quantity"]').type('100')
      cy.get('[data-testid="input-unit-price"]').type('50')
      
      cy.get('[data-testid="total-value"]').should('contain', '5000')
    })
  })

  describe('Custom Validation Rules', () => {
    it('should validate business hours for salon', () => {
      cy.visit('/admin/marketplace/beauty/salons/create')
      
      cy.get('[data-testid="input-working-hours-start"]').type('14:00')
      cy.get('[data-testid="input-working-hours-end"]').type('09:00')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="error-hours"]').should('contain', /end after start|конец после начала/i)
    })

    it('should validate booking duration availability', () => {
      cy.visit('/admin/marketplace/beauty/bookings/create')
      
      cy.get('[data-testid="select-service"]').click()
      cy.get('[data-testid="service-2hour"]').click()
      cy.get('[data-testid="input-booking-date"]').type('2026-03-20')
      cy.get('[data-testid="select-time-slot"]').click()
      cy.get('[data-testid="time-17:00"]').click() // Only 1 hour left
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/not enough time|недостаточно времени/i).should('be.visible')
    })

    it('should validate leave balance before approval', () => {
      cy.visit('/admin/b2b/hr')
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="annual-leave-balance"]').then(($balance: JQuery<HTMLElement>) => {
        const balance = parseInt($balance.text())
        
        cy.get('[data-testid="request-leave-button"]').click()
        cy.get('[data-testid="select-leave-type"]').click()
        cy.get('[data-testid="leave-annual"]').click()
        cy.get('[data-testid="input-start-date"]').type('2026-03-15')
        cy.get('[data-testid="input-end-date"]').type(`2026-04-${15 + balance}`)
        cy.get('[data-testid="submit-button"]').click()
        
        cy.contains(/exceed|превышает/i).should('be.visible')
      })
    })
  })
})
