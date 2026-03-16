// @ts-nocheck
/// <reference types="cypress" />
/// <reference types="chai" />

/**
 * B2B Communications E2E Tests
 * 
 * Tests for newsletters, announcements, and internal messaging
 */

describe('B2B Communications', () => {
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
    cy.loginAs('admin@kotvrf.ru', 'password123')
  })

  describe('Newsletter Management', () => {
    it('should display newsletters list', () => {
      cy.visit('/admin/b2b/communications')
      
      cy.get('[data-testid="newsletter-table"]').should('be.visible')
      cy.get('[data-testid="newsletter-row"]').should('have.length.greaterThan', 0)
    })

    it('should create new newsletter campaign', () => {
      cy.visit('/admin/b2b/communications/create')
      
      cy.get('[data-testid="input-subject"]').type('March Company Update')
      cy.get('[data-testid="input-title"]').type('Monthly Newsletter - March 2026')
      cy.get('[data-testid="textarea-content"]').type('This is our March newsletter with important updates')
      cy.get('[data-testid="select-recipients"]').click()
      cy.get('[data-testid="recipient-all"]').click()
      cy.get('[data-testid="input-send-date"]').type('2026-03-20')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/created|создана/i).should('be.visible')
    })

    it('should schedule newsletter for future sending', () => {
      cy.visit('/admin/b2b/communications/create')
      
      cy.get('[data-testid="input-subject"]').type('Future Newsletter')
      cy.get('[data-testid="textarea-content"]').type('Content for future newsletter')
      cy.get('[data-testid="select-recipients"]').click()
      cy.get('[data-testid="recipient-all"]').click()
      cy.get('[data-testid="input-send-date"]').type('2026-04-01')
      cy.get('[data-testid="checkbox-schedule"]').click()
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="status-badge"]').should('contain', 'Scheduled')
    })

    it('should send newsletter immediately', () => {
      cy.visit('/admin/b2b/communications/create')
      
      cy.get('[data-testid="input-subject"]').type('Urgent Announcement')
      cy.get('[data-testid="textarea-content"]').type('Urgent company announcement')
      cy.get('[data-testid="select-recipients"]').click()
      cy.get('[data-testid="recipient-all"]').click()
      cy.get('[data-testid="checkbox-send-now"]').click()
      cy.get('[data-testid="submit-button"]').click()
      
      cy.get('[data-testid="status-badge"]').should('contain', 'Sent')
    })

    it('should edit draft newsletters', () => {
      cy.visit('/admin/b2b/communications')
      
      cy.get('[data-testid="newsletter-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      
      cy.get('[data-testid="input-subject"]').clear().type('Updated Subject')
      cy.get('[data-testid="textarea-content"]').clear().type('Updated content')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/updated|обновлено/i).should('be.visible')
    })

    it('should prevent editing of sent newsletters', () => {
      cy.visit('/admin/b2b/communications')
      
      // Find sent newsletter
      cy.get('[data-testid="newsletter-status"]').each(($status, index) => {
        if ($status.text().includes('Sent')) {
          cy.get('[data-testid="newsletter-row"]').eq(index).click()
          cy.get('[data-testid="edit-button"]').should('be.disabled')
        }
      })
    })
  })

  describe('Newsletter Recipients', () => {
    it('should select specific recipients for newsletter', () => {
      cy.visit('/admin/b2b/communications/create')
      
      cy.get('[data-testid="input-subject"]').type('Department Newsletter')
      cy.get('[data-testid="textarea-content"]').type('Content for specific department')
      cy.get('[data-testid="select-recipients"]').click()
      cy.get('[data-testid="recipient-sales-dept"]').click()
      cy.get('[data-testid="recipient-marketing-dept"]').click()
      
      cy.get('[data-testid="recipient-count"]').should('contain', /\d+ recipients/)
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/created|создана/i).should('be.visible')
    })

    it('should exclude specific users from newsletter', () => {
      cy.visit('/admin/b2b/communications/create')
      
      cy.get('[data-testid="input-subject"]').type('Newsletter with Exclusions')
      cy.get('[data-testid="textarea-content"]').type('Content')
      cy.get('[data-testid="select-recipients"]').click()
      cy.get('[data-testid="recipient-all"]').click()
      
      cy.get('[data-testid="exclude-users"]').click()
      cy.get('[data-testid="exclude-user-1"]').click()
      cy.get('[data-testid="exclude-user-2"]').click()
      
      cy.get('[data-testid="submit-button"]').click()
      cy.contains(/created|создана/i).should('be.visible')
    })

    it('should track newsletter delivery status', () => {
      cy.visit('/admin/b2b/communications')
      
      cy.get('[data-testid="newsletter-row"]').first().click()
      cy.get('[data-testid="delivery-tab"]').click()
      
      cy.get('[data-testid="delivery-entry"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="delivery-status"]').first().should('exist')
      cy.get('[data-testid="delivery-timestamp"]').first().should('exist')
    })
  })

  describe('Newsletter Templates', () => {
    it('should create newsletter template', () => {
      cy.visit('/admin/b2b/communications/templates')
      cy.get('[data-testid="create-template"]').click()
      
      cy.get('[data-testid="input-template-name"]').type('Monthly Update Template')
      cy.get('[data-testid="textarea-template-content"]').type('{{month}} Newsletter {{year}}')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/created|создана/i).should('be.visible')
    })

    it('should use template for new newsletter', () => {
      cy.visit('/admin/b2b/communications/create')
      
      cy.get('[data-testid="select-template"]').click()
      cy.get('[data-testid="template-option"]').first().click()
      
      cy.get('[data-testid="textarea-content"]').should('not.be.empty')
    })

    it('should preview template before sending', () => {
      cy.visit('/admin/b2b/communications/create')
      
      cy.get('[data-testid="input-subject"]').type('Test Newsletter')
      cy.get('[data-testid="textarea-content"]').type('Test content')
      cy.get('[data-testid="preview-button"]').click()
      
      cy.get('[data-testid="preview-modal"]').should('be.visible')
      cy.get('[data-testid="preview-subject"]').should('contain', 'Test Newsletter')
    })
  })

  describe('Newsletter Analytics', () => {
    it('should track newsletter open rates', () => {
      cy.visit('/admin/b2b/communications')
      
      cy.get('[data-testid="newsletter-row"]').first().click()
      cy.get('[data-testid="analytics-tab"]').click()
      
      cy.get('[data-testid="open-rate"]').should('match', /\d+%/)
      cy.get('[data-testid="click-rate"]').should('match', /\d+%/)
    })

    it('should show engagement statistics', () => {
      cy.visit('/admin/b2b/communications')
      
      cy.get('[data-testid="newsletter-row"]').first().click()
      cy.get('[data-testid="analytics-tab"]').click()
      
      cy.get('[data-testid="total-sent"]').should('match', /\d+/)
      cy.get('[data-testid="total-delivered"]').should('match', /\d+/)
      cy.get('[data-testid="total-opened"]').should('match', /\d+/)
      cy.get('[data-testid="total-clicked"]').should('match', /\d+/)
    })

    it('should export newsletter report', () => {
      cy.visit('/admin/b2b/communications')
      
      cy.get('[data-testid="newsletter-row"]').first().click()
      cy.get('[data-testid="export-button"]').click()
      cy.get('[data-testid="export-csv"]').click()
      
      cy.readFile('cypress/downloads/newsletter-report.csv').should('exist')
    })
  })

  describe('Announcements', () => {
    it('should create company-wide announcement', () => {
      cy.visit('/admin/b2b/communications/announcements')
      cy.get('[data-testid="create-announcement"]').click()
      
      cy.get('[data-testid="input-title"]').type('Important Company Notice')
      cy.get('[data-testid="textarea-content"]').type('This is an important announcement for all staff')
      cy.get('[data-testid="select-priority"]').click()
      cy.get('[data-testid="priority-high"]').click()
      cy.get('[data-testid="checkbox-pin"]').click()
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/created|создано/i).should('be.visible')
    })

    it('should display pinned announcements prominently', () => {
      cy.visit('/admin/dashboard')
      
      cy.get('[data-testid="pinned-announcement"]').should('be.visible')
    })

    it('should expire announcements automatically', () => {
      cy.visit('/admin/b2b/communications/announcements')
      cy.get('[data-testid="create-announcement"]').click()
      
      cy.get('[data-testid="input-title"]').type('Temporary Notice')
      cy.get('[data-testid="textarea-content"]').type('This notice will expire')
      cy.get('[data-testid="input-expiry-date"]').type('2026-03-20')
      cy.get('[data-testid="submit-button"]').click()
      
      // Advance time and check expiration
      cy.clock()
      cy.tick(86400000) // 1 day
      cy.visit('/admin/b2b/communications/announcements')
      cy.get('[data-testid="announcement-row"]').should('not.contain', 'Temporary Notice')
    })
  })

  describe('Communication Permissions', () => {
    it('should restrict communications access to authorized users', () => {
      cy.loginAs('viewer@kotvrf.ru', 'password123')
      cy.visit('/admin/b2b/communications')
      
      cy.contains(/not authorized|не авторизован/i).should('be.visible')
    })

    it('should allow view-only for specific roles', () => {
      cy.loginAs('manager@kotvrf.ru', 'password123')
      cy.visit('/admin/b2b/communications')
      
      cy.get('[data-testid="newsletter-row"]').first().click()
      cy.get('[data-testid="edit-button"]').should('be.disabled')
      cy.get('[data-testid="delete-button"]').should('be.disabled')
    })

    it('should log all communication changes', () => {
      cy.visit('/admin/b2b/communications')
      cy.get('[data-testid="newsletter-row"]').first().click()
      cy.get('[data-testid="history-tab"]').click()
      
      cy.get('[data-testid="audit-entry"]').should('have.length.greaterThan', 0)
    })
  })

  describe('Communication Integrations', () => {
    it('should send actual emails to recipients', () => {
      cy.visit('/admin/b2b/communications/create')
      
      cy.get('[data-testid="input-subject"]').type('Email Test')
      cy.get('[data-testid="textarea-content"]').type('Testing email delivery')
      cy.get('[data-testid="select-recipients"]').click()
      cy.get('[data-testid="recipient-all"]').click()
      cy.get('[data-testid="checkbox-send-now"]').click()
      cy.get('[data-testid="submit-button"]').click()
      
      // Check email was queued
      cy.visit('/admin/queue/emails')
      cy.get('[data-testid="email-entry"]').should('have.length.greaterThan', 0)
    })

    it('should create audit logs for communications', () => {
      cy.visit('/admin/b2b/communications/create')
      cy.get('[data-testid="input-subject"]').type('Test Newsletter')
      cy.get('[data-testid="textarea-content"]').type('Test content')
      cy.get('[data-testid="select-recipients"]').click()
      cy.get('[data-testid="recipient-all"]').click()
      cy.get('[data-testid="submit-button"]').click()
      
      // Check audit log
      cy.visit('/admin/audit-logs')
      cy.get('[data-testid="search-input"]').type('newsletter')
      cy.get('[data-testid="audit-entry"]').should('have.length.greaterThan', 0)
    })
  })
})
