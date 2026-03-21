describe('Taxi Service Tests (Такси сервис)', () => {
  const baseUrl = 'http://localhost:8000';
  const businessEmail = 'taxi-business@test.com';
  const driverEmail = 'taxi-driver@test.com';
  const passengerEmail = 'taxi-passenger@test.com';
  const password = 'password';

  describe('Driver Registration & Profile', () => {
    it('Should register taxi driver', () => {
      cy.visit(`${baseUrl}/register/taxi-driver`);
      cy.get('input[name="email"]').type('new-driver@test.com');
      cy.get('input[name="password"]').type(password);
      cy.get('input[name="phone"]').type('79991234567');
      cy.get('input[name="vehicle_type"]').select('sedan');
      cy.contains('button', 'Register').click();
      cy.wait(500);
      cy.contains('Driver registered successfully').should('be.visible');
    });

    it('Should upload driver license and documents', () => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(driverEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);

      cy.visit(`${baseUrl}/app/taxi/documents`);
      cy.get('input[type="file"][name="license"]').selectFile('cypress/fixtures/driver-license.pdf', { force: true });
      cy.wait(300);
      cy.get('input[type="file"][name="passport"]').selectFile('cypress/fixtures/passport.pdf', { force: true });
      cy.wait(300);
      cy.contains('button', 'Upload').click();
      cy.wait(500);
      cy.contains('Documents uploaded').should('be.visible');
    });

    it('Should verify driver background check', () => {
      cy.visit(`${baseUrl}/app/taxi/verification`);
      cy.wait(2000);
      cy.get('[data-test="verification-status"]').should('contain', 'Verified');
      cy.contains('Background check passed').should('be.visible');
    });

    it('Should add vehicle information', () => {
      cy.visit(`${baseUrl}/app/taxi/vehicle`);
      cy.get('input[name="make"]').type('Toyota');
      cy.get('input[name="model"]').type('Camry');
      cy.get('input[name="year"]').type('2023');
      cy.get('input[name="license_plate"]').type('AB123CD');
      cy.get('input[name="vin"]').type('JTDKARFP0K3006519');
      cy.get('input[name="color"]').type('White');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Vehicle info saved').should('be.visible');
    });

    it('Should upload vehicle documents', () => {
      cy.visit(`${baseUrl}/app/taxi/vehicle-docs`);
      cy.get('input[type="file"][name="registration"]').selectFile('cypress/fixtures/vehicle-registration.pdf', { force: true });
      cy.wait(300);
      cy.get('input[type="file"][name="insurance"]').selectFile('cypress/fixtures/insurance-policy.pdf', { force: true });
      cy.wait(300);
      cy.get('input[type="file"][name="vehicle_photo"]').selectFile('cypress/fixtures/vehicle-photo.jpg', { force: true });
      cy.wait(300);
      cy.contains('button', 'Upload').click();
      cy.wait(500);
      cy.contains('Vehicle documents uploaded').should('be.visible');
    });

    it('Should set bank account for payouts', () => {
      cy.visit(`${baseUrl}/app/taxi/payment`);
      cy.get('input[name="bank_account"]').type('12345678901234567890');
      cy.get('input[name="bank_name"]').type('Sberbank');
      cy.get('input[name="routing_number"]').type('044525225');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Bank account saved').should('be.visible');
    });

    it('Should configure taxi preferences', () => {
      cy.visit(`${baseUrl}/app/taxi/preferences`);
      cy.get('select[name="car_class"]').select('economy');
      cy.get('input[name="accept_children"]').check();
      cy.get('input[name="accept_pets"]').check();
      cy.get('input[name="accept_shared_rides"]').check();
      cy.get('input[name="wifi_available"]').check();
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Preferences saved').should('be.visible');
    });

    it('Should set working hours', () => {
      cy.visit(`${baseUrl}/app/taxi/schedule`);
      cy.get('input[name="monday_start"]').type('08:00');
      cy.get('input[name="monday_end"]').type('20:00');
      cy.get('input[name="tuesday_start"]').type('08:00');
      cy.get('input[name="tuesday_end"]').type('20:00');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Schedule saved').should('be.visible');
    });

    it('Should view driver profile', () => {
      cy.visit(`${baseUrl}/app/taxi/profile`);
      cy.get('[data-test="name"]').should('not.be.empty');
      cy.get('[data-test="rating"]').should('be.visible');
      cy.get('[data-test="completed-rides"]').should('be.visible');
      cy.get('[data-test="vehicle-info"]').should('be.visible');
    });
  });

  describe('Ride Management - Driver Side', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(driverEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should toggle driver online status', () => {
      cy.visit(`${baseUrl}/app/taxi/driver`);
      cy.get('[data-test="online-toggle"]').click();
      cy.wait(300);
      cy.get('[data-test="status"]').should('contain', 'Online');
      cy.get('[data-test="online-toggle"]').click();
      cy.wait(300);
      cy.get('[data-test="status"]').should('contain', 'Offline');
    });

    it('Should view available ride requests', () => {
      cy.visit(`${baseUrl}/app/taxi/rides/available`);
      cy.get('[data-test="ride-request"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="pickup-location"]').should('be.visible');
      cy.get('[data-test="destination"]').should('be.visible');
      cy.get('[data-test="estimated-fare"]').should('contain', '₽');
      cy.get('[data-test="distance"]').should('contain', 'km');
      cy.get('[data-test="passenger-rating"]').should('be.visible');
    });

    it('Should accept ride request', () => {
      cy.visit(`${baseUrl}/app/taxi/rides/available`);
      cy.get('[data-test="ride-request"]').first().within(() => {
        cy.contains('button', 'Accept').click();
      });
      cy.wait(500);
      cy.contains('Ride accepted').should('be.visible');
    });

    it('Should reject ride request', () => {
      cy.visit(`${baseUrl}/app/taxi/rides/available`);
      cy.get('[data-test="ride-request"]').first().within(() => {
        cy.contains('button', 'Reject').click();
      });
      cy.wait(300);
      cy.get('textarea[name="reason"]').type('Wrong direction');
      cy.contains('button', 'Confirm').click();
      cy.wait(500);
      cy.contains('Ride rejected').should('be.visible');
    });

    it('Should navigate to pickup location', () => {
      cy.visit(`${baseUrl}/app/taxi/active-ride/1`);
      cy.contains('button', 'Start Navigation').click();
      cy.wait(300);
      cy.get('[data-test="map"]').should('be.visible');
      cy.get('[data-test="navigation-active"]').should('have.attr', 'data-active', 'true');
      cy.get('[data-test="eta-pickup"]').should('be.visible');
    });

    it('Should arrive at pickup location and notify passenger', () => {
      cy.visit(`${baseUrl}/app/taxi/active-ride/1`);
      cy.contains('button', 'Arrived at Pickup').click();
      cy.wait(500);
      cy.contains('Passenger notified').should('be.visible');
      cy.get('[data-test="status"]').should('contain', 'Waiting for passenger');
    });

    it('Should start ride once passenger boards', () => {
      cy.visit(`${baseUrl}/app/taxi/active-ride/1`);
      cy.contains('button', 'Passenger Boarded').click();
      cy.wait(500);
      cy.contains('Ride started').should('be.visible');
      cy.get('[data-test="status"]').should('contain', 'In Transit');
    });

    it('Should navigate to destination', () => {
      cy.visit(`${baseUrl}/app/taxi/active-ride/1`);
      cy.contains('button', 'Navigate to Destination').click();
      cy.wait(300);
      cy.get('[data-test="eta-destination"]').should('be.visible');
    });

    it('Should end ride at destination', () => {
      cy.visit(`${baseUrl}/app/taxi/active-ride/1`);
      cy.get('[data-test="status"]').should('contain', 'In Transit');
      cy.contains('button', 'End Ride').click();
      cy.wait(500);
      cy.contains('Ride completed').should('be.visible');
    });

    it('Should confirm ride fare and payment', () => {
      cy.visit(`${baseUrl}/app/taxi/active-ride/1`);
      cy.contains('button', 'End Ride').click();
      cy.wait(500);
      cy.get('[data-test="fare-details"]').should('be.visible');
      cy.get('[data-test="base-fare"]').should('contain', '₽');
      cy.get('[data-test="distance-charge"]').should('contain', '₽');
      cy.get('[data-test="total-fare"]').should('contain', '₽');
      cy.contains('button', 'Confirm').click();
      cy.wait(500);
      cy.contains('Payment processed').should('be.visible');
    });

    it('Should handle ride cancellation by driver', () => {
      cy.visit(`${baseUrl}/app/taxi/active-ride/1`);
      cy.contains('button', 'Cancel Ride').click();
      cy.wait(300);
      cy.get('select[name="reason"]').select('mechanical_issue');
      cy.get('textarea[name="details"]').type('Engine problem');
      cy.contains('button', 'Confirm').click();
      cy.wait(500);
      cy.contains('Ride cancelled').should('be.visible');
    });

    it('Should handle emergency SOS', () => {
      cy.visit(`${baseUrl}/app/taxi/active-ride/1`);
      cy.contains('button', 'Emergency').click();
      cy.wait(300);
      cy.contains('Emergency services notified').should('be.visible');
      cy.get('[data-test="sos-active"]').should('have.attr', 'data-active', 'true');
    });

    it('Should report passenger issue', () => {
      cy.visit(`${baseUrl}/app/taxi/active-ride/1`);
      cy.contains('button', 'Report Issue').click();
      cy.wait(300);
      cy.get('select[name="issue_type"]').select('passenger_behavior');
      cy.get('textarea[name="description"]').type('Rude passenger');
      cy.contains('button', 'Submit').click();
      cy.wait(500);
      cy.contains('Issue reported').should('be.visible');
    });

    it('Should view and manage ride history', () => {
      cy.visit(`${baseUrl}/app/taxi/rides/history`);
      cy.get('[data-test="ride-item"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="ride-date"]').should('be.visible');
      cy.get('[data-test="ride-distance"]').should('contain', 'km');
      cy.get('[data-test="ride-fare"]').should('contain', '₽');
      cy.get('[data-test="passenger-rating"]').should('be.visible');
    });
  });

  describe('Ride Management - Passenger Side', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(passengerEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should request taxi ride', () => {
      cy.visit(`${baseUrl}/app/taxi`);
      cy.get('input[name="pickup"]').type('123 Main Street');
      cy.wait(500);
      cy.get('input[name="destination"]').type('456 Oak Avenue');
      cy.wait(500);
      cy.get('select[name="car_class"]').select('economy');
      cy.contains('button', 'Request Ride').click();
      cy.wait(500);
      cy.contains('Searching for driver').should('be.visible');
    });

    it('Should schedule future ride', () => {
      cy.visit(`${baseUrl}/app/taxi/schedule`);
      cy.get('input[name="pickup"]').type('123 Main Street');
      cy.get('input[name="destination"]').type('456 Oak Avenue');
      cy.get('input[name="date"]').type('2026-03-20');
      cy.get('input[name="time"]').type('14:00');
      cy.contains('button', 'Schedule').click();
      cy.wait(500);
      cy.contains('Ride scheduled').should('be.visible');
    });

    it('Should track driver in real-time', () => {
      cy.visit(`${baseUrl}/app/taxi/ride/123`);
      cy.contains('Driver Arriving').should('be.visible');
      cy.get('[data-test="driver-name"]').should('not.be.empty');
      cy.get('[data-test="driver-rating"]').should('be.visible');
      cy.get('[data-test="vehicle-info"]').should('be.visible');
      cy.get('[data-test="map"]').should('be.visible');
      cy.get('[data-test="driver-location"]').should('be.visible');
      cy.get('[data-test="eta"]').should('be.visible');
    });

    it('Should rate driver after ride', () => {
      cy.visit(`${baseUrl}/app/taxi/rate-driver/123`);
      cy.get('[data-test="star-5"]').click();
      cy.wait(300);
      cy.get('textarea[name="review"]').type('Great driver, very professional');
      cy.contains('button', 'Submit').click();
      cy.wait(500);
      cy.contains('Rating submitted').should('be.visible');
    });

    it('Should share ride with another passenger', () => {
      cy.visit(`${baseUrl}/app/taxi`);
      cy.get('input[name="pickup"]').type('123 Main Street');
      cy.get('input[name="destination"]').type('456 Oak Avenue');
      cy.get('input[name="share_ride"]').check();
      cy.contains('button', 'Request Ride').click();
      cy.wait(500);
      cy.contains('Shared ride requested').should('be.visible');
    });

    it('Should add stops to ride', () => {
      cy.visit(`${baseUrl}/app/taxi/ride/123`);
      cy.contains('button', 'Add Stop').click();
      cy.wait(300);
      cy.get('input[name="stop_address"]').type('789 Pine Road');
      cy.contains('button', 'Confirm').click();
      cy.wait(500);
      cy.contains('Stop added').should('be.visible');
    });

    it('Should cancel ride as passenger', () => {
      cy.visit(`${baseUrl}/app/taxi/ride/123`);
      cy.contains('button', 'Cancel').click();
      cy.wait(300);
      cy.get('textarea[name="reason"]').type('Changed plans');
      cy.contains('button', 'Confirm Cancellation').click();
      cy.wait(500);
      cy.contains('Ride cancelled').should('be.visible');
    });

    it('Should report driver issue', () => {
      cy.visit(`${baseUrl}/app/taxi/ride/123`);
      cy.contains('button', 'Report').click();
      cy.wait(300);
      cy.get('select[name="issue_type"]').select('driver_behavior');
      cy.get('textarea[name="description"]').type('Unsafe driving');
      cy.contains('button', 'Submit').click();
      cy.wait(500);
      cy.contains('Report submitted').should('be.visible');
    });

    it('Should handle emergency during ride', () => {
      cy.visit(`${baseUrl}/app/taxi/ride/123`);
      cy.contains('button', 'Emergency').click();
      cy.wait(500);
      cy.contains('Emergency services contacted').should('be.visible');
    });

    it('Should split ride fare with other passengers', () => {
      cy.visit(`${baseUrl}/app/taxi/ride/123`);
      cy.get('[data-test="shared-passengers"]').should('have.length', 2);
      cy.get('[data-test="your-share"]').should('contain', '₽');
    });
  });

  describe('Taxi Fleet Management', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should view fleet management dashboard', () => {
      cy.visit(`${baseUrl}/app/taxi/fleet`);
      cy.contains('Fleet Management').should('be.visible');
      cy.get('[data-test="total-drivers"]').should('be.visible');
      cy.get('[data-test="active-drivers"]').should('be.visible');
      cy.get('[data-test="total-vehicles"]').should('be.visible');
    });

    it('Should add driver to fleet', () => {
      cy.visit(`${baseUrl}/app/taxi/fleet/drivers`);
      cy.contains('button', 'Add Driver').click();
      cy.wait(300);
      cy.get('input[name="email"]').type('driver@test.com');
      cy.get('select[name="car_class"]').select('economy');
      cy.contains('button', 'Add').click();
      cy.wait(500);
      cy.contains('Driver added').should('be.visible');
    });

    it('Should manage vehicle maintenance schedule', () => {
      cy.visit(`${baseUrl}/app/taxi/fleet/maintenance`);
      cy.get('[data-test="vehicle-item"]').should('have.length.greaterThan', 0);
      cy.contains('button', 'Schedule Maintenance').click();
      cy.wait(300);
      cy.get('input[name="date"]').type('2026-03-25');
      cy.get('textarea[name="notes"]').type('Oil change and filter');
      cy.contains('button', 'Schedule').click();
      cy.wait(500);
      cy.contains('Maintenance scheduled').should('be.visible');
    });

    it('Should track driver performance metrics', () => {
      cy.visit(`${baseUrl}/app/taxi/fleet/drivers/1`);
      cy.get('[data-test="total-rides"]').should('be.visible');
      cy.get('[data-test="average-rating"]').should('be.visible');
      cy.get('[data-test="completion-rate"]').should('contain', '%');
      cy.get('[data-test="cancellation-rate"]').should('contain', '%');
    });

    it('Should manage surge pricing', () => {
      cy.visit(`${baseUrl}/app/taxi/surge-pricing`);
      cy.get('select[name="location"]').select('downtown');
      cy.get('input[name="multiplier"]').type('1.5');
      cy.get('input[name="start_time"]').type('17:00');
      cy.get('input[name="end_time"]').type('20:00');
      cy.contains('button', 'Apply Surge').click();
      cy.wait(500);
      cy.contains('Surge pricing applied').should('be.visible');
    });

    it('Should handle driver ratings and reviews', () => {
      cy.visit(`${baseUrl}/app/taxi/fleet/drivers/1/reviews`);
      cy.get('[data-test="review-item"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="average-rating"]').should('be.visible');
    });
  });

  describe('Driver Earnings & Payouts', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(driverEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should view earnings dashboard', () => {
      cy.visit(`${baseUrl}/app/taxi/earnings`);
      cy.contains('Today Earnings').should('be.visible');
      cy.get('[data-test="today-amount"]').should('contain', '₽');
      cy.get('[data-test="today-rides"]').should('be.visible');
      cy.get('[data-test="weekly-amount"]').should('contain', '₽');
      cy.get('[data-test="monthly-amount"]').should('contain', '₽');
    });

    it('Should view ride-by-ride breakdown', () => {
      cy.visit(`${baseUrl}/app/taxi/earnings/breakdown`);
      cy.get('[data-test="ride-entry"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="base-fare"]').should('contain', '₽');
      cy.get('[data-test="distance-charge"]').should('contain', '₽');
      cy.get('[data-test="time-charge"]').should('contain', '₽');
      cy.get('[data-test="total"]').should('contain', '₽');
    });

    it('Should request payout', () => {
      cy.visit(`${baseUrl}/app/taxi/earnings`);
      cy.get('[data-test="available-balance"]').should('be.visible');
      cy.contains('button', 'Request Payout').click();
      cy.wait(300);
      cy.get('input[name="amount"]').type('10000');
      cy.contains('button', 'Confirm').click();
      cy.wait(500);
      cy.contains('Payout requested').should('be.visible');
    });

    it('Should track payout status', () => {
      cy.visit(`${baseUrl}/app/taxi/payouts`);
      cy.get('[data-test="payout-item"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="payout-status"]').each(($el) => {
        cy.wrap($el).should('be.oneOf', ['Pending', 'Processing', 'Completed']);
      });
    });

    it('Should view commission breakdown', () => {
      cy.visit(`${baseUrl}/app/taxi/earnings/commission`);
      cy.contains('Commission Details').should('be.visible');
      cy.get('[data-test="platform-commission"]').should('contain', '%');
      cy.get('[data-test="insurance-fee"]').should('contain', '%');
      cy.get('[data-test="total-deduction"]').should('contain', '%');
    });

    it('Should view bonuses and incentives', () => {
      cy.visit(`${baseUrl}/app/taxi/bonuses`);
      cy.contains('Active Bonuses').should('be.visible');
      cy.get('[data-test="bonus-item"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="bonus-condition"]').should('be.visible');
      cy.get('[data-test="bonus-amount"]').should('contain', '₽');
    });
  });

  describe('Taxi Safety & Support', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(driverEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should access safety features', () => {
      cy.visit(`${baseUrl}/app/taxi/safety`);
      cy.contains('Safety Features').should('be.visible');
      cy.get('input[name="panic_button"]').should('be.visible');
      cy.contains('Share Trip').should('be.visible');
    });

    it('Should report safety incident', () => {
      cy.visit(`${baseUrl}/app/taxi/safety`);
      cy.contains('button', 'Report Incident').click();
      cy.wait(300);
      cy.get('select[name="incident_type"]').select('passenger_harassment');
      cy.get('textarea[name="description"]').type('Passenger made inappropriate comments');
      cy.contains('button', 'Submit').click();
      cy.wait(500);
      cy.contains('Incident reported').should('be.visible');
    });

    it('Should contact support', () => {
      cy.visit(`${baseUrl}/app/taxi/support`);
      cy.contains('button', 'Start Chat').click();
      cy.wait(500);
      cy.get('[data-test="chat-active"]').should('be.visible');
      cy.get('input[name="message"]').type('How do I change my car class?');
      cy.contains('button', 'Send').click();
      cy.wait(500);
      cy.contains('Message sent').should('be.visible');
    });

    it('Should view driver insurance coverage', () => {
      cy.visit(`${baseUrl}/app/taxi/insurance`);
      cy.contains('Insurance Coverage').should('be.visible');
      cy.get('[data-test="coverage-amount"]').should('contain', '₽');
    });

    it('Should file insurance claim', () => {
      cy.visit(`${baseUrl}/app/taxi/insurance/claim`);
      cy.get('select[name="claim_type"]').select('accident');
      cy.get('textarea[name="description"]').type('Minor collision');
      cy.get('input[type="file"]').selectFile('cypress/fixtures/accident-photo.jpg', { force: true });
      cy.wait(300);
      cy.contains('button', 'Submit Claim').click();
      cy.wait(500);
      cy.contains('Claim submitted').should('be.visible');
    });
  });

  describe('Advanced Taxi Features', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(driverEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should view heatmap of ride demand', () => {
      cy.visit(`${baseUrl}/app/taxi/demand-heatmap`);
      cy.contains('Demand Heatmap').should('be.visible');
      cy.get('[data-test="heatmap"]').should('be.visible');
      cy.get('[data-test="high-demand-zone"]').should('have.css', 'background-color').and('include', 'red');
    });

    it('Should get navigation suggestions', () => {
      cy.visit(`${baseUrl}/app/taxi/active-ride/1`);
      cy.contains('Suggested Routes').should('be.visible');
      cy.get('[data-test="route-option"]').should('have.length', 3);
      cy.get('[data-test="eta"]').should('be.visible');
    });

    it('Should handle ride pooling (multiple passengers)', () => {
      cy.visit(`${baseUrl}/app/taxi/ride/123`);
      cy.get('[data-test="passengers"]').should('have.length', 2);
      cy.contains('Shared Ride').should('be.visible');
      cy.get('[data-test="stop-sequence"]').should('have.length.greaterThan', 1);
    });

    it('Should view accessibility options', () => {
      cy.visit(`${baseUrl}/app/taxi/accessibility`);
      cy.get('input[name="wheelchair_accessible"]').check();
      cy.get('input[name="service_dog_friendly"]').check();
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Accessibility settings saved').should('be.visible');
    });

    it('Should manage preferred passenger list', () => {
      cy.visit(`${baseUrl}/app/taxi/preferences`);
      cy.contains('Preferred Passengers').should('be.visible');
      cy.get('[data-test="passenger-item"]').should('have.length.greaterThan', 0);
    });

    it('Should set ride quality preferences', () => {
      cy.visit(`${baseUrl}/app/taxi/ride-quality`);
      cy.get('select[name="music_preference"]').select('none');
      cy.get('input[name="temperature_preference"]').type('22');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Preferences saved').should('be.visible');
    });

    it('Should view AI-powered ride suggestions', () => {
      cy.visit(`${baseUrl}/app/taxi/suggestions`);
      cy.contains('Personalized Suggestions').should('be.visible');
      cy.get('[data-test="suggestion-item"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="estimated-earnings"]').should('contain', '₽');
    });

    it('Should track and improve driver score', () => {
      cy.visit(`${baseUrl}/app/taxi/driver-score`);
      cy.contains('Driver Score').should('be.visible');
      cy.get('[data-test="score-value"]').should('be.visible');
      cy.get('[data-test="score-breakdown"]').should('be.visible');
    });

    it('Should manage vehicle maintenance reminders', () => {
      cy.visit(`${baseUrl}/app/taxi/vehicle-maintenance`);
      cy.get('[data-test="maintenance-reminder"]').should('have.length.greaterThan', 0);
      cy.contains('Oil Change Due').should('be.visible');
    });

    it('Should view traffic and routing optimization', () => {
      cy.visit(`${baseUrl}/app/taxi/active-ride/1`);
      cy.contains('Traffic Conditions').should('be.visible');
      cy.get('[data-test="traffic-light"]').should('be.visible');
      cy.contains('button', 'Optimize Route').click();
      cy.wait(500);
      cy.contains('Route optimized').should('be.visible');
    });
  });

  describe('Analytics & Reporting', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(driverEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should view performance analytics', () => {
      cy.visit(`${baseUrl}/app/taxi/analytics`);
      cy.contains('Performance Metrics').should('be.visible');
      cy.get('[data-test="total-distance"]').should('contain', 'km');
      cy.get('[data-test="total-time"]').should('contain', 'hours');
      cy.get('[data-test="average-rating"]').should('be.visible');
    });

    it('Should export ride history', () => {
      cy.visit(`${baseUrl}/app/taxi/rides/history`);
      cy.contains('button', 'Export').click();
      cy.wait(300);
      cy.get('select[name="format"]').select('CSV');
      cy.contains('button', 'Download').click();
      cy.wait(500);
      cy.readFile('cypress/downloads/taxi-history.csv').should('exist');
    });

    it('Should view monthly reports', () => {
      cy.visit(`${baseUrl}/app/taxi/reports`);
      cy.get('[data-test="monthly-report"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="total-rides"]').should('contain', 'rides');
      cy.get('[data-test="total-earnings"]').should('contain', '₽');
    });

    it('Should compare performance over time', () => {
      cy.visit(`${baseUrl}/app/taxi/performance-comparison`);
      cy.get('[data-test="trend-chart"]').should('be.visible');
      cy.get('[data-test="earning-trend"]').should('be.visible');
    });
  });

  describe('Passenger Features', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(passengerEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should save favorite locations', () => {
      cy.visit(`${baseUrl}/app/taxi`);
      cy.contains('button', 'Save Location').click();
      cy.wait(300);
      cy.get('input[name="label"]').type('Home');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Location saved').should('be.visible');
    });

    it('Should view saved payment methods', () => {
      cy.visit(`${baseUrl}/app/taxi/payment-methods`);
      cy.get('[data-test="payment-method"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="default-method"]').should('be.visible');
    });

    it('Should add credit card', () => {
      cy.visit(`${baseUrl}/app/taxi/payment-methods`);
      cy.contains('button', 'Add Card').click();
      cy.wait(300);
      cy.get('input[name="card_number"]').type('4111111111111111');
      cy.get('input[name="exp_month"]').type('12');
      cy.get('input[name="exp_year"]').type('2025');
      cy.get('input[name="cvc"]').type('123');
      cy.contains('button', 'Add').click();
      cy.wait(500);
      cy.contains('Card added').should('be.visible');
    });

    it('Should set ride preferences', () => {
      cy.visit(`${baseUrl}/app/taxi/preferences`);
      cy.get('select[name="preferred_car_class"]').select('business');
      cy.get('input[name="prefer_female_driver"]').check();
      cy.get('input[name="require_ac"]').check();
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Preferences saved').should('be.visible');
    });

    it('Should view ride receipts', () => {
      cy.visit(`${baseUrl}/app/taxi/receipts`);
      cy.get('[data-test="receipt-item"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="fare-breakdown"]').should('be.visible');
    });

    it('Should download ride invoice', () => {
      cy.visit(`${baseUrl}/app/taxi/receipts/123`);
      cy.contains('button', 'Download Invoice').click();
      cy.wait(500);
      cy.readFile('cypress/downloads/receipt.pdf').should('exist');
    });

    it('Should contact driver before ride', () => {
      cy.visit(`${baseUrl}/app/taxi/ride/123`);
      cy.contains('button', 'Contact Driver').click();
      cy.wait(300);
      cy.get('textarea[name="message"]').type('I am at the main entrance');
      cy.contains('button', 'Send').click();
      cy.wait(500);
      cy.contains('Message sent').should('be.visible');
    });

    it('Should provide feedback after ride', () => {
      cy.visit(`${baseUrl}/app/taxi/feedback/123`);
      cy.get('select[name="cleanliness"]').select('5');
      cy.get('select[name="driver_behavior"]').select('5');
      cy.get('textarea[name="comments"]').type('Excellent service!');
      cy.contains('button', 'Submit').click();
      cy.wait(500);
      cy.contains('Feedback submitted').should('be.visible');
    });
  });
});
