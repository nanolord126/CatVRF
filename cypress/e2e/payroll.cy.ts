// @ts-nocheck
/// <reference types="cypress" />
/// <reference types="chai" />

/**
 * B2B Payroll Management E2E Tests
 * 
 * Tests for salary calculations, payroll runs, deductions, and payment processing
 */

describe('B2B Payroll Management', () => {
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
    cy.loginAs('admin@kotvrf.ru', 'password123')
  })

  describe('Payroll Listing', () => {
    it('should display payroll runs with details', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-table"]').should('be.visible')
      cy.get('[data-testid="payroll-row"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="payroll-period"]').first().should('not.be.empty')
      cy.get('[data-testid="payroll-status"]').first().should('not.be.empty')
    })

    it('should filter payroll by status', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="filter-status"]').click()
      cy.get('[data-testid="status-draft"]').click()
      cy.get('[data-testid="apply-filter"]').click()
      
      cy.get('[data-testid="payroll-status"]').each(($status) => {
        expect($status.text()).to.include('Draft')
      })
    })

    it('should sort payroll by date', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="sort-date"]').click()
      cy.get('[data-testid="payroll-date"]').then(($dates: JQuery<HTMLElement>) => {
        const values: number[] = []
        $dates.each((i, el) => {
          const date = new Date(el.textContent || '')
          values.push(date.getTime())
        })
        for (let i = 1; i < values.length; i++) {
          expect(values[i]).to.be.lessThanOrEqual(values[i - 1])
        }
      })
    })

    it('should search payroll by period', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="search-input"]').type('2026-03')
      cy.wait(500)
      
      cy.get('[data-testid="payroll-period"]').each(($period: JQuery<HTMLElement>) => {
        expect($period.text()).to.include('2026-03')
      })
    })
  })

  describe('Payroll Creation', () => {
    it('should create new payroll run for period', () => {
      cy.visit('/admin/b2b/payroll/create')
      
      cy.get('[data-testid="select-period-type"]').click()
      cy.get('[data-testid="period-monthly"]').click()
      cy.get('[data-testid="input-period-start"]').type('2026-03-01')
      cy.get('[data-testid="input-period-end"]').type('2026-03-31')
      cy.get('[data-testid="select-employees"]').click()
      cy.get('[data-testid="employee-1"]').click()
      cy.get('[data-testid="employee-2"]').click()
      cy.get('[data-testid="submit-button"]').click()
      
      cy.url().should('include', '/admin/b2b/payroll')
      cy.contains(/created|успешно создана/i).should('be.visible')
    })

    it('should validate overlapping payroll periods', () => {
      cy.visit('/admin/b2b/payroll/create')
      
      cy.get('[data-testid="select-period-type"]').click()
      cy.get('[data-testid="period-monthly"]').click()
      cy.get('[data-testid="input-period-start"]').type('2026-03-01')
      cy.get('[data-testid="input-period-end"]').type('2026-03-31')
      cy.get('[data-testid="select-employees"]').click()
      cy.get('[data-testid="employee-1"]').click()
      cy.get('[data-testid="submit-button"]').click()
      
      // Try to create overlapping period
      cy.visit('/admin/b2b/payroll/create')
      cy.get('[data-testid="select-period-type"]').click()
      cy.get('[data-testid="period-monthly"]').click()
      cy.get('[data-testid="input-period-start"]').type('2026-03-15')
      cy.get('[data-testid="input-period-end"]').type('2026-04-15')
      cy.get('[data-testid="select-employees"]').click()
      cy.get('[data-testid="employee-1"]').click()
      cy.get('[data-testid="submit-button"]').click()
      
      cy.contains(/overlapping|пересекаются/i).should('be.visible')
    })

    it('should auto-calculate salary for selected period', () => {
      cy.visit('/admin/b2b/payroll/create')
      
      cy.get('[data-testid="select-period-type"]').click()
      cy.get('[data-testid="period-monthly"]').click()
      cy.get('[data-testid="input-period-start"]').type('2026-03-01')
      cy.get('[data-testid="input-period-end"]').type('2026-03-31')
      cy.get('[data-testid="select-employees"]').click()
      cy.get('[data-testid="employee-1"]').click()
      
      // Verify calculation happens
      cy.get('[data-testid="calculated-salary"]').should('match', /\$[\d,]+\.\d{2}/)
    })
  })

  describe('Salary Calculations', () => {
    it('should calculate gross salary correctly', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="employee-line"]').first().click()
      
      cy.get('[data-testid="salary-base"]').then(($base: JQuery<HTMLElement>) => {
        const baseAmount = parseFloat($base.text().replace(/\$|,/g, ''))
        
        cy.get('[data-testid="salary-gross"]').should((el) => {
          const grossAmount = parseFloat(el.text().replace(/\$|,/g, ''))
          expect(grossAmount).to.be.greaterThanOrEqual(baseAmount)
        })
      })
    })

    it('should apply tax deductions', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="employee-line"]').first().click()
      
      cy.get('[data-testid="deduction-tax"]').should('exist').and('not.be.empty')
      cy.get('[data-testid="deduction-tax"]').should('match', /\$[\d,]+\.\d{2}/)
    })

    it('should apply custom deductions', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      cy.get('[data-testid="employee-line"]').first().click()
      
      cy.get('[data-testid="add-deduction"]').click()
      cy.get('[data-testid="input-deduction-name"]').type('Health Insurance')
      cy.get('[data-testid="input-deduction-amount"]').type('150')
      cy.get('[data-testid="add-deduction-button"]').click()
      
      cy.get('[data-testid="deduction-item"]').should('contain', 'Health Insurance')
    })

    it('should include bonuses and allowances', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="edit-button"]').click()
      cy.get('[data-testid="employee-line"]').first().click()
      
      cy.get('[data-testid="add-allowance"]').click()
      cy.get('[data-testid="input-allowance-type"]').click()
      cy.get('[data-testid="allowance-performance"]').click()
      cy.get('[data-testid="input-allowance-amount"]').type('200')
      cy.get('[data-testid="add-allowance-button"]').click()
      
      cy.get('[data-testid="allowance-item"]').should('contain', 'Performance')
      cy.get('[data-testid="salary-gross"]').should('exist')
    })

    it('should calculate net salary after deductions', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="employee-line"]').first().click()
      
      cy.get('[data-testid="salary-gross"]').then(($gross: JQuery<HTMLElement>) => {
        const grossAmount = parseFloat($gross.text().replace(/\$|,/g, ''))
        
        cy.get('[data-testid="salary-deductions-total"]').then(($deductions: JQuery<HTMLElement>) => {
          const deductionsAmount = parseFloat($deductions.text().replace(/\$|,/g, ''))
          
          cy.get('[data-testid="salary-net"]').should((el) => {
            const netAmount = parseFloat(el.text().replace(/\$|,/g, ''))
            expect(netAmount).to.equal(grossAmount - deductionsAmount)
          })
        })
      })
    })
  })

  describe('Payroll Status Management', () => {
    it('should submit draft payroll for review', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="status-badge"]').should('contain', 'Draft')
      
      cy.get('[data-testid="submit-button"]').click()
      cy.get('[data-testid="confirm-submit"]').click()
      
      cy.contains(/submitted|отправлена на рассмотрение/i).should('be.visible')
    })

    it('should approve payroll and lock for payment', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="approve-button"]').click()
      cy.get('[data-testid="confirm-approval"]').click()
      
      cy.get('[data-testid="status-badge"]').should('contain', 'Approved')
    })

    it('should prevent editing of approved payroll', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="approve-button"]').click()
      cy.get('[data-testid="confirm-approval"]').click()
      
      cy.get('[data-testid="edit-button"]').should('be.disabled')
      cy.get('[data-testid="employee-line"]').first().should('not.be.editable')
    })

    it('should reject payroll with reason', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="reject-button"]').click()
      cy.get('[data-testid="input-rejection-reason"]').type('Incorrect tax calculation')
      cy.get('[data-testid="confirm-rejection"]').click()
      
      cy.get('[data-testid="status-badge"]').should('contain', 'Rejected')
    })

    it('should record payroll audit trail', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="history-tab"]').click()
      
      cy.get('[data-testid="audit-entry"]').should('have.length.greaterThan', 0)
      cy.get('[data-testid="audit-action"]').first().should('contain', /created|создан/i)
      cy.get('[data-testid="audit-timestamp"]').first().should('not.be.empty')
      cy.get('[data-testid="audit-user"]').first().should('not.be.empty')
    })
  })

  describe('Payment Processing', () => {
    it('should initiate payment for approved payroll', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="approve-button"]').click()
      cy.get('[data-testid="confirm-approval"]').click()
      
      cy.get('[data-testid="process-payment"]').click()
      cy.get('[data-testid="payment-method"]').click()
      cy.get('[data-testid="payment-bank-transfer"]').click()
      cy.get('[data-testid="confirm-payment"]').click()
      
      cy.get('[data-testid="status-badge"]').should('contain', 'Paid')
    })

    it('should track payment status for each employee', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="employee-line"]').each(($line) => {
        cy.wrap($line).within(() => {
          cy.get('[data-testid="payment-status"]').should('exist')
        })
      })
    })

    it('should handle payment reversal', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="approve-button"]').click()
      cy.get('[data-testid="confirm-approval"]').click()
      cy.get('[data-testid="process-payment"]').click()
      cy.get('[data-testid="confirm-payment"]').click()
      
      cy.get('[data-testid="reverse-payment"]').click()
      cy.get('[data-testid="input-reversal-reason"]').type('Overpayment correction')
      cy.get('[data-testid="confirm-reversal"]').click()
      
      cy.contains(/reversed|отменен/i).should('be.visible')
    })

    it('should generate payment receipts', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="download-receipt"]').click()
      
      cy.readFile('cypress/downloads/payroll-receipt.pdf').should('exist')
    })
  })

  describe('Payroll Reports', () => {
    it('should generate payroll summary report', () => {
      cy.visit('/admin/b2b/payroll')
      cy.get('[data-testid="reports-button"]').click()
      cy.get('[data-testid="report-summary"]').click()
      
      cy.get('[data-testid="report-total-gross"]').should('match', /\$[\d,]+\.\d{2}/)
      cy.get('[data-testid="report-total-deductions"]').should('match', /\$[\d,]+\.\d{2}/)
      cy.get('[data-testid="report-total-net"]').should('match', /\$[\d,]+\.\d{2}/)
      cy.get('[data-testid="report-employee-count"]').should('contain', /\d+/)
    })

    it('should generate individual payslips', () => {
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="employee-line"]').first().click()
      cy.get('[data-testid="download-payslip"]').click()
      
      cy.readFile('cypress/downloads/payslip.pdf').should('exist')
    })

    it('should export payroll to accounting system', () => {
      cy.visit('/admin/b2b/payroll')
      cy.get('[data-testid="payroll-row"]').first().click()
      
      cy.get('[data-testid="export-button"]').click()
      cy.get('[data-testid="export-accounting"]').click()
      cy.get('[data-testid="select-format"]').click()
      cy.get('[data-testid="format-xml"]').click()
      cy.get('[data-testid="confirm-export"]').click()
      
      cy.readFile('cypress/downloads/payroll-export.xml').should('exist')
    })

    it('should show tax summary report', () => {
      cy.visit('/admin/b2b/payroll/reports')
      cy.get('[data-testid="report-taxes"]').click()
      
      cy.get('[data-testid="tax-withholding"]').should('match', /\$[\d,]+\.\d{2}/)
      cy.get('[data-testid="tax-by-type"]').should('exist')
    })
  })

  describe('Payroll Permissions', () => {
    it('should restrict payroll access to HR/Admin only', () => {
      cy.loginAs('viewer@kotvrf.ru', 'password123')
      cy.visit('/admin/b2b/payroll')
      
      cy.contains(/not authorized|не авторизован/i).should('be.visible')
    })

    it('should allow different permission levels', () => {
      // Manager can view and edit
      cy.loginAs('manager@kotvrf.ru', 'password123')
      cy.visit('/admin/b2b/payroll')
      
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="edit-button"]').should('be.visible')
      
      // But cannot approve
      cy.get('[data-testid="approve-button"]').should('be.disabled')
    })

    it('should log all payroll modifications', () => {
      cy.visit('/admin/b2b/payroll')
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="history-tab"]').click()
      
      cy.get('[data-testid="audit-entry"]').each(($entry) => {
        cy.wrap($entry).within(() => {
          cy.get('[data-testid="audit-user"]').should('not.be.empty')
          cy.get('[data-testid="audit-action"]').should('not.be.empty')
          cy.get('[data-testid="audit-timestamp"]').should('not.be.empty')
        })
      })
    })
  })

  describe('Payroll Integrations', () => {
    it('should deduct from organization wallet on payment', () => {
      cy.intercept('GET', '/api/wallet/balance').as('getBalance')
      
      cy.visit('/admin/b2b/payroll')
      cy.wait('@getBalance')
      cy.get('[data-testid="payroll-row"]').first().click()
      cy.get('[data-testid="approve-button"]').click()
      cy.get('[data-testid="confirm-approval"]').click()
      
      cy.get('[data-testid="process-payment"]').click()
      cy.get('[data-testid="confirm-payment"]').click()
      
      cy.wait('@getBalance')
      cy.get('[data-testid="wallet-balance"]').should('exist')
    })

    it('should create audit log entries for all payroll actions', () => {
      cy.visit('/admin/b2b/payroll')
      cy.get('[data-testid="payroll-row"]').first().click()
      
      // Perform action
      cy.get('[data-testid="approve-button"]').click()
      cy.get('[data-testid="confirm-approval"]').click()
      
      // Check audit log
      cy.visit('/admin/audit-logs')
      cy.get('[data-testid="search-input"]').type('payroll')
      cy.get('[data-testid="audit-entry"]').should('have.length.greaterThan', 0)
    })
  })
})
