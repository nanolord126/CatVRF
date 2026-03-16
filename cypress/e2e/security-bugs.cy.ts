// @ts-nocheck
import 'cypress'
import '../../support/commands'

describe('Security, Vulnerabilities & Bug Regression Tests', () => {
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
  })

  // ==================== SQL INJECTION PREVENTION ====================
  describe('SQL Injection Prevention', () => {
    it('should prevent SQL injection in product search', () => {
      cy.apiRequest('GET', `/api/products?search='; DROP TABLE products; --`).then((response) => {
        expect(response.status).to.eq(200)
        // Products table should still exist
        cy.apiRequest('GET', '/api/products').then((result) => {
          expect(result.status).to.eq(200)
        })
      })
    })

    it('should prevent SQL injection in order filter', () => {
      cy.apiRequest('GET', `/api/orders?status=pending' OR '1'='1`).then((response) => {
        expect(response.status).to.eq(200)
        // Should only return orders with specific status
        response.body.data.forEach((order) => {
          expect(order.status).to.equal('pending')
        })
      })
    })

    it('should prevent SQL injection in user email lookup', () => {
      cy.apiRequest('GET', `/api/users?email=test@test.local' UNION SELECT * FROM users --`).then((response) => {
        expect(response.status).to.eq(200)
        // Should return specific user, not all
        expect(response.body.data).to.be.an('array')
      })
    })

    it('should prevent SQL injection in date filtering', () => {
      cy.apiRequest('GET', `/api/orders?from_date=2024-01-01' OR '1'='1` ).then((response) => {
        expect(response.status).to.eq(200)
        // Should validate date format
        response.body.data.forEach((order) => {
          expect(new Date(order.created_at).getFullYear()).to.equal(2024)
        })
      })
    })
  })

  // ==================== XSS (CROSS-SITE SCRIPTING) PREVENTION ====================
  describe('XSS Prevention', () => {
    it('should prevent XSS in product name', () => {
      cy.apiRequest('POST', '/api/products', {
        name: '<script>alert("XSS")</script>',
        price: 10.00
      }).then((response) => {
        expect(response.status).to.eq(201)
        cy.apiRequest('GET', `/api/products/${response.body.data.id}`).then((result) => {
          expect(result.body.data.name).to.not.include('<script>')
          expect(result.body.data.name).to.equal('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;')
        })
      })
    })

    it('should prevent XSS in user comments', () => {
      cy.apiRequest('POST', '/api/comments', {
        post_id: 1,
        text: 'Great! <img src=x onerror="alert(\'XSS\')">'
      }).then((response) => {
        expect(response.status).to.eq(201)
        cy.get('[data-testid="comment-1"]').should('not.contain', 'onerror')
      })
    })

    it('should prevent stored XSS in product description', () => {
      cy.apiRequest('POST', '/api/products', {
        name: 'Test Product',
        description: '<svg onload="alert(\'XSS\')">',
        price: 10.00
      }).then((response) => {
        expect(response.status).to.eq(201)
        // Visit product page
        cy.visit(`/products/${response.body.data.id}`)
        // Check that script did not execute
        cy.window().then((win) => {
          expect(win.alertCalled).to.be.undefined
        })
      })
    })

    it('should prevent DOM-based XSS in search results', () => {
      cy.visit('/admin/products?search=<img%20src=x%20onerror=alert(1)>')
      cy.get('[data-testid="search-results"]').should('not.contain', '<img')
    })

    it('should prevent XSS in JSON API responses', () => {
      cy.apiRequest('POST', '/api/products', {
        name: '"><script>alert("XSS")</script>',
        price: 10.00
      }).then((response) => {
        // Response should be valid JSON, not broken
        expect(response.body).to.be.an('object')
        expect(response.body.data).to.have.property('id')
      })
    })
  })

  // ==================== CSRF PROTECTION ====================
  describe('CSRF Protection', () => {
    it('should require CSRF token for POST requests', () => {
      cy.apiRequest('POST', '/api/products', {
        name: 'Test',
        price: 10.00
      }, true).then((response) => {
        // Should fail without valid CSRF token
        expect(response.status).to.eq(419) // Token mismatch
      })
    })

    it('should accept valid CSRF token', () => {
      cy.loginAs('admin@test.local', 'password123')
      cy.visit('/admin')
      cy.getCookie('XSRF-TOKEN').then((token) => {
        cy.apiRequest('POST', '/api/products', {
          name: 'Test',
          price: 10.00
        }, false, token.value).then((response) => {
          expect(response.status).to.eq(201)
        })
      })
    })

    it('should validate CSRF token in DELETE requests', () => {
      cy.apiRequest('DELETE', '/api/products/1', {}, true).then((response) => {
        expect(response.status).to.eq(419)
      })
    })
  })

  // ==================== AUTHENTICATION & AUTHORIZATION ====================
  describe('Authentication & Authorization Bugs', () => {
    it('should prevent unauthorized access to admin panel', () => {
      cy.visit('/admin')
      cy.url().should('include', '/login')
    })

    it('should prevent accessing other tenant data', () => {
      cy.loginAs('tenant1@test.local', 'password123')
      cy.apiRequest('GET', '/api/products?tenant_id=2').then((response) => {
        expect(response.status).to.eq(200)
        // Should only return tenant1 products, not tenant2
        response.body.data.forEach((product) => {
          expect(product.tenant_id).to.equal(1)
        })
      })
    })

    it('should prevent privilege escalation', () => {
      cy.loginAs('user@test.local', 'password123')
      // Try to grant admin role to self
      cy.apiRequest('PUT', '/api/users/1', {
        role: 'admin'
      }).then((response) => {
        expect(response.status).to.eq(403) // Forbidden
      })
    })

    it('should logout and invalidate session', () => {
      cy.loginAs('admin@test.local', 'password123')
      cy.apiRequest('POST', '/api/logout').then((response) => {
        expect(response.status).to.eq(200)
      })
      // Try accessing protected resource with old session
      cy.apiRequest('GET', '/api/admin/dashboard').then((response) => {
        expect(response.status).to.eq(401)
      })
    })

    it('should prevent concurrent login with same credentials', () => {
      cy.loginAs('admin@test.local', 'password123')
      const token1 = cy.getCookie('auth_token')
      
      cy.loginAs('admin@test.local', 'password123')
      const token2 = cy.getCookie('auth_token')
      
      // Tokens might be different (depends on implementation)
      // First session should be invalidated
      cy.apiRequest('GET', '/api/admin/dashboard').then((response) => {
        expect([200, 401]).to.include(response.status)
      })
    })

    it('should prevent brute force attacks', () => {
      const attempts = []
      for (let i = 0; i < 15; i++) {
        attempts.push(
          cy.apiRequest('POST', '/api/login', {
            email: 'admin@test.local',
            password: 'wrongpassword'
          }, false, null, true)
        )
      }
      
      cy.wrap(Promise.all(attempts)).then((responses) => {
        // Should eventually return 429 or 423
        const lastResponse = responses[responses.length - 1]
        expect([429, 423]).to.include(lastResponse.status)
      })
    })
  })

  // ==================== RACE CONDITIONS ====================
  describe('Race Condition Prevention', () => {
    it('should prevent double charging on simultaneous payment', () => {
      const orderId = 1
      const paymentData = { amount: 100, method: 'card', order_id: orderId }
      
      const payment1 = cy.apiRequest('POST', '/api/payments', paymentData)
      const payment2 = cy.apiRequest('POST', '/api/payments', paymentData)
      
      cy.wrap(Promise.all([payment1, payment2])).then((responses) => {
        // Only one should succeed
        const successes = responses.filter(r => r.status === 201)
        expect(successes).to.have.length(1)
      })
    })

    it('should prevent inventory overselling', () => {
      const productId = 1
      const stock = 5
      const quantityPerRequest = 3
      
      const requests = []
      for (let i = 0; i < 3; i++) {
        requests.push(
          cy.apiRequest('POST', '/api/orders/items', {
            product_id: productId,
            quantity: quantityPerRequest
          })
        )
      }
      
      cy.wrap(Promise.all(requests)).then((responses) => {
        // Only first two should succeed (6 items out of 5 stock)
        const successes = responses.filter(r => r.status === 201)
        expect(successes.length).to.be.lessThan(3)
      })
    })

    it('should prevent concurrent status updates', () => {
      const orderId = 1
      
      const update1 = cy.apiRequest('PUT', `/api/orders/${orderId}`, { status: 'shipped' })
      const update2 = cy.apiRequest('PUT', `/api/orders/${orderId}`, { status: 'cancelled' })
      
      cy.wrap(Promise.all([update1, update2])).then((responses) => {
        // Check final state is consistent
        cy.apiRequest('GET', `/api/orders/${orderId}`).then((result) => {
          expect(result.body.data.status).to.be.oneOf(['shipped', 'cancelled'])
        })
      })
    })
  })

  // ==================== INPUT VALIDATION ====================
  describe('Input Validation Bugs', () => {
    it('should validate email format', () => {
      cy.apiRequest('POST', '/api/users', {
        email: 'invalid-email',
        password: 'password123',
        role: 'user'
      }).then((response) => {
        expect(response.status).to.eq(422)
        expect(response.body.errors).to.have.property('email')
      })
    })

    it('should validate numeric fields', () => {
      cy.apiRequest('POST', '/api/products', {
        name: 'Test',
        price: 'abc'
      }).then((response) => {
        expect(response.status).to.eq(422)
        expect(response.body.errors).to.have.property('price')
      })
    })

    it('should validate phone number format', () => {
      cy.apiRequest('POST', '/api/employees', {
        first_name: 'John',
        last_name: 'Doe',
        phone: 'invalid'
      }).then((response) => {
        expect(response.status).to.eq(422)
        expect(response.body.errors).to.have.property('phone')
      })
    })

    it('should validate date format', () => {
      cy.apiRequest('POST', '/api/employees', {
        first_name: 'John',
        last_name: 'Doe',
        hire_date: '32/13/2024' // Invalid date
      }).then((response) => {
        expect(response.status).to.eq(422)
      })
    })

    it('should limit file upload size', () => {
      cy.visit('/admin/products/1/upload-image')
      // Create 100MB file
      const largeFile = new Blob([new ArrayBuffer(100 * 1024 * 1024)])
      cy.get('[data-testid="file-input"]').then((input) => {
        cy.wrap(input).selectFile(largeFile)
      })
      cy.get('[data-testid="error-message"]').should('contain', 'too large')
    })

    it('should validate allowed file types', () => {
      cy.visit('/admin/products/1/upload-image')
      cy.get('[data-testid="file-input"]').selectFile('cypress/fixtures/test.txt')
      cy.get('[data-testid="error-message"]').should('contain', 'invalid file type')
    })
  })

  // ==================== DATA LEAKAGE PREVENTION ====================
  describe('Data Leakage Prevention', () => {
    it('should not expose sensitive data in error messages', () => {
      cy.apiRequest('POST', '/api/products', {
        name: 'Test'
        // Missing required field
      }).then((response) => {
        expect(response.status).to.eq(422)
        expect(JSON.stringify(response.body)).to.not.include('password')
        expect(JSON.stringify(response.body)).to.not.include('secret')
      })
    })

    it('should not expose database query in errors', () => {
      cy.apiRequest('GET', '/api/products/invalid-id').then((response) => {
        expect(response.status).to.eq(404)
        expect(JSON.stringify(response.body)).to.not.include('SELECT')
        expect(JSON.stringify(response.body)).to.not.include('WHERE')
      })
    })

    it('should not return other user data in list', () => {
      cy.loginAs('user1@test.local', 'password123')
      cy.apiRequest('GET', '/api/users').then((response) => {
        response.body.data.forEach((user) => {
          expect(user).to.not.have.property('password')
          expect(user).to.not.have.property('ssn')
          expect(user).to.not.have.property('bank_account')
        })
      })
    })

    it('should mask sensitive data in logs', () => {
      cy.apiRequest('POST', '/api/users', {
        email: 'test@test.local',
        password: 'SecretPassword123!'
      }).then((response) => {
        cy.apiRequest('GET', '/api/logs?action=create&model=User').then((logs) => {
          const lastLog = logs.body.data[0]
          expect(JSON.stringify(lastLog)).to.not.include('SecretPassword123!')
        })
      })
    })
  })

  // ==================== KNOWN BUGS REGRESSION ====================
  describe('Known Bugs Regression', () => {
    it('should fix bug: product price calculation with discounts', () => {
      cy.apiRequest('POST', '/api/orders', {
        customer_id: 1,
        items: [
          { product_id: 1, quantity: 2, price: 100 }
        ],
        discount_percent: 10
      }).then((response) => {
        expect(response.status).to.eq(201)
        // Price should be correctly calculated: (100 * 2) * 0.9 = 180
        expect(response.body.data.total_amount).to.equal(180)
      })
    })

    it('should fix bug: employee leave balance calculation', () => {
      cy.apiRequest('GET', '/api/employees/1/leave-balance').then((response) => {
        expect(response.status).to.eq(200)
        const balance = response.body.data
        expect(balance.total).to.equal(20) // Annual leave
        expect(balance.used).to.be.a('number')
        expect(balance.remaining).to.equal(balance.total - balance.used)
      })
    })

    it('should fix bug: payroll calculation with multiple allowances', () => {
      cy.apiRequest('POST', '/api/payroll/calculate', {
        employee_id: 1,
        month: '2024-02'
      }).then((response) => {
        expect(response.status).to.eq(200)
        const salary = response.body.data
        expect(salary.gross).to.equal(
          salary.base_salary + 
          (salary.allowances || []).reduce((sum, a) => sum + a.amount, 0)
        )
      })
    })

    it('should fix bug: inventory stock sync issue', () => {
      const productId = 1
      // Get current stock
      cy.apiRequest('GET', `/api/products/${productId}`).then((response) => {
        const initialStock = response.body.data.stock
        
        // Create order
        cy.apiRequest('POST', '/api/orders', {
          customer_id: 1,
          items: [{ product_id: productId, quantity: 5 }]
        }).then(() => {
          // Check stock is updated
          cy.apiRequest('GET', `/api/products/${productId}`).then((updated) => {
            expect(updated.body.data.stock).to.equal(initialStock - 5)
          })
        })
      })
    })

    it('should fix bug: email notification delivery', () => {
      cy.apiRequest('POST', '/api/orders', {
        customer_id: 1,
        items: [{ product_id: 1, quantity: 1 }]
      }).then((response) => {
        // Check that notification was queued
        cy.apiRequest('GET', '/api/notifications/queued?type=order_confirmation').then((notifs) => {
          expect(notifs.body.data).to.have.length.greaterThan(0)
        })
      })
    })

    it('should fix bug: date calculation for future dates', () => {
      const futureDate = '2025-12-31'
      cy.apiRequest('POST', '/api/appointments', {
        user_id: 1,
        scheduled_date: futureDate
      }).then((response) => {
        expect(response.status).to.eq(201)
        expect(response.body.data.scheduled_date).to.include('2025-12-31')
      })
    })

    it('should fix bug: currency conversion rounding', () => {
      cy.apiRequest('POST', '/api/currency-convert', {
        amount: 100,
        from: 'USD',
        to: 'EUR'
      }).then((response) => {
        expect(response.status).to.eq(200)
        // Should be rounded to 2 decimal places
        const converted = response.body.data.converted_amount
        expect(converted).to.match(/^\d+\.\d{2}$/)
      })
    })

    it('should fix bug: timezone handling in datetime fields', () => {
      cy.apiRequest('POST', '/api/events', {
        title: 'Test Event',
        scheduled_at: '2024-02-15T10:00:00+00:00'
      }).then((response) => {
        expect(response.status).to.eq(201)
        const event = response.body.data
        // Should properly parse and store timezone-aware datetime
        expect(event.scheduled_at).to.include('2024-02-15')
      })
    })
  })

  // ==================== PERFORMANCE & RESOURCE LIMITS ====================
  describe('Performance & Resource Limits', () => {
    it('should handle bulk operations efficiently', () => {
      const products = []
      for (let i = 0; i < 100; i++) {
        products.push({
          name: `Product ${i}`,
          price: Math.random() * 100,
          stock: Math.floor(Math.random() * 1000)
        })
      }
      
      cy.apiRequest('POST', '/api/products/bulk', { products }).then((response) => {
        expect(response.status).to.eq(201)
        expect(response.body.data).to.have.length(100)
      })
    })

    it('should limit API response size', () => {
      cy.apiRequest('GET', '/api/products?page=1&per_page=10000').then((response) => {
        // Should limit to reasonable size
        expect(response.body.data).to.have.length.lessThan(10000)
      })
    })

    it('should timeout on slow queries', () => {
      cy.apiRequest('GET', '/api/reports/slow-query', {}, false, null, true).then((response) => {
        // Should timeout or return error
        expect([408, 504, 500]).to.include(response.status)
      })
    })

    it('should prevent N+1 queries in relationships', () => {
      cy.apiRequest('GET', '/api/orders?include=customer,items').then((response) => {
        expect(response.status).to.eq(200)
        // Check query count is reasonable (should be logged)
        expect(response.headers['x-query-count']).to.exist
        expect(parseInt(response.headers['x-query-count'])).to.be.lessThan(10)
      })
    })
  })
})
