# Cypress E2E Testing Best Practices

## 📌 Структура Теста

### Правильная Структура
```typescript
describe('Feature Name', () => {
  let testData: any
  
  beforeEach(() => {
    cy.resetDatabase()
    cy.seedDatabase()
    cy.loginAs('admin@test.local', 'password123')
  })
  
  afterEach(() => {
    // Cleanup if needed
  })
  
  describe('Specific Functionality', () => {
    it('should [expected behavior] when [precondition]', () => {
      // Arrange
      const itemName = 'Test Item'
      
      // Act
      cy.visit('/inventory')
      cy.get('[data-testid="btn-create"]').click()
      cy.get('[data-testid="input-name"]').type(itemName)
      cy.get('[data-testid="btn-submit"]').click()
      
      // Assert
      cy.get('[data-testid="toast-success"]').should('be.visible')
      cy.contains(itemName).should('be.visible')
    })
  })
})
```

## 🎯 Селекторы

### Приоритет Использования
1. **data-testid** ✅ (ЛУЧШИЙ ВЫБОР)
   ```typescript
   cy.get('[data-testid="submit-button"]')
   ```

2. **Cypress Query Selectors** ✅
   ```typescript
   cy.contains('Submit').click()
   cy.get('button').contains('Submit')
   ```

3. **CSS Селекторы** ⚠️ (Хрупкие)
   ```typescript
   cy.get('.btn.btn-primary') // Избегать
   ```

4. **XPath** ❌ (Не использовать)
   ```typescript
   cy.xpath('//button[@class="submit"]') // НЕ ИСПОЛЬЗОВАТЬ
   ```

### Правила для HTML
```html
<!-- HTML должен иметь data-testid -->
<form data-testid="inventory-form">
  <input data-testid="input-name" type="text" />
  <input data-testid="input-sku" type="text" />
  <button data-testid="btn-submit" type="submit">Submit</button>
</form>
```

## ⏱️ Управление Временем

### Явные Ожидания
```typescript
// ✅ ПРАВИЛЬНО - Явное ожидание
cy.get('[data-testid="alert-message"]', { timeout: 5000 })
  .should('be.visible')

// ❌ НЕПРАВИЛЬНО - Неявное ожидание
cy.wait(3000)
cy.get('[data-testid="alert-message"]')
```

### Ожидание API Запросов
```typescript
// ✅ ПРАВИЛЬНО - Ожидание конкретного запроса
cy.intercept('GET', '/api/inventory').as('getInventory')
cy.get('[data-testid="btn-refresh"]').click()
cy.wait('@getInventory').then((interception) => {
  expect(interception.response?.statusCode).to.eq(200)
  expect(interception.response?.body.data).to.have.length(10)
})

// ❌ НЕПРАВИЛЬНО - Слепое ожидание
cy.wait(2000)
```

## 🔐 Аутентификация

### Правильная Логин Процедура
```typescript
beforeEach(() => {
  // Вариант 1: Использование Custom Command
  cy.loginAs('admin@test.local', 'password123')
  
  // Вариант 2: Прямой запрос (быстрее)
  cy.request({
    method: 'POST',
    url: '/api/login',
    body: {
      email: 'admin@test.local',
      password: 'password123'
    }
  }).then((response) => {
    window.localStorage.setItem('auth_token', response.body.token)
  })
})
```

## 📊 Обработка Данных

### Работа с Fixtures
```typescript
// Load fixture data
cy.fixture('inventory-valid.csv').then((csvData) => {
  cy.get('[data-testid="input-file"]').selectFile(csvData)
})

// Inline data
const testData = {
  name: 'Test Item',
  sku: 'TEST-001',
  quantity: 100
}
```

### Создание Тестовых Данных
```typescript
// API-based setup (рекомендуется)
beforeEach(() => {
  cy.apiRequest('POST', '/api/inventory', {
    name: 'Test Item',
    sku: 'TEST-001',
    quantity: 100
  }).then((response) => {
    expect(response.status).to.eq(201)
  })
})
```

## ✅ Assertions (Утверждения)

