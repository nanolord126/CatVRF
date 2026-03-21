describe('Avatar & Photo Management Tests (Аватары и фото)', () => {
  const baseUrl = 'http://localhost:8000';
  const userEmail = 'avatar-user@test.com';
  const password = 'password';

  beforeEach(() => {
    cy.visit(`${baseUrl}/login`);
    cy.get('input[name="email"]').type(userEmail);
    cy.get('input[name="password"]').type(password);
    cy.get('button[type="submit"]').click();
    cy.wait(500);
    cy.url().should('include', '/app').or('include', '/tenant');
  });

  describe('Avatar Upload & Management', () => {
    it('Should upload user avatar image', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('[data-test="avatar-upload"] input[type="file"]').selectFile('cypress/fixtures/avatar.jpg', { force: true });
      cy.wait(500);
      cy.get('[data-test="avatar-preview"]').should('be.visible');
      cy.contains('button', 'Save Avatar').click();
      cy.wait(500);
      cy.contains('Avatar updated successfully').should('be.visible');
    });

    it('Should validate avatar dimensions (min 200x200px)', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('[data-test="avatar-upload"] input[type="file"]').selectFile('cypress/fixtures/small-avatar.jpg', { force: true });
      cy.wait(300);
      cy.contains('Image must be at least 200x200px').should('be.visible');
    });

    it('Should reject non-square avatar images', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('[data-test="avatar-upload"] input[type="file"]').selectFile('cypress/fixtures/rect-image.jpg', { force: true });
      cy.wait(300);
      cy.contains('Avatar must be square (1:1 ratio)').should('be.visible');
    });

    it('Should compress avatar automatically to max 500KB', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('[data-test="avatar-upload"] input[type="file"]').selectFile('cypress/fixtures/large-avatar.jpg', { force: true });
      cy.wait(500);
      cy.get('[data-test="file-size"]').then(($el) => {
        const size = parseInt($el.text());
        expect(size).to.be.lessThan(500000); // 500KB
      });
      cy.contains('button', 'Save Avatar').click();
      cy.wait(500);
      cy.contains('Avatar saved').should('be.visible');
    });

    it('Should display avatar in user profile', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('[data-test="avatar-upload"] input[type="file"]').selectFile('cypress/fixtures/avatar.jpg', { force: true });
      cy.wait(500);
      cy.contains('button', 'Save Avatar').click();
      cy.wait(500);
      
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('[data-test="user-avatar"]').should('have.attr', 'src').and('include', '.jpg');
    });

    it('Should delete current avatar', () => {
      // First upload avatar
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('[data-test="avatar-upload"] input[type="file"]').selectFile('cypress/fixtures/avatar.jpg', { force: true });
      cy.wait(500);
      cy.contains('button', 'Save Avatar').click();
      cy.wait(500);
      
      // Then delete
      cy.get('[data-test="delete-avatar-btn"]').click();
      cy.wait(300);
      cy.contains('Are you sure?').should('be.visible');
      cy.contains('button', 'Delete').click();
      cy.wait(500);
      cy.contains('Avatar deleted').should('be.visible');
      cy.get('[data-test="default-avatar"]').should('be.visible');
    });

    it('Should show default avatar for new users', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('[data-test="default-avatar"]').should('be.visible');
    });

    it('Should crop avatar before upload with preview', () => {
      cy.visit(`${baseUrl}/app/profile`);
      cy.get('[data-test="avatar-upload"] input[type="file"]').selectFile('cypress/fixtures/large-avatar.jpg', { force: true });
      cy.wait(500);
      cy.get('[data-test="crop-tool"]').should('be.visible');
      cy.get('[data-test="crop-preview"]').should('be.visible');
      cy.get('[data-test="crop-slider"]').invoke('val', 50).trigger('input');
      cy.contains('button', 'Crop').click();
      cy.wait(300);
      cy.contains('button', 'Save Avatar').click();
      cy.wait(500);
      cy.contains('Avatar saved').should('be.visible');
    });
  });

  describe('Business Logo Upload', () => {
    it('Should upload business logo', () => {
      cy.visit(`${baseUrl}/tenant/profile/branding`);
      cy.get('[data-test="logo-upload"] input[type="file"]').selectFile('cypress/fixtures/logo.png', { force: true });
      cy.wait(500);
      cy.get('[data-test="logo-preview"]').should('be.visible');
      cy.contains('button', 'Save Logo').click();
      cy.wait(500);
      cy.contains('Logo updated').should('be.visible');
    });

    it('Should validate logo file format (PNG/SVG only)', () => {
      cy.visit(`${baseUrl}/tenant/profile/branding`);
      cy.get('[data-test="logo-upload"] input[type="file"]').selectFile('cypress/fixtures/logo.jpg', { force: true });
      cy.wait(300);
      cy.contains('Only PNG and SVG formats allowed').should('be.visible');
    });

    it('Should resize logo for different contexts (favicon, header, etc)', () => {
      cy.visit(`${baseUrl}/tenant/profile/branding`);
      cy.get('[data-test="logo-upload"] input[type="file"]').selectFile('cypress/fixtures/logo.png', { force: true });
      cy.wait(500);
      cy.contains('button', 'Save Logo').click();
      cy.wait(500);
      cy.contains('Logo saved').should('be.visible');
      
      // Verify resized versions
      cy.get('[data-test="favicon"]').should('have.attr', 'href').and('include', 'favicon');
      cy.get('[data-test="header-logo"]').should('be.visible');
    });

    it('Should display logo on business pages', () => {
      cy.visit(`${baseUrl}/tenant/profile/branding`);
      cy.get('[data-test="logo-upload"] input[type="file"]').selectFile('cypress/fixtures/logo.png', { force: true });
      cy.wait(500);
      cy.contains('button', 'Save Logo').click();
      cy.wait(500);
      
      cy.visit(`${baseUrl}/app`);
      cy.get('[data-test="business-logo"]').should('be.visible');
    });
  });

  describe('Product Gallery & Photos', () => {
    it('Should upload multiple product photos', () => {
      cy.visit(`${baseUrl}/app/products/1/gallery`);
      cy.get('[data-test="photo-upload"] input[type="file"]').selectFile(
        ['cypress/fixtures/product-photo1.jpg', 'cypress/fixtures/product-photo2.jpg', 'cypress/fixtures/product-photo3.jpg'],
        { force: true }
      );
      cy.wait(500);
      cy.contains('Uploading 3 photos...').should('be.visible');
      cy.wait(1000);
      cy.contains('Photos uploaded successfully').should('be.visible');
    });

    it('Should set primary product photo', () => {
      cy.visit(`${baseUrl}/app/products/1/gallery`);
      cy.get('[data-test="gallery-item"]').eq(1).within(() => {
        cy.contains('button', 'Set as Primary').click();
      });
      cy.wait(500);
      cy.contains('Primary photo updated').should('be.visible');
      cy.get('[data-test="gallery-item"]').eq(1).should('have.class', 'primary');
    });

    it('Should reorder photos via drag-drop', () => {
      cy.visit(`${baseUrl}/app/products/1/gallery`);
      cy.get('[data-test="gallery-item"]').eq(0).trigger('dragstart');
      cy.get('[data-test="gallery-item"]').eq(2).trigger('drop');
      cy.wait(500);
      cy.contains('Photos reordered').should('be.visible');
    });

    it('Should delete product photo', () => {
      cy.visit(`${baseUrl}/app/products/1/gallery`);
      cy.get('[data-test="gallery-item"]').first().within(() => {
        cy.get('[data-test="delete-btn"]').click();
      });
      cy.wait(300);
      cy.contains('Delete this photo?').should('be.visible');
      cy.contains('button', 'Delete').click();
      cy.wait(500);
      cy.contains('Photo deleted').should('be.visible');
    });

    it('Should add photo description and tags', () => {
      cy.visit(`${baseUrl}/app/products/1/gallery`);
      cy.get('[data-test="gallery-item"]').first().click();
      cy.wait(300);
      cy.get('textarea[name="description"]').type('Beautiful product photo');
      cy.get('input[name="tags"]').type('new,trending,bestseller');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Photo details saved').should('be.visible');
    });
  });

  describe('Portfolio & Before-After Photos', () => {
    it('Should upload before-after portfolio photo (Beauty)', () => {
      cy.visit(`${baseUrl}/app/portfolio/add`);
      cy.get('[data-test="before-photo"] input[type="file"]').selectFile('cypress/fixtures/before.jpg', { force: true });
      cy.wait(500);
      cy.get('[data-test="after-photo"] input[type="file"]').selectFile('cypress/fixtures/after.jpg', { force: true });
      cy.wait(500);
      cy.get('input[name="title"]').type('Hair Transformation');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Portfolio item added').should('be.visible');
    });

    it('Should display before-after comparison', () => {
      cy.visit(`${baseUrl}/app/portfolio`);
      cy.get('[data-test="portfolio-item"]').first().click();
      cy.wait(300);
      cy.get('[data-test="before-after-slider"]').should('be.visible');
      // Drag slider to compare
      cy.get('[data-test="slider-handle"]').trigger('mousedown').trigger('mousemove', { clientX: 100 }).trigger('mouseup');
      cy.wait(300);
      cy.get('[data-test="before-after-slider"]').should('be.visible');
    });
  });

  describe('Photo Editing & Effects', () => {
    it('Should apply basic filters to photos', () => {
      cy.visit(`${baseUrl}/app/products/1/photo/1/edit`);
      cy.get('[data-test="filter-sepia"]').click();
      cy.wait(300);
      cy.get('[data-test="preview"]').should('have.class', 'sepia');
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Photo updated').should('be.visible');
    });

    it('Should adjust brightness and contrast', () => {
      cy.visit(`${baseUrl}/app/products/1/photo/1/edit`);
      cy.get('[data-test="brightness-slider"]').invoke('val', 120).trigger('input');
      cy.wait(300);
      cy.get('[data-test="contrast-slider"]').invoke('val', 110).trigger('input');
      cy.wait(300);
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Photo updated').should('be.visible');
    });

    it('Should crop and straighten photos', () => {
      cy.visit(`${baseUrl}/app/products/1/photo/1/edit`);
      cy.get('[data-test="crop-tool"]').click();
      cy.wait(300);
      cy.get('[data-test="crop-area"]').trigger('dragstart').trigger('drag').trigger('dragend');
      cy.wait(300);
      cy.get('[data-test="straighten-slider"]').invoke('val', 5).trigger('input');
      cy.contains('button', 'Apply').click();
      cy.wait(500);
      cy.contains('button', 'Save').click();
      cy.wait(500);
      cy.contains('Photo updated').should('be.visible');
    });
  });

  describe('Photo Upload - Security & Fraud Check', () => {
    it('Should scan uploaded photos for NSFW content', () => {
      cy.visit(`${baseUrl}/app/products/create`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/product-photo.jpg', { force: true });
      cy.wait(500);
      cy.get('[data-test="nsfw-scan"]').should('contain', 'Scanning');
      cy.wait(1000);
      cy.get('[data-test="nsfw-result"]').should('contain', 'Clean');
    });

    it('Should detect watermarks on product photos', () => {
      cy.visit(`${baseUrl}/app/products/create`);
      cy.get('input[type="file"]').selectFile('cypress/fixtures/watermarked-product.jpg', { force: true });
      cy.wait(500);
      cy.contains('Photo contains watermark').should('be.visible');
    });

    it('Should check for duplicate photos', () => {
      cy.visit(`${baseUrl}/app/products/1/gallery`);
      cy.get('[data-test="photo-upload"] input[type="file"]').selectFile('cypress/fixtures/product-photo1.jpg', { force: true });
      cy.wait(500);
      cy.contains('button', 'Upload').click();
      cy.wait(500);
      
      // Try uploading same photo again
      cy.get('[data-test="photo-upload"] input[type="file"]').selectFile('cypress/fixtures/product-photo1.jpg', { force: true });
      cy.wait(500);
      cy.contains('button', 'Upload').click();
      cy.wait(500);
      cy.contains('Duplicate photo detected').should('be.visible');
    });
  });

  describe('Photo Management - Tenant Isolation', () => {
    it('Should enforce tenant isolation on photos', () => {
      // Upload photo as tenant 1
      cy.visit(`${baseUrl}/app/products/create`);
      cy.get('input[name="name"]').type('Tenant 1 Product');
      cy.get('input[type="file"]').selectFile('cypress/fixtures/product-photo.jpg', { force: true });
      cy.wait(500);
      cy.contains('button', 'Save').click();
      cy.wait(500);
      
      // Logout and login as tenant 2
      cy.visit(`${baseUrl}/logout`);
      cy.wait(500);
      cy.visit(`${baseUrl}/login`);
      cy.get('input[name="email"]').type('tenant2@test.com');
      cy.get('input[name="password"]').type('password');
      cy.get('button[type="submit"]').click();
      cy.wait(500);
      
      // Verify can't see tenant 1's photos
      cy.visit(`${baseUrl}/app/products`);
      cy.contains('Tenant 1 Product').should('not.exist');
    });
  });
});
