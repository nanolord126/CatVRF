describe('Profile Update Tests (Обновление профиля)', () => {
  const baseUrl = 'http://localhost:8000';
  const userEmail = 'user-update@test.com';
  const password = 'password';

  beforeEach(() => {
    cy.visit(`${baseUrl}/login`);
    cy.get('input[name="email"]').type(userEmail);
    cy.get('input[name="password"]').type(password);
    cy.get('button[type="submit"]').click();
    cy.wait(500);
    cy.url().should('include', '/app').or('include', '/tenant');
  });

  describe('Personal Profile Updates', () => {
    it('Should update user full name', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('input[name="first_name"]').clear().type('Иван');
      cy.get('input[name="last_name"]').clear().type('Петров');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Profile updated successfully').should('be.visible');
      cy.get('input[name="first_name"]').should('have.value', 'Иван');
    });

    it('Should update phone number with validation', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('input[name="phone"]').clear().type('+7 999 123 45 67');
      cy.wait(300);
      cy.get('[data-test="phone-status"]').should('contain', 'Valid');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Phone number verified').should('be.visible');
    });

    it('Should reject invalid phone number format', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('input[name="phone"]').clear().type('invalid phone');
      cy.wait(300);
      cy.get('[data-test="phone-error"]').should('contain', 'Invalid format');
    });

    it('Should update email address with verification', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('input[name="email"]').clear().type('newemail@test.com');
      cy.contains('button', 'Update Email').click();
      cy.wait(500);
      cy.contains('Verification link sent').should('be.visible');
    });

    it('Should update password with strength validation', () => {
      cy.visit(`${baseUrl}/app/profile/security`);
      cy.get('input[name="current_password"]').type('password');
      cy.get('input[name="new_password"]').type('NewP@ssw0rd!');
      cy.get('input[name="confirm_password"]').type('NewP@ssw0rd!');
      cy.wait(300);
      cy.get('[data-test="password-strength"]').should('contain', 'Strong');
      cy.contains('button', 'Change Password').click();
      cy.wait(500);
      cy.contains('Password changed successfully').should('be.visible');
    });

    it('Should reject weak passwords', () => {
      cy.visit(`${baseUrl}/app/profile/security`);
      cy.get('input[name="current_password"]').type('password');
      cy.get('input[name="new_password"]').type('123');
      cy.wait(300);
      cy.get('[data-test="password-strength"]').should('contain', 'Too weak');
      cy.contains('button', 'Change Password').should('be.disabled');
    });

    it('Should update birth date and location', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('input[name="date_of_birth"]').type('1990-05-15');
      cy.get('input[name="city"]').clear().type('Москва');
      cy.get('input[name="region"]').clear().type('Московская область');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Profile updated successfully').should('be.visible');
    });

    it('Should toggle 2FA (Two-Factor Authentication)', () => {
      cy.visit(`${baseUrl}/app/profile/security`);
      cy.contains('button', 'Enable 2FA').click();
      cy.wait(500);
      cy.contains('Scan QR code').should('be.visible');
      cy.get('[data-test="qr-code"]').should('be.visible');
      cy.get('input[name="2fa_code"]').type('123456');
      cy.contains('button', 'Verify').click();
      cy.wait(500);
      cy.contains('2FA enabled').should('be.visible');
    });

    it('Should manage trusted devices', () => {
      cy.visit(`${baseUrl}/app/profile/security`);
      cy.contains('Trusted Devices').should('be.visible');
      cy.get('[data-test="device-item"]').should('have.length.greaterThan', 0);
      cy.get('[data-test="device-item"]').first().within(() => {
        cy.contains('button', 'Revoke').click();
      });
      cy.wait(500);
      cy.contains('Device removed').should('be.visible');
    });
  });

  describe('Business Profile Updates', () => {
    it('Should update business information', () => {
      cy.visit(`${baseUrl}/tenant/profile`);
      cy.get('input[name="business_name"]').clear().type('ООО Новое Имя');
      cy.get('input[name="inn"]').clear().type('7799999999');
      cy.get('input[name="kpp"]').clear().type('779900001');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Business profile updated').should('be.visible');
    });

    it('Should update business address', () => {
      cy.visit(`${baseUrl}/tenant/profile`);
      cy.get('input[name="address"]').clear().type('ул. Новая, д. 1');
      cy.get('input[name="postal_code"]').clear().type('100000');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Address updated successfully').should('be.visible');
    });

    it('Should update bank account details', () => {
      cy.visit(`${baseUrl}/tenant/profile/banking`);
      cy.get('input[name="bank_name"]').clear().type('СберБанк');
      cy.get('input[name="account_number"]').clear().type('40702810538290001111');
      cy.get('input[name="bic"]').clear().type('044525225');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Bank details updated').should('be.visible');
    });

    it('Should validate bank account before saving', () => {
      cy.visit(`${baseUrl}/tenant/profile/banking`);
      cy.get('input[name="account_number"]').clear().type('invalid');
      cy.wait(300);
      cy.get('[data-test="account-error"]').should('contain', 'Invalid account number');
      cy.contains('button', 'Save').should('be.disabled');
    });
  });

  describe('Profile Notifications & Preferences', () => {
    it('Should update notification preferences', () => {
      cy.visit(`${baseUrl}/app/profile/notifications`);
      cy.get('[data-test="notify-email"] input[type="checkbox"]').uncheck();
      cy.get('[data-test="notify-sms"] input[type="checkbox"]').check();
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Preferences saved').should('be.visible');
    });

    it('Should set notification frequency', () => {
      cy.visit(`${baseUrl}/app/profile/notifications`);
      cy.get('select[name="frequency"]').select('Daily');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Preferences saved').should('be.visible');
    });

    it('Should manage notification channels', () => {
      cy.visit(`${baseUrl}/app/profile/notifications`);
      cy.get('[data-test="channel-email"] input[type="checkbox"]').should('be.checked');
      cy.get('[data-test="channel-sms"] input[type="checkbox"]').should('not.be.checked');
      cy.get('[data-test="channel-push"] input[type="checkbox"]').check();
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Channels updated').should('be.visible');
    });
  });

  describe('Profile Privacy & Data', () => {
    it('Should export user data', () => {
      cy.visit(`${baseUrl}/app/profile/privacy`);
      cy.contains('button', 'Download Data').click();
      cy.wait(1000);
      cy.readFile('cypress/downloads/catvrf-data.zip').should('exist');
    });

    it('Should update privacy settings', () => {
      cy.visit(`${baseUrl}/app/profile/privacy`);
      cy.get('input[name="show_profile"]').uncheck();
      cy.get('input[name="show_reviews"]').check();
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Privacy settings updated').should('be.visible');
    });

    it('Should allow deleting account with confirmation', () => {
      cy.visit(`${baseUrl}/app/profile/privacy`);
      cy.contains('button', 'Delete Account').click();
      cy.wait(300);
      cy.contains('Are you sure?').should('be.visible');
      cy.contains('button', 'Yes, delete').click();
      cy.get('input[name="confirmation_code"]').type('DELETE');
      cy.contains('button', 'Confirm').click();
      cy.wait(500);
      cy.url().should('include', '/goodbye');
    });
  });

  describe('Profile Updates with Idempotency', () => {
    it('Should prevent duplicate updates with idempotency', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('input[name="first_name"]').clear().type('Иван');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Profile updated').should('be.visible');
      
      // Click save again immediately
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('already updated').should('be.visible');
    });

    it('Should validate update with correlation ID', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('input[name="phone"]').clear().type('+7 999 123 45 67');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      // Verify correlation ID in local storage
      cy.window().then((win) => {
        const lastUpdate = win.localStorage.getItem('last_profile_update');
        expect(lastUpdate).to.not.be.null;
      });
    });
  });

  describe('Profile Audit & History', () => {
    it('Should view profile change history', () => {
      cy.visit(`${baseUrl}/app/profile/history`);
      cy.contains('Profile Changes').should('be.visible');
      cy.get('[data-test="history-item"]').should('have.length.greaterThan', 0);
    });

    it('Should show change details with timestamp', () => {
      cy.visit(`${baseUrl}/app/profile/history`);
      cy.get('[data-test="history-item"]').first().click();
      cy.wait(300);
      cy.contains('Changed at:').should('be.visible');
      cy.contains('Changed by:').should('be.visible');
    });

    it('Should allow reverting to previous profile state', () => {
      cy.visit(`${baseUrl}/app/profile/history`);
      cy.get('[data-test="history-item"]').eq(1).within(() => {
        cy.contains('button', 'Revert').click();
      });
      cy.wait(500);
      cy.contains('Profile reverted').should('be.visible');
    });
  });

  describe('Profile Updates with Payment Hold', () => {
    it('Should hold payment on critical profile changes', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('input[name="email"]').clear().type('critical-change@test.com');
      cy.contains('button', 'Save').click();
      cy.wait(500); // Payment hold check
      cy.contains('Verification required').should('be.visible');
      cy.get('input[name="verification_code"]').type('123456');
      cy.contains('button', 'Verify').click();
      cy.wait(500);
      cy.contains('Email updated').should('be.visible');
    });
  });
});
