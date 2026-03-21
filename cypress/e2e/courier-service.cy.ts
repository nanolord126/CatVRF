describe('Courier Service Tests (Курьерские услуги)', () => {
  const baseUrl = 'http://localhost:8000';
  const businessEmail = 'courier-business@test.com';
  const courierEmail = 'courier@test.com';
  const customerEmail = 'courier-customer@test.com';
  const password = 'password';

  describe('Courier Registration & Profile', () => {
    it('Should register new courier', () => {
      cy.visit(`${baseUrl}/register/courier`);
      cy.get('input[name="email"]').type('new-courier@test.com');
      cy.get('input[name="password"]').type(password);
      cy.get('input[name="phone"]').type('79991234567');
      cy.get('input[name="vehicle_type"]').select('bicycle');
      cy.contains('button', 'Register').click();
      cy.wait(500);
      cy.contains('Courier registered successfully').should('be.visible');
    });

    it('Should upload courier documents (license, passport)', () => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(courierEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);

      cy.visit(`${baseUrl}/app/courier/documents`);
      cy.get('input[type="file"][name="passport"]').selectFile('cypress/fixtures/passport.pdf', { force: true });
      cy.wait(300);
      cy.get('input[type="file"][name="license"]').selectFile('cypress/fixtures/license.pdf', { force: true });
      cy.wait(300);
      cy.contains('button', 'Upload').click();
      cy.wait(500);
      cy.contains('Documents uploaded').should('be.visible');
    });

    it('Should verify courier identity', () => {
      cy.visit(`${baseUrl}/app/courier/verification`);
      cy.wait(300);
      cy.contains('Verification in progress').should('be.visible');
      cy.wait(2000);
      cy.get('[data-test="verification-status"]').should('contain', 'Verified');
    });

    it('Should set courier vehicle information', () => {
      cy.visit(`${baseUrl}/app/courier/vehicle`);
      cy.get('select[name="vehicle_type"]').select('motorcycle');
      cy.get('input[name="vehicle_number"]').type('AB123CD');
      cy.get('input[name="vehicle_color"]').type('Black');
      cy.get('input[name="vehicle_model"]').type('Honda CB500');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Vehicle info saved').should('be.visible');
    });

    it('Should set service areas (zones)', () => {
      cy.visit(`${baseUrl}/app/courier/service-areas`);
      cy.get('[data-test="map-container"]').should('be.visible');
      cy.contains('button', 'Add Zone').click();
      cy.wait(300);
      cy.get('input[name="zone_name"]').type('Zone A - Downtown');
      cy.get('input[name="radius"]').type('5');
      cy.contains('button', 'Save Zone').click();
      cy.wait(500);
      cy.contains('Zone added').should('be.visible');
    });

    it('Should set courier working hours', () => {
      cy.visit(`${baseUrl}/app/courier/schedule`);
      cy.get('input[name="monday_start"]').type('09:00');
      cy.get('input[name="monday_end"]').type('18:00');
      cy.get('input[name="tuesday_start"]').type('09:00');
      cy.get('input[name="tuesday_end"]').type('18:00');
      cy.contains('button', 'Save Schedule').click();
      cy.wait(500);
      cy.contains('Schedule updated').should('be.visible');
    });

    it('Should set delivery rates and pricing', () => {
      cy.visit(`${baseUrl}/app/courier/pricing`);
      cy.get('input[name="base_fee"]').clear().type('100');
      cy.get('input[name="per_km"]').clear().type('20');
      cy.get('input[name="rush_multiplier"]').clear().type('1.5');
      cy.contains('button', 'Save Pricing').click();
      cy.wait(500);
      cy.contains('Pricing updated').should('be.visible');
    });

    it('Should set payment methods for courier', () => {
      cy.visit(`${baseUrl}/app/courier/payment-methods`);
      cy.get('input[name="bank_account"]').type('12345678901234567890');
      cy.get('input[name="bank_name"]').type('Sberbank');
      cy.get('input[name="card_number"]').type('4111111111111111');
      cy.contains('button', 'Add').click();
      cy.wait(500);
      cy.contains('Payment method added').should('be.visible');
    });

    it('Should view courier profile', () => {
      cy.visit(`${baseUrl}/app/courier/profile`);
      cy.get('[data-test="name"]').should('contain', 'Courier Name');
      cy.get('[data-test="rating"]').should('be.visible');
      cy.get('[data-test="completed-deliveries"]').should('contain', 'deliveries');
    });
  });

  describe('Delivery Order Management', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(courierEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should view available deliveries', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/available`);
      cy.get('[data-test="delivery-item"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="delivery-distance"]').should('be.visible');
      cy.get('[data-test="delivery-fee"]').should('contain', '₽');
    });

    it('Should accept delivery order', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/available`);
      cy.get('[data-test="delivery-item"]').first().within(() => {
        cy.contains('button', 'Accept').click();
      });
      cy.wait(500);
      cy.contains('Delivery accepted').should('be.visible');
    });

    it('Should view accepted delivery details', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/active`);
      cy.get('[data-test="active-delivery"]').first().click();
      cy.wait(300);
      cy.contains('Delivery Details').should('be.visible');
      cy.get('[data-test="pickup-address"]').should('be.visible');
      cy.get('[data-test="delivery-address"]').should('be.visible');
      cy.get('[data-test="customer-name"]').should('be.visible');
      cy.get('[data-test="package-description"]').should('be.visible');
    });

    it('Should navigate to pickup location with GPS', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/active/1`);
      cy.contains('button', 'Navigate').click();
      cy.wait(300);
      cy.get('[data-test="map-container"]').should('be.visible');
      cy.get('[data-test="gps-active"]').should('have.attr', 'data-tracking', 'true');
    });

    it('Should mark delivery as picked up', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/active/1`);
      cy.contains('button', 'Pickup Collected').click();
      cy.wait(300);
      cy.get('input[name="signature"]').should('be.visible');
      cy.contains('button', 'Confirm Pickup').click();
      cy.wait(500);
      cy.contains('Package picked up').should('be.visible');
    });

    it('Should navigate to delivery location', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/active/1`);
      cy.get('[data-test="status"]').should('contain', 'In Transit');
      cy.contains('button', 'Deliver').click();
      cy.wait(500);
      cy.get('[data-test="delivery-map"]').should('be.visible');
    });

    it('Should confirm delivery with customer signature', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/active/1`);
      cy.contains('button', 'Confirm Delivery').click();
      cy.wait(300);
      cy.get('canvas[data-test="signature-pad"]').should('be.visible');
      cy.get('canvas').click(10, 10).click(20, 20).click(30, 10);
      cy.contains('button', 'Confirm').click();
      cy.wait(500);
      cy.contains('Delivery completed').should('be.visible');
    });

    it('Should take photo of delivery', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/active/1`);
      cy.contains('button', 'Take Photo').click();
      cy.wait(300);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/delivery-photo.jpg', { force: true });
      cy.wait(500);
      cy.contains('Photo uploaded').should('be.visible');
    });

    it('Should handle delivery issue (package damaged)', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/active/1`);
      cy.contains('button', 'Report Issue').click();
      cy.wait(300);
      cy.get('select[name="issue_type"]').select('package_damaged');
      cy.get('textarea[name="description"]').type('Package corner is damaged');
      cy.get('input[type="file"]').selectFile('cypress/fixtures/damaged-package.jpg', { force: true });
      cy.contains('button', 'Submit').click();
      cy.wait(500);
      cy.contains('Issue reported').should('be.visible');
    });

    it('Should handle failed delivery attempt', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/active/1`);
      cy.contains('button', 'Unable to Deliver').click();
      cy.wait(300);
      cy.get('select[name="reason"]').select('customer_not_available');
      cy.get('textarea[name="notes"]').type('Customer not home');
      cy.contains('button', 'Submit').click();
      cy.wait(500);
      cy.contains('Return to sender initiated').should('be.visible');
    });

    it('Should reject delivery order', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/available`);
      cy.get('[data-test="delivery-item"]').first().within(() => {
        cy.contains('button', 'Decline').click();
      });
      cy.wait(500);
      cy.contains('Delivery declined').should('be.visible');
    });
  });

  describe('Courier Earnings & Payouts', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(courierEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should view earnings dashboard', () => {
      cy.visit(`${baseUrl}/app/courier/earnings`);
      cy.contains('Today Earnings').should('be.visible');
      cy.get('[data-test="today-earnings"]').should('contain', '₽');
      cy.get('[data-test="week-earnings"]').should('contain', '₽');
      cy.get('[data-test="month-earnings"]').should('contain', '₽');
    });

    it('Should view delivery history with earnings', () => {
      cy.visit(`${baseUrl}/app/courier/history`);
      cy.get('[data-test="history-item"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="delivery-fee"]').each(($el) => {
        cy.wrap($el).should('contain', '₽');
      });
    });

    it('Should request payout to bank account', () => {
      cy.visit(`${baseUrl}/app/courier/earnings`);
      cy.get('[data-test="available-balance"]').should('be.visible');
      cy.contains('button', 'Request Payout').click();
      cy.wait(300);
      cy.get('input[name="amount"]').type('5000');
      cy.contains('button', 'Submit').click();
      cy.wait(500);
      cy.contains('Payout requested').should('be.visible');
    });

    it('Should track payout status', () => {
      cy.visit(`${baseUrl}/app/courier/payouts`);
      cy.get('[data-test="payout-item"]').first().within(() => {
        cy.get('[data-test="status"]').should('be.oneOf', ['Pending', 'Processing', 'Completed']);
      });
    });

    it('Should view earnings breakdown by delivery type', () => {
      cy.visit(`${baseUrl}/app/courier/earnings-breakdown`);
      cy.get('[data-test="standard-deliveries"]').should('contain', '₽');
      cy.get('[data-test="rush-deliveries"]').should('contain', '₽');
      cy.get('[data-test="scheduled-deliveries"]').should('contain', '₽');
    });

    it('Should view commission deduction details', () => {
      cy.visit(`${baseUrl}/app/courier/earnings`);
      cy.contains('Commission Breakdown').should('be.visible');
      cy.get('[data-test="platform-commission"]').should('contain', '%');
      cy.get('[data-test="insurance-fee"]').should('be.visible');
    });

    it('Should view bonus and incentives', () => {
      cy.visit(`${baseUrl}/app/courier/bonuses`);
      cy.contains('Active Bonuses').should('be.visible');
      cy.get('[data-test="bonus-item"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="bonus-condition"]').should('be.visible');
      cy.get('[data-test="bonus-amount"]').should('contain', '₽');
    });
  });

  describe('Courier Performance & Ratings', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(courierEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should view courier rating', () => {
      cy.visit(`${baseUrl}/app/courier/profile`);
      cy.get('[data-test="rating"]').should('contain', '4.');
      cy.get('[data-test="review-count"]').should('be.visible');
    });

    it('Should view detailed reviews', () => {
      cy.visit(`${baseUrl}/app/courier/reviews`);
      cy.get('[data-test="review-item"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="review-rating"]').each(($el) => {
        const rating = parseInt($el.text());
        expect(rating).to.be.within(1, 5);
      });
    });

    it('Should view performance statistics', () => {
      cy.visit(`${baseUrl}/app/courier/performance`);
      cy.contains('On-time Delivery Rate').should('be.visible');
      cy.get('[data-test="on-time-rate"]').should('contain', '%');
      cy.contains('Completion Rate').should('be.visible');
      cy.get('[data-test="completion-rate"]').should('contain', '%');
      cy.contains('Average Rating').should('be.visible');
    });

    it('Should view acceptance rate', () => {
      cy.visit(`${baseUrl}/app/courier/performance`);
      cy.contains('Acceptance Rate').should('be.visible');
      cy.get('[data-test="acceptance-rate"]').should('contain', '%');
    });

    it('Should track delivery metrics', () => {
      cy.visit(`${baseUrl}/app/courier/metrics`);
      cy.get('[data-test="total-deliveries"]').should('contain', 'deliveries');
      cy.get('[data-test="successful-deliveries"]').should('contain', 'deliveries');
      cy.get('[data-test="failed-deliveries"]').should('contain', 'deliveries');
    });

    it('Should view disputes and ratings impact', () => {
      cy.visit(`${baseUrl}/app/courier/disputes`);
      cy.contains('Open Disputes').should('be.visible');
      cy.get('[data-test="dispute-item"]').should('have.length.greaterThan', 0);
    });
  });

  describe('Delivery Request Creation & Fulfillment', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should create delivery request', () => {
      cy.visit(`${baseUrl}/app/delivery/create`);
      cy.get('input[name="pickup_address"]').type('123 Main Street');
      cy.get('input[name="delivery_address"]').type('456 Oak Avenue');
      cy.get('input[name="package_description"]').type('Documents in envelope');
      cy.get('input[name="package_weight"]').type('0.5');
      cy.get('select[name="delivery_type"]').select('standard');
      cy.contains('button', 'Create Request').click();
      cy.wait(500);
      cy.contains('Delivery request created').should('be.visible');
    });

    it('Should create rush delivery request', () => {
      cy.visit(`${baseUrl}/app/delivery/create`);
      cy.get('input[name="pickup_address"]').type('123 Main Street');
      cy.get('input[name="delivery_address"]').type('456 Oak Avenue');
      cy.get('select[name="delivery_type"]').select('rush');
      cy.get('input[name="package_description"]').type('Urgent document');
      cy.contains('button', 'Create Request').click();
      cy.wait(500);
      cy.contains('Rush delivery created').should('be.visible');
    });

    it('Should create scheduled delivery request', () => {
      cy.visit(`${baseUrl}/app/delivery/create`);
      cy.get('input[name="pickup_address"]').type('123 Main Street');
      cy.get('input[name="delivery_address"]').type('456 Oak Avenue');
      cy.get('select[name="delivery_type"]').select('scheduled');
      cy.get('input[name="scheduled_date"]').type('2026-03-20');
      cy.get('input[name="scheduled_time"]').type('14:00');
      cy.contains('button', 'Create Request').click();
      cy.wait(500);
      cy.contains('Scheduled delivery created').should('be.visible');
    });

    it('Should track active delivery in real-time', () => {
      cy.visit(`${baseUrl}/app/delivery/track/123`);
      cy.contains('Delivery Status').should('be.visible');
      cy.get('[data-test="status"]').should('be.visible');
      cy.get('[data-test="courier-name"]').should('be.visible');
      cy.get('[data-test="courier-location"]').should('be.visible');
      cy.get('[data-test="eta"]').should('be.visible');
    });

    it('Should view delivery history', () => {
      cy.visit(`${baseUrl}/app/delivery/history`);
      cy.get('[data-test="delivery-item"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="delivery-status"]').each(($el) => {
        cy.wrap($el).should('be.oneOf', ['Delivered', 'Cancelled', 'Failed']);
      });
    });

    it('Should cancel delivery request', () => {
      cy.visit(`${baseUrl}/app/delivery`);
      cy.get('[data-test="active-delivery"]').first().within(() => {
        cy.contains('button', 'Cancel').click();
      });
      cy.wait(300);
      cy.get('textarea[name="reason"]').type('Schedule changed');
      cy.contains('button', 'Confirm Cancellation').click();
      cy.wait(500);
      cy.contains('Delivery cancelled').should('be.visible');
    });

    it('Should dispute delivery charge', () => {
      cy.visit(`${baseUrl}/app/delivery/history`);
      cy.get('[data-test="delivery-item"]').first().within(() => {
        cy.contains('button', 'Dispute').click();
      });
      cy.wait(300);
      cy.get('textarea[name="reason"]').type('Charge is incorrect');
      cy.contains('button', 'Submit').click();
      cy.wait(500);
      cy.contains('Dispute submitted').should('be.visible');
    });
  });

  describe('Courier Support & Issues', () => {
    it('Should report technical issue', () => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(courierEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);

      cy.visit(`${baseUrl}/app/support`);
      cy.contains('button', 'Report Issue').click();
      cy.wait(300);
      cy.get('select[name="category"]').select('technical');
      cy.get('textarea[name="description"]').type('App keeps crashing');
      cy.contains('button', 'Submit').click();
      cy.wait(500);
      cy.contains('Issue reported').should('be.visible');
    });

    it('Should contact support via chat', () => {
      cy.visit(`${baseUrl}/app/support/chat`);
      cy.get('[data-test="chat-available"]').should('be.visible');
      cy.get('input[name="message"]').type('How to update my profile?');
      cy.contains('button', 'Send').click();
      cy.wait(500);
      cy.contains('Message sent').should('be.visible');
    });

    it('Should view FAQ', () => {
      cy.visit(`${baseUrl}/app/courier/faq`);
      cy.contains('How to register').should('be.visible');
      cy.contains('How to earn money').should('be.visible');
    });

    it('Should appeal rating dispute', () => {
      cy.visit(`${baseUrl}/app/courier/profile`);
      cy.contains('button', 'Appeal').click();
      cy.wait(300);
      cy.get('textarea[name="reason"]').type('Rating is unfair');
      cy.contains('button', 'Submit Appeal').click();
      cy.wait(500);
      cy.contains('Appeal submitted').should('be.visible');
    });
  });

  describe('Advanced Courier Features', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(courierEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should enable/disable availability', () => {
      cy.visit(`${baseUrl}/app/courier/availability`);
      cy.get('[data-test="availability-toggle"]').click();
      cy.wait(300);
      cy.get('[data-test="status"]').should('contain', 'Offline');
      cy.get('[data-test="availability-toggle"]').click();
      cy.wait(300);
      cy.get('[data-test="status"]').should('contain', 'Online');
    });

    it('Should view and accept bulk deliveries', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/bulk`);
      cy.get('[data-test="bulk-offer"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="total-deliveries"]').should('contain', 'deliveries');
      cy.get('[data-test="total-earning"]').should('contain', '₽');
      cy.contains('button', 'Accept All').click();
      cy.wait(500);
      cy.contains('Bulk deliveries accepted').should('be.visible');
    });

    it('Should optimize delivery route', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/active`);
      cy.contains('button', 'Optimize Route').click();
      cy.wait(500);
      cy.get('[data-test="optimized-route"]').should('be.visible');
      cy.get('[data-test="estimated-savings"]').should('be.visible');
    });

    it('Should view temperature-controlled delivery options', () => {
      cy.visit(`${baseUrl}/app/delivery/create`);
      cy.get('select[name="delivery_type"]').select('temperature-controlled');
      cy.get('input[name="temperature_min"]').type('2');
      cy.get('input[name="temperature_max"]').type('8');
      cy.contains('button', 'Create Request').click();
      cy.wait(500);
      cy.contains('Delivery created').should('be.visible');
    });

    it('Should manage delivery zones and preferences', () => {
      cy.visit(`${baseUrl}/app/courier/preferences`);
      cy.get('input[name="preferred_distance_max"]').clear().type('10');
      cy.get('select[name="preferred_delivery_type"]').select('rush');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Preferences saved').should('be.visible');
    });

    it('Should view and accept referral deliveries', () => {
      cy.visit(`${baseUrl}/app/courier/referrals`);
      cy.contains('Referral Deliveries').should('be.visible');
      cy.get('[data-test="referral-item"]').should('have.length.greaterThan', 0);
    });

    it('Should track package with barcode/QR code', () => {
      cy.visit(`${baseUrl}/app/courier/scan`);
      cy.get('input[name="barcode"]').type('1234567890123');
      cy.contains('button', 'Scan').click();
      cy.wait(500);
      cy.contains('Package found').should('be.visible');
      cy.get('[data-test="package-details"]').should('be.visible');
    });

    it('Should view multi-stop delivery', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/active`);
      cy.get('[data-test="delivery-item"][data-multi-stop="true"]').first().click();
      cy.wait(300);
      cy.get('[data-test="stop-item"]').should('have.length', 3);
      cy.get('[data-test="stop-address"]').each(($el) => {
        cy.wrap($el).should('not.be.empty');
      });
    });

    it('Should handle COD (Cash on Delivery)', () => {
      cy.visit(`${baseUrl}/app/courier/deliveries/active`);
      cy.get('[data-test="delivery-item"][data-cod="true"]').first().click();
      cy.wait(300);
      cy.contains('Cash on Delivery').should('be.visible');
      cy.get('[data-test="cod-amount"]').should('contain', '₽');
      cy.contains('button', 'Confirm COD').click();
      cy.wait(500);
      cy.contains('COD confirmed').should('be.visible');
    });

    it('Should process pickup appointment scheduling', () => {
      cy.visit(`${baseUrl}/app/delivery/create`);
      cy.contains('button', 'Schedule Pickup').click();
      cy.wait(300);
      cy.get('input[name="pickup_date"]').type('2026-03-20');
      cy.get('input[name="pickup_time"]').type('14:00');
      cy.contains('button', 'Confirm').click();
      cy.wait(500);
      cy.contains('Pickup scheduled').should('be.visible');
    });
  });

  describe('Courier Analytics & Reporting', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(courierEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should view courier daily report', () => {
      cy.visit(`${baseUrl}/app/courier/reports/daily`);
      cy.get('[data-test="deliveries-count"]').should('be.visible');
      cy.get('[data-test="total-earnings"]').should('contain', '₽');
      cy.get('[data-test="successful-rate"]').should('contain', '%');
    });

    it('Should export delivery history', () => {
      cy.visit(`${baseUrl}/app/courier/history`);
      cy.contains('button', 'Export').click();
      cy.wait(300);
      cy.get('select[name="format"]').select('CSV');
      cy.contains('button', 'Download').click();
      cy.wait(500);
      cy.readFile('cypress/downloads/courier-history.csv').should('exist');
    });

    it('Should view performance trends', () => {
      cy.visit(`${baseUrl}/app/courier/performance-trends`);
      cy.get('[data-test="trend-chart"]').should('be.visible');
      cy.get('[data-test="rating-trend"]').should('be.visible');
      cy.get('[data-test="completion-trend"]').should('be.visible');
    });
  });

  describe('Courier Compliance & Insurance', () => {
    it('Should view insurance coverage', () => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(courierEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);

      cy.visit(`${baseUrl}/app/courier/insurance`);
      cy.contains('Insurance Coverage').should('be.visible');
      cy.get('[data-test="coverage-amount"]').should('contain', '₽');
      cy.get('[data-test="coverage-type"]').should('be.visible');
    });

    it('Should file insurance claim', () => {
      cy.visit(`${baseUrl}/app/courier/insurance/claim`);
      cy.get('select[name="reason"]').select('package_lost');
      cy.get('input[name="package_value"]').type('5000');
      cy.get('textarea[name="description"]').type('Package was lost during delivery');
      cy.contains('button', 'Submit Claim').click();
      cy.wait(500);
      cy.contains('Claim submitted').should('be.visible');
    });

    it('Should renew insurance policy', () => {
      cy.visit(`${baseUrl}/app/courier/insurance`);
      cy.contains('button', 'Renew').click();
      cy.wait(300);
      cy.contains('Insurance renewed').should('be.visible');
    });

    it('Should accept terms and conditions', () => {
      cy.visit(`${baseUrl}/app/courier/compliance`);
      cy.get('input[name="accept_terms"]').check();
      cy.get('input[name="accept_privacy"]').check();
      cy.contains('button', 'Accept').click();
      cy.wait(500);
      cy.contains('Terms accepted').should('be.visible');
    });
  });
});
