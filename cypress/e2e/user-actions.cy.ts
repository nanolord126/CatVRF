describe('User Actions Tests (Действия пользователя)', () => {
  const baseUrl = 'http://localhost:8000';
  const userEmail = 'action-user@test.com';
  const password = 'password';

  beforeEach(() => {
    cy.visit(`${baseUrl}/login`);
    cy.get('input[name="email"]').type(userEmail);
    cy.get('input[name="password"]').type(password);
    cy.get('button[type="submit"]').click();
    cy.wait(500);
  });

  describe('CRUD Actions - Create', () => {
    it('Should create new product with all required fields', () => {
      cy.visit(`${baseUrl}/app/products/create`);
      cy.get('input[name="name"]').type('Test Product');
      cy.get('textarea[name="description"]').type('Product description');
      cy.get('input[name="price"]').type('1000');
      cy.get('select[name="category"]').select('Category 1');
      cy.contains('button', 'Create').click();
      cy.wait(500);
      cy.contains('Product created successfully').should('be.visible');
      cy.url().should('include', '/products/');
    });

    it('Should validate required fields on create', () => {
      cy.visit(`${baseUrl}/app/products/create`);
      cy.get('input[name="name"]').should('be.empty');
      cy.contains('button', 'Create').click();
      cy.wait(300);
      cy.contains('Name is required').should('be.visible');
      cy.contains('Price is required').should('be.visible');
    });

    it('Should prevent creation with invalid data types', () => {
      cy.visit(`${baseUrl}/app/products/create`);
      cy.get('input[name="name"]').type('Test Product');
      cy.get('input[name="price"]').type('not-a-number');
      cy.wait(300);
      cy.contains('Price must be a number').should('be.visible');
      cy.contains('button', 'Create').should('be.disabled');
    });

    it('Should create bulk items via CSV import', () => {
      cy.visit(`${baseUrl}/app/products/bulk-import`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/products.csv', { force: true });
      cy.wait(500);
      cy.contains('button', 'Import').click();
      cy.wait(1000);
      cy.contains('100 products imported').should('be.visible');
    });
  });

  describe('CRUD Actions - Read & Search', () => {
    it('Should list all user products', () => {
      cy.visit(`${baseUrl}/app/products`);
      cy.get('[data-test="product-item"]').should('have.length.greaterThan', 0);
    });

    it('Should search products by name', () => {
      cy.visit(`${baseUrl}/app/products`);
      cy.get('input[name="search"]').type('Test');
      cy.wait(500);
      cy.get('[data-test="product-item"]').each(($el) => {
        cy.wrap($el).should('contain', 'Test');
      });
    });

    it('Should filter products by category', () => {
      cy.visit(`${baseUrl}/app/products`);
      cy.get('select[name="category"]').select('Category 1');
      cy.wait(500);
      cy.get('[data-test="product-item"]').each(($el) => {
        cy.wrap($el).should('contain', 'Category 1');
      });
    });

    it('Should sort products by price ascending', () => {
      cy.visit(`${baseUrl}/app/products`);
      cy.get('select[name="sort"]').select('price_asc');
      cy.wait(500);
      cy.get('[data-test="product-price"]').then(($prices) => {
        const prices = [...$prices].map(el => parseFloat(el.innerText));
        for (let i = 1; i < prices.length; i++) {
          expect(prices[i]).to.be.greaterThanOrEqual(prices[i - 1]);
        }
      });
    });

    it('Should view product details page', () => {
      cy.visit(`${baseUrl}/app/products`);
      cy.get('[data-test="product-item"]').first().click();
      cy.wait(500);
      cy.contains('Product Details').should('be.visible');
      cy.get('[data-test="product-price"]').should('be.visible');
    });

    it('Should paginate through products', () => {
      cy.visit(`${baseUrl}/app/products`);
      cy.get('[data-test="pagination"]').should('be.visible');
      cy.get('[data-test="page-2"]').click();
      cy.wait(500);
      cy.get('[data-test="current-page"]').should('contain', '2');
    });
  });

  describe('CRUD Actions - Update', () => {
    it('Should update product name and description', () => {
      cy.visit(`${baseUrl}/app/products`);
      cy.get('[data-test="product-item"]').first().click();
      cy.wait(300);
      cy.contains('button', 'Edit').click();
      cy.wait(300);
      cy.get('input[name="name"]').clear().type('Updated Product');
      cy.get('textarea[name="description"]').clear().type('Updated description');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Product updated successfully').should('be.visible');
    });

    it('Should update product price with validation', () => {
      cy.visit(`${baseUrl}/app/products`);
      cy.get('[data-test="product-item"]').first().click();
      cy.wait(300);
      cy.contains('button', 'Edit').click();
      cy.wait(300);
      cy.get('input[name="price"]').clear().type('2000');
      cy.wait(300);
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Price updated').should('be.visible');
    });

    it('Should bulk update multiple products', () => {
      cy.visit(`${baseUrl}/app/products`);
      cy.get('[data-test="select-all"]').click();
      cy.wait(300);
      cy.get('[data-test="bulk-actions"]').should('be.visible');
      cy.get('[data-test="bulk-category"]').select('New Category');
      cy.contains('button', 'Apply').click();
      cy.wait(500);
      cy.contains('Updated 15 products').should('be.visible');
    });

    it('Should prevent update with invalid data', () => {
      cy.visit(`${baseUrl}/app/products/1/edit`);
      cy.get('input[name="price"]').clear().type('invalid');
      cy.wait(300);
      cy.contains('Price must be a valid number').should('be.visible');
      cy.contains('button', 'Save').should('be.disabled');
    });
  });

  describe('CRUD Actions - Delete', () => {
    it('Should delete single product', () => {
      cy.visit(`${baseUrl}/app/products`);
      cy.get('[data-test="product-item"]').last().within(() => {
        cy.get('[data-test="delete-btn"]').click();
      });
      cy.wait(300);
      cy.contains('Are you sure?').should('be.visible');
      cy.contains('button', 'Delete').click();
      cy.wait(500);
      cy.contains('Product deleted successfully').should('be.visible');
    });

    it('Should bulk delete multiple products', () => {
      cy.visit(`${baseUrl}/app/products`);
      cy.get('[data-test="product-item"]').each(($el, idx) => {
        if (idx < 3) {
          cy.wrap($el).find('[data-test="checkbox"]').check();
        }
      });
      cy.wait(300);
      cy.get('[data-test="bulk-delete"]').click();
      cy.wait(300);
      cy.contains('Delete 3 items?').should('be.visible');
      cy.contains('button', 'Delete').click();
      cy.wait(500);
      cy.contains('Deleted 3 products').should('be.visible');
    });

    it('Should soft delete with undo option', () => {
      cy.visit(`${baseUrl}/app/products`);
      cy.get('[data-test="product-item"]').first().within(() => {
        cy.get('[data-test="delete-btn"]').click();
      });
      cy.wait(300);
      cy.contains('button', 'Delete').click();
      cy.wait(500);
      cy.contains('button', 'Undo').should('be.visible');
      cy.contains('button', 'Undo').click();
      cy.wait(500);
      cy.contains('Product restored').should('be.visible');
    });
  });

  describe('Role-Based Actions', () => {
    it('Owner should see all administrative actions', () => {
      cy.visit(`${baseUrl}/tenant/team`);
      cy.contains('Add Team Member').should('be.visible');
      cy.contains('button', 'Settings').should('be.visible');
      cy.contains('button', 'Billing').should('be.visible');
    });

    it('Manager should see limited actions', () => {
      // Login as manager
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type('manager@tenant.com');
      cy.get('input[name="password"]').type('password');
      cy.get('button[type="submit"]').click();
      cy.wait(500);
      
      cy.visit(`${baseUrl}/tenant/team`);
      cy.contains('Add Team Member').should('not.exist');
      cy.contains('button', 'Billing').should('not.exist');
    });

    it('Employee should see only their own data', () => {
      // Login as employee
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type('employee@tenant.com');
      cy.get('input[name="password"]').type('password');
      cy.get('button[type="submit"]').click();
      cy.wait(500);
      
      cy.visit(`${baseUrl}/app`);
      cy.contains('button', 'Team').should('not.exist');
      cy.contains('button', 'Settings').should('not.exist');
    });
  });

  describe('Complex Actions - Multi-Step', () => {
    it('Should complete product creation workflow', () => {
      // Step 1: Create product
      cy.visit(`${baseUrl}/app/products/create`);
      cy.get('input[name="name"]').type('Multi-Step Product');
      cy.get('input[name="price"]').type('5000');
      cy.contains('button', 'Next').click();
      cy.wait(300);
      
      // Step 2: Upload images
      cy.get('input[type="file"]').selectFile('cypress/fixtures/product.jpg', { force: true });
      cy.wait(500);
      cy.contains('button', 'Next').click();
      cy.wait(300);
      
      // Step 3: Review and publish
      cy.contains('Product Name:').should('contain', 'Multi-Step Product');
      cy.contains('button', 'Publish').click();
      cy.wait(500);
      cy.contains('Product published').should('be.visible');
    });

    it('Should handle checkout process', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      cy.get('[data-test="wishlist-item"]').first().within(() => {
        cy.contains('button', 'Buy Now').click();
      });
      cy.wait(500);
      
      // Checkout
      cy.contains('Checkout').should('be.visible');
      cy.get('input[name="address"]').type('ул. Новая, д. 1');
      cy.get('select[name="delivery"]').select('Standard');
      cy.wait(300);
      cy.contains('button', 'Pay').click();
      cy.wait(1000); // Payment processing
      cy.contains('Order confirmed').should('be.visible');
    });
  });

  describe('Actions with Fraud Detection', () => {
    it('Should flag suspicious bulk actions', () => {
      cy.visit(`${baseUrl}/app/products`);
      // Select and delete 100+ items
      cy.get('[data-test="select-all"]').click();
      cy.wait(300);
      cy.get('[data-test="bulk-delete"]').click();
      cy.wait(500);
      cy.contains('Suspicious activity detected').should('be.visible');
      cy.contains('Requires manager approval').should('be.visible');
    });

    it('Should require 2FA for sensitive actions', () => {
      cy.visit(`${baseUrl}/tenant/settings/billing`);
      cy.contains('button', 'Change Bank Account').click();
      cy.wait(500);
      cy.contains('Verify with 2FA').should('be.visible');
      cy.get('input[name="2fa_code"]').type('123456');
      cy.contains('button', 'Verify').click();
      cy.wait(500);
      cy.contains('Verified').should('be.visible');
    });
  });

  describe('Actions Audit Trail', () => {
    it('Should log all user actions', () => {
      cy.visit(`${baseUrl}/app/audit-log`);
      cy.contains('Action Log').should('be.visible');
      cy.get('[data-test="log-item"]').should('have.length.greaterThan', 0);
    });

    it('Should show action details with timestamp and user', () => {
      cy.visit(`${baseUrl}/app/audit-log`);
      cy.get('[data-test="log-item"]').first().click();
      cy.wait(300);
      cy.contains('Performed by:').should('be.visible');
      cy.contains('Timestamp:').should('be.visible');
      cy.contains('IP Address:').should('be.visible');
    });

    it('Should filter audit log by action type', () => {
      cy.visit(`${baseUrl}/app/audit-log`);
      cy.get('select[name="action_type"]').select('Create');
      cy.wait(500);
      cy.get('[data-test="log-item"]').each(($el) => {
        cy.wrap($el).should('contain', 'created');
      });
    });
  });

  describe('Actions - Idempotency & Concurrency', () => {
    it('Should prevent duplicate action execution', () => {
      cy.visit(`${baseUrl}/app/products/create`);
      cy.get('input[name="name"]').type('Unique Product');
      cy.get('input[name="price"]').type('1000');
      cy.contains('button', 'Create').click();
      cy.wait(500);
      cy.contains('created successfully').should('be.visible');
      
      // Simulate double-click
      cy.contains('button', 'Create').click();
      cy.wait(500);
      cy.contains('already created|duplicate').should('be.visible');
    });

    it('Should handle concurrent updates safely', () => {
      cy.visit(`${baseUrl}/app/products/1`);
      cy.contains('button', 'Edit').click();
      cy.wait(300);
      cy.get('input[name="price"]').clear().type('2000');
      
      // Simulate concurrent update
      cy.window().then((win) => {
        cy.request('PUT', `${baseUrl}/api/products/1`, { price: 3000 });
      });
      
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Conflict detected').should('be.visible');
    });
  });
});
