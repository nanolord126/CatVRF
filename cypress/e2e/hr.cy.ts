// @ts-nocheck
/// <reference types="cypress" />
/// <reference types="chai" />

/**
 * B2B HR Management E2E Tests
 * 
 * Tests for employee management, leave requests, performance tracking
 */

describe('B2B HR Management', () => {
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
    cy.loginAs('admin@kotvrf.ru', 'password123')
  })

  describe('Employee Management', () => {
    it('should display employee list with details', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-table"]').should('be.visible')
      cy.get('[data-testid="employee-row"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="employee-name"]').first().should('not.be.empty')
      cy.get('[data-testid="employee-position"]').first().should('not.be.empty')
      cy.get('[data-testid="employee-status"]').first().should('not.be.empty')
    })

    it('should create new employee record', () => {
      cy.visit('/admin/b2b/hr/create')
      
      cy.get('[data-testid="input-first-name"]').type('John')
      cy.get('[data-testid="input-last-name"]').type('Smith')
      cy.get('[data-testid="input-email"]').type('john.smith@kotvrf.ru')
      cy.get('[data-testid="input-phone"]').type('+79991234567')
      cy.get('[data-testid="select-position"]').click()
      cy.get('[data-testid="position-manager"]').click()
      cy.get('[data-testid="select-department"]').click()
      cy.get('[data-testid="department-sales"]').click()
      cy.get('[data-testid="input-hire-date"]').type('2026-03-01')
      cy.get('[data-testid="input-salary"]').type('50000')
      cy.get('[data-testid="textarea-notes"]').type('New sales manager')
      
      cy.get('[data-testid="submit-button"]').click()
      
      cy.url().should('include', '/admin/b2b/hr')
      cy.contains(/created|успешно создан/i).should('be.visible')
    })

    it('should validate required employee fields', () => {
      cy.visit('/admin/b2b/hr/create')
      
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/required|обязательное/i).should('be.visible')
    })

    it('should edit employee information', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      
      cy.get('[data-testid="input-salary"]').clear().type('55000')
      cy.get('[data-testid="select-position"]').click()
      cy.get('[data-testid="position-lead"]').click()
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/updated|обновлено/i).should('be.visible')
    })

    it('should prevent duplicate email addresses', () => {
      cy.visit('/admin/b2b/hr/create')
      
      cy.get('[data-testid="input-first-name"]').type('Jane')
      cy.get('[data-testid="input-last-name"]').type('Doe')
      cy.get('[data-testid="input-email"]').type('admin@kotvrf.ru')
      cy.get('[data-testid="input-phone"]').type('+79991234568')
      cy.get('[data-testid="select-position"]').click()
      cy.get('[data-testid="position-manager"]').click()
      cy.get('[data-testid="select-department"]').click()
      cy.get('[data-testid="department-hr"]').click()
      cy.get('[data-testid="input-hire-date"]').type('2026-03-01')
      cy.get('[data-testid="input-salary"]').type('45000')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/duplicate|уже существует/i).should('be.visible')
    })
  })

  describe('Employee Leave Management', () => {
    it('should display leave balance for employees', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="leave-section"]').should('be.visible')
      cy.get('[data-testid="annual-leave-balance"]').should('match', /\d+/)
      cy.get('[data-testid="sick-leave-balance"]').should('match', /\d+/)
    })

    it('should create leave request', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="request-leave-button"]').click()
      
      cy.get('[data-testid="select-leave-type"]').click()
      cy.get('[data-testid="leave-annual"]').click()
      cy.get('[data-testid="input-start-date"]').type('2026-03-15')
      cy.get('[data-testid="input-end-date"]').type('2026-03-22')
      cy.get('[data-testid="textarea-reason"]').type('Family vacation')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/created|подана/i).should('be.visible')
    })

    it('should approve leave requests', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="leave-requests"]').click()
      
      cy.get('[data-testid="leave-request-row"]').first().click()
      cy.get('[data-testid="approve-button"]').click()
      cy.get('[data-testid="textarea-notes"]').type('Approved')
      cy.get('[data-testid="confirm-button"]').click()
      
      cy.contains(/approved|одобрено/i).should('be.visible')
    })

    it('should reject leave requests with reason', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="leave-requests"]').click()
      
      cy.get('[data-testid="leave-request-row"]').first().click()
      cy.get('[data-testid="reject-button"]').click()
      cy.get('[data-testid="textarea-reason"]').type('Insufficient staff coverage')
      cy.get('[data-testid="confirm-button"]').click()
      
      cy.contains(/rejected|отклонено/i).should('be.visible')
    })

    it('should update leave balance after approval', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="annual-leave-balance"]').then(($balance: JQuery<HTMLElement>) => {
        const initialBalance = parseInt($balance.text())
        
        cy.get('[data-testid="request-leave-button"]').click()
        cy.get('[data-testid="select-leave-type"]').click()
        cy.get('[data-testid="leave-annual"]').click()
        cy.get('[data-testid="input-start-date"]').type('2026-03-15')
        cy.get('[data-testid="input-end-date"]').type('2026-03-22')
        cy.get('[data-testid="textarea-reason"]').type('Vacation')
        cy.get('[data-testid="submit-button"]').click()
        
        cy.visit('/admin/b2b/hr')
        cy.get('[data-testid="employee-row"]').first().click()
        cy.get('[data-testid="annual-leave-balance"]').should('have.text', String(initialBalance - 8))
      })
    })

    it('should show leave history for employee', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="leave-history"]').click()
      
      cy.get('[data-testid="history-entry"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="history-date"]').first().should('not.be.empty')
      cy.get('[data-testid="history-status"]').first().should('not.be.empty')
    })
  })

  describe('Employee Performance', () => {
    it('should record performance reviews', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="add-review-button"]').click()
      
      cy.get('[data-testid="input-review-date"]').type('2026-03-15')
      cy.get('[data-testid="select-reviewer"]').click()
      cy.get('[data-testid="reviewer-option"]').first().click()
      cy.get('[data-testid="input-rating"]').type('4')
      cy.get('[data-testid="textarea-comments"]').type('Good performance this quarter')
      cy.get('[data-testid="textarea-improvements"]').type('Focus on team collaboration')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/added|добавлено/i).should('be.visible')
    })

    it('should display performance history', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="performance-tab"]').click()
      
      cy.get('[data-testid="review-entry"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="review-date"]').first().should('not.be.empty')
      cy.get('[data-testid="review-rating"]').first().should('match', /[1-5]/)
    })

    it('should calculate performance score trends', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="performance-tab"]').click()
      
      cy.get('[data-testid="performance-chart"]').should('be.visible')
      cy.get('[data-testid="average-rating"]').should('match', /\d+\.?\d*/)
    })
  })

  describe('Employee Documents', () => {
    it('should manage employee documents', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="documents-tab"]').click()
      
      cy.get('[data-testid="upload-document-button"]').click()
      cy.get('[data-testid="select-document-type"]').click()
      cy.get('[data-testid="document-contract"]').click()
      cy.get('[data-testid="file-input"]').selectFile('cypress/fixtures/contract.pdf')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/uploaded|загружено/i).should('be.visible')
    })

    it('should track document expiration dates', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="documents-tab"]').click()
      
      cy.get('[data-testid="document-entry"]').each(($doc) => {
        cy.wrap($doc).within(() => {
          cy.get('[data-testid="document-expiry"]').should('exist')
        })
      })
    })

    it('should alert on expiring documents', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="document-alert"]').should('exist')
      cy.get('[data-testid="document-alert"]').should('contain', /expiring|истекает/i)
    })
  })

  describe('Employee Contacts & Emergency', () => {
    it('should maintain emergency contact information', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      cy.get('[data-testid="emergency-section"]').click()
      
      cy.get('[data-testid="input-emergency-name"]').type('Jane Smith')
      cy.get('[data-testid="input-emergency-phone"]').type('+79991234569')
      cy.get('[data-testid="input-relationship"]').type('Spouse')
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/updated|обновлено/i).should('be.visible')
    })

    it('should track multiple emergency contacts', () => {
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      
      cy.get('[data-testid="add-emergency-contact"]').click()
      cy.get('[data-testid="input-emergency-name"]').last().type('John Doe')
      cy.get('[data-testid="input-emergency-phone"]').last().type('+79991234570')
      cy.get('[data-testid="input-relationship"]').last().type('Parent')
      
      cy.get('[data-testid="add-emergency-contact"]').click()
      cy.get('[data-testid="input-emergency-name"]').last().type('Bob Smith')
      cy.get('[data-testid="input-emergency-phone"]').last().type('+79991234571')
      cy.get('[data-testid="input-relationship"]').last().type('Brother')
      
      cy.get('[data-testid="submit-button"]').click()
      
      cy.visit('/admin/b2b/hr')
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="emergency-contact"]').should('have.length', 2)
    })
  })

  describe('HR Reports', () => {
    it('should generate employee roster report', () => {
      cy.visit('/admin/b2b/hr')
      cy.get('[data-testid="reports-button"]').click()
      cy.get('[data-testid="report-roster"]').click()
      
      cy.get('[data-testid="report-total-employees"]').should('match', /\d+/)
      cy.get('[data-testid="report-by-department"]').should('exist')
      cy.get('[data-testid="report-by-position"]').should('exist')
    })

    it('should generate leave summary report', () => {
      cy.visit('/admin/b2b/hr/reports')
      cy.get('[data-testid="report-leave-summary"]').click()
      
      cy.get('[data-testid="report-annual-leave-used"]').should('match', /\d+/)
      cy.get('[data-testid="report-sick-leave-used"]').should('match', /\d+/)
      cy.get('[data-testid="leave-by-employee"]').should('exist')
    })

    it('should export employee data to CSV', () => {
      cy.visit('/admin/b2b/hr')
      cy.get('[data-testid="export-button"]').click()
      cy.get('[data-testid="export-csv"]').click()
      
      cy.readFile('cypress/downloads/employees.csv').should('exist')
    })

    it('should generate compliance report', () => {
      cy.visit('/admin/b2b/hr/reports')
      cy.get('[data-testid="report-compliance"]').click()
      
      cy.get('[data-testid="compliance-check"]').should('exist')
      cy.get('[data-testid="missing-documents"]').should('exist')
      cy.get('[data-testid="expiring-certifications"]').should('exist')
    })
  })

  describe('HR Permissions', () => {
    it('should restrict HR access to authorized users', () => {
      cy.loginAs('viewer@kotvrf.ru', 'password123')
      cy.visit('/admin/b2b/hr')
      
      cy.contains(/not authorized|не авторизован/i).should('be.visible')
    })

    it('should show limited actions for read-only users', () => {
      cy.loginAs('manager@kotvrf.ru', 'password123')
      cy.visit('/admin/b2b/hr')
      
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="edit-button"]').should('be.disabled')
      cy.get('[data-testid="delete-button"]').should('be.disabled')
    })

    it('should log all HR changes', () => {
      cy.visit('/admin/b2b/hr')
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="history-tab"]').click()
      
      cy.get('[data-testid="audit-entry"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="audit-action"]').first().should('not.be.empty')
      cy.get('[data-testid="audit-user"]').first().should('not.be.empty')
    })
  })

  describe('HR Integrations', () => {
    it('should sync employee data to payroll', () => {
      cy.visit('/admin/b2b/hr')
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      cy.get('[data-testid="input-salary"]').clear().type('60000')
      cy.get('[data-testid="submit-button"]').click()
      
      // Check payroll reflects change
      cy.visit('/admin/b2b/payroll/create')
      cy.get('[data-testid="select-employees"]').click()
      cy.get('[data-testid="employee-1"]').then(($emp) => {
        cy.wrap($emp).should('contain', '60000')
      })
    })

    it('should create audit logs for HR actions', () => {
      cy.visit('/admin/b2b/hr')
      cy.get('[data-testid="employee-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      cy.get('[data-testid="input-salary"]').clear().type('65000')
      cy.get('[data-testid="submit-button"]').click()
      
      // Check audit log
      cy.visit('/admin/audit-logs')
      cy.get('[data-testid="search-input"]').type('employee')
      cy.get('[data-testid="audit-entry"]').should('have.length.greaterThan', 0)
    })
  })
})
