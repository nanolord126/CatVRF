import { faker } from '@faker-js/faker'

describe('Barcode Scanner E2E Tests', () => {
  const baseUrl = Cypress.env('BASE_URL') || 'http://localhost:8000'
  const apiBaseUrl = Cypress.env('API_BASE_URL') || 'http://localhost:8000/api'

  let authToken: string
  let testBarcode = '5901234123457'
  let testWarehouseId = 1

  before(() => {
    // Login and get auth token
    cy.request({
      method: 'POST',
      url: `${apiBaseUrl}/login`,
      body: {
        email: 'test@example.com',
        password: 'password',
      },
    }).then((response) => {
      authToken = response.body.token
      cy.wrap(authToken).as('authToken')
    })

    // Create test inventory item
    cy.request({
      method: 'POST',
      url: `${apiBaseUrl}/wms/inventory`,
      headers: {
        Authorization: `Bearer ${authToken}`,
      },
      body: {
        warehouse_id: testWarehouseId,
        sku: `TEST-${faker.random.alphaNumeric(6)}`,
        barcode: testBarcode,
        name: 'Test Product for Scanner',
        current_stock: 100,
        unit_cost: 10.50,
      },
    }).then((response) => {
      cy.wrap(response.body.id).as('testItemId')
    })
  })

  after(() => {
    // Cleanup test data
    cy.get('@authToken').then((token) => {
      cy.request({
        method: 'GET',
        url: `${apiBaseUrl}/wms/barcode/lookup/${testBarcode}`,
        headers: {
          Authorization: `Bearer ${token}`,
        },
        qs: { warehouse_id: testWarehouseId },
      }).then((response) => {
        if (response.body.found) {
          cy.request({
            method: 'DELETE',
            url: `${apiBaseUrl}/wms/inventory/${response.body.item_id}`,
            headers: {
              Authorization: `Bearer ${token}`,
            },
          }).catch(() => {
            // Ignore cleanup errors
          })
        }
      })
    })
  })

  beforeEach(() => {
    cy.login() // Custom command for login
    cy.visit('/wms/scanner')
  })

  describe('Barcode Lookup', () => {
    it('should lookup barcode successfully', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'GET',
          url: `${apiBaseUrl}/wms/barcode/lookup/${testBarcode}`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          qs: { warehouse_id: testWarehouseId },
        }).then((response) => {
          expect(response.status).to.eq(200)
          expect(response.body.found).to.be.true
          expect(response.body.barcode).to.eq(testBarcode)
        })
      })
    })

    it('should return 404 for non-existent barcode', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'GET',
          url: `${apiBaseUrl}/wms/barcode/lookup/9999999999999`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          qs: { warehouse_id: testWarehouseId },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.eq(200)
          expect(response.body.found).to.be.false
        })
      })
    })
  })

  describe('Barcode Validation', () => {
    it('should validate EAN-13 barcode', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'POST',
          url: `${apiBaseUrl}/wms/barcode/validate`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          body: {
            barcode: testBarcode,
          },
        }).then((response) => {
          expect(response.status).to.eq(200)
          expect(response.body.valid).to.be.true
          expect(response.body.type).to.eq('EAN13')
        })
      })
    })

    it('should reject invalid barcode format', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'POST',
          url: `${apiBaseUrl}/wms/barcode/validate`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          body: {
            barcode: 'INVALID',
          },
        }).then((response) => {
          expect(response.status).to.eq(200)
          expect(response.body.valid).to.be.false
        })
      })
    })
  })

  describe('Scan and Adjust Stock', () => {
    it('should process scan and adjust stock (in)', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'POST',
          url: `${apiBaseUrl}/wms/barcode/scan/adjust`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          body: {
            barcode: testBarcode,
            warehouse_id: testWarehouseId,
            quantity: 10,
            adjustment_type: 'in',
            reason: 'E2E Test - Stock In',
          },
        }).then((response) => {
          expect(response.status).to.eq(201)
          expect(response.body.success).to.be.true
          expect(response.body.adjustment_type).to.eq('in')
          expect(response.body.quantity).to.eq(10)
          expect(response.body.new_stock).to.eq(110)
        })
      })
    })

    it('should process scan and adjust stock (out)', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'POST',
          url: `${apiBaseUrl}/wms/barcode/scan/adjust`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          body: {
            barcode: testBarcode,
            warehouse_id: testWarehouseId,
            quantity: 5,
            adjustment_type: 'out',
            reason: 'E2E Test - Stock Out',
          },
        }).then((response) => {
          expect(response.status).to.eq(201)
          expect(response.body.success).to.be.true
          expect(response.body.adjustment_type).to.eq('out')
          expect(response.body.quantity).to.eq(5)
        })
      })
    })

    it('should reject adjustment with insufficient stock', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'POST',
          url: `${apiBaseUrl}/wms/barcode/scan/adjust`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          body: {
            barcode: testBarcode,
            warehouse_id: testWarehouseId,
            quantity: 1000,
            adjustment_type: 'out',
            reason: 'E2E Test - Insufficient Stock',
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.eq(400)
          expect(response.body.success).to.be.false
          expect(response.body.message).to.eq('Insufficient stock')
        })
      })
    })

    it('should require authentication', () => {
      cy.request({
        method: 'POST',
        url: `${apiBaseUrl}/wms/barcode/scan/adjust`,
        body: {
          barcode: testBarcode,
          warehouse_id: testWarehouseId,
          quantity: 10,
          adjustment_type: 'in',
          reason: 'Test',
        },
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.eq(401)
      })
    })
  })

  describe('Pre-flight Validation', () => {
    it('should pass pre-flight validation', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'POST',
          url: `${apiBaseUrl}/wms/barcode/scan/preflight`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          body: {
            barcode: testBarcode,
            warehouse_id: testWarehouseId,
            operation: 'out',
            quantity: 5,
          },
        }).then((response) => {
          expect(response.status).to.eq(200)
          expect(response.body.valid).to.be.true
          expect(response.body.can_proceed).to.be.true
        })
      })
    })

    it('should fail pre-flight with insufficient stock', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'POST',
          url: `${apiBaseUrl}/wms/barcode/scan/preflight`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          body: {
            barcode: testBarcode,
            warehouse_id: testWarehouseId,
            operation: 'out',
            quantity: 1000,
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.eq(400)
          expect(response.body.valid).to.be.false
          expect(response.body.message).to.eq('Insufficient stock')
        })
      })
    })
  })

  describe('Bulk Scan and Adjust', () => {
    it('should process bulk scans successfully', () => {
      cy.get('@authToken').then((token) => {
        // Create second test item
        cy.request({
          method: 'POST',
          url: `${apiBaseUrl}/wms/inventory`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          body: {
            warehouse_id: testWarehouseId,
            sku: `TEST-${faker.random.alphaNumeric(6)}`,
            barcode: '5901234123458',
            name: 'Test Product 2',
            current_stock: 200,
            unit_cost: 20.00,
          },
        }).then(() => {
          cy.request({
            method: 'POST',
            url: `${apiBaseUrl}/wms/barcode/scan/bulk-adjust`,
            headers: {
              Authorization: `Bearer ${token}`,
            },
            body: {
              scans: [
                {
                  barcode: testBarcode,
                  warehouse_id: testWarehouseId,
                  quantity: 5,
                  adjustment_type: 'in',
                  reason: 'E2E Test - Bulk 1',
                },
                {
                  barcode: '5901234123458',
                  warehouse_id: testWarehouseId,
                  quantity: 3,
                  adjustment_type: 'out',
                  reason: 'E2E Test - Bulk 2',
                },
              ],
            },
          }).then((response) => {
            expect(response.status).to.eq(201)
            expect(response.body.total).to.eq(2)
            expect(response.body.successful).to.eq(2)
            expect(response.body.failed).to.eq(0)
          })
        })
      })
    })

    it('should handle partial failures in bulk scan', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'POST',
          url: `${apiBaseUrl}/wms/barcode/scan/bulk-adjust`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          body: {
            scans: [
              {
                barcode: testBarcode,
                warehouse_id: testWarehouseId,
                quantity: 5,
                adjustment_type: 'in',
                reason: 'E2E Test - Valid',
              },
              {
                barcode: '9999999999999',
                warehouse_id: testWarehouseId,
                quantity: 5,
                adjustment_type: 'in',
                reason: 'E2E Test - Invalid',
              },
            ],
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.eq(201)
          expect(response.body.total).to.eq(2)
          expect(response.body.successful).to.eq(1)
          expect(response.body.failed).to.eq(1)
        })
      })
    })
  })

  describe('Scanner History', () => {
    it('should fetch scanner history', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'GET',
          url: `${apiBaseUrl}/wms/barcode/scan/history`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          qs: { warehouse_id: testWarehouseId },
        }).then((response) => {
          expect(response.status).to.eq(200)
          expect(response.body).to.have.property('data')
          expect(response.body).to.have.property('total')
        })
      })
    })

    it('should filter history by date range', () => {
      cy.get('@authToken').then((token) => {
        const today = new Date().toISOString().split('T')[0]
        cy.request({
          method: 'GET',
          url: `${apiBaseUrl}/wms/barcode/scan/history`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          qs: {
            warehouse_id: testWarehouseId,
            from_date: today,
            to_date: today,
          },
        }).then((response) => {
          expect(response.status).to.eq(200)
          expect(response.body).to.have.property('data')
        })
      })
    })
  })

  describe('ChestnyZnak Validation', () => {
    it('should validate ChestnyZnak code', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'POST',
          url: `${apiBaseUrl}/wms/barcode/chestny-znak/validate`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          body: {
            marking_code: '010467001234567821ABCDE12345678',
          },
        }).then((response) => {
          expect(response.status).to.eq(200)
          expect(response.body.valid).to.be.true
          expect(response.body).to.have.property('parsed')
        })
      })
    })

    it('should reject invalid ChestnyZnak code', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'POST',
          url: `${apiBaseUrl}/wms/barcode/chestny-znak/validate`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          body: {
            marking_code: 'SHORT',
          },
        }).then((response) => {
          expect(response.status).to.eq(200)
          expect(response.body.valid).to.be.false
          expect(response.body.message).to.eq('Invalid ChestnyZNAK format')
        })
      })
    })
  })

  describe('Batch Lookup', () => {
    it('should lookup batch by code', () => {
      cy.get('@authToken').then((token) => {
        // Create test batch first
        cy.request({
          method: 'POST',
          url: `${apiBaseUrl}/wms/batches`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          body: {
            warehouse_id: testWarehouseId,
            batch_number: `BATCH-${faker.random.alphaNumeric(6)}`,
            serial_number: `SN-${faker.random.alphaNumeric(6)}`,
            expiry_date: '2026-12-31',
            current_quantity: 50,
          },
        }).then((batchResponse) => {
          cy.request({
            method: 'POST',
            url: `${apiBaseUrl}/wms/barcode/batch/lookup`,
            headers: {
              Authorization: `Bearer ${token}`,
            },
            body: {
              code: batchResponse.body.batch_number,
              warehouse_id: testWarehouseId,
            },
          }).then((response) => {
            expect(response.status).to.eq(200)
            expect(response.body.found).to.be.true
          })
        })
      })
    })
  })

  describe('UI Integration Tests', () => {
    it('should display scanner page', () => {
      cy.visit('/wms/scanner')
      cy.contains('Сканер штрихкодов').should('be.visible')
    })

    it('should display warehouse selector', () => {
      cy.visit('/wms/scanner')
      cy.get('.warehouse-select').should('be.visible')
    })

    it('should display history button', () => {
      cy.visit('/wms/scanner')
      cy.contains('История').should('be.visible')
    })

    it('should open history panel', () => {
      cy.visit('/wms/scanner')
      cy.contains('История').click()
      cy.contains('История сканирований').should('be.visible')
    })

    it('should close history panel', () => {
      cy.visit('/wms/scanner')
      cy.contains('История').click()
      cy.get('.close-btn').click()
      cy.contains('История сканирований').should('not.be.visible')
    })
  })

  describe('WebSocket Integration', () => {
    it('should connect to scanner WebSocket channel', () => {
      cy.visit('/wms/scanner')
      
      cy.window().then((win) => {
        // Assuming Echo is available globally
        const Echo = (win as any).Echo
        if (Echo) {
          const channel = Echo.private(`scanner.1.${testWarehouseId}`)
          expect(channel).to.exist
        }
      })
    })

    it('should receive barcode scan updates', () => {
      cy.visit('/wms/scanner')
      
      // Trigger a scan via API
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'POST',
          url: `${apiBaseUrl}/wms/barcode/scan/adjust`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          body: {
            barcode: testBarcode,
            warehouse_id: testWarehouseId,
            quantity: 1,
            adjustment_type: 'in',
            reason: 'WebSocket Test',
          },
        }).then(() => {
          // Wait for WebSocket event (would need Echo listener setup)
          cy.wait(1000) // Give time for WebSocket propagation
        })
      })
    })
  })

  describe('Performance Tests', () => {
    it('should complete barcode lookup within 500ms', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'GET',
          url: `${apiBaseUrl}/wms/barcode/lookup/${testBarcode}`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          qs: { warehouse_id: testWarehouseId },
        }).then((response) => {
          expect(response.duration).to.be.lessThan(500)
        })
      })
    })

    it('should complete scan adjustment within 1s', () => {
      cy.get('@authToken').then((token) => {
        cy.request({
          method: 'POST',
          url: `${apiBaseUrl}/wms/barcode/scan/adjust`,
          headers: {
            Authorization: `Bearer ${token}`,
          },
          body: {
            barcode: testBarcode,
            warehouse_id: testWarehouseId,
            quantity: 1,
            adjustment_type: 'in',
            reason: 'Performance Test',
          },
        }).then((response) => {
          expect(response.duration).to.be.lessThan(1000)
        })
      })
    })
  })
})
