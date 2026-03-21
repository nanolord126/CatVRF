describe('Wishlist Service E2E Tests', () => {
  const baseUrl = 'http://localhost:8000';
  const testUser = {
    email: 'customer@example.com',
    password: 'password123',
  };

  beforeEach(() => {
    cy.clearCookies();
    cy.clearLocalStorage();
    
    cy.visit(`${baseUrl}/login`);
    cy.get('input[name="email"]').type(testUser.email);
    cy.get('input[name="password"]').type(testUser.password);
    cy.get('button[type="submit"]').click();
    
    cy.url().should('include', '/app');
  });

  describe('Add to Wishlist', () => {
    it('Should add product to wishlist', () => {
      cy.visit(`${baseUrl}/app/marketplace`);
      
      // Find first product
      cy.get('[data-cy=product-card]').first().within(() => {
        cy.get('button[data-cy=add-to-wishlist]').click();
      });
      
      // Check success message
      cy.get('[data-cy=toast-success]').should('contain', 'Добавлено в избранное');
      
      // Verify heart icon changed
      cy.get('[data-cy=product-card]').first().within(() => {
        cy.get('[data-cy=wishlist-icon]').should('have.class', 'filled');
      });
    });

    it('Should prevent duplicate additions', () => {
      cy.visit(`${baseUrl}/app/marketplace`);
      
      // Add to wishlist
      cy.get('[data-cy=product-card]').first().within(() => {
        cy.get('button[data-cy=add-to-wishlist]').click();
      });
      
      cy.get('[data-cy=toast-success]').should('be.visible');
      
      // Try to add again
      cy.get('[data-cy=product-card]').first().within(() => {
        cy.get('button[data-cy=add-to-wishlist]').click();
      });
      
      // Should show message that item already in wishlist
      cy.get('[data-cy=toast-info]').should('contain', 'уже в избранном');
    });

    it('Should add service to wishlist', () => {
      cy.visit(`${baseUrl}/app/services/beauty`);
      
      cy.get('[data-cy=service-card]').first().within(() => {
        cy.get('button[data-cy=add-to-wishlist]').click();
      });
      
      cy.get('[data-cy=toast-success]').should('be.visible');
    });

    it('Should increment wishlist count badge', () => {
      cy.visit(`${baseUrl}/app/marketplace`);
      
      // Check initial wishlist count
      cy.get('[data-cy=wishlist-badge]').then(($badge) => {
        const initialCount = parseInt($badge.text());
        
        // Add item
        cy.get('[data-cy=product-card]').first().within(() => {
          cy.get('button[data-cy=add-to-wishlist]').click();
        });
        
        // Check count increased
        cy.get('[data-cy=wishlist-badge]').should('contain', initialCount + 1);
      });
    });
  });

  describe('View Wishlist', () => {
    it('Should display wishlist items', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      cy.contains('Избранное').should('be.visible');
      cy.get('[data-cy=wishlist-item]').should('have.length.greaterThan', 0);
    });

    it('Should show empty wishlist message', () => {
      // Clear wishlist first
      cy.request({
        method: 'DELETE',
        url: `${baseUrl}/api/wishlist/clear`,
        headers: {
          'Authorization': `Bearer ${Cypress.env('API_TOKEN')}`,
        },
      });
      
      cy.visit(`${baseUrl}/app/wishlist`);
      cy.contains('Избранное пусто').should('be.visible');
      cy.get('button[data-cy=continue-shopping]').should('be.visible');
    });

    it('Should display wishlist with correct product details', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      cy.get('[data-cy=wishlist-item]').first().within(() => {
        cy.get('[data-cy=product-image]').should('be.visible');
        cy.get('[data-cy=product-name]').should('not.be.empty');
        cy.get('[data-cy=product-price]').should('contain', '₽');
      });
    });

    it('Should filter wishlist by item type', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      // Filter by products
      cy.get('button[data-cy=filter-products]').click();
      cy.get('[data-cy=wishlist-item]').each(($item) => {
        cy.wrap($item).should('have.attr', 'data-type', 'product');
      });
      
      // Filter by services
      cy.get('button[data-cy=filter-services]').click();
      cy.get('[data-cy=wishlist-item]').each(($item) => {
        cy.wrap($item).should('have.attr', 'data-type', 'service');
      });
    });
  });

  describe('Remove from Wishlist', () => {
    it('Should remove item from wishlist', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      cy.get('[data-cy=wishlist-item]').first().within(() => {
        cy.get('button[data-cy=remove-from-wishlist]').click();
      });
      
      cy.get('[data-cy=toast-success]').should('contain', 'Удалено из избранного');
    });

    it('Should decrement wishlist count', () => {
      cy.visit(`${baseUrl}/app/marketplace`);
      
      cy.get('[data-cy=wishlist-badge]').then(($badge) => {
        const initialCount = parseInt($badge.text());
        
        // Go to wishlist and remove
        cy.visit(`${baseUrl}/app/wishlist`);
        cy.get('[data-cy=wishlist-item]').first().within(() => {
          cy.get('button[data-cy=remove-from-wishlist]').click();
        });
        
        // Check count decreased
        cy.get('[data-cy=wishlist-badge]').should('contain', initialCount - 1);
      });
    });

    it('Should clear all wishlist items', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      cy.get('button[data-cy=clear-wishlist]').click();
      cy.get('button[data-cy=confirm-clear]').click();
      
      cy.get('[data-cy=toast-success]').should('contain', 'Избранное очищено');
      cy.contains('Избранное пусто').should('be.visible');
    });
  });

  describe('Share Wishlist', () => {
    it('Should generate share link', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      cy.get('button[data-cy=share-wishlist]').click();
      cy.get('[data-cy=share-modal]').should('be.visible');
      cy.get('input[data-cy=share-link]').should('have.value').and('include', baseUrl);
    });

    it('Should copy share link to clipboard', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      cy.get('button[data-cy=share-wishlist]').click();
      cy.get('button[data-cy=copy-link]').click();
      
      cy.get('[data-cy=toast-success]').should('contain', 'Скопировано');
    });

    it('Should access shared wishlist with public link', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      cy.get('button[data-cy=share-wishlist]').click();
      cy.get('input[data-cy=share-link]').invoke('val').then((shareLink) => {
        cy.visit(shareLink as string);
        
        // Should see shared wishlist without login
        cy.contains('Общее избранное').should('be.visible');
        cy.get('[data-cy=wishlist-item]').should('have.length.greaterThan', 0);
      });
    });

    it('Should show social share options', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      cy.get('button[data-cy=share-wishlist]').click();
      cy.get('[data-cy=share-modal]').within(() => {
        cy.get('button[data-cy=share-vk]').should('be.visible');
        cy.get('button[data-cy=share-telegram]').should('be.visible');
        cy.get('button[data-cy=share-whatsapp]').should('be.visible');
      });
    });
  });

  describe('Group Purchase from Wishlist', () => {
    it('Should initiate group purchase', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      cy.get('[data-cy=wishlist-item]').first().within(() => {
        cy.get('button[data-cy=group-purchase]').click();
      });
      
      cy.get('[data-cy=group-purchase-modal]').should('be.visible');
    });

    it('Should add group purchase participants', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      cy.get('[data-cy=wishlist-item]').first().within(() => {
        cy.get('button[data-cy=group-purchase]').click();
      });
      
      // Add participant
      cy.get('input[data-cy=participant-email]').type('friend@example.com');
      cy.get('button[data-cy=add-participant]').click();
      
      cy.get('[data-cy=participant-list]').should('contain', 'friend@example.com');
    });

    it('Should calculate split cost', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      cy.get('[data-cy=wishlist-item]').first().then(($item) => {
        const price = $item.find('[data-cy=product-price]').text();
        const priceValue = parseInt(price.match(/\\d+/)[0]);
        
        cy.wrap($item).within(() => {
          cy.get('button[data-cy=group-purchase]').click();
        });
        
        // Add 2 participants (3 people total)
        for (let i = 0; i < 2; i++) {
          cy.get('input[data-cy=participant-email]').type(`friend${i}@example.com`);
          cy.get('button[data-cy=add-participant]').click();
        }
        
        // Check split amount
        const splitAmount = Math.ceil(priceValue / 3);
        cy.get('[data-cy=your-cost]').should('contain', splitAmount);
      });
    });

    it('Should send payment requests to participants', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      cy.get('[data-cy=wishlist-item]').first().within(() => {
        cy.get('button[data-cy=group-purchase]').click();
      });
      
      cy.get('input[data-cy=participant-email]').type('friend@example.com');
      cy.get('button[data-cy=add-participant]').click();
      
      cy.get('button[data-cy=send-requests]').click();
      
      cy.get('[data-cy=toast-success]').should('contain', 'Запросы отправлены');
    });

    it('Should track payment status from participants', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      // Navigate to group purchase details
      cy.get('[data-cy=active-group-purchase]').first().click();
      
      cy.get('[data-cy=payment-status]').should('be.visible');
      cy.get('[data-cy=participant-payment]').each(($item) => {
        cy.wrap($item).should('have.attr', 'data-status').and('match', /(pending|paid|expired)/);
      });
    });
  });

  describe('Wishlist Analytics', () => {
    it('Should display wishlist statistics', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      cy.get('[data-cy=wishlist-stats]').should('be.visible');
      cy.get('[data-cy=total-items]').should('contain.text', /\\d+/);
      cy.get('[data-cy=total-value]').should('contain', '₽');
    });

    it('Should show most popular items in wishlist', () => {
      cy.visit(`${baseUrl}/app/wishlist/analytics`);
      
      cy.get('[data-cy=popular-items]').should('be.visible');
      cy.get('[data-cy=item-count]').each(($count) => {
        cy.wrap($count).invoke('text').then((text) => {
          expect(parseInt(text)).to.be.greaterThan(0);
        });
      });
    });
  });

  describe('Wishlist Sync', () => {
    it('Should sync wishlist across devices', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      
      // Add item
      cy.visit(`${baseUrl}/app/marketplace`);
      cy.get('[data-cy=product-card]').first().within(() => {
        cy.get('button[data-cy=add-to-wishlist]').click();
      });
      
      // Open in another window/tab simulation
      cy.request({
        method: 'GET',
        url: `${baseUrl}/api/wishlist`,
        headers: {
          'Authorization': `Bearer ${Cypress.env('API_TOKEN')}`,
        },
      }).then((response) => {
        expect(response.body.items).to.have.length.greaterThan(0);
      });
    });
  });
});
