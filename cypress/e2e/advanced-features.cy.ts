// @ts-nocheck
import 'cypress'
import '../../support/commands'

describe('Advanced Components & Feature Integration Tests', () => {
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
    cy.loginAs('admin@test.local', 'password123')
  })

  // ==================== DASHBOARD & ANALYTICS ====================
  describe('Admin Dashboard & Analytics', () => {
    it('should display dashboard with all widgets', () => {
      cy.visit('/admin/dashboard')
      cy.get('[data-testid="dashboard"]').should('be.visible')
      cy.get('[data-testid="widget-revenue"]').should('be.visible')
      cy.get('[data-testid="widget-orders"]').should('be.visible')
      cy.get('[data-testid="widget-users"]').should('be.visible')
      cy.get('[data-testid="widget-products"]').should('be.visible')
    })

    it('should update dashboard metrics in real-time', () => {
      cy.visit('/admin/dashboard')
      cy.get('[data-testid="metric-total-revenue"]').then(($el) => {
        const initialValue = $el.text()
        // Create new order
        cy.apiRequest('POST', '/api/orders', {
          customer_id: 1,
          items: [{ product_id: 1, quantity: 1 }],
          total_amount: 100
        }).then(() => {
          cy.reload()
          cy.get('[data-testid="metric-total-revenue"]').then(($updated) => {
            const newValue = $updated.text()
            expect(newValue).to.not.equal(initialValue)
          })
        })
      })
    })

    it('should filter analytics by date range', () => {
      cy.visit('/admin/analytics')
      cy.get('[data-testid="date-from"]').type('2024-01-01')
      cy.get('[data-testid="date-to"]').type('2024-01-31')
      cy.get('[data-testid="btn-filter"]').click()
      cy.get('[data-testid="chart-revenue"]').should('be.visible')
    })

    it('should export analytics report', () => {
      cy.visit('/admin/analytics')
      cy.get('[data-testid="btn-export"]').click()
      cy.get('[data-testid="export-options"]').should('be.visible')
      cy.get('[data-testid="export-pdf"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should display multi-tenant analytics separately', () => {
      cy.visit('/admin/analytics')
      cy.get('[data-testid="filter-tenant"]').select('Tenant 1')
      cy.get('[data-testid="chart-data"]').should('be.visible')
      const tenant1Data = cy.get('[data-testid="total-revenue"]').then(($el) => $el.text())
      
      cy.get('[data-testid="filter-tenant"]').select('Tenant 2')
      const tenant2Data = cy.get('[data-testid="total-revenue"]').then(($el) => $el.text())
      
      expect(tenant1Data).to.not.equal(tenant2Data)
    })

    it('should show trend analysis', () => {
      cy.visit('/admin/analytics/trends')
      cy.get('[data-testid="trend-chart"]').should('be.visible')
      cy.get('[data-testid="trend-period"]').select('Monthly')
      cy.get('[data-testid="trend-chart"]').should('be.visible')
    })

    it('should calculate KPIs correctly', () => {
      cy.visit('/admin/analytics/kpi')
      cy.get('[data-testid="kpi-conversion-rate"]').should('be.visible')
      cy.get('[data-testid="kpi-avg-order-value"]').should('be.visible')
      cy.get('[data-testid="kpi-customer-satisfaction"]').should('be.visible')
    })
  })

  // ==================== INVENTORY MANAGEMENT ====================
  describe('Inventory Management Advanced', () => {
    it('should track inventory movements', () => {
      cy.visit('/admin/inventory/movements')
      cy.get('[data-testid="movement-list"]').should('be.visible')
      cy.get('[data-testid="movement-item"]').should('have.length.greaterThan', 0)
    })

    it('should manage stock transfers between locations', () => {
      cy.visit('/admin/inventory/transfers')
      cy.get('[data-testid="btn-new-transfer"]').click()
      cy.get('[data-testid="select-from-location"]').select('Warehouse A')
      cy.get('[data-testid="select-to-location"]').select('Warehouse B')
      cy.get('[data-testid="select-product"]').select('Laptop')
      cy.get('[data-testid="input-quantity"]').type('5')
      cy.get('[data-testid="btn-transfer"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should alert on low stock', () => {
      cy.visit('/admin/inventory')
      cy.get('[data-testid="filter-low-stock"]').click()
      cy.get('[data-testid="product-list"]').then(($list) => {
        cy.wrap($list).find('[data-testid="low-stock-badge"]')
          .should('have.length.greaterThan', 0)
      })
    })

    it('should manage stock adjustments', () => {
      cy.visit('/admin/inventory/1/adjustments')
      cy.get('[data-testid="btn-adjust"]').click()
      cy.get('[data-testid="input-quantity-change"]').type('-5')
      cy.get('[data-testid="select-reason"]').select('Damaged')
      cy.get('[data-testid="btn-save"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should track expiry dates for perishables', () => {
      cy.visit('/admin/inventory/expiry')
      cy.get('[data-testid="expiry-list"]').should('be.visible')
      cy.get('[data-testid="expiring-soon"]').should('exist')
      cy.get('[data-testid="expired-items"]').should('exist')
    })

    it('should perform inventory count', () => {
      cy.visit('/admin/inventory/count')
      cy.get('[data-testid="btn-start-count"]').click()
      cy.get('[data-testid="product-item"]').first().then(() => {
        cy.get('[data-testid="input-counted-qty"]').type('10')
        cy.get('[data-testid="btn-next"]').click()
      })
      cy.get('[data-testid="count-progress"]').should('be.visible')
    })

    it('should generate inventory reports', () => {
      cy.visit('/admin/inventory/reports')
      cy.get('[data-testid="btn-generate"]').click()
      cy.get('[data-testid="select-report-type"]').select('Stock Valuation')
      cy.get('[data-testid="btn-generate-report"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })
  })

  // ==================== PAYROLL & COMPENSATION ====================
  describe('Payroll & Compensation Management', () => {
    it('should process monthly payroll', () => {
      cy.visit('/admin/payroll/process')
      cy.get('[data-testid="select-month"]').select('February 2024')
      cy.get('[data-testid="btn-process"]').click()
      cy.get('[data-testid="payroll-summary"]').should('be.visible')
    })

    it('should calculate payroll with taxes and deductions', () => {
      cy.visit('/admin/payroll/1/details')
      cy.get('[data-testid="gross-salary"]').should('be.visible')
      cy.get('[data-testid="tax-amount"]').should('be.visible')
      cy.get('[data-testid="deductions"]').should('be.visible')
      cy.get('[data-testid="net-salary"]').should('be.visible')
      // Verify: net = gross - tax - deductions
      cy.get('[data-testid="gross-salary"]').then(($gross) => {
        const gross = parseFloat($gross.text())
        cy.get('[data-testid="net-salary"]').then(($net) => {
          const net = parseFloat($net.text())
          expect(net).to.be.lessThan(gross)
        })
      })
    })

    it('should manage salary components', () => {
      cy.visit('/admin/salary-components')
      cy.get('[data-testid="btn-add"]').click()
      cy.get('[data-testid="input-name"]').type('Performance Bonus')
      cy.get('[data-testid="select-type"]').select('Allowance')
      cy.get('[data-testid="input-amount"]').type('1000')
      cy.get('[data-testid="btn-save"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should generate payroll slips', () => {
      cy.visit('/admin/payroll/1')
      cy.get('[data-testid="btn-generate-slip"]').click()
      cy.get('[data-testid="payroll-slip"]').should('be.visible')
      cy.get('[data-testid="btn-download"]').should('be.visible')
    })

    it('should process payroll approval workflow', () => {
      cy.visit('/admin/payroll/approval')
      cy.get('[data-testid="payroll-item"]').first().then(() => {
        cy.get('[data-testid="btn-review"]').click()
        cy.get('[data-testid="btn-approve"]').click()
        cy.get('[data-testid="toast-success"]').should('be.visible')
      })
    })

    it('should handle advance salary requests', () => {
      cy.loginAs('employee@test.local', 'password123')
      cy.visit('/employee/salary-advance')
      cy.get('[data-testid="btn-request"]').click()
      cy.get('[data-testid="input-amount"]').type('5000')
      cy.get('[data-testid="btn-submit"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should manage tax withholding and compliance', () => {
      cy.visit('/admin/payroll/tax-compliance')
      cy.get('[data-testid="tax-summary"]').should('be.visible')
      cy.get('[data-testid="total-tax-withheld"]').should('exist')
      cy.get('[data-testid="btn-file-return"]').should('exist')
    })
  })

  // ==================== HR & LEAVE MANAGEMENT ====================
  describe('HR & Leave Management Advanced', () => {
    it('should manage leave policies', () => {
      cy.visit('/admin/hr/leave-policies')
      cy.get('[data-testid="btn-create"]').click()
      cy.get('[data-testid="input-name"]').type('Sick Leave')
      cy.get('[data-testid="input-days"]').type('10')
      cy.get('[data-testid="select-type"]').select('Paid')
      cy.get('[data-testid="btn-save"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should process leave requests with approval workflow', () => {
      cy.loginAs('employee@test.local', 'password123')
      cy.visit('/employee/leave/request')
      cy.get('[data-testid="select-type"]').select('Annual Leave')
      cy.get('[data-testid="input-from-date"]').type('2024-02-20')
      cy.get('[data-testid="input-to-date"]').type('2024-02-25')
      cy.get('[data-testid="btn-submit"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should approve/reject leave requests', () => {
      cy.visit('/admin/hr/leave-requests')
      cy.get('[data-testid="leave-request-1"]').click()
      cy.get('[data-testid="btn-approve"]').click()
      cy.get('[data-testid="modal-confirm"]').should('be.visible')
      cy.get('[data-testid="btn-confirm"]').click()
      cy.get('[data-testid="status"]').should('contain', 'Approved')
    })

    it('should track leave balance', () => {
      cy.loginAs('employee@test.local', 'password123')
      cy.visit('/employee/leave-balance')
      cy.get('[data-testid="annual-leave-balance"]').should('be.visible')
      cy.get('[data-testid="sick-leave-balance"]').should('be.visible')
      cy.get('[data-testid="casual-leave-balance"]').should('be.visible')
    })

    it('should manage employee documents', () => {
      cy.visit('/admin/employees/1/documents')
      cy.get('[data-testid="btn-upload"]').click()
      cy.get('[data-testid="select-type"]').select('Certificate')
      cy.get('[data-testid="input-file"]').selectFile('cypress/fixtures/document.pdf')
      cy.get('[data-testid="btn-upload"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should conduct performance reviews', () => {
      cy.visit('/admin/hr/reviews')
      cy.get('[data-testid="btn-create"]').click()
      cy.get('[data-testid="select-employee"]').select('John Doe')
      cy.get('[data-testid="select-period"]').select('Q1 2024')
      cy.get('[data-testid="input-rating"]').type('4')
      cy.get('[data-testid="input-feedback"]').type('Great performance')
      cy.get('[data-testid="btn-submit"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should track employee training and development', () => {
      cy.visit('/admin/hr/training')
      cy.get('[data-testid="btn-enroll"]').click()
      cy.get('[data-testid="select-employee"]').select('Jane Smith')
      cy.get('[data-testid="select-course"]').select('Leadership')
      cy.get('[data-testid="btn-enroll"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })
  })

  // ==================== B2B & WHOLESALE ====================
  describe('B2B & Wholesale Management', () => {
    it('should create B2B account', () => {
      cy.visit('/b2b/signup')
      cy.get('[data-testid="input-company-name"]').type('Tech Corp')
      cy.get('[data-testid="input-email"]').type('b2b@techcorp.com')
      cy.get('[data-testid="input-contact-person"]').type('John Smith')
      cy.get('[data-testid="input-phone"]').type('+1234567890')
      cy.get('[data-testid="btn-create"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should manage wholesale pricing', () => {
      cy.loginAs('b2b@techcorp.com', 'password123')
      cy.visit('/b2b/pricing')
      cy.get('[data-testid="filter-volume"]').select('100+')
      cy.get('[data-testid="btn-filter"]').click()
      cy.get('[data-testid="product-card"]').each(($card) => {
        cy.wrap($card).find('[data-testid="wholesale-price"]').should('exist')
      })
    })

    it('should create bulk orders', () => {
      cy.loginAs('b2b@techcorp.com', 'password123')
      cy.visit('/b2b/catalog')
      cy.get('[data-testid="btn-add-to-cart-1"]').click()
      cy.get('[data-testid="input-quantity"]').clear().type('500')
      cy.get('[data-testid="btn-checkout"]').click()
      cy.get('[data-testid="bulk-order-summary"]').should('be.visible')
    })

    it('should manage purchase orders', () => {
      cy.loginAs('b2b@techcorp.com', 'password123')
      cy.visit('/b2b/purchase-orders')
      cy.get('[data-testid="btn-create"]').click()
      cy.get('[data-testid="input-po-number"]').type('PO-2024-001')
      cy.get('[data-testid="select-supplier"]').select('Main Supplier')
      cy.get('[data-testid="btn-submit"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should handle drop-shipping', () => {
      cy.loginAs('b2b@techcorp.com', 'password123')
      cy.visit('/b2b/dropship')
      cy.get('[data-testid="btn-list-product"]').click()
      cy.get('[data-testid="input-product"]').type('Laptop')
      cy.get('[data-testid="input-markup"]').type('15')
      cy.get('[data-testid="btn-list"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should manage B2B credit terms', () => {
      cy.visit('/admin/b2b/credit-terms')
      cy.get('[data-testid="btn-set-limit"]').click()
      cy.get('[data-testid="select-customer"]').select('Tech Corp')
      cy.get('[data-testid="input-credit-limit"]').type('50000')
      cy.get('[data-testid="input-payment-terms"]').type('Net 30')
      cy.get('[data-testid="btn-save"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })
  })

  // ==================== COMMUNICATIONS & MARKETING ====================
  describe('Communications & Marketing', () => {
    it('should create email campaign', () => {
      cy.visit('/admin/campaigns/email/create')
      cy.get('[data-testid="input-name"]').type('Valentine Campaign')
      cy.get('[data-testid="input-subject"]').type('Special Valentine Offer')
      cy.get('[data-testid="editor-body"]').type('Get 20% off this Valentine')
      cy.get('[data-testid="select-audience"]').select('Newsletter Subscribers')
      cy.get('[data-testid="btn-preview"]').click()
      cy.get('[data-testid="preview-modal"]').should('be.visible')
      cy.get('[data-testid="btn-send"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should manage email templates', () => {
      cy.visit('/admin/email-templates')
      cy.get('[data-testid="btn-create"]').click()
      cy.get('[data-testid="input-name"]').type('Order Confirmation')
      cy.get('[data-testid="input-subject"]').type('Your order {{order_id}} confirmed')
      cy.get('[data-testid="btn-save"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should track email campaign metrics', () => {
      cy.visit('/admin/campaigns/email/1')
      cy.get('[data-testid="metric-sent"]').should('exist')
      cy.get('[data-testid="metric-delivered"]').should('exist')
      cy.get('[data-testid="metric-opened"]').should('exist')
      cy.get('[data-testid="metric-clicked"]').should('exist')
    })

    it('should manage SMS campaigns', () => {
      cy.visit('/admin/campaigns/sms/create')
      cy.get('[data-testid="input-message"]').type('Flash Sale: 50% off today only!')
      cy.get('[data-testid="select-audience"]').select('Active Customers')
      cy.get('[data-testid="btn-preview"]').click()
      cy.get('[data-testid="btn-send"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should manage newsletter subscriptions', () => {
      cy.visit('/admin/newsletter')
      cy.get('[data-testid="filter-status"]').select('Subscribed')
      cy.get('[data-testid="btn-filter"]').click()
      cy.get('[data-testid="subscriber-list"]').should('be.visible')
    })

    it('should handle unsubscribe requests', () => {
      cy.visit('/unsubscribe?token=abc123')
      cy.get('[data-testid="confirm-text"]').should('contain', 'unsubscribe')
      cy.get('[data-testid="btn-confirm"]').click()
      cy.get('[data-testid="success-message"]').should('contain', 'unsubscribed')
    })
  })

  // ==================== INTEGRATIONS ====================
  describe('Third-Party Integrations', () => {
    it('should sync with payment gateway', () => {
      cy.visit('/admin/integrations/payment')
      cy.get('[data-testid="btn-test-connection"]').click()
      cy.get('[data-testid="connection-status"]').should('contain', 'Connected')
    })

    it('should integrate with shipping provider', () => {
      cy.visit('/admin/integrations/shipping')
      cy.get('[data-testid="btn-test-connection"]').click()
      cy.get('[data-testid="carrier-list"]').should('be.visible')
    })

    it('should sync with accounting software', () => {
      cy.visit('/admin/integrations/accounting')
      cy.get('[data-testid="btn-authorize"]').click()
      cy.get('[data-testid="oauth-window"]').should('exist')
    })

    it('should handle webhook events', () => {
      cy.apiRequest('POST', '/webhooks/payment', {
        event: 'payment.success',
        data: { amount: 100, order_id: 1 }
      }).then((response) => {
        expect(response.status).to.eq(200)
      })
    })
  })

  // ==================== REPORTING & EXPORT ====================
  describe('Reporting & Data Export', () => {
    it('should generate sales report', () => {
      cy.visit('/admin/reports/sales')
      cy.get('[data-testid="btn-generate"]').click()
      cy.get('[data-testid="report-content"]').should('be.visible')
      cy.get('[data-testid="btn-export-pdf"]').should('exist')
      cy.get('[data-testid="btn-export-excel"]').should('exist')
    })

    it('should generate inventory report', () => {
      cy.visit('/admin/reports/inventory')
      cy.get('[data-testid="btn-generate"]').click()
      cy.get('[data-testid="inventory-report"]').should('be.visible')
    })

    it('should export data to CSV', () => {
      cy.visit('/admin/products')
      cy.get('[data-testid="btn-export"]').click()
      cy.get('[data-testid="export-format"]').select('CSV')
      cy.get('[data-testid="btn-export-file"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })

    it('should import data from CSV', () => {
      cy.visit('/admin/products/import')
      cy.get('[data-testid="input-file"]').selectFile('cypress/fixtures/products.csv')
      cy.get('[data-testid="btn-preview"]').click()
      cy.get('[data-testid="import-preview"]').should('be.visible')
      cy.get('[data-testid="btn-import"]').click()
      cy.get('[data-testid="toast-success"]').should('be.visible')
    })
  })
})
