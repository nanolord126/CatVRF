describe('Security Vulnerabilities - API Authentication & Rate Limiting', () => {
  const baseUrl = 'http://localhost:8000/api';
  const webhookUrl = 'http://localhost:8000/internal/webhooks';

  describe('Vulnerability 1: API Authentication (Sanctum)', () => {
    it('Should reject unauthenticated API requests', () => {
      cy.request({
        method: 'GET',
        url: `${baseUrl}/user/profile`,
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.equal(401);
        expect(response.body.message).to.contain('Unauthenticated');
      });
    });

    it('Should reject invalid token', () => {
      cy.request({
        method: 'GET',
        url: `${baseUrl}/user/profile`,
        headers: {
          'Authorization': 'Bearer invalid-token-12345',
        },
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.equal(401);
      });
    });

    it('Should accept valid Sanctum token', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'GET',
          url: `${baseUrl}/user/profile`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
        }).then((response) => {
          expect(response.status).to.equal(200);
          expect(response.body).to.have.property('id');
        });
      });
    });

    it('Should validate token expiration', () => {
      cy.login('user@test.com', 'password').then((token) => {
        // Simulate token expiration
        cy.wait(65000);
        cy.request({
          method: 'GET',
          url: `${baseUrl}/user/profile`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          failOnStatusCode: false,
        }).then((response) => {
          // Token should be expired
          expect(response.status).to.equal(401);
        });
      });
    });

    it('Should support token refresh', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/auth/refresh`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
        }).then((response) => {
          expect(response.status).to.equal(200);
          expect(response.body).to.have.property('access_token');
        });
      });
    });

    it('Should validate API key for B2B integrations', () => {
      cy.request({
        method: 'GET',
        url: `${baseUrl}/b2b/products`,
        headers: {
          'X-API-Key': 'invalid-key',
        },
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.equal(401);
      });
    });

    it('Should accept valid API key', () => {
      const validApiKey = 'test-api-key-12345';
      cy.request({
        method: 'GET',
        url: `${baseUrl}/b2b/products`,
        headers: {
          'X-API-Key': validApiKey,
        },
      }).then((response) => {
        expect(response.status).to.equal(200);
      });
    });

    it('Should require specific scopes for sensitive endpoints', () => {
      cy.login('user@test.com', 'password', ['read:profile']).then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/wallet/withdraw`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: {
            amount: 1000,
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.equal(403);
          expect(response.body.message).to.contain('insufficient scope');
        });
      });
    });

    it('Should include token in response headers', () => {
      cy.request({
        method: 'POST',
        url: `${baseUrl}/auth/login`,
        body: {
          email: 'user@test.com',
          password: 'password',
        },
      }).then((response) => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('access_token');
        expect(response.body).to.have.property('token_type', 'Bearer');
        expect(response.body).to.have.property('expires_in');
      });
    });
  });

  describe('Vulnerability 2: Rate Limiting (Tenant-Aware + Sliding Window)', () => {
    it('Should enforce rate limit on payment endpoints', () => {
      cy.login('user@test.com', 'password').then((token) => {
        const requests = [];
        for (let i = 0; i < 101; i++) {
          requests.push(
            cy.request({
              method: 'POST',
              url: `${baseUrl}/payments/init`,
              headers: {
                'Authorization': `Bearer ${token}`,
              },
              body: {
                amount: 100,
                method: 'card',
              },
              failOnStatusCode: false,
            })
          );
        }
        return Promise.all(requests);
      }).then((responses) => {
        const lastResponse = responses[responses.length - 1];
        expect(lastResponse.status).to.equal(429);
        expect(lastResponse.headers['retry-after']).to.exist;
      });
    });

    it('Should return rate limit headers', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/payments/init`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: {
            amount: 100,
            method: 'card',
          },
        }).then((response) => {
          expect(response.headers['x-ratelimit-limit']).to.exist;
          expect(response.headers['x-ratelimit-remaining']).to.exist;
          expect(response.headers['x-ratelimit-reset']).to.exist;
        });
      });
    });

    it('Should use sliding window for rate limiting', () => {
      cy.login('user@test.com', 'password').then((token) => {
        // Make 50 requests in first minute
        for (let i = 0; i < 50; i++) {
          cy.request({
            method: 'POST',
            url: `${baseUrl}/promo/apply`,
            headers: {
              'Authorization': `Bearer ${token}`,
            },
            body: {
              code: 'PROMO123',
            },
          });
        }
        // Wait 10 seconds
        cy.wait(10000);
        // Make 51st request (should be allowed due to sliding window)
        cy.request({
          method: 'POST',
          url: `${baseUrl}/promo/apply`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: {
            code: 'PROMO123',
          },
        }).then((response) => {
          expect(response.status).to.equal(200);
        });
      });
    });

    it('Should isolate rate limits per tenant', () => {
      cy.login('tenant1@test.com', 'password').then((token1) => {
        cy.login('tenant2@test.com', 'password').then((token2) => {
          // Make 100 requests as tenant1
          const tenant1Requests = [];
          for (let i = 0; i < 100; i++) {
            tenant1Requests.push(
              cy.request({
                method: 'POST',
                url: `${baseUrl}/search`,
                headers: {
                  'Authorization': `Bearer ${token1}`,
                },
                body: {
                  query: 'test',
                },
                failOnStatusCode: false,
              })
            );
          }
          return Promise.all(tenant1Requests);
        }).then(() => {
          // Tenant2 should still have quota
          cy.login('tenant2@test.com', 'password').then((token2) => {
            cy.request({
              method: 'POST',
              url: `${baseUrl}/search`,
              headers: {
                'Authorization': `Bearer ${token2}`,
              },
              body: {
                query: 'test',
              },
            }).then((response) => {
              expect(response.status).to.equal(200);
            });
          });
        });
      });
    });

    it('Should enforce burst protection', () => {
      cy.login('user@test.com', 'password').then((token) => {
        const simultaneousRequests = [];
        for (let i = 0; i < 20; i++) {
          simultaneousRequests.push(
            cy.request({
              method: 'POST',
              url: `${baseUrl}/payments/init`,
              headers: {
                'Authorization': `Bearer ${token}`,
              },
              body: {
                amount: 100,
              },
              failOnStatusCode: false,
            })
          );
        }
        return Promise.all(simultaneousRequests);
      }).then((responses) => {
        const blockedCount = responses.filter(r => r.status === 429).length;
        expect(blockedCount).to.be.greaterThan(0);
      });
    });

    it('Should rate limit webhook attempts', () => {
      const webhookPayload = {
        event: 'payment.completed',
        data: { payment_id: '123' },
      };
      
      const requests = [];
      for (let i = 0; i < 101; i++) {
        requests.push(
          cy.request({
            method: 'POST',
            url: `${webhookUrl}/payment`,
            body: webhookPayload,
            failOnStatusCode: false,
          })
        );
      }
      
      return Promise.all(requests);
    }).then((responses) => {
      const lastResponse = responses[responses.length - 1];
      expect(lastResponse.status).to.equal(429);
    });

    it('Should track rate limit per IP address', () => {
      cy.request({
        method: 'GET',
        url: `${baseUrl}/public/categories`,
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.headers['x-ratelimit-limit']).to.exist;
      });
    });
  });

  describe('Vulnerability 3: Replay Attack Protection (Idempotency)', () => {
    it('Should accept payment with idempotency key', () => {
      cy.login('user@test.com', 'password').then((token) => {
        const idempotencyKey = 'payment-' + Date.now();
        cy.request({
          method: 'POST',
          url: `${baseUrl}/payments/init`,
          headers: {
            'Authorization': `Bearer ${token}`,
            'Idempotency-Key': idempotencyKey,
          },
          body: {
            amount: 5000,
            method: 'card',
            card_token: 'tok_visa',
          },
        }).then((response) => {
          expect(response.status).to.equal(200);
          expect(response.body).to.have.property('transaction_id');
        });
      });
    });

    it('Should reject duplicate payment with same idempotency key', () => {
      cy.login('user@test.com', 'password').then((token) => {
        const idempotencyKey = 'payment-' + Date.now();
        
        // First request
        cy.request({
          method: 'POST',
          url: `${baseUrl}/payments/init`,
          headers: {
            'Authorization': `Bearer ${token}`,
            'Idempotency-Key': idempotencyKey,
          },
          body: {
            amount: 5000,
            method: 'card',
            card_token: 'tok_visa',
          },
        }).then((firstResponse) => {
          expect(firstResponse.status).to.equal(200);
          const firstTransactionId = firstResponse.body.transaction_id;
          
          // Second identical request
          cy.request({
            method: 'POST',
            url: `${baseUrl}/payments/init`,
            headers: {
              'Authorization': `Bearer ${token}`,
              'Idempotency-Key': idempotencyKey,
            },
            body: {
              amount: 5000,
              method: 'card',
              card_token: 'tok_visa',
            },
          }).then((secondResponse) => {
            expect(secondResponse.status).to.equal(200);
            expect(secondResponse.body.transaction_id).to.equal(firstTransactionId);
          });
        });
      });
    });

    it('Should validate idempotency key format', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/payments/init`,
          headers: {
            'Authorization': `Bearer ${token}`,
            'Idempotency-Key': 'invalid-key-format',
          },
          body: {
            amount: 5000,
            method: 'card',
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect([400, 422]).to.include(response.status);
        });
      });
    });

    it('Should verify payload hash for idempotency', () => {
      cy.login('user@test.com', 'password').then((token) => {
        const idempotencyKey = 'payment-' + Date.now();
        
        cy.request({
          method: 'POST',
          url: `${baseUrl}/payments/init`,
          headers: {
            'Authorization': `Bearer ${token}`,
            'Idempotency-Key': idempotencyKey,
          },
          body: {
            amount: 5000,
            method: 'card',
          },
        }).then(() => {
          // Try with different payload but same key
          cy.request({
            method: 'POST',
            url: `${baseUrl}/payments/init`,
            headers: {
              'Authorization': `Bearer ${token}`,
              'Idempotency-Key': idempotencyKey,
            },
            body: {
              amount: 10000, // Different amount
              method: 'card',
            },
            failOnStatusCode: false,
          }).then((response) => {
            expect(response.status).to.equal(422);
          });
        });
      });
    });

    it('Should clean up expired idempotency records', () => {
      cy.login('user@test.com', 'password').then((token) => {
        const oldIdempotencyKey = 'old-' + (Date.now() - 86400000); // 1 day old
        
        cy.request({
          method: 'POST',
          url: `${baseUrl}/payments/init`,
          headers: {
            'Authorization': `Bearer ${token}`,
            'Idempotency-Key': oldIdempotencyKey,
          },
          body: {
            amount: 5000,
            method: 'card',
          },
          failOnStatusCode: false,
        }).then((response) => {
          // Expired key should be treated as new
          expect(response.status).to.equal(200);
        });
      });
    });

    it('Should require idempotency key for critical operations', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/wallet/withdraw`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: {
            amount: 5000,
            method: 'bank_transfer',
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.equal(400);
          expect(response.body.message).to.contain('Idempotency-Key');
        });
      });
    });
  });

  describe('Vulnerability 4: Webhook Signature Validation', () => {
    it('Should reject webhook without signature', () => {
      const payload = {
        event: 'payment.completed',
        data: {
          payment_id: '123',
          amount: 5000,
        },
      };
      
      cy.request({
        method: 'POST',
        url: `${webhookUrl}/payment`,
        body: payload,
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.equal(401);
        expect(response.body.message).to.contain('signature');
      });
    });

    it('Should reject webhook with invalid signature', () => {
      const payload = {
        event: 'payment.completed',
        data: {
          payment_id: '123',
          amount: 5000,
        },
      };
      
      cy.request({
        method: 'POST',
        url: `${webhookUrl}/payment`,
        headers: {
          'X-Tinkoff-Signature': 'invalid-signature-12345',
          'X-Webhook-ID': 'webhook-123',
          'X-Webhook-Timestamp': Math.floor(Date.now() / 1000),
        },
        body: payload,
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.equal(401);
      });
    });

    it('Should accept webhook with valid signature', () => {
      const payload = {
        event: 'payment.completed',
        data: {
          payment_id: '123',
          amount: 5000,
          status: 'captured',
        },
      };
      
      // Generate valid signature using shared secret
      const signature = generateHmacSha256(JSON.stringify(payload), process.env.WEBHOOK_SECRET);
      
      cy.request({
        method: 'POST',
        url: `${webhookUrl}/payment`,
        headers: {
          'X-Tinkoff-Signature': signature,
          'X-Webhook-ID': 'webhook-123',
          'X-Webhook-Timestamp': Math.floor(Date.now() / 1000),
        },
        body: payload,
      }).then((response) => {
        expect(response.status).to.equal(200);
      });
    });

    it('Should verify webhook timestamp to prevent replay', () => {
      const payload = {
        event: 'payment.completed',
        data: { payment_id: '123' },
      };
      
      const oldTimestamp = Math.floor((Date.now() - 600000) / 1000); // 10 minutes old
      const signature = generateHmacSha256(JSON.stringify(payload), process.env.WEBHOOK_SECRET);
      
      cy.request({
        method: 'POST',
        url: `${webhookUrl}/payment`,
        headers: {
          'X-Tinkoff-Signature': signature,
          'X-Webhook-ID': 'webhook-123',
          'X-Webhook-Timestamp': oldTimestamp,
        },
        body: payload,
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.equal(401);
        expect(response.body.message).to.contain('timestamp');
      });
    });

    it('Should verify webhook source IP', () => {
      const payload = {
        event: 'payment.completed',
        data: { payment_id: '123' },
      };
      
      const signature = generateHmacSha256(JSON.stringify(payload), process.env.WEBHOOK_SECRET);
      
      cy.request({
        method: 'POST',
        url: `${webhookUrl}/payment`,
        headers: {
          'X-Tinkoff-Signature': signature,
          'X-Webhook-ID': 'webhook-123',
          'X-Webhook-Timestamp': Math.floor(Date.now() / 1000),
          'X-Forwarded-For': '192.168.1.1', // Spoofed IP
        },
        body: payload,
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.equal(401);
      });
    });

    it('Should log all webhook requests for audit', () => {
      const payload = {
        event: 'payment.completed',
        data: { payment_id: '123' },
      };
      
      const signature = generateHmacSha256(JSON.stringify(payload), process.env.WEBHOOK_SECRET);
      
      cy.request({
        method: 'POST',
        url: `${webhookUrl}/payment`,
        headers: {
          'X-Tinkoff-Signature': signature,
          'X-Webhook-ID': 'webhook-123',
          'X-Webhook-Timestamp': Math.floor(Date.now() / 1000),
        },
        body: payload,
      }).then(() => {
        cy.visit(`${baseUrl.replace('/api', '')}/app/admin/logs/webhooks`);
        cy.contains('webhook-123').should('be.visible');
      });
    });

    it('Should support multiple webhook providers with different signatures', () => {
      const tinkoffPayload = {
        event: 'payment.completed',
        data: { payment_id: 'tink-123' },
      };
      
      const tinkoffSignature = generateHmacSha256(
        JSON.stringify(tinkoffPayload),
        process.env.TINKOFF_WEBHOOK_SECRET
      );
      
      cy.request({
        method: 'POST',
        url: `${webhookUrl}/payment`,
        headers: {
          'X-Tinkoff-Signature': tinkoffSignature,
          'X-Webhook-Provider': 'tinkoff',
          'X-Webhook-Timestamp': Math.floor(Date.now() / 1000),
        },
        body: tinkoffPayload,
      }).then((response) => {
        expect(response.status).to.equal(200);
      });
    });

    it('Should handle webhook signature rotation', () => {
      // Test with new signature key
      const payload = {
        event: 'payment.completed',
        data: { payment_id: '123' },
      };
      
      const newSignature = generateHmacSha256(
        JSON.stringify(payload),
        process.env.WEBHOOK_SECRET_NEW
      );
      
      cy.request({
        method: 'POST',
        url: `${webhookUrl}/payment`,
        headers: {
          'X-Tinkoff-Signature': newSignature,
          'X-Webhook-ID': 'webhook-123',
          'X-Webhook-Timestamp': Math.floor(Date.now() / 1000),
        },
        body: payload,
      }).then((response) => {
        expect(response.status).to.equal(200);
      });
    });
  });

  describe('Vulnerability 5: RBAC - User vs Tenant CRM Separation', () => {
    it('Should prevent regular user from accessing CRM functions', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'GET',
          url: `${baseUrl}/crm/hr/employees`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.equal(403);
        });
      });
    });

    it('Should prevent regular user from viewing tenant payroll', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'GET',
          url: `${baseUrl}/crm/payroll`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.equal(403);
        });
      });
    });

    it('Should prevent regular user from managing tenant payout settings', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/crm/payout-settings`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: {
            auto_payout: true,
            threshold: 10000,
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.equal(403);
        });
      });
    });

    it('Should prevent user from accessing other tenant data', () => {
      cy.login('user1@test.com', 'password').then((token1) => {
        cy.login('tenant2@test.com', 'password').then((token2) => {
          // User1 tries to access Tenant2's data
          cy.request({
            method: 'GET',
            url: `${baseUrl}/tenant/2/dashboard`,
            headers: {
              'Authorization': `Bearer ${token1}`,
            },
            failOnStatusCode: false,
          }).then((response) => {
            expect(response.status).to.equal(403);
          });
        });
      });
    });

    it('Should allow tenant owner to access CRM functions', () => {
      cy.login('tenant@test.com', 'password', ['role:tenant-owner']).then((token) => {
        cy.request({
          method: 'GET',
          url: `${baseUrl}/crm/hr/employees`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
        }).then((response) => {
          expect(response.status).to.equal(200);
        });
      });
    });

    it('Should enforce role-based access to sensitive operations', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/wallet/admin-refund`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: {
            user_id: 123,
            amount: 5000,
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.equal(403);
        });
      });
    });

    it('Should verify permissions for API tokens', () => {
      const apiToken = 'api_key_limited_scope';
      cy.request({
        method: 'POST',
        url: `${baseUrl}/admin/users`,
        headers: {
          'X-API-Key': apiToken,
        },
        body: {
          email: 'new@test.com',
        },
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.equal(403);
      });
    });
  });

  describe('Vulnerability 6: Input Validation on All Endpoints', () => {
    it('Should validate required fields', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/payments/init`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: {
            // Missing required 'amount' field
            method: 'card',
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.equal(422);
          expect(response.body.errors).to.have.property('amount');
        });
      });
    });

    it('Should validate numeric fields', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/payments/init`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: {
            amount: 'not-a-number',
            method: 'card',
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.equal(422);
          expect(response.body.errors).to.have.property('amount');
        });
      });
    });

    it('Should validate email format', () => {
      cy.request({
        method: 'POST',
        url: `${baseUrl}/auth/register`,
        body: {
          email: 'invalid-email',
          password: 'password123',
        },
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.equal(422);
        expect(response.body.errors).to.have.property('email');
      });
    });

    it('Should validate password strength', () => {
      cy.request({
        method: 'POST',
        url: `${baseUrl}/auth/register`,
        body: {
          email: 'user@test.com',
          password: '123', // Too weak
        },
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.equal(422);
        expect(response.body.errors).to.have.property('password');
      });
    });

    it('Should validate enum values', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/payments/init`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: {
            amount: 5000,
            method: 'invalid-method',
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.equal(422);
          expect(response.body.errors).to.have.property('method');
        });
      });
    });

    it('Should sanitize string inputs', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/promo/apply`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: {
            code: '<script>alert("xss")</script>',
          },
          failOnStatusCode: false,
        }).then((response) => {
          // Should reject or sanitize
          expect([400, 422]).to.include(response.status);
        });
      });
    });

    it('Should validate array inputs', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/wishlist/bulk-add`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: {
            product_ids: 'not-an-array',
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.equal(422);
        });
      });
    });

    it('Should validate date format', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/booking/create`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: {
            date: 'invalid-date',
            time: '14:00',
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.equal(422);
          expect(response.body.errors).to.have.property('date');
        });
      });
    });

    it('Should validate file uploads', () => {
      cy.login('user@test.com', 'password').then((token) => {
        const form = new FormData();
        form.append('file', new Blob(['test'], { type: 'application/pdf' }), 'test.exe');
        
        cy.request({
          method: 'POST',
          url: `${baseUrl}/documents/upload`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: form,
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.equal(422);
        });
      });
    });

    it('Should validate max length constraints', () => {
      cy.request({
        method: 'POST',
        url: `${baseUrl}/auth/register`,
        body: {
          email: 'user@test.com',
          password: 'password123',
          name: 'a'.repeat(256), // Exceeds max length
        },
        failOnStatusCode: false,
      }).then((response) => {
        expect(response.status).to.equal(422);
        expect(response.body.errors).to.have.property('name');
      });
    });

    it('Should validate nested object structures', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/orders/create`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: {
            items: [
              {
                product_id: 123,
                // Missing required quantity
                price: 100,
              },
            ],
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect(response.status).to.equal(422);
        });
      });
    });

    it('Should validate against SQL injection patterns', () => {
      cy.login('user@test.com', 'password').then((token) => {
        cy.request({
          method: 'POST',
          url: `${baseUrl}/search`,
          headers: {
            'Authorization': `Bearer ${token}`,
          },
          body: {
            query: "'; DROP TABLE users; --",
          },
          failOnStatusCode: false,
        }).then((response) => {
          expect([400, 422]).to.include(response.status);
        });
      });
    });
  });

  // Helper function
  function generateHmacSha256(data, secret) {
    return Cypress.crypto.encryptAES(data, secret, 'sha256');
  }
});
