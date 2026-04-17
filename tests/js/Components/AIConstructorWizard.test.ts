/**
 * AIConstructorWizard.test.ts — Vitest компонентные тесты.
 *
 * ВАЖНО: Wizard использует <Teleport to="body"> — содержимое диалога
 * рендерится ВНЕ корневого элемента компонента.
 * Поэтому w.find('[role="dialog"]') всегда returns null.
 * Используем document.body.querySelector() через хелпер qb().
 *
 * Покрытие:
 *  - Wizard скрыт по умолчанию
 *  - Открывается по custom-event 'ai-constructor-open'
 *  - Шаги: upload → analyze → results → saved
 *  - Закрывается по ESC и крестику
 *  - Ошибка fetch → возврат к upload с баннером
 *  - addToCart диспатчит 'livewire:dispatch'
 *  - ARIA: role="dialog", aria-modal, aria-labelledby
 */
// @ts-nocheck
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import AIConstructorWizard from '@frontend/pages/AIConstructorWizard.vue'

// ──────────────────────────── helpers ────────────────────────────

/** Монтирует компонент прикреплённым к document.body (нужно для Teleport) */
function mountWizard(props = {}) {
  return mount(AIConstructorWizard, {
    props,
    attachTo: document.body,
    global: {
      stubs: {
        Teleport: false,
        Transition: false,
      },   // разворачиваем Teleport и отключаем transition-анимации в тестах
    },
  })
}

/** Диспатчит кастомное событие открытия wizard'а */
function openWizard(payload: { vertical?: string; isB2B?: boolean; correlationId?: string } = {}) {
  document.dispatchEvent(new CustomEvent('ai-constructor-open', {
    detail: {
      vertical: 'beauty',
      isB2B: false,
      correlationId: 'test-corr-id',
      ...payload,
    },
  }))
}

/**
 * Ищет элемент в document.body — единственный способ найти
 * содержимое Teleport, которое рендерится вне w.element.
 */
function qb(selector: string): Element | null {
  return document.body.querySelector(selector)
}

/** Создаёт mock File */
function makeFile(name = 'photo.jpg'): File {
  return new File(['...'], name, { type: 'image/jpeg' })
}

// ──────────────────────────── mocks ────────────────────────────

let fetchMock: ReturnType<typeof vi.spyOn>

beforeEach(() => {
  fetchMock = vi.spyOn(global, 'fetch')
  // Удаляем Teleport-остатки в body от предыдущих тестов
  document.querySelectorAll('[role="dialog"]').forEach(el => el.remove())
})

afterEach(() => {
  vi.restoreAllMocks()
})

// ──────────────────────────── Tests ────────────────────────────

describe('AIConstructorWizard — visibility', () => {
  it('не отображается при монтировании', async () => {
    const w = mountWizard()
    await flushPromises()
    // Проверяем state компонента + отсутствие dialog в body
    expect((w.vm as any).isOpen).toBe(false)
    expect(qb('[role="dialog"]')).toBeNull()
    w.unmount()
  })

  it('открывается при получении события ai-constructor-open', async () => {
    const w = mountWizard()
    openWizard()
    await flushPromises()
    expect((w.vm as any).isOpen).toBe(true)
    // Teleport рендерит в body — ищем через qb()
    expect(qb('[role="dialog"]')).not.toBeNull()
    w.unmount()
  })

  it('закрывается при нажатии ESC', async () => {
    const w = mountWizard()
    openWizard()
    await flushPromises()
    expect(qb('[role="dialog"]')).not.toBeNull()

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    await flushPromises()
    expect((w.vm as any).isOpen).toBe(false)
    w.unmount()
  })

  it('закрывается при клике на кнопку закрытия ×', async () => {
    const w = mountWizard()
    openWizard()
    await flushPromises()

    const closeBtn = qb('[data-cy="wizard-close"]') as HTMLElement | null
    expect(closeBtn).not.toBeNull()
    closeBtn!.click()
    await flushPromises()

    expect((w.vm as any).isOpen).toBe(false)
    w.unmount()
  })
})

// ──────────────────────────────────────────────────────────────