### Хорошие Assertions
```typescript
// ✅ Специфичные утверждения
cy.get('[data-testid="item-name"]')
  .should('have.text', 'Test Item')
  .should('be.visible')
  .should('not.be.disabled')

// ✅ Проверка видимости
cy.get('[data-testid="success-message"]')
  .should('be.visible')
  .invoke('text')
  .should('contain', 'Successfully saved')

// ✅ Проверка значений
cy.get('[data-testid="input-quantity"]')
  .should('have.value', '100')

// ✅ Проверка класса
cy.get('[data-testid="error-alert"]')
  .should('have.class', 'alert-danger')
```

### Плохие Assertions
```typescript
// ❌ Слишком общие
cy.get('[data-testid="form"]').should('exist')

// ❌ Проверка деталей реализации
cy.get('[data-testid="button"]')
  .should('have.css', 'background-color', 'rgb(0, 123, 255)')

// ❌ Проверка скрытых деталей
cy.window().then((win) => {
  expect(win.someGlobalVariable).to.eq(123)
})
```

## 🔄 Обработка Ошибок

### Правильная Обработка Ошибок
```typescript
// ✅ Проверка ошибок валидации
cy.get('[data-testid="btn-submit"]').click()
cy.get('[data-testid="error-name"]')
  .should('be.visible')
  .should('contain', 'Name is required')

// ✅ API ошибки
cy.request({
  method: 'POST',
  url: '/api/inventory',
  body: { invalid: 'data' },
  failOnStatusCode: false
}).then((response) => {
  expect(response.status).to.eq(422)
  expect(response.body.errors).to.have.property('name')
})

// ✅ Обработка toast уведомлений
cy.get('[data-testid="toast-error"]')
  .should('be.visible')
  .should('contain', 'An error occurred')
```

## 🔒 Security Testing

### Проверка RBAC
```typescript
it('should prevent unauthorized access', () => {
  cy.loginAs('viewer@test.local', 'password123')
  cy.visit('/admin/inventory/create')
  cy.get('[data-testid="access-denied"]')
    .should('be.visible')
})

it('should require confirmation for sensitive operations', () => {
  cy.loginAs('admin@test.local', 'password123')
  cy.get('[data-testid="btn-delete"]').click()
  cy.get('[data-testid="modal-confirm"]').should('be.visible')
  cy.get('[data-testid="input-password"]').type('password123')
  cy.get('[data-testid="btn-confirm"]').click()
})
```

## 📱 Тестирование Таблиц и Списков

### Проверка Содержимого
```typescript
it('should display inventory items with correct data', () => {
  cy.get('[data-testid="inventory-table"]')
    .within(() => {
      cy.get('tbody tr').should('have.length', 10)
      cy.get('tbody tr').first().within(() => {
        cy.get('td').eq(0).should('contain', 'Test Item')
        cy.get('td').eq(1).should('contain', 'TEST-001')
        cy.get('td').eq(2).should('contain', '100')
      })
    })
})
```

### Фильтрация и Сортировка
```typescript
it('should filter items correctly', () => {
  cy.get('[data-testid="input-filter"]').type('electronics')
  cy.get('[data-testid="btn-search"]').click()
  cy.get('[data-testid="inventory-table"] tbody tr')
    .should('have.length.greaterThan', 0)
    .first()
    .should('contain', 'electronics')
})

it('should sort items by name', () => {
  cy.get('[data-testid="header-name"]').click()
  cy.get('[data-testid="inventory-table"] tbody tr').first()
    .get('td').eq(0).invoke('text')
    .then((firstItemName) => {
      cy.get('[data-testid="inventory-table"] tbody tr').last()
        .get('td').eq(0).invoke('text')
        .then((lastItemName) => {
          expect(firstItemName).to.be.lessThan(lastItemName)
        })
    })
})
```

## 📑 Пагинация

### Проверка Страниц
```typescript
it('should paginate correctly', () => {
  // Page 1
  cy.get('[data-testid="inventory-table"] tbody tr')
    .should('have.length', 10)
  
  // Go to next page
  cy.get('[data-testid="btn-next-page"]').click()
  cy.get('[data-testid="current-page"]')
    .should('contain', '2')
  
  // Verify different items
  cy.get('[data-testid="inventory-table"] tbody tr').first()
    .should('not.contain', 'Test Item 1')
})
```

## 💾 Экспорт и Импорт

