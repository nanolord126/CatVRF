describe('Cashback & Rewards Programs (Кешбек и награды)', () => {
  const baseUrl = 'http://localhost:8000';
  const businessEmail = 'cashback-business@test.com';
  const customerEmail = 'cashback-customer@test.com';
  const password = 'password';

  describe('Cashback Program Setup', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should create tiered cashback program', () => {
      cy.visit(`${baseUrl}/tenant/rewards/cashback`);
      cy.contains('button', 'Create Program').click();
      cy.wait(300);
      cy.get('input[name="name"]').type('Loyalty Cashback');
      cy.get('input[name="percentage"]').type('5');
      cy.get('select[name="tier_1"]').select('Bronze - 2%');
      cy.get('select[name="tier_2"]').select('Silver - 5%');
      cy.get('select[name="tier_3"]').select('Gold - 10%');
      cy.contains('button', 'Create').click();
      cy.wait(500);
      cy.contains('Cashback program created').should('be.visible');
    });

    it('Should set minimum purchase threshold for cashback', () => {
      cy.visit(`${baseUrl}/tenant/rewards/cashback/1/edit`);
      cy.get('input[name="min_purchase"]').clear().type('1000');
      cy.get('input[name="max_cashback_per_transaction"]').clear().type('500');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Settings updated').should('be.visible');
    });

    it('Should exclude products from cashback', () => {
      cy.visit(`${baseUrl}/tenant/rewards/cashback/1/exclusions`);
      cy.contains('button', 'Add Exclusion').click();
      cy.wait(300);
      cy.get('select[name="product"]').select('Premium Product');
      cy.contains('button', 'Add').click();
      cy.wait(500);
      cy.contains('Exclusion added').should('be.visible');
    });

    it('Should set cashback expiration policy', () => {
      cy.visit(`${baseUrl}/tenant/rewards/cashback/1/settings`);
      cy.get('input[name="expiration_days"]').clear().type('365');
      cy.get('input[name="rollover_percentage"]').clear().type('50');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Expiration policy updated').should('be.visible');
    });
  });

  describe('Cashback Calculation & Tracking', () => {
    it('Should calculate cashback on customer purchase', () => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(customerEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
      
      cy.visit(`${baseUrl}/app/checkout`);
      cy.get('[data-test="product-item"]').first().click();
      cy.wait(300);
      cy.contains('button', 'Add to Cart').click();
      cy.wait(300);
      cy.get('input[name="quantity"]').clear().type('2');
      cy.contains('button', 'Checkout').click();
      cy.wait(500);
      
      // Verify cashback calculation
      cy.get('[data-test="cashback-amount"]').should('be.visible');
      cy.get('[data-test="cashback-amount"]').then(($el) => {
        const cashback = parseFloat($el.text());
        expect(cashback).to.be.greaterThan(0);
      });
    });

    it('Should apply cashback to wallet after purchase', () => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(customerEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
      
      const initialBalance = cy.get('[data-test="wallet-balance"]').text();
      
      cy.visit(`${baseUrl}/app/checkout`);
      cy.contains('button', 'Pay').click();
      cy.wait(1000);
      
      cy.visit(`${baseUrl}/app/wallet`);
      cy.get('[data-test="wallet-balance"]').then(($el) => {
        const newBalance = parseFloat($el.text());
        expect(newBalance).to.be.greaterThan(parseFloat(initialBalance));
      });
    });

    it('Should track cashback in transaction history', () => {
      cy.visit(`${baseUrl}/app/wallet/history`);
      cy.get('[data-test="transaction-type"]').each(($el) => {
        cy.wrap($el).should('contain.text', 'cashback');
      });
    });

    it('Should display cashback breakdown by tier', () => {
      cy.visit(`${baseUrl}/app/rewards/cashback-details`);
      cy.get('[data-test="tier-bronze"]').should('contain', '2%');
      cy.get('[data-test="tier-silver"]').should('contain', '5%');
      cy.get('[data-test="tier-gold"]').should('contain', '10%');
    });
  });

  describe('Cashback Redemption', () => {
    it('Should allow cashback redemption to bank account', () => {
      cy.visit(`${baseUrl}/app/rewards/redeem`);
      cy.get('[data-test="cashback-available"]').should('be.visible');
      cy.contains('button', 'Redeem to Bank').click();
      cy.wait(300);
      cy.get('select[name="bank_account"]').select('Primary Account');
      cy.get('input[name="amount"]').type('1000');
      cy.contains('button', 'Confirm').click();
      cy.wait(500);
      cy.contains('Redemption initiated').should('be.visible');
    });

    it('Should allow cashback to be used as payment method', () => {
      cy.visit(`${baseUrl}/app/checkout`);
      cy.contains('button', 'Pay with Cashback').should('be.visible').click();
      cy.wait(500);
      cy.get('[data-test="cashback-amount-available"]').should('be.visible');
      cy.contains('button', 'Use Cashback').click();
      cy.wait(500);
      cy.contains('Cashback applied').should('be.visible');
    });

    it('Should prevent redemption below minimum amount', () => {
      cy.visit(`${baseUrl}/app/rewards/redeem`);
      cy.get('input[name="amount"]').type('50');
      cy.wait(300);
      cy.contains('Minimum redemption: 500 ₽').should('be.visible');
      cy.contains('button', 'Confirm').should('be.disabled');
    });
  });

  describe('Cashback Analytics & Reports', () => {
    beforeEach(() => {
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type(businessEmail);
      cy.get('input[name="password"]').type(password);
      cy.get('button[type="submit"]').click();
      cy.wait(500);
    });

    it('Should show total cashback distributed report', () => {
      cy.visit(`${baseUrl}/tenant/reports/cashback`);
      cy.contains('Total Distributed').should('be.visible');
      cy.get('[data-test="total-amount"]').should('contain', '₽');
    });

    it('Should display cashback by customer tier', () => {
      cy.visit(`${baseUrl}/tenant/reports/cashback-tiers`);
      cy.get('[data-test="tier-bronze-total"]').should('be.visible');
      cy.get('[data-test="tier-silver-total"]').should('be.visible');
      cy.get('[data-test="tier-gold-total"]').should('be.visible');
    });

    it('Should show cashback ROI analysis', () => {
      cy.visit(`${baseUrl}/tenant/reports/cashback-roi`);
      cy.contains('Cashback ROI').should('be.visible');
      cy.get('[data-test="roi-percentage"]').should('contain', '%');
      cy.get('[data-test="revenue-increase"]').should('contain', '₽');
    });
  });
});
