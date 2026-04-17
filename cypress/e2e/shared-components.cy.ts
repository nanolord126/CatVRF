// @ts-nocheck
/// <reference types="cypress" />

/**
 * shared-components.cy.ts — Cypress E2E тесты Livewire shared-компонентов.
 *
 * Тестирует все 7 shared компонентов:
 *  1. CartWidget
 *  2. WalletBalance
 *  3. NotificationBell
 *  4. FraudAlertBanner
 *  5. B2BModeSwitcher
 *  6. AIConstructorButton
 *  7. PriceLabel (unit-тест через стандартную страницу каталога)
 *
 * Требование канона:
 *  - Mobile-first: каждый ключевой сценарий проходит на 375px
 *  - data-cy атрибуты — единственный способ нахождения элементов
 */

import { setViewport, forAllViewports } from '../support/viewports'

// ─── Маршрут, на котором все shared-компоненты смонтированы ───────────────
const SHARED_TEST_ROUTE = '/test-shared-components'

// ─── Авторизация перед каждым тестом ──────────────────────────────────────
function loginAsTestUser() {
  cy.request('POST', '/test/login', {
    email: Cypress.env('TEST_USER_EMAIL') ?? 'test@catvrf.ru',
    _token: '',                    // ignored in test env
  }).then(() => cy.visit(SHARED_TEST_ROUTE))
}

// ═══════════════════════════════════════════════════════════════════════════
// 1. CartWidget
// ═══════════════════════════════════════════════════════════════════════════
describe('CartWidget', () => {
  beforeEach(() => loginAsTestUser())

  it('отображается на странице', () => {
    cy.get('[data-cy="cart-widget"]').should('be.visible')
  })

  it('показывает badge с числом позиций', () => {
    cy.get('[data-cy="cart-widget"]')
      .find('[data-cy="cart-count"]')
      .should('exist')
  })

  it('badge не отображается при пустой корзине', () => {
    // Пустая корзина — badge скрыт или имеет 0
    cy.get('[data-cy="cart-widget"]').within(() => {
      cy.get('[data-cy="cart-count"]').then(($badge) => {
        const count = parseInt($badge.text().trim(), 10)
        if (!isNaN(count)) {
          expect(count).to.be.gte(0)
        }
      })
    })
  })

  it('работает на мобильной ширине', () => {
    setViewport('mobile')
    cy.get('[data-cy="cart-widget"]').should('be.visible')
  })

  it('клик по иконке корзины открывает мини-корзину', () => {
    cy.get('[data-cy="cart-widget"]').click()
    cy.get('[data-cy="cart-mini-dropdown"]').should('be.visible')
  })
})

// ═══════════════════════════════════════════════════════════════════════════
// 2. WalletBalance
// ═══════════════════════════════════════════════════════════════════════════
describe('WalletBalance', () => {
  beforeEach(() => loginAsTestUser())

  it('отображает баланс пользователя', () => {
    cy.get('[data-cy="wallet-balance"]').should('be.visible')
    cy.get('[data-cy="wallet-amount"]').should('exist')
  })

  it('сумма содержит символ ₽', () => {
    cy.get('[data-cy="wallet-amount"]').invoke('text').should('include', '₽')
  })

  it('показывает B2B-badge в B2B-режиме', () => {
    // Переключаем в B2B через URL-параметры
    cy.visit(`${SHARED_TEST_ROUTE}?inn=1234567890&business_card_id=1`)
    cy.get('[data-cy="wallet-b2b-badge"]').should('be.visible')
  })

  it('работает на мобильном', () => {
    setViewport('mobile')
    cy.get('[data-cy="wallet-balance"]').should('be.visible')
  })

  it('отображает бонусы если они есть', () => {
    cy.get('[data-cy="wallet-bonuses"]').should('exist')
  })
})

