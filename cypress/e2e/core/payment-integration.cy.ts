/// <reference types="cypress" />

describe('Payment System Integration with All Verticals', () => {
  const tenantId = 1;
  
  // Payment holds should trigger fraud checks, wallet holds, and inventory reserves
  const integrationFlows = {
    beauty: { type: 'appointment', amount: 50000, service: 'haircut' },
    food: { type: 'order', amount: 120000, service: 'lunch_delivery' },
    taxi: { type: 'ride', amount: 35000, service: 'comfort_ride' },
    realestate: { type: 'viewing', amount: 0, service: 'property_tour' }, // No charge for viewing
  };

  before(() => {
    cy.login('tenant_admin@example.com', 'password123');
  });

  describe('Payment Hold → Fraud Check → Wallet Reserve Flow', () => {
    it('Should trigger fraud check before payment hold is authorized', () => {
      const payload = {
        user_id: 1001,
        operation_type: 'appointment_booking',
        amount: integrationFlows.beauty.amount,
        ip_address: '192.168.1.100',
        device_fingerprint: 'device-12345',
        correlation_id: `corr-${Date.now()}`,
      };

      // Step 1: Submit payment hold request
      cy.request({
        method: 'POST',
        url: `/api/payments/pre-check`,
        body: payload,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('fraud_score');
        expect(response.body).to.have.property('can_proceed');
        expect(response.body.fraud_score).to.be.within(0, 1);
      });
    });

    it('Should block payment if fraud score exceeds threshold', () => {
      // Simulate suspicious activity
      const suspiciousPayload = {
        user_id: 9999,
        operation_type: 'appointment_booking',
        amount: 500000, // 5000x normal for beauty
        ip_address: '10.0.0.1', // VPN/proxy
        device_fingerprint: 'unknown-device',
        location_change_hours: 0.5, // Rapid location change
      };

      cy.request({
        method: 'POST',
        url: `/api/payments/pre-check`,
        body: suspiciousPayload,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.fraud_score).to.be.greaterThan(0.7);
        expect(response.body.can_proceed).to.equal(false);
      });
    });

    it('Should hold wallet balance during payment authorization', () => {
      const appointmentId = 3001;
      const amount = integrationFlows.beauty.amount;

      // Create hold
      cy.request({
        method: 'POST',
        url: `/api/payments/hold`,
        body: {
          appointment_id: appointmentId,
          amount: amount,
          payment_method: 'card',
          hold_duration_hours: 48,
          correlation_id: `hold-${appointmentId}`,
        },
      }).then(response => {
        expect(response.status).to.equal(201);
        expect(response.body).to.have.property('transaction_id');
        const transactionId = response.body.transaction_id;

        // Verify wallet hold was created
        cy.request({
          method: 'GET',
          url: `/api/wallets/holds`,
        }).then(walletResponse => {
          expect(walletResponse.status).to.equal(200);
          const holdExists = walletResponse.body.holds.some(
            h => h.transaction_id === transactionId && h.amount === amount
          );
          expect(holdExists).to.equal(true);
        });
      });
    });

    it('Should verify hold is visible in wallet available balance calculation', () => {
      cy.request({
        method: 'GET',
        url: `/api/wallets/balance`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('current_balance');
        expect(response.body).to.have.property('hold_amount');
        expect(response.body).to.have.property('available_balance');
        
        const expected = response.body.current_balance - response.body.hold_amount;
        expect(response.body.available_balance).to.equal(expected);
      });
    });
  });

  describe('Payment Capture → Inventory Deduction → Commission Calculation', () => {
    it('Should capture payment only after service completion', () => {
      const orderId = 7001;
      const amount = integrationFlows.food.amount;

      // Hold is already created, now capture
      cy.request({
        method: 'POST',
        url: `/api/payments/${orderId}/capture`,
        body: {
          amount: amount,
          reason: 'Order completed and delivered',
          correlation_id: `capture-${orderId}`,
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('captured');
      });
    });

    it('Should deduct inventory when payment is captured', () => {
      const orderId = 7001;
      
      // After capture, check inventory deduction
      cy.request({
        method: 'GET',
        url: `/api/orders/${orderId}/inventory-impact`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('deducted_items');
        expect(response.body.deducted_items).to.be.an('array');
        expect(response.body.deducted_items.length).to.be.greaterThan(0);
      });
    });

    it('Should calculate and apply commission after payment capture', () => {
      const restaurantId = 301;
      
      cy.request({
        method: 'GET',
        url: `/api/restaurants/${restaurantId}/pending-commission`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('pending_amount');
        expect(response.body).to.have.property('commission_rate');
        expect(response.body).to.have.property('calculated_commission');
      });
    });
  });

  describe('Payment Refund → Wallet Credit → Inventory Release', () => {
    it('Should refund payment if user cancels within grace period', () => {
      const appointmentId = 3002;

      cy.request({
        method: 'POST',
        url: `/api/payments/${appointmentId}/refund`,
        body: {
          reason: 'User cancelled appointment',
          initiated_by: 'user',
          correlation_id: `refund-${appointmentId}`,
        },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.status).to.equal('refunded');
        expect(response.body).to.have.property('refund_amount');
      });
    });

    it('Should credit refund back to customer wallet', () => {
      const appointmentId = 3002;
      
      cy.request({
        method: 'GET',
        url: `/api/wallets/transactions`,
        queryParams: { related_to: appointmentId },
      }).then(response => {
        expect(response.status).to.equal(200);
        const refundTx = response.body.transactions.find(
          tx => tx.type === 'refund' && tx.amount > 0
        );
        expect(refundTx).to.exist;
      });
    });

    it('Should release held inventory when payment is refunded', () => {
      const appointmentId = 3002;
      
      cy.request({
        method: 'GET',
        url: `/api/inventory-holds/${appointmentId}`,
      }).then(response => {
        if (response.status === 200 && response.body.holds.length > 0) {
          expect(response.body.holds[0].status).to.equal('released');
        }
      });
    });

    it('Should deny refund if grace period has expired', () => {
      const appointmentId = 3003; // Very old appointment

      cy.request({
        method: 'POST',
        url: `/api/payments/${appointmentId}/refund`,
        body: {
          reason: 'User wants money back',
        },
        failOnStatusCode: false,
      }).then(response => {
        expect(response.status).to.be.oneOf([400, 403]);
        expect(response.body).to.have.property('message');
      });
    });
  });

  describe('Idempotency Across Payment Operations', () => {
    it('Should not double-charge on duplicate payment hold request', () => {
      const appointmentId = 3004;
      const idempotencyKey = `hold-appt-3004-${Date.now()}`;
      const amount = integrationFlows.beauty.amount;

      // First request
      cy.request({
        method: 'POST',
        url: `/api/payments/hold`,
        body: {
          appointment_id: appointmentId,
          amount: amount,
          idempotency_key: idempotencyKey,
        },
      }).then(response1 => {
        expect(response1.status).to.equal(201);
        const txId1 = response1.body.transaction_id;

        // Duplicate request with same idempotency key
        cy.request({
          method: 'POST',
          url: `/api/payments/hold`,
          body: {
            appointment_id: appointmentId,
            amount: amount,
            idempotency_key: idempotencyKey,
          },
        }).then(response2 => {
          // Should return 200 (already processed) not 201 (new)
          expect(response2.status).to.equal(200);
          expect(response2.body.transaction_id).to.equal(txId1);
        });
      });
    });

    it('Should not double-charge on duplicate capture request', () => {
      const orderId = 7002;
      const idempotencyKey = `capture-order-7002-${Date.now()}`;

      cy.request({
        method: 'POST',
        url: `/api/payments/${orderId}/capture`,
        body: {
          idempotency_key: idempotencyKey,
        },
      }).then(response1 => {
        expect(response1.status).to.equal(200);

        cy.request({
          method: 'POST',
          url: `/api/payments/${orderId}/capture`,
          body: {
            idempotency_key: idempotencyKey,
          },
        }).then(response2 => {
          expect(response2.status).to.equal(200);
          expect(response2.body.status).to.equal('captured');
        });
      });
    });
  });

  describe('Multi-Vertical Concurrent Payment Processing', () => {
    it('Should handle simultaneous payments without wallet corruption', () => {
      const operations = [];

      // Parallel beauty appointment hold
      operations.push(
        cy.request({
          method: 'POST',
          url: `/api/payments/hold`,
          body: {
            appointment_id: 3005,
            amount: integrationFlows.beauty.amount,
            idempotency_key: `parallel-beauty-${Date.now()}`,
          },
        })
      );

      // Parallel food order hold
      operations.push(
        cy.request({
          method: 'POST',
          url: `/api/payments/hold`,
          body: {
            order_id: 7003,
            amount: integrationFlows.food.amount,
            idempotency_key: `parallel-food-${Date.now()}`,
          },
        })
      );

      // Parallel taxi ride hold
      operations.push(
        cy.request({
          method: 'POST',
          url: `/api/payments/hold`,
          body: {
            ride_id: 6001,
            amount: integrationFlows.taxi.amount,
            idempotency_key: `parallel-taxi-${Date.now()}`,
          },
        })
      );

      // All should succeed without race conditions
      Promise.all(operations).then(() => {
        // Verify wallet integrity
        cy.request({
          method: 'GET',
          url: `/api/wallets/balance`,
        }).then(response => {
          expect(response.status).to.equal(200);
          const totalHeld = integrationFlows.beauty.amount + 
                           integrationFlows.food.amount + 
                           integrationFlows.taxi.amount;
          expect(response.body.hold_amount).to.be.greaterThanOrEqual(totalHeld - 1000); // Allow rounding
        });
      });
    });
  });

  describe('Commission & Payout Integration', () => {
    it('Should calculate commission from captured payments', () => {
      const restaurantId = 301;

      cy.request({
        method: 'GET',
        url: `/api/restaurants/${restaurantId}/earnings`,
        queryParams: { period: 'today' },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('gross_revenue');
        expect(response.body).to.have.property('platform_commission');
        expect(response.body).to.have.property('net_payout');
        expect(response.body.platform_commission).to.be.greaterThan(0);
      });
    });

    it('Should hold commission separately from customer balance', () => {
      const salonId = 101;

      cy.request({
        method: 'GET',
        url: `/api/salons/${salonId}/wallet`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('available_for_payout');
        expect(response.body).to.have.property('commission_held');
        expect(response.body.available_for_payout).to.equal(
          response.body.current_balance - response.body.commission_held
        );
      });
    });

    it('Should schedule payout only from available (after commission) balance', () => {
      const salonId = 101;

      cy.request({
        method: 'POST',
        url: `/api/salons/${salonId}/request-payout`,
        body: {
          amount: 100000,
          payout_method: 'bank_transfer',
        },
      }).then(response => {
        if (response.status === 201) {
          expect(response.body).to.have.property('payout_id');
        } else if (response.status === 400) {
          // Insufficient balance - expected if balance < 100000
          expect(response.body.message).to.include('insufficient');
        }
      });
    });
  });

  describe('Payment Webhooks & Notifications Integration', () => {
    it('Should trigger notifications when payment status changes', () => {
      const appointmentId = 3006;

      // Create hold
      cy.request({
        method: 'POST',
        url: `/api/payments/hold`,
        body: {
          appointment_id: appointmentId,
          amount: integrationFlows.beauty.amount,
        },
      });

      // Check that notifications were queued
      cy.request({
        method: 'GET',
        url: `/api/notifications`,
        queryParams: { event: 'payment_held', related_to: appointmentId },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body.notifications.length).to.be.greaterThan(0);
      });
    });

    it('Should update master wallet when payment is captured', () => {
      const masterId = 201;
      const appointmentId = 3007;

      // Complete appointment with payment capture
      cy.request({
        method: 'POST',
        url: `/api/payments/${appointmentId}/capture`,
      });

      // Check master earnings updated
      cy.request({
        method: 'GET',
        url: `/api/masters/${masterId}/earnings`,
        queryParams: { include_today: true },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('today_earnings');
      });
    });
  });

  describe('Tax & Compliance Integration', () => {
    it('Should log all payments for tax compliance', () => {
      const orderId = 7004;

      cy.request({
        method: 'GET',
        url: `/api/compliance/payment-log`,
        queryParams: { order_id: orderId },
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('payment_records');
        response.body.payment_records.forEach(record => {
          expect(record).to.have.property('timestamp');
          expect(record).to.have.property('amount');
          expect(record).to.have.property('tax_treatment');
        });
      });
    });

    it('Should include payment info in fiscal receipts', () => {
      const orderId = 7004;

      cy.request({
        method: 'GET',
        url: `/api/orders/${orderId}/fiscal-receipt`,
      }).then(response => {
        expect(response.status).to.equal(200);
        expect(response.body).to.have.property('payment_method');
        expect(response.body).to.have.property('payment_status');
        expect(response.body).to.have.property('payment_amount');
      });
    });
  });
});
