// @ts-nocheck
/// <reference types="cypress" />
/// <reference types="chai" />

/**
 * API Integration E2E Tests
 * 
 * Tests for API endpoints, integrations with services, and external APIs
 */

describe('API Integration', () => {
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
    cy.loginAs('admin@kotvrf.ru', 'password123')
  })

  describe('Inventory API', () => {
    it('should get inventory items via API', () => {
      cy.request({
        method: 'GET',
        url: '/api/inventory',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        expect(response.status).to.equal(200)
        expect(response.body).to.have.property('data')
        expect(response.body.data).to.be.an('array')
      })
    })

    it('should create inventory item via API', () => {
      cy.request({
        method: 'POST',
        url: '/api/inventory',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` },
        body: {
          name: 'API Test Item',
          sku: 'API-001',
          quantity: 50,
          reorder_level: 10,
          unit_price: 29.99
        }
      }).then((response: any) => {
        expect(response.status).to.equal(201)
        expect(response.body).to.have.property('id')
        expect(response.body.name).to.equal('API Test Item')
      })
    })

    it('should update inventory item via API', () => {
      cy.request({
        method: 'GET',
        url: '/api/inventory',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        const itemId = response.body.data[0].id
        
        cy.request({
          method: 'PUT',
          url: `/api/inventory/${itemId}`,
          headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` },
          body: { quantity: 100 }
        }).then((updateResponse: any) => {
          expect(updateResponse.status).to.equal(200)
          expect(updateResponse.body.quantity).to.equal(100)
        })
      })
    })

    it('should delete inventory item via API', () => {
      cy.request({
        method: 'GET',
        url: '/api/inventory',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        const itemId = response.body.data[0].id
        
        cy.request({
          method: 'DELETE',
          url: `/api/inventory/${itemId}`,
          headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
        }).then((deleteResponse: any) => {
          expect(deleteResponse.status).to.equal(204)
        })
      })
    })

    it('should handle API errors gracefully', () => {
      cy.request({
        method: 'GET',
        url: '/api/inventory/999999',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` },
        failOnStatusCode: false
      }).then((response: any) => {
        expect(response.status).to.equal(404)
        expect(response.body).to.have.property('message')
      })
    })
  })

  describe('Payroll API', () => {
    it('should list payroll runs via API', () => {
      cy.request({
        method: 'GET',
        url: '/api/payroll',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        expect(response.status).to.equal(200)
        expect(response.body.data).to.be.an('array')
      })
    })

    it('should create payroll run via API', () => {
      cy.request({
        method: 'POST',
        url: '/api/payroll',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` },
        body: {
          period_start: '2026-03-01',
          period_end: '2026-03-31',
          type: 'monthly'
        }
      }).then((response: any) => {
        expect(response.status).to.equal(201)
        expect(response.body).to.have.property('id')
        expect(response.body.status).to.equal('draft')
      })
    })

    it('should process payroll payment via API', () => {
      cy.request({
        method: 'GET',
        url: '/api/payroll',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        const payrollId = response.body.data[0].id
        
        cy.request({
          method: 'POST',
          url: `/api/payroll/${payrollId}/process-payment`,
          headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` },
          body: { method: 'bank_transfer' }
        }).then((processResponse: any) => {
          expect(processResponse.status).to.equal(200)
          expect(processResponse.body.status).to.equal('paid')
        })
      })
    })

    it('should calculate payroll via API', () => {
      cy.request({
        method: 'POST',
        url: '/api/payroll/calculate',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` },
        body: {
          base_salary: 50000,
          tax_percentage: 20,
          deductions: [
            { name: 'Health Insurance', amount: 150 }
          ]
        }
      }).then((response: any) => {
        expect(response.status).to.equal(200)
        expect(response.body).to.have.property('gross_salary')
        expect(response.body).to.have.property('net_salary')
        expect(response.body).to.have.property('total_deductions')
      })
    })
  })

  describe('HR API', () => {
    it('should get employees via API', () => {
      cy.request({
        method: 'GET',
        url: '/api/employees',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        expect(response.status).to.equal(200)
        expect(response.body.data).to.be.an('array')
      })
    })

    it('should create employee via API', () => {
      cy.request({
        method: 'POST',
        url: '/api/employees',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` },
        body: {
          first_name: 'John',
          last_name: 'Smith',
          email: 'john.smith@company.com',
          phone: '+7 999 123-45-67',
          position: 'Manager',
          department: 'Sales',
          hire_date: '2026-03-01',
          salary: 60000
        }
      }).then((response: any) => {
        expect(response.status).to.equal(201)
        expect(response.body).to.have.property('id')
      })
    })

    it('should manage leave requests via API', () => {
      cy.request({
        method: 'GET',
        url: '/api/employees',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        const employeeId = response.body.data[0].id
        
        cy.request({
          method: 'POST',
          url: `/api/employees/${employeeId}/leave-request`,
          headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` },
          body: {
            type: 'annual',
            start_date: '2026-03-15',
            end_date: '2026-03-22',
            reason: 'Vacation'
          }
        }).then((leaveResponse: any) => {
          expect(leaveResponse.status).to.equal(201)
          expect(leaveResponse.body.status).to.equal('pending')
        })
      })
    })
  })

  describe('Beauty Salon API', () => {
    it('should get salons via API', () => {
      cy.request({
        method: 'GET',
        url: '/api/beauty/salons',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        expect(response.status).to.equal(200)
        expect(response.body.data).to.be.an('array')
      })
    })

    it('should get available booking slots via API', () => {
      cy.request({
        method: 'GET',
        url: '/api/beauty/salons',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        const salonId = response.body.data[0].id
        
        cy.request({
          method: 'GET',
          url: `/api/beauty/salons/${salonId}/availability?date=2026-03-20`,
          headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
        }).then((availResponse: any) => {
          expect(availResponse.status).to.equal(200)
          expect(availResponse.body.slots).to.be.an('array')
        })
      })
    })

    it('should create booking via API', () => {
      cy.request({
        method: 'POST',
        url: '/api/beauty/bookings',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` },
        body: {
          salon_id: 1,
          service_id: 1,
          client_name: 'Test Client',
          client_phone: '+7 999 123-45-67',
          booking_date: '2026-03-20',
          time_slot: '09:00'
        }
      }).then((response: any) => {
        expect(response.status).to.equal(201)
        expect(response.body.status).to.equal('pending')
      })
    })
  })

  describe('Authentication API', () => {
    it('should authenticate with valid credentials', () => {
      cy.request({
        method: 'POST',
        url: '/api/auth/login',
        body: {
          email: 'admin@kotvrf.ru',
          password: 'password123'
        }
      }).then((response: any) => {
        expect(response.status).to.equal(200)
        expect(response.body).to.have.property('token')
        expect(response.body).to.have.property('user')
      })
    })

    it('should reject invalid credentials', () => {
      cy.request({
        method: 'POST',
        url: '/api/auth/login',
        body: {
          email: 'admin@kotvrf.ru',
          password: 'wrongpassword'
        },
        failOnStatusCode: false
      }).then((response: any) => {
        expect(response.status).to.equal(401)
      })
    })

    it('should refresh auth token', () => {
      cy.request({
        method: 'POST',
        url: '/api/auth/refresh',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        expect(response.status).to.equal(200)
        expect(response.body).to.have.property('token')
      })
    })

    it('should logout user', () => {
      cy.request({
        method: 'POST',
        url: '/api/auth/logout',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        expect(response.status).to.equal(200)
      })
    })
  })

  describe('API Rate Limiting', () => {
    it('should enforce rate limits on API endpoints', () => {
      let requestCount = 0
      
      // Make multiple requests
      for (let i = 0; i < 150; i++) {
        cy.request({
          method: 'GET',
          url: '/api/inventory',
          headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` },
          failOnStatusCode: false
        }).then((response: any) => {
          if (response.status === 429) {
            requestCount++
          }
        })
      }
      
      // Should get rate limited after many requests
      cy.wrap(null).then(() => {
        expect(requestCount).to.be.greaterThan(0)
      })
    })
  })

  describe('API Pagination', () => {
    it('should paginate API results', () => {
      cy.request({
        method: 'GET',
        url: '/api/inventory?page=1&per_page=10',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        expect(response.status).to.equal(200)
        expect(response.body).to.have.property('data')
        expect(response.body).to.have.property('meta')
        expect(response.body.meta).to.have.property('current_page')
        expect(response.body.meta).to.have.property('per_page')
        expect(response.body.meta).to.have.property('total')
      })
    })

    it('should navigate through pages', () => {
      cy.request({
        method: 'GET',
        url: '/api/inventory?page=1&per_page=10',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        const totalPages = Math.ceil(response.body.meta.total / response.body.meta.per_page)
        
        if (totalPages > 1) {
          cy.request({
            method: 'GET',
            url: `/api/inventory?page=2&per_page=10`,
            headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
          }).then((page2Response: any) => {
            expect(page2Response.status).to.equal(200)
            expect(page2Response.body.meta.current_page).to.equal(2)
          })
        }
      })
    })
  })

  describe('API Filtering', () => {
    it('should filter results by status', () => {
      cy.request({
        method: 'GET',
        url: '/api/inventory?status=in_stock',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        expect(response.status).to.equal(200)
        response.body.data.forEach((item: any) => {
          expect(item.status).to.equal('in_stock')
        })
      })
    })

    it('should filter results by date range', () => {
      cy.request({
        method: 'GET',
        url: '/api/payroll?start_date=2026-03-01&end_date=2026-03-31',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        expect(response.status).to.equal(200)
        expect(response.body.data).to.be.an('array')
      })
    })

    it('should search by keyword', () => {
      cy.request({
        method: 'GET',
        url: '/api/inventory?search=widget',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        expect(response.status).to.equal(200)
        response.body.data.forEach((item: any) => {
          expect(item.name.toLowerCase()).to.include('widget')
        })
      })
    })
  })

  describe('API Error Handling', () => {
    it('should return validation errors for invalid data', () => {
      cy.request({
        method: 'POST',
        url: '/api/inventory',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` },
        body: { name: '' }, // Missing required fields
        failOnStatusCode: false
      }).then((response: any) => {
        expect(response.status).to.equal(422)
        expect(response.body).to.have.property('errors')
      })
    })

    it('should handle unauthorized requests', () => {
      cy.request({
        method: 'GET',
        url: '/api/inventory',
        failOnStatusCode: false
      }).then((response: any) => {
        expect(response.status).to.equal(401)
      })
    })

    it('should handle forbidden operations', () => {
      cy.request({
        method: 'POST',
        url: '/api/settings',
        headers: { 'Authorization': `Bearer ${Cypress.env('MANAGER_TOKEN')}` },
        body: { key: 'value' },
        failOnStatusCode: false
      }).then((response: any) => {
        expect(response.status).to.equal(403)
      })
    })

    it('should return server errors gracefully', () => {
      cy.request({
        method: 'GET',
        url: '/api/error-endpoint',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` },
        failOnStatusCode: false
      }).then((response: any) => {
        expect(response.status).to.be.greaterThanOrEqual(500)
        expect(response.body).to.have.property('message')
      })
    })
  })

  describe('API Response Format', () => {
    it('should return consistent JSON response format', () => {
      cy.request({
        method: 'GET',
        url: '/api/inventory',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        expect(response.body).to.have.property('data')
        expect(response.body).to.have.property('meta')
        expect(response.body).to.have.property('status')
      })
    })

    it('should include proper headers in response', () => {
      cy.request({
        method: 'GET',
        url: '/api/inventory',
        headers: { 'Authorization': `Bearer ${Cypress.env('API_TOKEN')}` }
      }).then((response: any) => {
        expect(response.headers).to.have.property('content-type')
        expect(response.headers['content-type']).to.include('application/json')
      })
    })
  })
})
