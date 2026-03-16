// @ts-nocheck
/// <reference types="cypress" />
/// <reference types="chai" />

/**
 * B2B Inventory Management E2E Tests
 * 
 * Tests for warehouse stock management, inventory tracking, and stock alerts
 */

describe('B2B Inventory Management', () => {
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
    cy.loginAs('admin@kotvrf.ru', 'password123')
  })

  describe('Inventory Listing', () => {
    it('should display inventory items with correct information', () => {
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="inventory-table"]').should('be.visible')
      cy.get('[data-testid="inventory-row"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="inventory-name"]').first().should('not.be.empty')
      cy.get('[data-testid="inventory-quantity"]').first().should('match', /\d+/)
    })

    it('should filter inventory by status', () => {
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="filter-status"]').click()
      cy.get('[data-testid="status-in-stock"]').click()
      cy.get('[data-testid="apply-filter"]').click()
      
      cy.get('[data-testid="inventory-status"]').each(($status) => {
        expect($status.text()).to.include('In Stock')
      })
    })

    it('should sort inventory by quantity', () => {
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="sort-quantity"]').click()
      cy.get('[data-testid="inventory-quantity"]').then(($quantities: JQuery<HTMLElement>) => {
        const values: number[] = []
        $quantities.each((i, el) => {
          values.push(parseInt(el.textContent || '0'))
        })
        // Verify sorted order
        for (let i = 1; i < values.length; i++) {
          expect(values[i]).to.be.greaterThanOrEqual(values[i - 1])
        }
      })
    })

    it('should search inventory items by name', () => {
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="search-input"]').type('Widget')
      cy.wait(500)
      
      cy.get('[data-testid="inventory-name"]').each(($item: JQuery<HTMLElement>) => {
        expect($item.text()).to.include('Widget')
      })
    })

    it('should paginate inventory results', () => {
      cy.visit('/admin/b2b/inventory?per_page=10')
      
      cy.get('[data-testid="inventory-row"]').should('have.length.lessThanOrEqual', 10)
      cy.get('[data-testid="pagination-next"]').should('exist')
      cy.get('[data-testid="pagination-next"]').click()
      cy.url().should('include', 'page=2')
    })
  })

  describe('Inventory Creation', () => {
    it('should create new inventory item with valid data', () => {
      cy.visit('/admin/b2b/inventory/create')
      
      cy.get('[data-testid="input-name"]').type('Premium Widget Pro')
      cy.get('[data-testid="input-sku"]').type('PWP-001-2026')
      cy.get('[data-testid="input-quantity"]').type('100')
      cy.get('[data-testid="input-reorder-level"]').type('20')
      cy.get('[data-testid="input-unit-price"]').type('49.99')
      cy.get('[data-testid="select-category"]').click()
      cy.get('[data-testid="category-electronics"]').click()
      cy.get('[data-testid="textarea-description"]').type('High quality widget for professional use')
      
      cy.get('[data-testid="submit-button"]').click()
      
      cy.url().should('include', '/admin/b2b/inventory')
      cy.contains(/created|успешно/i).should('be.visible')
    })

    it('should validate required fields on inventory creation', () => {
      cy.visit('/admin/b2b/inventory/create')
      
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/required|обязательное/i).should('be.visible').and('have.length.greaterThan', 0)
    })

    it('should prevent duplicate SKU creation', () => {
      cy.visit('/admin/b2b/inventory/create')
      
      cy.get('[data-testid="input-name"]').type('Test Item')
      cy.get('[data-testid="input-sku"]').type('TEST-001')
      cy.get('[data-testid="input-quantity"]').type('50')
      cy.get('[data-testid="input-reorder-level"]').type('10')
      cy.get('[data-testid="input-unit-price"]').type('29.99')
      cy.get('[data-testid="submit-button"]').click()
      
      // Try to create with same SKU
      cy.visit('/admin/b2b/inventory/create')
      cy.get('[data-testid="input-name"]').type('Duplicate Item')
      cy.get('[data-testid="input-sku"]').type('TEST-001')
      cy.get('[data-testid="input-quantity"]').type('25')
      cy.get('[data-testid="input-reorder-level"]').type('5')
      cy.get('[data-testid="input-unit-price"]').type('19.99')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/duplicate|уже существует/i).should('be.visible')
    })
  })

  describe('Inventory Updates', () => {
    it('should edit inventory item details', () => {
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="inventory-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      
      cy.get('[data-testid="input-quantity"]').clear().type('200')
      cy.get('[data-testid="input-unit-price"]').clear().type('59.99')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/updated|обновлено/i).should('be.visible')
    })

    it('should update inventory quantity through stock movement', () => {
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="inventory-row"]').first().click()
      cy.get('[data-testid="adjust-stock-button"]').click()
      
      cy.get('[data-testid="input-adjustment"]').type('50')
      cy.get('[data-testid="select-reason"]').click()
      cy.get('[data-testid="reason-received"]').click()
      cy.get('[data-testid="textarea-notes"]').type('Stock replenishment')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/adjustment|изменение/i).should('be.visible')
    })

    it('should track inventory movement history', () => {
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="inventory-row"]').first().click()
      cy.get('[data-testid="history-tab"]').click()
      
      cy.get('[data-testid="movement-entry"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="movement-timestamp"]').first().should('not.be.empty')
      cy.get('[data-testid="movement-user"]').first().should('not.be.empty')
    })
  })

  describe('Stock Alerts & Thresholds', () => {
    it('should show low stock alerts for items below reorder level', () => {
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="inventory-alert"]').should('exist')
      cy.get('[data-testid="inventory-alert"]').should('contain', /low|низкий/i)
    })

    it('should filter items by alert status', () => {
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="filter-button"]').click()
      cy.get('[data-testid="filter-alerts-only"]').click()
      cy.get('[data-testid="apply-filter"]').click()
      
      cy.get('[data-testid="inventory-alert"]').each(($alert) => {
        expect($alert).to.exist
      })
    })

    it('should allow setting custom reorder levels', () => {
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="inventory-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      
      cy.get('[data-testid="input-reorder-level"]').clear().type('50')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.visit('/admin/b2b/inventory')
      cy.get('[data-testid="inventory-row"]').first().click()
      cy.get('[data-testid="reorder-level-display"]').should('contain', '50')
    })
  })

  describe('Inventory Deletion', () => {
    it('should archive inventory item instead of deleting', () => {
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="inventory-row"]').first().click()
      cy.get('[data-testid="delete-button"]').click()
      cy.get('[data-testid="confirm-archive"]').click()
      
      cy.contains(/archived|архивировано/i).should('be.visible')
    })

    it('should not allow deletion of items with recent movements', () => {
      cy.visit('/admin/b2b/inventory')
      
      // Create item and make a movement
      cy.get('[data-testid="inventory-row"]').first().click()
      cy.get('[data-testid="adjust-stock-button"]').click()
      cy.get('[data-testid="input-adjustment"]').type('10')
      cy.get('[data-testid="select-reason"]').click()
      cy.get('[data-testid="reason-issued"]').click()
      cy.get('[data-testid="submit-button"]').click()
      
      // Try to delete
      cy.visit('/admin/b2b/inventory')
      cy.get('[data-testid="inventory-row"]').first().click()
      cy.get('[data-testid="delete-button"]').click()
      
      cy.contains(/cannot delete|не может быть удален/i).should('be.visible')
    })
  })

  describe('Inventory Permissions', () => {
    it('should restrict inventory access to B2B users only', () => {
      cy.loginAs('viewer@kotvrf.ru', 'password123')
      cy.visit('/admin/b2b/inventory')
      
      cy.contains(/not authorized|не авторизован/i).should('be.visible')
      cy.url().should('include', '/admin')
    })

    it('should allow create only for manager role', () => {
      cy.loginAs('manager@kotvrf.ru', 'password123')
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="create-button"]').should('be.visible')
    })

    it('should prevent unauthorized price modifications', () => {
      // Create test user with limited permissions
      cy.visit('/admin/b2b/inventory')
      cy.get('[data-testid="inventory-row"]').first().click()
      
      // Price field should be read-only for non-managers
      cy.get('[data-testid="input-unit-price"]').should('be.disabled')
    })
  })

  describe('Bulk Operations', () => {
    it('should allow bulk status updates', () => {
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="select-all-checkbox"]').click()
      cy.get('[data-testid="bulk-actions-button"]').click()
      cy.get('[data-testid="bulk-action-archive"]').click()
      cy.get('[data-testid="confirm-bulk-action"]').click()
      
      cy.contains(/updated|обновлено/i).should('be.visible')
    })

    it('should allow bulk export to CSV', () => {
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="export-button"]').click()
      cy.get('[data-testid="export-csv"]').click()
      
      // Check that file download started
      cy.readFile('cypress/downloads/inventory.csv').should('exist')
    })

    it('should allow bulk import from CSV', () => {
      cy.visit('/admin/b2b/inventory')
      
      cy.get('[data-testid="import-button"]').click()
      cy.get('[data-testid="file-input"]').selectFile('cypress/fixtures/inventory-import.csv')
      cy.get('[data-testid="preview-button"]').click()
      
      cy.get('[data-testid="import-row"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="confirm-import"]').click()
      
      cy.contains(/imported|импортировано/i).should('be.visible')
    })
  })

  describe('Inventory Reports', () => {
    it('should generate inventory summary report', () => {
      cy.visit('/admin/b2b/inventory')
      cy.get('[data-testid="reports-button"]').click()
      cy.get('[data-testid="report-summary"]').click()
      
      cy.get('[data-testid="report-total-items"]').should('contain', /\d+/)
      cy.get('[data-testid="report-total-value"]').should('match', /\$[\d,]+\.\d{2}/)
      cy.get('[data-testid="report-low-stock-count"]').should('contain', /\d+/)
    })

    it('should show inventory valuation report', () => {
      cy.visit('/admin/b2b/inventory/reports')
      
      cy.get('[data-testid="report-date-range"]').click()
      cy.get('[data-testid="date-start"]').type('2026-01-01')
      cy.get('[data-testid="date-end"]').type('2026-03-15')
      cy.get('[data-testid="generate-button"]').click()
      
      cy.get('[data-testid="valuation-table"]').should('be.visible')
      cy.get('[data-testid="valuation-total"]').should('match', /\$[\d,]+\.\d{2}/)
    })

    it('should track cost of goods sold by category', () => {
      cy.visit('/admin/b2b/inventory/reports')
      cy.get('[data-testid="report-cogs"]').click()
      
      cy.get('[data-testid="cogs-row"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="cogs-amount"]').first().should('match', /\$[\d,]+\.\d{2}/)
    })
  })

  describe('Inventory Integration', () => {
    it('should sync inventory with orders', () => {
      cy.visit('/admin/marketplace/concerts')
      
      // Create an order that should deduct inventory
      cy.get('[data-testid="create-button"]').click()
      cy.get('[data-testid="input-name"]').type('New Concert')
      cy.get('[data-testid="input-capacity"]').type('500')
      cy.get('[data-testid="input-price"]').type('50')
      cy.get('[data-testid="submit-button"]').click()
      
      // Check inventory was adjusted
      cy.visit('/admin/b2b/inventory')
      cy.get('[data-testid="inventory-movement"]').should('exist')
    })

    it('should maintain inventory consistency across users', () => {
      cy.intercept('GET', '/api/inventory/*').as('getInventory')
      
      cy.visit('/admin/b2b/inventory')
      cy.wait('@getInventory')
      cy.get('[data-testid="inventory-quantity"]').first().then(($qty: JQuery<HTMLElement>) => {
        const initialQty = $qty.text()
        
        // Make update
        cy.get('[data-testid="inventory-row"]').first().click()
        cy.get('[data-testid="adjust-stock-button"]').click()
        cy.get('[data-testid="input-adjustment"]').type('10')
        cy.get('[data-testid="select-reason"]').click()
        cy.get('[data-testid="reason-received"]').click()
        cy.get('[data-testid="submit-button"]').click()
        
        // Verify update
        cy.visit('/admin/b2b/inventory')
        cy.wait('@getInventory')
        cy.get('[data-testid="inventory-quantity"]').first().should('not.have.text', initialQty)
      })
    })
  })
})