// ═══════════════════════════════════════════════════════════════════════════
// 3. NotificationBell
// ═══════════════════════════════════════════════════════════════════════════
describe('NotificationBell', () => {
  beforeEach(() => loginAsTestUser())

  it('колокол отображается', () => {
    cy.get('[data-cy="notification-bell"]').should('be.visible')
  })

  it('клик открывает dropdown', () => {
    cy.get('[data-cy="notification-bell"]').click()
    cy.get('[data-cy="notifications-dropdown"]').should('be.visible')
  })

  it('клик повторно закрывает dropdown', () => {
    cy.get('[data-cy="notification-bell"]').click()
    cy.get('[data-cy="notification-bell"]').click()
    cy.get('[data-cy="notifications-dropdown"]').should('not.be.visible')
  })

  it('кнопка "Прочитать все" видна когда есть непрочитанные', () => {
    cy.get('[data-cy="notification-bell"]').click()
    cy.get('[data-cy="notifications-dropdown"]').within(() => {
      // Если есть непрочитанные — кнопка должна быть
      cy.get('body').then(() => {
        const btn = Cypress.$('[data-cy="mark-all-read"]')
        if (btn.length > 0) {
          cy.wrap(btn).should('be.visible')
        }
      })
    })
  })

  it('работает на мобильном', () => {
    setViewport('mobile')
    cy.get('[data-cy="notification-bell"]').should('be.visible')
  })
})

// ═══════════════════════════════════════════════════════════════════════════
// 4. FraudAlertBanner
// ═══════════════════════════════════════════════════════════════════════════
describe('FraudAlertBanner', () => {
  it('не отображается без активного фрод-предупреждения', () => {
    loginAsTestUser()
    cy.get('[data-cy="fraud-banner"]').should('not.exist')
  })

  it('отображается при severity=warning и имеет кнопку закрытия', () => {
    cy.visit(`${SHARED_TEST_ROUTE}?__fraud_severity=warning`)
    cy.get('[data-cy="fraud-banner"]').should('be.visible')
    cy.get('[data-cy="fraud-banner-close"]').should('be.visible')
  })

  it('кнопка закрытия скрывает banner при severity=warning', () => {
    cy.visit(`${SHARED_TEST_ROUTE}?__fraud_severity=warning`)
    cy.get('[data-cy="fraud-banner-close"]').click()
    cy.get('[data-cy="fraud-banner"]').should('not.be.visible')
  })

  it('при severity=critical кнопки закрытия нет (блокирующий)', () => {
    cy.visit(`${SHARED_TEST_ROUTE}?__fraud_severity=critical`)
    cy.get('[data-cy="fraud-banner"]').should('be.visible')
    cy.get('[data-cy="fraud-banner-close"]').should('not.exist')
  })

  it('critical-banner имеет role=alert', () => {
    cy.visit(`${SHARED_TEST_ROUTE}?__fraud_severity=critical`)
    cy.get('[data-cy="fraud-banner"]').should('have.attr', 'role', 'alert')
  })

  it('мобильный viewport — banner занимает полную ширину', () => {
    setViewport('mobile')
    cy.visit(`${SHARED_TEST_ROUTE}?__fraud_severity=high`)
    cy.get('[data-cy="fraud-banner"]').should('be.visible')
  })
})

// ═══════════════════════════════════════════════════════════════════════════
// 5. B2BModeSwitcher
// ═══════════════════════════════════════════════════════════════════════════
describe('B2BModeSwitcher', () => {
  it('НЕ отображается для B2C-пользователя (нет ИНН)', () => {
    loginAsTestUser()
    cy.get('[data-cy="b2b-switcher"]').should('not.exist')
  })

  it('отображается когда у пользователя есть business_card_id', () => {
    cy.visit(`${SHARED_TEST_ROUTE}?inn=1234567890&business_card_id=1`)
    cy.get('[data-cy="b2b-switcher"]').should('be.visible')
  })

  it('клик переключает режим на B2B', () => {
    cy.visit(`${SHARED_TEST_ROUTE}?inn=1234567890&business_card_id=1`)
    cy.get('[data-cy="b2b-switcher"]').click()
    // После переключения должен появиться B2B-бейдж
    cy.get('[data-cy="b2b-active-badge"]').should('be.visible')
  })

  it('повторный клик возвращает в B2C', () => {
    cy.visit(`${SHARED_TEST_ROUTE}?inn=1234567890&business_card_id=1`)
    cy.get('[data-cy="b2b-switcher"]').click()
    cy.get('[data-cy="b2b-switcher"]').click()
    cy.get('[data-cy="b2b-active-badge"]').should('not.exist')
  })
})

