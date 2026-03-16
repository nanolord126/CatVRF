// @ts-nocheck
describe('Localization & Russian UI - Complete Coverage', () => {
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
    // Set Russian locale
    cy.window().then((win) => {
      localStorage.setItem('locale', 'ru')
      localStorage.setItem('lang', 'ru')
    })
    cy.loginAs('admin@test.local', 'password123')
  })

  describe('UI Language Localization', () => {
    it('should display all labels in Russian', () => {
      cy.visit('/inventory')
      cy.contains('Инвентарь').should('be.visible')
      cy.contains('Создать элемент').should('be.visible')
      cy.contains('Удалить').should('be.visible')
      cy.contains('Редактировать').should('be.visible')
    })

    it('should localize navigation menu to Russian', () => {
      cy.get('[data-testid="nav-inventory"]').should('contain', 'Инвентарь')
      cy.get('[data-testid="nav-payroll"]').should('contain', 'Зарплата')
      cy.get('[data-testid="nav-hr"]').should('contain', 'HR')
      cy.get('[data-testid="nav-communications"]').should('contain', 'Коммуникации')
      cy.get('[data-testid="nav-beauty"]').should('contain', 'Красота')
    })

    it('should display Russian button labels', () => {
      cy.get('[data-testid="btn-save"]').should('have.text', 'Сохранить')
      cy.get('[data-testid="btn-cancel"]').should('have.text', 'Отмена')
      cy.get('[data-testid="btn-delete"]').should('have.text', 'Удалить')
      cy.get('[data-testid="btn-create"]').should('have.text', 'Создать')
      cy.get('[data-testid="btn-edit"]').should('have.text', 'Редактировать')
    })

    it('should show Russian placeholder text', () => {
      cy.get('[data-testid="input-name"]')
        .should('have.attr', 'placeholder', 'Введите название')
      cy.get('[data-testid="input-email"]')
        .should('have.attr', 'placeholder', 'Введите электронную почту')
      cy.get('[data-testid="input-phone"]')
        .should('have.attr', 'placeholder', 'Введите номер телефона')
    })

    it('should display Russian error messages', () => {
      cy.visit('/inventory/create')
      cy.get('[data-testid="btn-submit"]').click()
      cy.get('[data-testid="error-name"]')
        .should('contain', 'Название обязательно')
      cy.get('[data-testid="error-sku"]')
        .should('contain', 'SKU обязателен')
    })

    it('should display Russian validation messages', () => {
      cy.get('[data-testid="input-email"]').type('invalid-email')
      cy.get('[data-testid="error-email"]')
        .should('contain', 'Неверный формат электронной почты')
    })

    it('should show Russian success messages', () => {
      cy.get('[data-testid="input-name"]').type('Test Item')
      cy.get('[data-testid="input-sku"]').type('TEST-001')
      cy.get('[data-testid="btn-save"]').click()
      cy.get('[data-testid="toast-success"]')
        .should('contain', 'Успешно сохранено')
    })

    it('should display Russian table headers', () => {
      cy.visit('/inventory')
      cy.contains('Название').should('be.visible')
      cy.contains('SKU').should('be.visible')
      cy.contains('Количество').should('be.visible')
      cy.contains('Цена').should('be.visible')
      cy.contains('Действия').should('be.visible')
    })

    it('should localize modal titles to Russian', () => {
      cy.get('[data-testid="btn-delete"]').click()
      cy.get('[data-testid="modal-title"]')
        .should('contain', 'Подтвердите удаление')
      cy.get('[data-testid="modal-message"]')
        .should('contain', 'Вы уверены?')
    })

    it('should display Russian date formats', () => {
      cy.visit('/hr/leaves')
      // Dates should be in format DD.MM.YYYY
      cy.contains(/\d{2}\.\d{2}\.\d{4}/).should('exist')
    })

    it('should show Russian currency formatting', () => {
      cy.visit('/payroll')
      // Check for Russian rubles symbol (₽) or RUB
      cy.contains(/\d+\s*₽|RUB/).should('exist')
    })

    it('should display Russian time format', () => {
      cy.visit('/beauty/bookings')
      // Time should be in HH:MM format
      cy.contains(/\d{2}:\d{2}/).should('exist')
    })

    it('should localize pagination controls', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="pagination"]').within(() => {
        cy.contains('Назад').should('be.visible')
        cy.contains('Вперед').should('be.visible')
      })
    })

    it('should display Russian tooltips', () => {
      cy.get('[data-testid="icon-info"]').trigger('hover')
      cy.get('[data-testid="tooltip"]')
        .should('contain', 'Это обязательное поле')
    })

    it('should translate form labels in Russian', () => {
      cy.visit('/inventory/create')
      cy.get('label[for="name"]').should('have.text', 'Название')
      cy.get('label[for="sku"]').should('have.text', 'SKU')
      cy.get('label[for="quantity"]').should('have.text', 'Количество')
      cy.get('label[for="price"]').should('have.text', 'Цена')
    })

    it('should display Russian months and days', () => {
      cy.get('[data-testid="date-picker"]').click()
      cy.contains('Январь|Февраль|Март|Апрель|Май').should('exist')
      cy.contains('Пн|Вт|Ср|Чт|Пт|Сб|Вс').should('exist')
    })

    it('should show Russian status labels', () => {
      cy.visit('/payroll')
      cy.contains('Черновик').should('be.visible')
      cy.contains('Одобрено').should('be.visible')
      cy.contains('Выплачено').should('be.visible')
    })

    it('should translate role names to Russian', () => {
      cy.visit('/admin/users')
      cy.contains('Администратор').should('be.visible')
      cy.contains('Менеджер').should('be.visible')
      cy.contains('Просмотр').should('be.visible')
    })

    it('should display Russian department names', () => {
      cy.visit('/hr/employees')
      cy.contains('Продажи').should('be.visible')
      cy.contains('IT').should('be.visible')
      cy.contains('Финансы').should('be.visible')
    })

    it('should show Russian leave types', () => {
      cy.visit('/hr/leaves')
      cy.contains('Ежегодный отпуск').should('be.visible')
      cy.contains('Больничный').should('be.visible')
      cy.contains('Отпуск без оплаты').should('be.visible')
    })

    it('should display Russian service categories', () => {
      cy.visit('/beauty/services')
      cy.contains('Стрижка').should('be.visible')
      cy.contains('Окрашивание').should('be.visible')
      cy.contains('Маникюр').should('be.visible')
    })
  })

  describe('Localization API', () => {
    it('should return Russian translations', () => {
      cy.apiRequest('GET', '/api/localization/ru').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data).to.have.property('inventory')
        expect(response.body.data.inventory).to.have.property('title')
        expect(response.body.data.inventory.title).to.eq('Инвентарь')
      })
    })

    it('should support multiple languages', () => {
      cy.apiRequest('GET', '/api/localization/en').then((response) => {
        expect(response.status).to.eq(200)
        expect(response.body.data.inventory.title).to.eq('Inventory')
      })
    })

    it('should handle missing translation keys', () => {
      cy.apiRequest('GET', '/api/localization/ru/missing-key').then((response) => {
        expect(response.status).to.eq(404)
      })
    })
  })

  describe('RTL vs LTR Support', () => {
    it('should use LTR layout for Russian', () => {
      cy.visit('/')
      cy.get('html').should('have.attr', 'dir', 'ltr')
    })

    it('should apply correct text direction', () => {
      cy.get('body').should('have.css', 'direction', 'ltr')
    })
  })

  describe('Number and Date Formatting', () => {
    it('should format large numbers with spaces', () => {
      cy.visit('/payroll')
      // Russian format: 1 234 567 instead of 1,234,567
      cy.contains(/\d{1,3}\s\d{3}/).should('exist')
    })

    it('should format dates as DD.MM.YYYY', () => {
      cy.visit('/hr/employees')
      cy.contains(/\d{2}\.\d{2}\.\d{4}/).should('exist')
    })

    it('should format currency with ruble symbol', () => {
      cy.visit('/beauty/bookings')
      // Format: 1 234,50 ₽
      cy.contains(/\d+\s*₽/).should('exist')
    })

    it('should format percentages correctly', () => {
      cy.visit('/payroll')
      cy.contains(/\d+\s*%/).should('exist')
    })

    it('should handle decimal separator as comma', () => {
      cy.get('[data-testid="input-price"]').should('have.attr', 'placeholder')
        .then((placeholder) => {
          expect(placeholder).to.include('1234,56')
        })
    })
  })

  describe('Locale Persistence', () => {
    it('should remember selected locale in localStorage', () => {
      cy.window().then((win) => {
        expect(win.localStorage.getItem('locale')).to.eq('ru')
      })
    })

    it('should apply locale on page reload', () => {
      cy.visit('/inventory')
      cy.contains('Инвентарь').should('be.visible')
      cy.reload()
      cy.contains('Инвентарь').should('be.visible')
    })

    it('should use browser language as default', () => {
      // Depends on browser language settings
      cy.clearLocalStorage('locale')
      cy.reload()
      // Should use browser language
      cy.get('html').should('have.attr', 'lang')
    })
  })

  describe('Locale Switching', () => {
    it('should switch from Russian to English', () => {
      cy.visit('/')
      cy.contains('Инвентарь').should('be.visible')
      
      cy.get('[data-testid="language-selector"]').click()
      cy.get('[data-testid="lang-en"]').click()
      
      cy.contains('Inventory').should('be.visible')
      cy.contains('Инвентарь').should('not.exist')
    })

    it('should preserve user data when switching locale', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="input-filter"]').type('test')
      
      cy.get('[data-testid="language-selector"]').click()
      cy.get('[data-testid="lang-en"]').click()
      
      // Filter should still be active
      cy.get('[data-testid="input-filter"]').should('have.value', 'test')
    })

    it('should update page immediately after locale change', () => {
      cy.visit('/inventory')
      cy.contains('Инвентарь').should('be.visible')
      
      cy.get('[data-testid="language-selector"]').click()
      cy.get('[data-testid="lang-en"]').click()
      
      // Should update without page reload
      cy.contains('Inventory').should('be.visible')
    })
  })

  describe('Right-to-Left Languages', () => {
    it('should handle Arabic locale structure', () => {
      cy.apiRequest('GET', '/api/localization/ar').then((response) => {
        expect(response.status).to.eq(200)
      })
    })

    it('should handle Hebrew locale structure', () => {
      cy.apiRequest('GET', '/api/localization/he').then((response) => {
        expect(response.status).to.eq(200)
      })
    })
  })

  describe('Internationalized Strings', () => {
    it('should properly handle Russian characters in input', () => {
      cy.visit('/inventory/create')
      cy.get('[data-testid="input-name"]').type('Проверка')
      cy.get('[data-testid="input-name"]').should('have.value', 'Проверка')
    })

    it('should support Russian in database', () => {
      cy.apiRequest('POST', '/api/inventory', {
        name: 'Русское название',
        sku: 'RUS-001',
        quantity: 100,
        unit_price: 29.99
      }).then((response) => {
        expect(response.status).to.eq(201)
        expect(response.body.data.name).to.eq('Русское название')
      })
    })

    it('should display Russian text in search results', () => {
      cy.apiRequest('POST', '/api/inventory', {
        name: 'Электроника',
        sku: 'ELEC-001',
        quantity: 50,
        unit_price: 99.99
      }).then(() => {
        cy.visit('/inventory')
        cy.get('[data-testid="input-filter"]').type('Электроника')
        cy.contains('Электроника').should('be.visible')
      })
    })

    it('should handle Russian special characters', () => {
      const specialText = 'Тест "кавычек" и апострофа'
      cy.visit('/inventory/create')
      cy.get('[data-testid="input-description"]').type(specialText)
      cy.get('[data-testid="input-description"]').should('have.value', specialText)
    })

    it('should preserve Russian text in exports', () => {
      cy.visit('/inventory')
      cy.get('[data-testid="btn-export"]').click()
      cy.readFile('cypress/downloads/inventory.csv').then((content) => {
        expect(content).to.include('Русский текст')
      })
    })
  })

  describe('Regional Settings', () => {
    it('should use correct currency symbol for region', () => {
      cy.visit('/payroll')
      cy.contains('₽').should('be.visible') // Russian Ruble
    })

    it('should use correct number separators', () => {
      cy.visit('/inventory')
      // Should use space as thousands separator (Russian style)
      cy.contains(/\d+\s\d{3}/).should('exist')
    })

    it('should format phone numbers correctly', () => {
      cy.visit('/hr/employees')
      // Russian phone format
      cy.contains(/\+7\s*\d{3}\s*\d{3}\s*\d{2}\s*\d{2}/).should('exist')
    })

    it('should use correct time format', () => {
      cy.visit('/beauty/bookings')
      // Russian 24-hour format
      cy.contains(/([01]\d|2[0-3]):[0-5]\d/).should('exist')
    })
  })
})
