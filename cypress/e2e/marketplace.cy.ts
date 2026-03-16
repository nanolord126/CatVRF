// @ts-nocheck
/// <reference types="cypress" />
/// <reference types="chai" />

/**
 * Marketplace E2E Tests
 * 
 * Tests for marketplace operations and workflows
 */

describe('Marketplace - Concert Management', () => {
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
    cy.loginAs('manager@kotvrf.ru', 'password123')
    cy.visit('/admin/marketplace/concerts')
  })

  describe('Listing', () => {
    it('should display concerts list', () => {
      cy.get('[data-testid="concerts-table"]').should('be.visible')
      cy.get('[data-testid="concert-row"]').should('have.length.at.least', 1)
    })

    it('should paginate through concerts', () => {
      // Add more concerts programmatically for pagination test
      cy.get('[data-testid="next-page"]').click()
      cy.url().should('include', 'page=2')
    })

    it('should filter concerts by name', () => {
      cy.get('[data-testid="filter-name"]').type('Symphony')
      cy.get('[data-testid="filter-submit"]').click()

      cy.get('[data-testid="concert-row"]').each(($row) => {
        cy.wrap($row).should('contain', 'Symphony')
      })
    })

    it('should sort concerts by date', () => {
      cy.get('[data-testid="sort-date"]').click()
      cy.get('[data-testid="concert-date-1"]').then(($el1: JQuery<HTMLElement>) => {
        cy.get('[data-testid="concert-date-2"]').then(($el2: JQuery<HTMLElement>) => {
          const date1 = new Date($el1.text())
          const date2 = new Date($el2.text())
          expect(date1.getTime()).to.be.lessThan(date2.getTime())
        })
      })
    })
  })

  describe('Creating', () => {
    it('should create new concert with valid data', () => {
      cy.get('[data-testid="create-button"]').click()
      cy.url().should('include', '/create')

      cy.get('input[name="name"]').type('New Concert')
      cy.get('textarea[name="description"]').type('Amazing concert event')
      cy.get('input[name="date"]').type('2026-04-15')
      cy.get('input[name="time"]').type('20:00')
      cy.get('input[name="venue"]').type('Grand Hall')
      cy.get('input[name="capacity"]').type('500')
      cy.get('input[name="price"]').type('50.00')

      cy.get('button[type="submit"]').click()

      // Verify success
      cy.get('[data-testid="success-message"]').should('contain', 'Concert created successfully')
      cy.url().should('include', '/concerts')
    })

    it('should show validation errors for empty fields', () => {
      cy.get('[data-testid="create-button"]').click()
      cy.get('button[type="submit"]').click()

      cy.get('[data-testid="name-error"]').should('contain', 'Name is required')
      cy.get('[data-testid="venue-error"]').should('contain', 'Venue is required')
    })

    it('should validate price format', () => {
      cy.get('[data-testid="create-button"]').click()
      cy.get('input[name="name"]').type('Concert')
      cy.get('input[name="price"]').type('invalid')
      cy.get('button[type="submit"]').click()

      cy.get('[data-testid="price-error"]').should('contain', 'must be a valid price')
    })
  })

  describe('Updating', () => {
    it('should update concert details', () => {
      cy.get('[data-testid="concert-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()

      cy.get('input[name="name"]').clear().type('Updated Concert')
      cy.get('input[name="capacity"]').clear().type('600')
      cy.get('button[type="submit"]').click()

      cy.get('[data-testid="success-message"]').should('contain', 'Concert updated')
    })

    it('should prevent unauthorized updates', () => {
      // Login as viewer
      cy.loginAs('viewer@kotvrf.ru', 'password123')
      cy.visit('/admin/marketplace/concerts')
      
      cy.get('[data-testid="concert-row"]').first().click()
      cy.get('[data-testid="edit-button"]').should('not.exist')
    })
  })

  describe('Deleting', () => {
    it('should delete concert with confirmation', () => {
      cy.get('[data-testid="concert-row"]').first().within(() => {
        cy.get('[data-testid="delete-button"]').click()
      })

      cy.get('[data-testid="confirm-dialog"]').should('be.visible')
      cy.get('[data-testid="confirm-delete"]').click()

      cy.get('[data-testid="success-message"]').should('contain', 'Concert deleted')
    })

    it('should cancel deletion on confirmation cancel', () => {
      const concertName = cy.get('[data-testid="concert-row"]').first().invoke('text')

      cy.get('[data-testid="concert-row"]').first().within(() => {
        cy.get('[data-testid="delete-button"]').click()
      })

      cy.get('[data-testid="cancel-delete"]').click()
      cy.get('[data-testid="confirm-dialog"]').should('not.exist')

      // Concert should still exist
      cy.get('[data-testid="concerts-table"]').should('contain', concertName)
    })
  })

  describe('Bulk Operations', () => {
    it('should select multiple concerts', () => {
      cy.get('[data-testid="select-all"]').click()
      cy.get('[data-testid="concert-checkbox"]').should('all.be.checked')
    })

    it('should bulk delete selected concerts', () => {
      cy.get('[data-testid="concert-checkbox"]').first().click()
      cy.get('[data-testid="concert-checkbox"]').eq(1).click()

      cy.get('[data-testid="bulk-delete"]').click()
      cy.get('[data-testid="confirm-delete"]').click()

      cy.get('[data-testid="success-message"]').should('contain', 'Deleted')
    })

    it('should export concerts to CSV', () => {
      cy.get('[data-testid="export-button"]').click()
      cy.get('[data-testid="export-csv"]').click()

      cy.readFile('cypress/downloads/concerts.csv').should('exist')
    })
  })

  describe('Search', () => {
    it('should search concerts in real-time', () => {
      cy.get('[data-testid="search-input"]').type('Jazz')
      cy.wait(500) // Debounce

      cy.get('[data-testid="concert-row"]').each(($row) => {
        cy.wrap($row).should('contain', 'Jazz')
      })
    })

    it('should handle search with no results', () => {
      cy.get('[data-testid="search-input"]').type('NonexistentConcert123')
      cy.wait(500)

      cy.get('[data-testid="no-results"]').should('contain', 'No concerts found')
    })
  })
})



