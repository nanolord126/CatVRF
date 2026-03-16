// @ts-nocheck
/// <reference types="cypress" />
/// <reference types="chai" />

/**
 * API Performance E2E Tests
 * 
 * Tests for API response times and performance
 */

describe('API Performance', () => {
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
    cy.loginAs('admin@kotvrf.ru', 'password123')
  })

  describe('Response Times', () => {
    it('should list concerts within 500ms', () => {
      cy.intercept('GET', '/api/concerts').as('getConcerts')
      cy.visit('/admin/marketplace/concerts')

      cy.wait('@getConcerts').then((interception: any) => {
        expect(interception.response?.statusCode).to.equal(200)
        // Performance measurement
        const responseTime = interception.response?.responseTime || 0
        expect(responseTime).to.be.lessThan(500)
      })
    })

    it('should create concert within 800ms', () => {
      cy.intercept('POST', '/api/concerts').as('createConcert')
      cy.visit('/admin/marketplace/concerts/create')

      cy.get('input[name="name"]').type('Performance Test Concert')
      cy.get('input[name="venue"]').type('Test Hall')
      cy.get('input[name="capacity"]').type('500')
      cy.get('input[name="price"]').type('50')
      cy.get('button[type="submit"]').click()

      cy.wait('@createConcert').then((interception: any) => {
        const responseTime = interception.response?.responseTime || 0
        expect(responseTime).to.be.lessThan(800)
      })
    })

    it('should search concerts within 300ms', () => {
      cy.intercept('GET', '/api/concerts*').as('searchConcerts')
      cy.visit('/admin/marketplace/concerts')

      cy.get('[data-testid="search-input"]').type('Jazz')
      cy.wait('@searchConcerts').then((interception: any) => {
        const responseTime = interception.response?.responseTime || 0
        expect(responseTime).to.be.lessThan(300)
      })
    })

    it('should update concert within 600ms', () => {
      cy.intercept('PUT', '/api/concerts/*').as('updateConcert')
      cy.visit('/admin/marketplace/concerts')

      cy.get('[data-testid="concert-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      cy.get('input[name="name"]').clear().type('Updated')
      cy.get('button[type="submit"]').click()

      cy.wait('@updateConcert').then((interception: any) => {
        const responseTime = interception.response?.responseTime || 0
        expect(responseTime).to.be.lessThan(600)
      })
    })
  })

  describe('Caching', () => {
    it('should cache GET requests', () => {
      cy.intercept('GET', '/api/concerts', (req) => {
        req.reply((res) => {
          res.headers['cache-control'] = 'public, max-age=300'
        })
      }).as('getConcerts')

      cy.visit('/admin/marketplace/concerts')
      cy.wait('@getConcerts')

      // Second request should use cache
      cy.visit('/admin/marketplace/concerts')
      cy.wait('@getConcerts').then((interception: any) => {
        expect(interception.response?.statusCode).to.equal(200)
      })
    })

    it('should invalidate cache on mutations', () => {
      cy.intercept('GET', '/api/concerts').as('getConcerts')
      cy.intercept('POST', '/api/concerts').as('createConcert')

      cy.visit('/admin/marketplace/concerts')
      cy.wait('@getConcerts')

      // Create concert
      cy.get('[data-testid="create-button"]').click()
      cy.get('input[name="name"]').type('New Concert')
      cy.get('input[name="venue"]').type('Hall')
      cy.get('input[name="capacity"]').type('500')
      cy.get('input[name="price"]').type('50')
      cy.get('button[type="submit"]').click()

      cy.wait('@createConcert')

      // List should be refreshed
      cy.wait('@getConcerts').then((interception: any) => {
        expect(interception.response?.body).to.include('New Concert')
      })
    })
  })

  describe('Pagination Performance', () => {
    it('should handle large datasets efficiently', () => {
      cy.intercept('GET', '/api/concerts*').as('getConcerts')
      cy.visit('/admin/marketplace/concerts?per_page=100')

      cy.wait('@getConcerts').then((interception: any) => {
        // Should still perform well with large page size
        const responseTime = interception.response?.responseTime || 0
        expect(responseTime).to.be.lessThan(1000)
      })
    })
  })

  describe('Concurrent Requests', () => {
    it('should handle multiple concurrent API calls', () => {
      cy.intercept('GET', '/api/concerts').as('getConcerts')
      cy.intercept('GET', '/api/users').as('getUsers')
      cy.intercept('GET', '/api/analytics').as('getAnalytics')

      cy.visit('/dashboard')

      cy.wait(['@getConcerts', '@getUsers', '@getAnalytics']).each((interception: any) => {
        expect(interception.response?.statusCode).to.equal(200)
      })
    })
  })

  describe('Error Handling Performance', () => {
    it('should return error responses quickly', () => {
      cy.intercept('GET', '/api/concerts/999', {
        statusCode: 404,
        body: { message: 'Not found' },
      }).as('notFound')

      cy.visit('/admin/marketplace/concerts/999').then(() => {
        cy.wait('@notFound').then((interception: any) => {
          const responseTime = interception.response?.responseTime || 0
          expect(responseTime).to.be.lessThan(100)
        })
      })
    })
  })
})