### CSV Импорт
```typescript
it('should import CSV file', () => {
  cy.get('[data-testid="btn-import"]').click()
  cy.get('[data-testid="input-file"]')
    .selectFile('cypress/fixtures/inventory-valid.csv')
  cy.get('[data-testid="btn-confirm"]').click()
  cy.get('[data-testid="toast-success"]')
    .should('contain', '5 items imported')
})
```

### CSV Экспорт
```typescript
it('should export as CSV', () => {
  cy.get('[data-testid="btn-export"]').click()
  cy.readFile('cypress/downloads/inventory.csv')
    .should('exist')
    .should('contain', 'TEST-001')
})
```

## 🗓️ Работа с Датами

### Тестирование Date Picker
```typescript
it('should select date range', () => {
  cy.get('[data-testid="input-start-date"]').click()
  cy.get('[data-testid="calendar"]')
    .find('[data-testid="date-1"]').click()
  
  cy.get('[data-testid="input-end-date"]').click()
  cy.get('[data-testid="calendar"]')
    .find('[data-testid="date-15"]').click()
  
  cy.get('[data-testid="input-start-date"]')
    .should('have.value', '2024-01-01')
  cy.get('[data-testid="input-end-date"]')
    .should('have.value', '2024-01-15')
})
```

## 📧 Email и Уведомления

### Проверка Уведомлений
```typescript
it('should show toast notification', () => {
  cy.get('[data-testid="btn-save"]').click()
  cy.get('[data-testid="toast-success"]')
    .should('be.visible')
    .should('contain', 'Saved successfully')
  
  // Check toast disappears after 3 seconds
  cy.get('[data-testid="toast-success"]', { timeout: 4000 })
    .should('not.exist')
})
```

## 🔀 Обработка Модалей

### Работа с Modal Windows
```typescript
it('should handle modal dialogs', () => {
  cy.get('[data-testid="btn-delete"]').click()
  
  cy.get('[data-testid="modal-delete"]')
    .should('be.visible')
    .within(() => {
      cy.get('[data-testid="modal-title"]')
        .should('contain', 'Confirm Delete')
      cy.get('[data-testid="btn-confirm"]').click()
    })
  
  cy.get('[data-testid="modal-delete"]')
    .should('not.exist')
})
```

## 🌐 Multi-Browser Testing

### Browser-specific Code
```typescript
it('should work in different browsers', () => {
  // Chrome/Edge specific
  cy.browser().then((browser) => {
    if (browser === 'chrome') {
      cy.get('[data-testid="chrome-feature"]')
        .should('be.visible')
    }
  })
})
```

## 📊 Performance Testing

### Проверка Времени Отклика
```typescript
it('should load inventory within 2 seconds', () => {
  const start = Date.now()
  cy.visit('/inventory')
  const duration = Date.now() - start
  expect(duration).to.be.lessThan(2000)
})

// Using cy.intercept for precise timing
it('should fetch inventory in reasonable time', () => {
  cy.intercept('GET', '/api/inventory').as('getInventory')
  cy.visit('/inventory')
  cy.wait('@getInventory').then((interception) => {
    const duration = interception.response?.duration || 0
    expect(duration).to.be.lessThan(500)
  })
})
```

## 🔍 Debugging

### Debug Commands
```typescript
// Log to console
cy.log('Current value:', value)

// Debug step-by-step
cy.get('[data-testid="input"]').debug()

// Print element
cy.get('[data-testid="form"]').then((el) => {
  console.log('Form HTML:', el.html())
  console.log('Form Text:', el.text())
})

// Pause execution
cy.pause()

// Step through test
cy.step('Starting inventory test')
```

## ✨ Best Practices Summary

| Практика | ✅ Делай | ❌ Не Делай |
|----------|---------|-----------|
| **Селекторы** | `data-testid` | CSS классы, иерархия |
| **Ожидания** | Явные с timeout | `cy.wait(ms)` |
| **Данные** | API создание | Manual UI вводы |
| **Assertions** | Специфичные | Общие проверки |
| **Ошибки** | Обработка всех кейсов | Ignore failures |
| **Логирование** | `cy.log()` | `console.log()` |
| **Cleanup** | `afterEach` hooks | Оставить данные |
| **Скорость** | Параллелизм | Sequential runs |

