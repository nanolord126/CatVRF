// @ts-nocheck
describe('Payment Services - Complete Coverage', () => {
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
    cy.loginAs('admin@test.local', 'password123')
  })

  describe('Payment Gateway Integration', () => {
    it('should initialize payment gateway', () => {
      cy.apiRequest('GET', '/api/payments/config').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data).to.have.property('gateway')
        expect(response.body.data).to.have.property('public_key')
      })
    })

    it('should create payment session', () => {
      cy.apiRequest('POST', '/api/payments/session', {
        amount: 5000,
        currency: 'RUB',
        description: 'Test Payment',
        return_url: 'http://localhost:8000/success'
      }).then((response) => {
        expect(response.status).to.eq(201)
        expect(response.body.data).to.have.property('session_id')
        expect(response.body.data).to.have.property('payment_url')
      })
    })

    it('should validate payment gateway credentials', () => {
      cy.apiRequest('POST', '/api/payments/validate-credentials').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data.valid).to.be.true
      })
    })
  })

  describe('Payment Processing', () => {
    it('should process payment successfully', () => {
      cy.apiRequest('POST', '/api/payments', {
        amount: 10000,
        currency: 'RUB',
        method: 'card',
        card_token: 'test_token_123'
      }).then((response) => {
        expect(response.status).to.eq(201)
        expect(response.body.data.status).to.eq('completed')
      })
    })

    it('should handle payment decline', () => {
      cy.apiRequest('POST', '/api/payments', {
        amount: 10000,
        currency: 'RUB',
        method: 'card',
        card_token: 'test_token_declined'
      }).then((response) => {
        expect(response.status).to.eq(402)
        expect(response.body.error).to.include('declined')
      })
    })

    it('should handle payment timeout', () => {
      cy.apiRequest('POST', '/api/payments', {
        amount: 10000,
        currency: 'RUB',
        method: 'card',
        card_token: 'test_token_timeout'
      }, { timeout: 1000 }).then((response) => {
        expect(response.status).to.eq(504)
      })
    })

    it('should create payment with metadata', () => {
      cy.apiRequest('POST', '/api/payments', {
        amount: 10000,
        currency: 'RUB',
        method: 'card',
        card_token: 'test_token_123',
        metadata: {
          order_id: 'ORD-12345',
          customer_email: 'customer@test.local',
          description: 'Beauty booking'
        }
      }).then((response) => {
        expect(response.status).to.eq(201)
        expect(response.body.data.metadata.order_id).to.eq('ORD-12345')
      })
    })
  })

  describe('Payment Methods', () => {
    it('should support card payment', () => {
      cy.apiRequest('POST', '/api/payments', {
        amount: 5000,
        method: 'card',
        card_token: 'test_token_123'
      }).then((response) => {
        expect(response.status).to.eq(201)
        expect(response.body.data.method).to.eq('card')
      })
    })

    it('should support bank transfer', () => {
      cy.apiRequest('POST', '/api/payments', {
        amount: 5000,
        method: 'bank_transfer',
        account_details: { account_number: '12345678' }
      }).then((response) => {
        expect(response.status).to.eq(201)
        expect(response.body.data.method).to.eq('bank_transfer')
      })
    })

    it('should support wallet/account balance', () => {
      cy.apiRequest('POST', '/api/payments', {
        amount: 5000,
        method: 'wallet'
      }).then((response) => {
        expect(response.status).to.eq(201) // or 402 if insufficient funds
        expect(response.body.data.method).to.eq('wallet')
      })
    })

    it('should support split payment', () => {
      cy.apiRequest('POST', '/api/payments/split', {
        amount: 10000,
        splits: [
          { method: 'card', amount: 5000, card_token: 'test_token_123' },
          { method: 'wallet', amount: 5000 }
        ]
      }).then((response) => {
        expect(response.status).to.eq(201)
        expect(response.body.data.splits).to.have.length(2)
      })
    })
  })

  describe('Refunds & Reversals', () => {
    it('should process full refund', () => {
      cy.apiRequest('POST', '/api/payments/1/refund', {
        amount: 10000,
        reason: 'Customer request'
      }).then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data.refund_status).to.eq('completed')
      })
    })

    it('should process partial refund', () => {
      cy.apiRequest('POST', '/api/payments/1/refund', {
        amount: 5000,
        reason: 'Partial refund'
      }).then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data.remaining_balance).to.eq(5000)
      })
    })

    it('should prevent double refund', () => {
      cy.apiRequest('POST', '/api/payments/1/refund', {
        amount: 10000
      }).then(() => {
        cy.apiRequest('POST', '/api/payments/1/refund', {
          amount: 10000
        }).then((response) => {
          expect(response.status).to.eq(409) // Conflict
          expect(response.body.error).to.include('already refunded')
        })
      })
    })

    it('should reverse payment', () => {
      cy.apiRequest('POST', '/api/payments/1/reverse').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data.status).to.eq('reversed')
      })
    })
  })

  describe('Subscription Payments', () => {
    it('should create subscription', () => {
      cy.apiRequest('POST', '/api/subscriptions', {
        plan_id: 1,
        payment_method: 'card',
        card_token: 'test_token_123',
        billing_cycle: 'monthly'
      }).then((response) => {
        expect(response.status).to.eq(201)
        expect(response.body.data.status).to.eq('active')
      })
    })

    it('should process recurring payment', () => {
      cy.apiRequest('POST', '/api/subscriptions/1/charge').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data.status).to.eq('charged')
      })
    })

    it('should cancel subscription', () => {
      cy.apiRequest('POST', '/api/subscriptions/1/cancel').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data.status).to.eq('cancelled')
      })
    })

    it('should pause subscription', () => {
      cy.apiRequest('POST', '/api/subscriptions/1/pause').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data.status).to.eq('paused')
      })
    })

    it('should resume subscription', () => {
      cy.apiRequest('POST', '/api/subscriptions/1/resume').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data.status).to.eq('active')
      })
    })
  })

  describe('Invoicing', () => {
    it('should create invoice', () => {
      cy.apiRequest('POST', '/api/invoices', {
        customer_id: 1,
        amount: 10000,
        currency: 'RUB',
        due_date: '2024-02-28',
        items: [
          { description: 'Service A', amount: 10000 }
        ]
      }).then((response) => {
        expect(response.status).to.eq(201)
        expect(response.body.data).to.have.property('invoice_number')
      })
    })

    it('should mark invoice as paid', () => {
      cy.apiRequest('POST', '/api/invoices/1/mark-paid', {
        payment_date: '2024-02-15'
      }).then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data.status).to.eq('paid')
      })
    })

    it('should send invoice', () => {
      cy.apiRequest('POST', '/api/invoices/1/send').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data.sent_at).to.exist
      })
    })

    it('should generate invoice PDF', () => {
      cy.apiRequest('GET', '/api/invoices/1/pdf').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.headers['content-type']).to.include('pdf')
      })
    })
  })

  describe('Payment Disputes', () => {
    it('should create payment dispute', () => {
      cy.apiRequest('POST', '/api/disputes', {
        payment_id: 1,
        reason: 'unauthorized_transaction',
        description: 'I did not authorize this payment'
      }).then((response) => {
        expect(response.status).to.eq(201)
        expect(response.body.data.status).to.eq('open')
      })
    })

    it('should provide dispute evidence', () => {
      cy.apiRequest('POST', '/api/disputes/1/evidence', {
        type: 'customer_communication',
        content: 'Email proof'
      }).then((response) => {
        expect(response.status).to.eq(201)
      })
    })

    it('should resolve dispute', () => {
      cy.apiRequest('PUT', '/api/disputes/1', {
        status: 'resolved',
        resolution: 'merchant_refunded'
      }).then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data.status).to.eq('resolved')
      })
    })
  })

  describe('Payment Reports & Analytics', () => {
    it('should retrieve payment summary', () => {
      cy.apiRequest('GET', '/api/reports/payments/summary').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data).to.have.property('total_amount')
        expect(response.body.data).to.have.property('transaction_count')
      })
    })

    it('should filter payments by date range', () => {
      cy.apiRequest('GET', '/api/payments?start_date=2024-01-01&end_date=2024-01-31').then((response) => {
        expect(response.status).to.eq(200)
        response.body.data.forEach((payment: any) => {
          expect(new Date(payment.created_at) >= new Date('2024-01-01')).to.be.true
          expect(new Date(payment.created_at) <= new Date('2024-01-31')).to.be.true
        })
      })
    })

    it('should generate payment analytics', () => {
      cy.apiRequest('GET', '/api/reports/payments/analytics').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data).to.have.property('daily_revenue')
        expect(response.body.data).to.have.property('payment_methods')
      })
    })

    it('should export payment report', () => {
      cy.apiRequest('GET', '/api/reports/payments/export?format=csv').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.headers['content-type']).to.include('csv')
      })
    })
  })

  describe('Payment Security', () => {
    it('should encrypt sensitive payment data', () => {
      // Verify that card data is not logged
      cy.apiRequest('POST', '/api/payments', {
        amount: 10000,
        card_token: 'test_token_123'
      }).then(() => {
        cy.apiRequest('GET', '/api/audit-logs').then((response) => {
          const paymentLog = response.body.data.find((log: any) => log.action === 'create_payment')
          expect(paymentLog.data).to.not.include('card_token')
          expect(paymentLog.data).to.not.include('card_number')
        })
      })
    })

    it('should validate payment amounts', () => {
      cy.apiRequest('POST', '/api/payments', {
        amount: -1000,
        card_token: 'test_token_123'
      }).then((response) => {
        expect(response.status).to.eq(400)
        expect(response.body.error).to.include('amount')
      })
    })

    it('should prevent payment tampering', () => {
      cy.apiRequest('POST', '/api/payments', {
        amount: 10000,
        signature: 'invalid_signature',
        card_token: 'test_token_123'
      }).then((response) => {
        expect(response.status).to.eq(400)
        expect(response.body.error).to.include('signature')
      })
    })

    it('should require authentication for payments', () => {
      cy.window().then((win) => {
        localStorage.removeItem('auth_token')
      })
      cy.apiRequest('POST', '/api/payments', {
        amount: 10000,
        card_token: 'test_token_123'
      }).then((response) => {
        expect(response.status).to.eq(401)
      })
    })
  })

  describe('Payment UI Integration', () => {
    it('should display payment form', () => {
      cy.visit('/beauty/bookings/1/pay')
      cy.get('[data-testid="payment-form"]').should('be.visible')
      cy.get('[data-testid="input-card-number"]').should('be.visible')
      cy.get('[data-testid="input-cvv"]').should('be.visible')
    })

    it('should show payment amount', () => {
      cy.visit('/beauty/bookings/1/pay')
      cy.get('[data-testid="payment-amount"]').should('contain', '₽')
    })

    it('should validate card input', () => {
      cy.visit('/beauty/bookings/1/pay')
      cy.get('[data-testid="input-card-number"]').type('invalid')
      cy.get('[data-testid="error-card"]').should('be.visible')
    })

    it('should process payment on form submit', () => {
      cy.visit('/beauty/bookings/1/pay')
      cy.get('[data-testid="input-card-number"]').type('4111111111111111')
      cy.get('[data-testid="input-expiry"]').type('12/25')
      cy.get('[data-testid="input-cvv"]').type('123')
      cy.get('[data-testid="btn-pay"]').click()
      cy.get('[data-testid="payment-success"]').should('be.visible')
    })

    it('should show payment error on failure', () => {
      cy.visit('/beauty/bookings/1/pay')
      cy.get('[data-testid="input-card-number"]').type('4000000000000002')
      cy.get('[data-testid="input-expiry"]').type('12/25')
      cy.get('[data-testid="input-cvv"]').type('123')
      cy.get('[data-testid="btn-pay"]').click()
      cy.get('[data-testid="payment-error"]').should('be.visible')
    })
  })

  describe('Payment Webhooks', () => {
    it('should handle payment success webhook', () => {
      cy.apiRequest('POST', '/api/webhooks/payment/success', {
        payment_id: 'pay_12345',
        status: 'completed',
        amount: 10000
      }).then((response) => {
        expect(response.status).to.eq(200)
      })
    })

    it('should handle payment failure webhook', () => {
      cy.apiRequest('POST', '/api/webhooks/payment/failure', {
        payment_id: 'pay_12345',
        error: 'declined'
      }).then((response) => {
        expect(response.status).to.eq(200)
      })
    })

    it('should verify webhook signature', () => {
      cy.apiRequest('POST', '/api/webhooks/payment', {
        payload: 'data',
        signature: 'invalid'
      }).then((response) => {
        expect(response.status).to.eq(401)
      })
    })
  })

  describe('Payment Reconciliation', () => {
    it('should reconcile payments with bank', () => {
      cy.apiRequest('POST', '/api/payments/reconcile', {
        bank_statement: 'statement_data'
      }).then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data).to.have.property('matched_payments')
        expect(response.body.data).to.have.property('discrepancies')
      })
    })

    it('should generate reconciliation report', () => {
      cy.apiRequest('GET', '/api/reports/reconciliation').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data).to.have.property('total_amount')
        expect(response.body.data).to.have.property('matched_count')
      })
    })
  })
})
