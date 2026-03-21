describe('File Upload Tests (Загрузка файлов)', () => {
  const baseUrl = 'http://localhost:8000';
  const businessEmail = 'business-upload@test.com';
  const password = 'password';

  beforeEach(() => {
    cy.visit(`${baseUrl}/login`);
    cy.get('input[name="email"]').type(businessEmail);
    cy.get('input[name="password"]').type(password);
    cy.get('button[type="submit"]').click();
    cy.wait(500);
    cy.url().should('include', '/app');
  });

  describe('CSV File Upload - Inventory Import', () => {
    it('Should upload valid CSV inventory file', () => {
      cy.visit(`${baseUrl}/app/inventory/import`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/inventory-valid.csv', { force: true });
      cy.wait(500);
      cy.contains('button', 'Import').click();
      cy.wait(1000);
      cy.contains('Successfully imported 50 items').should('be.visible');
    });

    it('Should reject invalid CSV with error message', () => {
      cy.visit(`${baseUrl}/app/inventory/import`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/inventory-invalid.csv', { force: true });
      cy.wait(500);
      cy.contains('button', 'Import').click();
      cy.wait(500);
      cy.contains('Invalid CSV format').should('be.visible');
    });

    it('Should validate CSV structure before processing', () => {
      cy.visit(`${baseUrl}/app/inventory/import`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/inventory-valid.csv', { force: true });
      cy.wait(500);
      cy.get('table').should('be.visible');
      cy.get('table tr').should('have.length.greaterThan', 1);
    });

    it('Should show import progress with detailed stats', () => {
      cy.visit(`${baseUrl}/app/inventory/import`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/inventory-valid.csv', { force: true });
      cy.wait(500);
      cy.contains('button', 'Import').click();
      cy.get('[data-test="progress-bar"]').should('be.visible');
      cy.get('[data-test="import-stats"]').should('contain', 'Processed:');
    });

    it('Should prevent duplicate imports with idempotency', () => {
      cy.visit(`${baseUrl}/app/inventory/import`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/inventory-valid.csv', { force: true });
      cy.contains('button', 'Import').click();
      cy.wait(1000);
      cy.contains('Successfully imported').should('be.visible');
      
      // Try importing again
      cy.visit(`${baseUrl}/app/inventory/import`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/inventory-valid.csv', { force: true });
      cy.contains('button', 'Import').click();
      cy.wait(500);
      cy.contains('File already processed').should('be.visible');
    });
  });

  describe('Excel File Upload - Payroll Data', () => {
    it('Should upload valid Excel payroll file', () => {
      cy.visit(`${baseUrl}/app/payroll/import`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/payroll-data.xlsx', { force: true });
      cy.wait(500);
      cy.contains('button', 'Import').click();
      cy.wait(1000);
      cy.contains('Payroll imported successfully').should('be.visible');
    });

    it('Should validate Excel columns before import', () => {
      cy.visit(`${baseUrl}/app/payroll/import`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/payroll-data.xlsx', { force: true });
      cy.wait(500);
      cy.get('[data-test="column-validation"]').should('contain', 'Employee Name');
      cy.get('[data-test="column-validation"]').should('contain', 'Salary');
    });

    it('Should calculate payroll totals after import', () => {
      cy.visit(`${baseUrl}/app/payroll/import`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/payroll-data.xlsx', { force: true });
      cy.wait(500);
      cy.contains('button', 'Import').click();
      cy.wait(1000);
      cy.get('[data-test="total-amount"]').should('contain', '₽');
    });
  });

  describe('Image File Upload - Product Photos', () => {
    it('Should upload product image successfully', () => {
      cy.visit(`${baseUrl}/app/products/create`);
      cy.get('input[name="name"]').type('Test Product');
      cy.get('input[type="file"][accept="image/*"]').selectFile('cypress/fixtures/product-image.jpg', { force: true });
      cy.wait(500);
      cy.get('[data-test="image-preview"]').should('be.visible');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Product created successfully').should('be.visible');
    });

    it('Should validate image dimensions', () => {
      cy.visit(`${baseUrl}/app/products/create`);
      cy.get('input[type="file"][accept="image/*"]').selectFile('cypress/fixtures/small-image.jpg', { force: true });
      cy.wait(500);
      cy.contains('Image must be at least 800x600px').should('be.visible');
    });

    it('Should reject unsupported image formats', () => {
      cy.visit(`${baseUrl}/app/products/create`);
      cy.get('input[type="file"][accept="image/*"]').selectFile('cypress/fixtures/invalid.gif', { force: true });
      cy.wait(500);
      cy.contains('Only JPG, PNG formats allowed').should('be.visible');
    });

    it('Should compress large images automatically', () => {
      cy.visit(`${baseUrl}/app/products/create`);
      cy.get('input[type="file"][accept="image/*"]').selectFile('cypress/fixtures/large-image.jpg', { force: true });
      cy.wait(500);
      cy.get('[data-test="file-size"]').then(($size) => {
        const originalSize = parseInt($size.text());
        cy.wait(1000);
        cy.get('[data-test="compressed-size"]').then(($compressed) => {
          const compressedSize = parseInt($compressed.text());
          expect(compressedSize).to.be.lessThan(originalSize);
        });
      });
    });
  });

  describe('PDF Upload - Documentation', () => {
    it('Should upload PDF document', () => {
      cy.visit(`${baseUrl}/app/documents/upload`);
      cy.get('input[type="file"][accept=".pdf"]').selectFile('cypress/fixtures/contract.pdf', { force: true });
      cy.wait(500);
      cy.contains('button', 'Upload').click();
      cy.wait(500);
      cy.contains('Document uploaded').should('be.visible');
    });

    it('Should validate PDF file size', () => {
      cy.visit(`${baseUrl}/app/documents/upload`);
      cy.get('input[type="file"][accept=".pdf"]').selectFile('cypress/fixtures/large-document.pdf', { force: true });
      cy.wait(500);
      cy.contains('File size exceeds 10MB limit').should('be.visible');
    });

    it('Should scan PDF for viruses before acceptance', () => {
      cy.visit(`${baseUrl}/app/documents/upload`);
      cy.get('input[type="file"][accept=".pdf"]').selectFile('cypress/fixtures/contract.pdf', { force: true });
      cy.wait(500);
      cy.contains('Scanning for threats...').should('be.visible');
      cy.wait(1000);
      cy.contains('File is safe').should('be.visible');
    });
  });

  describe('Bulk File Upload - Drag & Drop', () => {
    it('Should upload multiple files via drag and drop', () => {
      cy.visit(`${baseUrl}/app/gallery/upload`);
      cy.get('[data-test="drop-zone"]').selectFile(
        ['cypress/fixtures/photo1.jpg', 'cypress/fixtures/photo2.jpg', 'cypress/fixtures/photo3.jpg'],
        { action: 'drag-drop', force: true }
      );
      cy.wait(500);
      cy.contains('Uploading 3 files...').should('be.visible');
      cy.wait(2000);
      cy.contains('All 3 files uploaded').should('be.visible');
    });

    it('Should show upload progress for bulk operations', () => {
      cy.visit(`${baseUrl}/app/gallery/upload`);
      cy.get('[data-test="drop-zone"]').selectFile(
        ['cypress/fixtures/photo1.jpg', 'cypress/fixtures/photo2.jpg'],
        { action: 'drag-drop', force: true }
      );
      cy.get('[data-test="progress-bar"]').should('be.visible');
      cy.get('[data-test="file-item"]').should('have.length', 2);
    });

    it('Should allow cancelling in-progress uploads', () => {
      cy.visit(`${baseUrl}/app/gallery/upload`);
      cy.get('[data-test="drop-zone"]').selectFile('cypress/fixtures/large-video.mp4', { action: 'drag-drop', force: true });
      cy.wait(500);
      cy.contains('button', 'Cancel').click();
      cy.wait(500);
      cy.contains('Upload cancelled').should('be.visible');
    });
  });

  describe('File Upload with Fraud Check', () => {
    it('Should perform fraud check on file upload', () => {
      cy.visit(`${baseUrl}/app/products/create`);
      cy.get('input[name="name"]').type('Suspicious Product');
      cy.get('input[type="file"][accept="image/*"]').selectFile('cypress/fixtures/suspicious-image.jpg', { force: true });
      cy.wait(500); // Fraud check
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Review required before publishing').should('be.visible');
    });

    it('Should scan uploaded files for malware', () => {
      cy.visit(`${baseUrl}/app/documents/upload`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/document.pdf', { force: true });
      cy.wait(500);
      cy.get('[data-test="scan-status"]').should('contain', 'Scanning');
      cy.wait(1500);
      cy.get('[data-test="scan-result"]').should('contain', 'Safe');
    });
  });

  describe('File Upload - Permission & Tenant Isolation', () => {
    it('Should enforce tenant isolation on uploaded files', () => {
      // Upload file as tenant 1
      cy.visit(`${baseUrl}/app/gallery/upload`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/tenant1-photo.jpg', { force: true });
      cy.contains('button', 'Upload').click();
      cy.wait(500);
      cy.contains('File uploaded').should('be.visible');
      
      // Logout and login as different tenant
      cy.visit(`${baseUrl}/logout`);
      cy.wait(500);
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type('different-tenant@test.com');
      cy.get('input[name="password"]').type('password');
      cy.get('button[type="submit"]').click();
      cy.wait(500);
      
      // Verify file not visible
      cy.visit(`${baseUrl}/app/gallery`);
      cy.contains('tenant1-photo.jpg').should('not.exist');
    });

    it('Should require role-based permission for bulk uploads', () => {
      // This test would need a low-permission user
      cy.visit(`${baseUrl}/app/inventory/import`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/inventory-valid.csv', { force: true });
      cy.wait(500);
      cy.contains('Permission denied for bulk imports').should('be.visible');
    });
  });
});
