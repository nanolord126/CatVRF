describe('RBAC Authorization E2E Tests', () => {
  const baseUrl = 'http://localhost:8000';

  const users = {
    owner: {
      email: 'owner@tenant.com',
      password: 'owner123',
      role: 'owner',
    },
    manager: {
      email: 'manager@tenant.com',
      password: 'manager123',
      role: 'manager',
    },
    employee: {
      email: 'employee@tenant.com',
      password: 'employee123',
      role: 'employee',
    },
    accountant: {
      email: 'accountant@tenant.com',
      password: 'accountant123',
      role: 'accountant',
    },
    customer: {
      email: 'customer@example.com',
      password: 'customer123',
      role: 'customer',
    },
  };

  const login = (user: { email: string; password: string }) => {
    cy.visit(`${baseUrl}/login`);
    cy.get('input[name="email"]').type(user.email);
    cy.get('input[name="password"]').type(user.password);
    cy.get('button[type="submit"]').click();
    cy.url().should('include', '/tenant').or('include', '/app').or('include', '/admin');
  };

  const logout = () => {
    cy.visit(`${baseUrl}/logout`);
  };

  describe('Owner Permissions', () => {
    beforeEach(() => {
      login(users.owner);
    });

    afterEach(() => {
      logout();
    });

    it('Should access tenant dashboard', () => {
      cy.visit(`${baseUrl}/tenant`);
      cy.url().should('include', '/tenant');
      cy.contains('Панель управления').should('be.visible');
    });

    it('Should access team management', () => {
      cy.visit(`${baseUrl}/tenant/team`);
      cy.contains('Команда').should('be.visible');
      cy.get('button[data-cy=invite-user]').should('be.visible');
    });

    it('Should access financial reports', () => {
      cy.visit(`${baseUrl}/tenant/financials`);
      cy.contains('Финансы').should('be.visible');
      cy.get('[data-cy=revenue-chart]').should('be.visible');
    });

    it('Should access withdrawal form', () => {
      cy.visit(`${baseUrl}/tenant/wallet/withdraw`);
      cy.get('button[data-cy=withdraw-button]').should('not.be.disabled');
      cy.get('input[name="amount"]').should('be.visible');
    });

    it('Should access settings', () => {
      cy.visit(`${baseUrl}/tenant/settings`);
      cy.contains('Настройки').should('be.visible');
      cy.get('button[data-cy=save-settings]').should('be.visible');
    });
  });

  describe('Manager Permissions', () => {
    beforeEach(() => {
      login(users.manager);
    });

    afterEach(() => {
      logout();
    });

    it('Should access analytics', () => {
      cy.visit(`${baseUrl}/tenant/analytics`);
      cy.contains('Аналитика').should('be.visible');
    });

    it('Should NOT access withdrawal form', () => {
      cy.visit(`${baseUrl}/tenant/wallet/withdraw`);
      cy.get('[data-cy=permission-denied]').should('be.visible');
      cy.contains('нет прав').should('be.visible');
    });

    it('Should NOT access team management', () => {
      cy.visit(`${baseUrl}/tenant/team`);
      cy.get('[data-cy=permission-denied]').should('be.visible');
    });

    it('Should NOT access settings', () => {
      cy.visit(`${baseUrl}/tenant/settings`);
      cy.get('[data-cy=permission-denied]').should('be.visible');
    });

    it('Should view analytics but not edit', () => {
      cy.visit(`${baseUrl}/tenant/analytics`);
      cy.get('[data-cy=view-only-badge]').should('be.visible');
      cy.get('button[data-cy=edit-filters]').should('be.disabled');
    });
  });

  describe('Employee Permissions', () => {
    beforeEach(() => {
      login(users.employee);
    });

    afterEach(() => {
      logout();
    });

    it('Should NOT access analytics', () => {
      cy.visit(`${baseUrl}/tenant/analytics`);
      cy.get('[data-cy=permission-denied]').should('be.visible');
    });

    it('Should NOT access withdrawal', () => {
      cy.visit(`${baseUrl}/tenant/wallet/withdraw`);
      cy.get('[data-cy=permission-denied]').should('be.visible');
    });

    it('Should access basic dashboard', () => {
      cy.visit(`${baseUrl}/tenant`);
      cy.url().should('include', '/tenant');
    });

    it('Should NOT see financial data', () => {
      cy.visit(`${baseUrl}/tenant/financials`);
      cy.get('[data-cy=permission-denied]').should('be.visible');
    });
  });

  describe('Accountant Permissions', () => {
    beforeEach(() => {
      login(users.accountant);
    });

    afterEach(() => {
      logout();
    });

    it('Should access financial reports', () => {
      cy.visit(`${baseUrl}/tenant/financials`);
      cy.contains('Финансовые отчёты').should('be.visible');
      cy.get('[data-cy=balance-table]').should('be.visible');
    });

    it('Should view transactions history', () => {
      cy.visit(`${baseUrl}/tenant/transactions`);
      cy.get('[data-cy=transactions-table]').should('be.visible');
      cy.get('th').should('contain', 'Сумма');
      cy.get('th').should('contain', 'Статус');
    });

    it('Should NOT access withdrawal form', () => {
      cy.visit(`${baseUrl}/tenant/wallet/withdraw`);
      cy.get('[data-cy=permission-denied]').should('be.visible');
    });

    it('Should NOT access team management', () => {
      cy.visit(`${baseUrl}/tenant/team`);
      cy.get('[data-cy=permission-denied]').should('be.visible');
    });

    it('Should see audit logs', () => {
      cy.visit(`${baseUrl}/tenant/audit-logs`);
      cy.contains('Лог операций').should('be.visible');
      cy.get('[data-cy=audit-table]').should('be.visible');
    });
  });

  describe('Customer Permissions', () => {
    beforeEach(() => {
      login(users.customer);
    });

    afterEach(() => {
      logout();
    });

    it('Should NOT access tenant CRM panel', () => {
      cy.visit(`${baseUrl}/tenant`);
      cy.get('[data-cy=access-denied]').should('be.visible');
      cy.contains('Доступ запрещён').should('be.visible');
    });

    it('Should access public app panel', () => {
      cy.visit(`${baseUrl}/app`);
      cy.url().should('include', '/app');
      cy.contains('Маркетплейс').should('be.visible');
    });

    it('Should access wishlist', () => {
      cy.visit(`${baseUrl}/app/wishlist`);
      cy.contains('Избранное').should('be.visible');
    });

    it('Should access orders', () => {
      cy.visit(`${baseUrl}/app/orders`);
      cy.contains('Мои заказы').should('be.visible');
    });

    it('Should access account profile', () => {
      cy.visit(`${baseUrl}/app/account`);
      cy.contains('Личный кабинет').should('be.visible');
    });
  });

  describe('Super Admin Permissions', () => {
    it('Should access admin panel', () => {
      cy.visit(`${baseUrl}/admin`);
      
      // May require special super admin login
      cy.url().should('include', '/admin');
      cy.contains('Панель администратора').should('be.visible');
    });

    it('Should access all tenants management', () => {
      cy.visit(`${baseUrl}/admin/tenants`);
      cy.get('[data-cy=tenants-table]').should('be.visible');
    });

    it('Should access fraud attempts', () => {
      cy.visit(`${baseUrl}/admin/fraud-attempts`);
      cy.contains('Подозрительные операции').should('be.visible');
    });

    it('Should access user management', () => {
      cy.visit(`${baseUrl}/admin/users`);
      cy.get('[data-cy=users-table]').should('be.visible');
    });
  });

  describe('Invitation Flow', () => {
    beforeEach(() => {
      login(users.owner);
    });

    afterEach(() => {
      logout();
    });

    it('Should invite new user to team', () => {
      cy.visit(`${baseUrl}/tenant/team`);
      cy.get('button[data-cy=invite-user]').click();
      
      cy.get('input[name="email"]').type('newuser@tenant.com');
      cy.get('select[name="role"]').select('manager');
      cy.get('button[type="submit"]').click();
      
      cy.get('[data-cy=invitation-sent]').should('be.visible');
      cy.contains('newuser@tenant.com').should('be.visible');
    });

    it('Should show pending invitation status', () => {
      cy.visit(`${baseUrl}/tenant/team`);
      
      // New user should show as pending
      cy.get('[data-cy=user-status]').should('contain', 'Ожидание');
    });
  });

  describe('Cross-Tenant Access Control', () => {
    it('User should NOT access other tenant data', () => {
      login(users.owner);
      
      // Try to access different tenant ID
      cy.visit(`${baseUrl}/tenant/123456/settings`); // Wrong tenant ID
      
      cy.get('[data-cy=access-denied]').should('be.visible');
      cy.contains('нет доступа к этой организации').should('be.visible');
      
      logout();
    });
  });

  describe('Role Update', () => {
    beforeEach(() => {
      login(users.owner);
    });

    afterEach(() => {
      logout();
    });

    it('Owner can change team member role', () => {
      cy.visit(`${baseUrl}/tenant/team`);
      
      cy.get('[data-cy=member-row]:contains("manager@tenant.com")').within(() => {
        cy.get('button[data-cy=edit-role]').click();
      });
      
      cy.get('select[name="role"]').select('accountant');
      cy.get('button[data-cy=save-role]').click();
      
      cy.get('[data-cy=role-updated]').should('be.visible');
    });

    it('Owner can remove team member', () => {
      cy.visit(`${baseUrl}/tenant/team`);
      
      cy.get('[data-cy=member-row]:contains("employee@tenant.com")').within(() => {
        cy.get('button[data-cy=remove-member]').click();
      });
      
      cy.get('button[data-cy=confirm-remove]').click();
      
      cy.get('[data-cy=member-removed]').should('be.visible');
      cy.contains('employee@tenant.com').should('not.exist');
    });
  });
});
