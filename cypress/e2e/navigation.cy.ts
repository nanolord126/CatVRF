// @ts-nocheck
describe('Navigation & Button Interactions - Complete', () => {
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
    cy.loginAs('admin@test.local', 'password123')
  })

  describe('Main Navigation Menu', () => {
    it('should navigate to Inventory from menu', () => {
      cy.get('[data-testid="nav-inventory"]').click()
      cy.url().should('include', '/inventory')
      cy.contains('Инвентарь').should('be.visible')
    })

    it('should navigate to Payroll from menu', () => {
      cy.get('[data-testid="nav-payroll"]').click()
      cy.url().should('include', '/payroll')
    })

    it('should navigate to HR from menu', () => {
      cy.get('[data-testid="nav-hr"]').click()
      cy.url().should('include', '/hr')
    })

    it('should navigate to Communications from menu', () => {
      cy.get('[data-testid="nav-communications"]').click()
      cy.url().should('include', '/communications')
    })

    it('should navigate to Beauty from menu', () => {
      cy.get('[data-testid="nav-beauty"]').click()
      cy.url().should('include', '/beauty')
    })

    it('should expand/collapse nested menu items', () => {
      cy.get('[data-testid="nav-inventory-toggle"]').click()
      cy.get('[data-testid="nav-inventory-submenu"]').should('be.visible')
      cy.get('[data-testid="nav-inventory-toggle"]').click()
      cy.get('[data-testid="nav-inventory-submenu"]').should('not.be.visible')
    })

    it('should highlight active menu item', () => {
      cy.get('[data-testid="nav-inventory"]').click()
      cy.get('[data-testid="nav-inventory"]').should('have.class', 'active')
    })

    it('should navigate to inventory sub-pages', () => {
      cy.get('[data-testid="nav-inventory"]').click()
      cy.get('[data-testid="nav-inventory-list"]').click()
      cy.url().should('include', '/inventory')
      
      cy.get('[data-testid="nav-inventory-reports"]').click()
      cy.url().should('include', '/inventory/reports')
    })
  })

  describe('Create Buttons', () => {
    it('should navigate to inventory create page', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-create"]').click()
      cy.url().should('include', '/inventory/create')
    })

    it('should navigate to employee create page', () => {
      cy.visit('/hr/employees')
      cy.get('[data-testid="btn-create"]').click()
      cy.url().should('include', '/hr/employees/create')
    })

    it('should navigate to payroll create page', () => {
      cy.visit('/payroll')
      cy.get('[data-testid="btn-create-payroll"]').click()
      cy.url().should('include', '/payroll/create')
    })

    it('should open modal for inline creation', () => {
      cy.visit('/beauty/services')
      cy.get('[data-testid="btn-create-modal"]').click()
      cy.get('[data-testid="modal-create"]').should('be.visible')
    })
  })

  describe('Edit & Update Buttons', () => {
    it('should navigate to edit page from list', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-edit-1"]').click()
      cy.url().should('include', '/inventory/1/edit')
    })

    it('should enable edit mode with button click', () => {
      cy.visit('/inventory/1/edit')
      cy.get('[data-testid="input-name"]').should('be.enabled')
      cy.get('[data-testid="input-quantity"]').should('be.enabled')
    })

    it('should save changes with Save button', () => {
      cy.visit('/inventory/1/edit')
      cy.get('[data-testid="input-quantity"]').clear().type('200')
      cy.get('[data-testid="btn-save"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
      cy.url().should('include', '/inventory')
    })

    it('should cancel editing without saving', () => {
      cy.visit('/inventory/1/edit')
      cy.get('[data-testid="input-quantity"]').clear().type('999')
      cy.get('[data-testid="btn-cancel"]').click()
      cy.url().should('include', '/inventory')
      // Verify changes were not saved
      cy.get('[data-testid="quantity-1"]').should('not.contain', '999')
    })
  })

  describe('Delete & Archive Buttons', () => {
    it('should show delete confirmation modal', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-delete-1"]').click()
      cy.get('[data-testid="modal-delete"]').should('be.visible')
    })

    it('should delete item on confirmation', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-delete-1"]').click()
      cy.get('[data-testid="btn-confirm-delete"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
      cy.get('[data-testid="row-1"]').should('not.exist')
    })

    it('should cancel delete operation', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-delete-1"]').click()
      cy.get('[data-testid="btn-cancel-delete"]').click()
      cy.get('[data-testid="modal-delete"]').should('not.exist')
      cy.get('[data-testid="row-1"]').should('exist')
    })

    it('should archive item instead of delete', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-more-1"]').click()
      cy.get('[data-testid="btn-archive-1"]').click()
      cy.get('[data-testid="toast-success"]').should('contain', 'Архивировано')
    })
  })

  describe('Form Buttons', () => {
    it('should enable/disable submit button based on form validity', () => {
      cy.visit('/inventory/create')
      cy.get('[data-testid="btn-submit"]').should('be.disabled')
      
      cy.get('[data-testid="input-name"]').type('New Item')
      cy.get('[data-testid="input-sku"]').type('NEW-001')
      cy.get('[data-testid="input-quantity"]').type('100')
      cy.get('[data-testid="btn-submit"]').should('be.enabled')
    })

    it('should show loading state while submitting', () => {
      cy.visit('/inventory/create')
      cy.get('[data-testid="input-name"]').type('New Item')
      cy.get('[data-testid="input-sku"]').type('NEW-001')
      cy.get('[data-testid="btn-submit"]').click()
      
      cy.get('[data-testid="btn-submit"]').should('have.class', 'loading')
      cy.get('[data-testid="btn-submit"]').should('be.disabled')
    })

    it('should clear form with Reset button', () => {
      cy.visit('/inventory/create')
      cy.get('[data-testid="input-name"]').type('Test')
      cy.get('[data-testid="input-sku"]').type('TEST-001')
      cy.get('[data-testid="btn-reset"]').click()
      
      cy.get('[data-testid="input-name"]').should('have.value', '')
      cy.get('[data-testid="input-sku"]').should('have.value', '')
    })
  })

  describe('Action Buttons in Table', () => {
    it('should show action buttons on row hover', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="row-1"]').trigger('mouseenter')
      cy.get('[data-testid="btn-edit-1"]').should('be.visible')
      cy.get('[data-testid="btn-delete-1"]').should('be.visible')
    })

    it('should have quick action buttons for multiple actions', () => {
      cy.visit('/payroll')
      cy.get('[data-testid="row-1"]').within(() => {
        cy.get('[data-testid="btn-view"]').should('be.visible')
        cy.get('[data-testid="btn-approve"]').should('be.visible')
        cy.get('[data-testid="btn-pay"]').should('be.visible')
      })
    })

    it('should open context menu on right-click', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="row-1"]').rightclick()
      cy.get('[data-testid="context-menu"]').should('be.visible')
      cy.get('[data-testid="menu-view"]').should('be.visible')
      cy.get('[data-testid="menu-edit"]').should('be.visible')
      cy.get('[data-testid="menu-delete"]').should('be.visible')
    })
  })

  describe('Filter & Search Buttons', () => {
    it('should apply filter on button click', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="input-filter-category"]').select('Electronics')
      cy.get('[data-testid="btn-apply-filter"]').click()
      cy.get('[data-testid="active-filter"]').should('contain', 'Electronics')
    })

    it('should clear all filters', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="input-filter-category"]').select('Electronics')
      cy.get('[data-testid="btn-apply-filter"]').click()
      cy.get('[data-testid="btn-clear-filters"]').click()
      cy.get('[data-testid="input-filter-category"]').should('have.value', '')
    })

    it('should search on Enter key', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="input-search"]').type('test{enter}')
      cy.get('[data-testid="search-results"]').should('be.visible')
    })

    it('should have search button as alternative', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="input-search"]').type('test')
      cy.get('[data-testid="btn-search"]').click()
      cy.get('[data-testid="search-results"]').should('be.visible')
    })
  })

  describe('Pagination Buttons', () => {
    it('should navigate to next page', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="current-page"]').should('contain', '1')
      cy.get('[data-testid="btn-next"]').click()
      cy.get('[data-testid="current-page"]').should('contain', '2')
    })

    it('should navigate to previous page', () => {
      cy.visit('/inventory?page=2')
      cy.get('[data-testid="btn-prev"]').click()
      cy.get('[data-testid="current-page"]').should('contain', '1')
    })

    it('should navigate to specific page', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="page-3"]').click()
      cy.get('[data-testid="current-page"]').should('contain', '3')
    })

    it('should disable previous button on first page', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-prev"]').should('be.disabled')
    })

    it('should disable next button on last page', () => {
      cy.visit('/inventory?page=999')
      cy.get('[data-testid="btn-next"]').should('be.disabled')
    })
  })

  describe('Export & Import Buttons', () => {
    it('should download file on export button click', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-export"]').click()
      cy.readFile('cypress/downloads/inventory.csv').should('exist')
    })

    it('should open file picker on import button click', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-import"]').click()
      cy.get('[data-testid="input-file"]').should('be.visible')
    })

    it('should export as different formats', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-export-menu"]').click()
      cy.get('[data-testid="export-csv"]').click()
      cy.readFile('cypress/downloads/inventory.csv').should('exist')
      
      cy.get('[data-testid="btn-export-menu"]').click()
      cy.get('[data-testid="export-pdf"]').click()
      cy.readFile('cypress/downloads/inventory.pdf').should('exist')
    })
  })

  describe('View Toggle Buttons', () => {
    it('should toggle between list and grid view', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-view-list"]').should('have.class', 'active')
      cy.get('[data-testid="btn-view-grid"]').click()
      cy.get('[data-testid="btn-view-grid"]').should('have.class', 'active')
      cy.get('[data-testid="inventory-grid"]').should('be.visible')
    })

    it('should toggle between table and card view', () => {
      cy.visit('/hr/employees')
      cy.get('[data-testid="btn-view-table"]').click()
      cy.get('[data-testid="employees-table"]').should('be.visible')
    })
  })

  describe('Action Menu Buttons', () => {
    it('should open dropdown menu on button click', () => {
      cy.visit('/inventory/1')
      cy.get('[data-testid="btn-actions"]').click()
      cy.get('[data-testid="dropdown-menu"]').should('be.visible')
    })

    it('should execute action from menu', () => {
      cy.visit('/inventory/1')
      cy.get('[data-testid="btn-actions"]').click()
      cy.get('[data-testid="action-duplicate"]').click()
      cy.get('[data-testid="toast-success"]').should('contain', 'Дублировано')
    })

    it('should close menu on outside click', () => {
      cy.visit('/inventory/1')
      cy.get('[data-testid="btn-actions"]').click()
      cy.get('[data-testid="dropdown-menu"]').should('be.visible')
      cy.get('[data-testid="main-content"]').click()
      cy.get('[data-testid="dropdown-menu"]').should('not.be.visible')
    })
  })

  describe('Approval & Workflow Buttons', () => {
    it('should approve item with button', () => {
      cy.visit('/payroll')
      cy.get('[data-testid="btn-approve-1"]').click()
      cy.get('[data-testid="status-1"]').should('contain', 'Одобрено')
    })

    it('should reject with confirmation', () => {
      cy.visit('/payroll')
      cy.get('[data-testid="btn-reject-1"]').click()
      cy.get('[data-testid="modal-reject"]').should('be.visible')
      cy.get('[data-testid="input-reject-reason"]').type('Invalid data')
      cy.get('[data-testid="btn-confirm-reject"]').click()
    })

    it('should transition through workflow', () => {
      cy.visit('/payroll')
      cy.get('[data-testid="btn-approve-1"]').click()
      cy.get('[data-testid="status-1"]').should('contain', 'Одобрено')
      cy.get('[data-testid="btn-pay-1"]').click()
      cy.get('[data-testid="status-1"]').should('contain', 'Выплачено')
    })
  })

  describe('Modal Dialog Buttons', () => {
    it('should close modal with close button', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-delete-1"]').click()
      cy.get('[data-testid="modal-delete"]').should('be.visible')
      cy.get('[data-testid="btn-close-modal"]').click()
      cy.get('[data-testid="modal-delete"]').should('not.exist')
    })

    it('should close modal with cancel button', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-delete-1"]').click()
      cy.get('[data-testid="btn-cancel"]').click()
      cy.get('[data-testid="modal-delete"]').should('not.exist')
    })

    it('should execute primary action on confirm', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-delete-1"]').click()
      cy.get('[data-testid="btn-confirm"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })
  })

  describe('Floating Action Buttons (FAB)', () => {
    it('should show FAB on scroll', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="fab-create"]').should('be.visible')
    })

    it('should navigate to create page on FAB click', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="fab-create"]').click()
      cy.url().should('include', '/inventory/create')
    })

    it('should show FAB menu on long press', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="fab"]').trigger('longpress')
      cy.get('[data-testid="fab-menu"]').should('be.visible')
    })
  })

  describe('Keyboard Navigation', () => {
    it('should navigate with Tab key', () => {
      cy.visit('/inventory/create')
      cy.get('[data-testid="input-name"]').focus()
      cy.focused().should('have.attr', 'data-testid', 'input-name')
      cy.get('body').tab()
      cy.focused().should('have.attr', 'data-testid', 'input-sku')
    })

    it('should submit form with Enter on button', () => {
      cy.visit('/inventory/create')
      cy.get('[data-testid="input-name"]').type('Test')
      cy.get('[data-testid="input-sku"]').type('TEST-001')
      cy.get('[data-testid="btn-submit"]').focus().type('{enter}')
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should cancel with Escape key', () => {
      cy.visit('/inventory/1/edit')
      cy.get('body').type('{esc}')
      cy.url().should('include', '/inventory')
    })
  })

  describe('Accessibility - ARIA Buttons', () => {
    it('should have aria-label on icon buttons', () => {
      cy.get('[data-testid="btn-edit-1"]').should('have.attr', 'aria-label')
      cy.get('[data-testid="btn-delete-1"]').should('have.attr', 'aria-label')
    })

    it('should have role attributes', () => {
      cy.get('[data-testid="btn-submit"]').should('have.attr', 'role', 'button')
    })

    it('should be keyboard accessible', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-create"]').focus()
      cy.focused().should('have.attr', 'data-testid', 'btn-create')
      cy.get('[data-testid="btn-create"]').type('{enter}')
      cy.url().should('include', '/inventory/create')
    })
  })

  describe('Button Tooltips', () => {
    it('should show tooltip on hover', () => {
      cy.get('[data-testid="btn-help"]').trigger('mouseenter')
      cy.get('[data-testid="tooltip"]').should('be.visible')
    })

    it('should hide tooltip on mouse leave', () => {
      cy.get('[data-testid="btn-help"]').trigger('mouseenter')
      cy.get('[data-testid="tooltip"]').should('be.visible')
      cy.get('[data-testid="btn-help"]').trigger('mouseleave')
      cy.get('[data-testid="tooltip"]').should('not.be.visible')
    })
  })
})