describe('AIConstructorWizard — шаги', () => {
  it('первый шаг — upload', async () => {
    const w = mountWizard()
    openWizard()
    await flushPromises()

    expect(qb('[data-cy="step-upload"]')).not.toBeNull()
    expect(qb('[data-cy="step-results"]')).toBeNull()
    w.unmount()
  })

  it('кнопка "Анализировать" неактивна без загруженного файла', async () => {
    const w = mountWizard()
    openWizard()
    await flushPromises()

    const btn = qb('[data-cy="btn-analyze"]') as HTMLButtonElement | null
    expect(btn).not.toBeNull()
    expect(btn!.disabled || btn!.hasAttribute('disabled')).toBe(true)
    w.unmount()
  })

  it('после успешного fetch отображается шаг results', async () => {
    fetchMock.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        vertical: 'beauty',
        type: 'face_analysis',
        payload: { styleProfile: { faceType: 'oval' } },
        suggestions: [{ productId: 1, name: 'Тест', amountRub: 1500, inStock: true, confidence: 0.95 }],
        confidenceScore: 0.95,
        arLink: '/beauty/ar-preview/1',
        correlationId: 'test-corr-id',
      }),
    } as Response)

    const w = mountWizard()
    openWizard()
    await flushPromises()

    ;(w.vm as any).selectedFile = makeFile()
    ;(w.vm as any).previewUrl   = 'blob:test'
    await flushPromises()

    ;(qb('[data-cy="btn-analyze"]') as HTMLButtonElement)!.click()
    await flushPromises()

    expect(qb('[data-cy="step-results"]')).not.toBeNull()
    w.unmount()
  })

  it('при ошибке fetch показывает error-banner и возвращает шаг upload', async () => {
    fetchMock.mockResolvedValueOnce({ ok: false, status: 422 } as Response)

    const w = mountWizard()
    openWizard()
    await flushPromises()

    ;(w.vm as any).selectedFile = makeFile()
    await flushPromises()

    ;(qb('[data-cy="btn-analyze"]') as HTMLButtonElement)!.click()
    await flushPromises()

    expect(qb('[data-cy="step-upload"]')).not.toBeNull()
    expect(qb('[data-cy="error-banner"]')).not.toBeNull()
    w.unmount()
  })

  it('кнопка "Сохранить" переводит на шаг saved', async () => {
    fetchMock
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          vertical: 'beauty',
          type: 'face_analysis',
          payload: {},
          suggestions: [],
          confidenceScore: 0.9,
          correlationId: 'x',
        }),
      } as Response)
      .mockResolvedValueOnce({ ok: true, json: async () => ({ saved: true }) } as Response)

    const w = mountWizard()
    openWizard()
    await flushPromises()

    ;(w.vm as any).selectedFile = makeFile()
    await flushPromises()
    ;(qb('[data-cy="btn-analyze"]') as HTMLButtonElement)!.click()
    await flushPromises()

    ;(qb('[data-cy="btn-save"]') as HTMLButtonElement)!.click()
    await flushPromises()

    expect(qb('[data-cy="step-saved"]')).not.toBeNull()
    w.unmount()
  })
})

// ──────────────────────────────────────────────────────────────

describe('AIConstructorWizard — интеграция корзины', () => {
  it('addToCart диспатчит событие livewire:dispatch с данными товара', async () => {
    fetchMock.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        vertical: 'beauty',
        type: 'face_analysis',
        payload: {},
        suggestions: [{ productId: 77, name: 'Маска', amountRub: 800, inStock: true, confidence: 0.92 }],
        confidenceScore: 0.92,
        correlationId: 'x',
      }),
    } as Response)

    const dispatchSpy = vi.spyOn(window, 'dispatchEvent')

    const w = mountWizard()
    openWizard()
    await flushPromises()

    ;(w.vm as any).selectedFile = makeFile()
    await flushPromises()
    ;(qb('[data-cy="btn-analyze"]') as HTMLButtonElement)!.click()
    await flushPromises()

    const cartBtn = qb('[data-cy="btn-add-to-cart-77"]') as HTMLButtonElement | null
    expect(cartBtn).not.toBeNull()
    cartBtn!.click()

    const calls = dispatchSpy.mock.calls.map(c => c[0] as CustomEvent)
    const cartEvent = calls.find(e => e.type === 'livewire:dispatch')
    expect(cartEvent).toBeDefined()
    expect((cartEvent as CustomEvent).detail).toMatchObject({ name: 'cart-add', params: { productId: 77 } })

    w.unmount()
  })

  it('кнопка «В корзину» отсутствует для товара не в наличии', async () => {
    fetchMock.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        vertical: 'beauty',
        type: 'face_analysis',
        payload: {},
        suggestions: [{ productId: 99, name: 'Шампунь', amountRub: 400, inStock: false, confidence: 0.8 }],
        confidenceScore: 0.8,
        correlationId: 'x',
      }),
    } as Response)

    const w = mountWizard()
    openWizard()
    await flushPromises()

    ;(w.vm as any).selectedFile = makeFile()
    await flushPromises()
    ;(qb('[data-cy="btn-analyze"]') as HTMLButtonElement)!.click()
    await flushPromises()

    expect(qb('[data-cy="btn-add-to-cart-99"]')).toBeNull()
    w.unmount()
  })
})

// ──────────────────────────────────────────────────────────────

describe('AIConstructorWizard — ARIA / доступность', () => {
  it('dialog имеет role="dialog" и aria-modal=true', async () => {
    const w = mountWizard()
    openWizard()
    await flushPromises()

    // Teleport рендерит вне w.element — ищем через document.body
    const dialog = qb('[role="dialog"]')
    expect(dialog).not.toBeNull()
    expect(dialog!.getAttribute('aria-modal')).toBe('true')
    w.unmount()
  })

  it('диалог имеет aria-labelledby указывающий на существующий заголовок', async () => {
    const w = mountWizard()
    openWizard()
    await flushPromises()

    const dialog = qb('[role="dialog"]')
    expect(dialog).not.toBeNull()

    const labelId = dialog!.getAttribute('aria-labelledby')
    expect(labelId).toBeTruthy()
    // Заголовок с таким id должен существовать в document
    expect(document.getElementById(labelId!)).not.toBeNull()
    w.unmount()
  })
})
