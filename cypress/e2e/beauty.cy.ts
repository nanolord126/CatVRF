// @ts-nocheck
/// <reference types="cypress" />
/// <reference types="chai" />

/**
 * Marketplace Beauty Salons E2E Tests
 * 
 * Tests for salon management, services, bookings, and appointments
 */

describe('Marketplace Beauty Salons', () => {
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
    cy.loginAs('admin@kotvrf.ru', 'password123')
  })

  describe('Salon Management', () => {
    it('should display salons list', () => {
      cy.visit('/admin/marketplace/beauty/salons')
      
      cy.get('[data-testid="salon-table"]').should('be.visible')
      cy.get('[data-testid="salon-row"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="salon-name"]').first().should('not.be.empty')
    })

    it('should create new beauty salon', () => {
      cy.visit('/admin/marketplace/beauty/salons/create')
      
      cy.get('[data-testid="input-name"]').type('Elite Beauty Studio')
      cy.get('[data-testid="input-description"]').type('Premium beauty and wellness center')
      cy.get('[data-testid="input-address"]').type('Moscow, Arbat St, 10')
      cy.get('[data-testid="input-phone"]').type('+7 499 123-45-67')
      cy.get('[data-testid="input-email"]').type('elite@beauty.ru')
      cy.get('[data-testid="input-working-hours-start"]').type('09:00')
      cy.get('[data-testid="input-working-hours-end"]').type('20:00')
      cy.get('[data-testid="textarea-about"]').type('Full range of beauty services')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.url().should('include', '/admin/marketplace/beauty/salons')
      cy.contains(/created|создан/i).should('be.visible')
    })

    it('should validate required salon fields', () => {
      cy.visit('/admin/marketplace/beauty/salons/create')
      
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/required|обязательное/i).should('be.visible')
    })

    it('should edit salon details', () => {
      cy.visit('/admin/marketplace/beauty/salons')
      
      cy.get('[data-testid="salon-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      
      cy.get('[data-testid="input-name"]').clear().type('Updated Salon Name')
      cy.get('[data-testid="input-phone"]').clear().type('+7 499 987-65-43')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/updated|обновлено/i).should('be.visible')
    })

    it('should upload salon photos', () => {
      cy.visit('/admin/marketplace/beauty/salons')
      
      cy.get('[data-testid="salon-row"]').first().click()
      cy.get('[data-testid="upload-photo-button"]').click()
      cy.get('[data-testid="file-input"]').selectFile('cypress/fixtures/salon-photo.jpg')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/uploaded|загружено/i).should('be.visible')
    })
  })

  describe('Beauty Services', () => {
    it('should list salon services', () => {
      cy.visit('/admin/marketplace/beauty/services')
      
      cy.get('[data-testid="service-table"]').should('be.visible')
      cy.get('[data-testid="service-row"]').should('have.length.greaterThan', 0)
    })

    it('should create new beauty service', () => {
      cy.visit('/admin/marketplace/beauty/services/create')
      
      cy.get('[data-testid="select-salon"]').click()
      cy.get('[data-testid="salon-option"]').first().click()
      cy.get('[data-testid="input-service-name"]').type('Professional Hair Coloring')
      cy.get('[data-testid="select-category"]').click()
      cy.get('[data-testid="category-hair"]').click()
      cy.get('[data-testid="input-duration"]').type('120')
      cy.get('[data-testid="input-price"]').type('3500')
      cy.get('[data-testid="textarea-description"]').type('Premium hair coloring service with consultation')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.url().should('include', '/admin/marketplace/beauty/services')
      cy.contains(/created|создана/i).should('be.visible')
    })

    it('should set service availability', () => {
      cy.visit('/admin/marketplace/beauty/services')
      
      cy.get('[data-testid="service-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      
      cy.get('[data-testid="schedule-tab"]').click()
      cy.get('[data-testid="monday-checkbox"]').click()
      cy.get('[data-testid="monday-start"]').type('09:00')
      cy.get('[data-testid="monday-end"]').type('18:00')
      
      cy.get('[data-testid="friday-checkbox"]').click()
      cy.get('[data-testid="friday-start"]').type('09:00')
      cy.get('[data-testid="friday-end"]').type('20:00')
      
      cy.get('[data-testid="submit-button"]').click()
      cy.contains(/updated|обновлено/i).should('be.visible')
    })

    it('should manage service pricing', () => {
      cy.visit('/admin/marketplace/beauty/services')
      
      cy.get('[data-testid="service-row"]').first().click()
      cy.get('[data-testid="pricing-tab"]').click()
      
      cy.get('[data-testid="input-base-price"]').clear().type('3999')
      cy.get('[data-testid="input-discount-percent"]').type('10')
      cy.get('[data-testid="calculated-price"]').should('contain', '3599.10')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/updated|обновлено/i).should('be.visible')
    })

    it('should assign staff to services', () => {
      cy.visit('/admin/marketplace/beauty/services')
      
      cy.get('[data-testid="service-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      cy.get('[data-testid="staff-tab"]').click()
      
      cy.get('[data-testid="add-staff-member"]').click()
      cy.get('[data-testid="select-staff"]').click()
      cy.get('[data-testid="staff-option"]').first().click()
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/updated|обновлено/i).should('be.visible')
    })
  })

  describe('Service Bookings', () => {
    it('should list all bookings', () => {
      cy.visit('/admin/marketplace/beauty/bookings')
      
      cy.get('[data-testid="booking-table"]').should('be.visible')
      cy.get('[data-testid="booking-row"]').should('have.length.greaterThan', 0)
    })

    it('should create new booking', () => {
      cy.visit('/admin/marketplace/beauty/bookings/create')
      
      cy.get('[data-testid="select-salon"]').click()
      cy.get('[data-testid="salon-option"]').first().click()
      cy.get('[data-testid="input-client-name"]').type('Irina Volkova')
      cy.get('[data-testid="input-client-phone"]').type('+7 999 123-45-67')
      cy.get('[data-testid="select-service"]').click()
      cy.get('[data-testid="service-option"]').first().click()
      cy.get('[data-testid="input-booking-date"]').type('2026-03-20')
      cy.get('[data-testid="select-time-slot"]').click()
      cy.get('[data-testid="time-option"]').first().click()
      cy.get('[data-testid="submit-button"]').click()
      
      cy.url().should('include', '/admin/marketplace/beauty/bookings')
      cy.contains(/created|создана/i).should('be.visible')
    })

    it('should confirm booking status', () => {
      cy.visit('/admin/marketplace/beauty/bookings')
      
      cy.get('[data-testid="booking-row"]').first().click()
      cy.get('[data-testid="confirm-button"]').click()
      cy.get('[data-testid="confirm-confirmation"]').click()
      
      cy.get('[data-testid="status-badge"]').should('contain', 'Confirmed')
    })

    it('should cancel booking with reason', () => {
      cy.visit('/admin/marketplace/beauty/bookings')
      
      cy.get('[data-testid="booking-row"]').first().click()
      cy.get('[data-testid="cancel-button"]').click()
      cy.get('[data-testid="select-reason"]').click()
      cy.get('[data-testid="reason-client-request"]').click()
      cy.get('[data-testid="textarea-notes"]').type('Client requested cancellation')
      cy.get('[data-testid="confirm-cancellation"]').click()
      
      cy.get('[data-testid="status-badge"]').should('contain', 'Cancelled')
    })

    it('should reschedule bookings', () => {
      cy.visit('/admin/marketplace/beauty/bookings')
      
      cy.get('[data-testid="booking-row"]').first().click()
      cy.get('[data-testid="reschedule-button"]').click()
      cy.get('[data-testid="input-new-date"]').type('2026-03-25')
      cy.get('[data-testid="select-new-time"]').click()
      cy.get('[data-testid="time-option"]').first().click()
      cy.get('[data-testid="confirm-reschedule"]').click()
      
      cy.contains(/rescheduled|перенесена/i).should('be.visible')
    })

    it('should show no-show warning', () => {
      cy.visit('/admin/marketplace/beauty/bookings')
      
      // Find upcoming booking
      cy.get('[data-testid="booking-status"]').each(($status, index) => {
        if ($status.text().includes('Confirmed')) {
          cy.get('[data-testid="booking-row"]').eq(index).click()
          
          // Mark as no-show
          cy.get('[data-testid="mark-noshow"]').click()
          cy.get('[data-testid="confirm-noshow"]').click()
          
          cy.get('[data-testid="status-badge"]').should('contain', 'No-Show')
        }
      })
    })
  })

  describe('Stylist Management', () => {
    it('should list salon stylists', () => {
      cy.visit('/admin/marketplace/beauty/stylists')
      
      cy.get('[data-testid="stylist-table"]').should('be.visible')
      cy.get('[data-testid="stylist-row"]').should('have.length.greaterThan', 0)
    })

    it('should add new stylist', () => {
      cy.visit('/admin/marketplace/beauty/stylists/create')
      
      cy.get('[data-testid="select-salon"]').click()
      cy.get('[data-testid="salon-option"]').first().click()
      cy.get('[data-testid="input-first-name"]').type('Olga')
      cy.get('[data-testid="input-last-name"]').type('Petrov')
      cy.get('[data-testid="input-phone"]').type('+7 999 876-54-32')
      cy.get('[data-testid="input-email"]').type('olga.petrov@salon.ru')
      cy.get('[data-testid="select-specialization"]').click()
      cy.get('[data-testid="specialization-hair"]').click()
      cy.get('[data-testid="input-experience"]').type('7')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/created|создан/i).should('be.visible')
    })

    it('should track stylist ratings and reviews', () => {
      cy.visit('/admin/marketplace/beauty/stylists')
      
      cy.get('[data-testid="stylist-row"]').first().click()
      cy.get('[data-testid="ratings-tab"]').click()
      
      cy.get('[data-testid="average-rating"]').should('match', /[0-5]\.[0-9]/)
      cy.get('[data-testid="review-entry"]').should('have.length.greaterThan', 0)
    })

    it('should manage stylist schedules', () => {
      cy.visit('/admin/marketplace/beauty/stylists')
      
      cy.get('[data-testid="stylist-row"]').first().click()
      cy.get('[data-testid="schedule-tab"]').click()
      
      cy.get('[data-testid="add-day-off"]').click()
      cy.get('[data-testid="input-day-off-date"]').type('2026-03-25')
      cy.get('[data-testid="input-reason"]').type('Vacation')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/added|добавлено/i).should('be.visible')
    })
  })

  describe('Beauty Payments', () => {
    it('should process booking payment', () => {
      cy.visit('/admin/marketplace/beauty/bookings')
      
      cy.get('[data-testid="booking-row"]').first().click()
      cy.get('[data-testid="payment-tab"]').click()
      
      cy.get('[data-testid="process-payment"]').click()
      cy.get('[data-testid="select-payment-method"]').click()
      cy.get('[data-testid="payment-card"]').click()
      cy.get('[data-testid="input-amount"]').should('match', /\d+\.\d{2}/)
      cy.get('[data-testid="confirm-payment"]').click()
      
      cy.get('[data-testid="payment-status"]').should('contain', 'Completed')
    })

    it('should issue refunds for cancelled bookings', () => {
      cy.visit('/admin/marketplace/beauty/bookings')
      
      cy.get('[data-testid="booking-row"]').first().click()
      cy.get('[data-testid="cancel-button"]').click()
      cy.get('[data-testid="select-reason"]').click()
      cy.get('[data-testid="reason-salon-request"]').click()
      cy.get('[data-testid="issue-refund"]').click()
      cy.get('[data-testid="confirm-refund"]').click()
      
      cy.contains(/refunded|возвращено/i).should('be.visible')
    })

    it('should track payment history', () => {
      cy.visit('/admin/marketplace/beauty/bookings')
      
      cy.get('[data-testid="booking-row"]').first().click()
      cy.get('[data-testid="payment-tab"]').click()
      cy.get('[data-testid="payment-history"]').click()
      
      cy.get('[data-testid="transaction-entry"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="transaction-amount"]').first().should('match', /\$[\d,]+\.\d{2}/)
    })
  })

  describe('Beauty Reports', () => {
    it('should generate bookings report', () => {
      cy.visit('/admin/marketplace/beauty')
      cy.get('[data-testid="reports-button"]').click()
      cy.get('[data-testid="report-bookings"]').click()
      
      cy.get('[data-testid="report-total-bookings"]').should('match', /\d+/)
      cy.get('[data-testid="report-confirmed"]').should('match', /\d+/)
      cy.get('[data-testid="report-cancelled"]').should('match', /\d+/)
      cy.get('[data-testid="report-revenue"]').should('match', /\$[\d,]+\.\d{2}/)
    })

    it('should generate stylist performance report', () => {
      cy.visit('/admin/marketplace/beauty/reports')
      cy.get('[data-testid="report-stylist-performance"]').click()
      
      cy.get('[data-testid="stylist-entry"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="stylist-bookings"]').first().should('match', /\d+/)
      cy.get('[data-testid="stylist-rating"]').first().should('match', /[0-5]\.[0-9]/)
    })

    it('should export salon data', () => {
      cy.visit('/admin/marketplace/beauty/salons')
      cy.get('[data-testid="export-button"]').click()
      cy.get('[data-testid="export-csv"]').click()
      
      cy.readFile('cypress/downloads/salons.csv').should('exist')
    })
  })

  describe('Beauty Integrations', () => {
    it('should sync bookings with calendar', () => {
      cy.visit('/admin/marketplace/beauty/bookings')
      cy.get('[data-testid="calendar-view"]').click()
      
      cy.get('[data-testid="calendar-event"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="calendar-event"]').first().click()
      cy.get('[data-testid="event-details"]').should('be.visible')
    })

    it('should send booking confirmations', () => {
      cy.visit('/admin/marketplace/beauty/bookings/create')
      
      cy.get('[data-testid="select-salon"]').click()
      cy.get('[data-testid="salon-option"]').first().click()
      cy.get('[data-testid="input-client-name"]').type('Test Client')
      cy.get('[data-testid="input-client-phone"]').type('+7 999 111-22-33')
      cy.get('[data-testid="select-service"]').click()
      cy.get('[data-testid="service-option"]').first().click()
      cy.get('[data-testid="input-booking-date"]').type('2026-03-22')
      cy.get('[data-testid="select-time-slot"]').click()
      cy.get('[data-testid="time-option"]').first().click()
      cy.get('[data-testid="submit-button"]').click()
      
      // Check email queue
      cy.visit('/admin/queue/emails')
      cy.get('[data-testid="email-entry"]').should('have.length.greaterThan', 0)
    })

    it('should create audit logs for all salon operations', () => {
      cy.visit('/admin/marketplace/beauty/bookings')
      cy.get('[data-testid="booking-row"]').first().click()
      cy.get('[data-testid="confirm-button"]').click()
      cy.get('[data-testid="confirm-confirmation"]').click()
      
      // Check audit log
      cy.visit('/admin/audit-logs')
      cy.get('[data-testid="search-input"]').type('beauty')
      cy.get('[data-testid="audit-entry"]').should('have.length.greaterThan', 0)
    })
  })
})