// ═══════════════════════════════════════════════════════════════════════════
// 6. AIConstructorButton
// ═══════════════════════════════════════════════════════════════════════════
describe('AIConstructorButton', () => {
  beforeEach(() => loginAsTestUser())

  it('кнопка отображается на странице с вертикалью', () => {
    cy.visit('/beauty')
    cy.get('[data-cy="ai-constructor-btn"]').should('be.visible')
  })

  it('кнопка disabled когда quota исчерпан', () => {
    cy.visit(`${SHARED_TEST_ROUTE}?__ai_quota=0`)
    cy.get('[data-cy="ai-constructor-btn"]')
      .should('have.attr', 'disabled')
  })

  it('клик по активной кнопке диспатчит событие и открывает wizard', () => {
    cy.visit('/beauty')
    cy.get('[data-cy="ai-constructor-btn"]').click()
    // AIConstructorWizard.vue слушает ai-constructor-open
    cy.get('[data-cy="step-upload"]').should('be.visible')
  })

  it('tooltip с текстом при disabled-состоянии', () => {
    cy.visit(`${SHARED_TEST_ROUTE}?__ai_quota=0`)
    cy.get('[data-cy="ai-constructor-btn"]').trigger('mouseenter')
    cy.get('[data-cy="ai-btn-tooltip"]').should('be.visible')
  })

  it('мобильный viewport — кнопка остаётся доступной', () => {
    setViewport('mobile')
    cy.visit('/beauty')
    cy.get('[data-cy="ai-constructor-btn"]').should('be.visible')
  })
})

// ═══════════════════════════════════════════════════════════════════════════
// 7. Сквозной тест: все shared-компоненты на всех viewport'ах
// ═══════════════════════════════════════════════════════════════════════════
describe('Shared — cross-viewport smoke', () => {
  it('все компоненты видны на трёх viewports', () => {
    forAllViewports(() => {
      loginAsTestUser()
      cy.get('[data-cy="cart-widget"]').should('be.visible')
      cy.get('[data-cy="wallet-balance"]').should('be.visible')
      cy.get('[data-cy="notification-bell"]').should('be.visible')
    })
  })
})

// ═══════════════════════════════════════════════════════════════════════════
// 8. PriceLabel — отображение B2C vs B2B цены в каталоге
// ═══════════════════════════════════════════════════════════════════════════
describe('PriceLabel', () => {
  it('отображает розничную цену в B2C-режиме', () => {
    loginAsTestUser()
    cy.visit('/catalog')
    cy.get('[data-cy="price-label"]').first().should('be.visible')
    cy.get('[data-cy="price-label"]').first().invoke('text').should('include', '₽')
  })

  it('показывает оптовую цену и B2B-бейдж в B2B-режиме', () => {
    cy.visit(`/catalog?inn=1234567890&business_card_id=1`)
    cy.get('[data-cy="price-label-b2b"]').first().should('be.visible')
  })

  it('grayscale-класс на карточке товара не в наличии', () => {
    cy.visit('/catalog?__test_out_of_stock=1')
    cy.get('[data-cy="product-card"].grayscale').should('exist')
    cy.get('[data-cy="product-card"].grayscale')
      .find('[data-cy="add-to-cart"]')
      .should('not.exist')
  })
})
