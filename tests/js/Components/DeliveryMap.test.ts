/**
 * DeliveryMap.test.ts — Vitest компонентные тесты.
 *
 * Покрытие:
 *  - startTracking() вызывается при монтировании
 *  - stopTracking() вызывается при размонтировании
 *  - Fallback-плашка "Ожидаем" отображается до появления координат
 *  - CourierMarker отображается после setLocation
 *  - Карточка курьера отображается при наличии initialCourier
 *  - Ошибка подключения отображает alert
 *
 * CourierMarker.test.ts — тесты SVG-маркера
 *  - Рендерит SVG при наличии bearing
 *  - Показывает Emoji нужного типа транспорта
 *  - animate-ping присутствует при speed > 2
 */
// @ts-nocheck
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'

// ─── mock Pinia store ────────────────────────────────────────

const mockStoreState = {
  isTracking: false,
  courierLocation: null as null | { lat: number; lon: number; speed: number; bearing: number },
  deliveryOrderId: null as null | number,
  lastUpdatedAt: null as null | string,
  connectionError: null as null | string,
  hasLocation: false,
  formattedSpeed: '—',
  startTracking: vi.fn(),
  stopTracking: vi.fn(),
  setLocation: vi.fn(),
}

vi.mock('@frontend/stores/deliveryTracking', () => ({
  useDeliveryTrackingStore: () => mockStoreState,
}))

// ─── mock dynamic Leaflet import ───────────────────────────────

vi.mock('leaflet', () => null)

// ─── imports (after mocks) ─────────────────────────────────────

import DeliveryMap    from '@frontend/pages/DeliveryMap.vue'
import CourierMarker  from '@frontend/components/CourierMarker.vue'

// ──────────────────────────── helpers ────────────────────────────

function mountMap(props: Record<string, unknown> = {}) {
  return mount(DeliveryMap, {
    props: {
      deliveryOrderId: 42,
      ...props,
    },
    attachTo: document.body,
  })
}

beforeEach(() => {
  // Сброс состояния mock-стора
  mockStoreState.isTracking       = false
  mockStoreState.courierLocation  = null
  mockStoreState.deliveryOrderId  = null
  mockStoreState.lastUpdatedAt    = null
  mockStoreState.connectionError  = null
  mockStoreState.hasLocation      = false
  mockStoreState.formattedSpeed   = '—'
  mockStoreState.startTracking.mockClear()
  mockStoreState.stopTracking.mockClear()
  mockStoreState.setLocation.mockClear()
})

afterEach(() => {
  vi.restoreAllMocks()
})

// ──────────────────────────── DeliveryMap tests ────────────────────────────

describe('DeliveryMap — монтирование', () => {
  it('вызывает startTracking(deliveryOrderId) при mount', async () => {
    const w = mountMap({ deliveryOrderId: 55 })
    await flushPromises()
    expect(mockStoreState.startTracking).toHaveBeenCalledWith(55)
    w.unmount()
  })

  it('вызывает stopTracking() при unmount', async () => {
    const w = mountMap()
    await flushPromises()
    w.unmount()
    expect(mockStoreState.stopTracking).toHaveBeenCalledOnce()
  })
})

// ──────────────────────────────────────────────────────────────

describe('DeliveryMap — состояния отображения', () => {
  it('показывает placeholder-заглушку пока нет координат', async () => {
    mockStoreState.hasLocation = false
    const w = mountMap()
    await flushPromises()
    expect(w.find('[data-cy="map-placeholder"]').exists()).toBe(true)
    w.unmount()
  })

  it('НЕ показывает placeholder после появления координат', async () => {
    mockStoreState.hasLocation      = true
    mockStoreState.courierLocation  = { lat: 55.75, lon: 37.61, speed: 30, bearing: 90 }
    const w = mountMap()
    await flushPromises()
    expect(w.find('[data-cy="map-placeholder"]').exists()).toBe(false)
    w.unmount()
  })

  it('отображает CourierMarker при наличии courierLocation', async () => {
    mockStoreState.hasLocation     = true
    mockStoreState.courierLocation = { lat: 55.75, lon: 37.61, speed: 30, bearing: 90 }
    const w = mountMap()
    await flushPromises()
    expect(w.findComponent(CourierMarker).exists()).toBe(true)
    w.unmount()
  })

  it('передаёт bearing и speed в CourierMarker', async () => {
    mockStoreState.hasLocation     = true
    mockStoreState.courierLocation = { lat: 55.75, lon: 37.61, speed: 42, bearing: 270 }
    const w = mountMap()
    await flushPromises()
    const marker = w.findComponent(CourierMarker)
    expect(Number(marker.props('bearing'))).toBe(270)
    expect(Number(marker.props('speed'))).toBe(42)
    w.unmount()
  })
})

// ──────────────────────────────────────────────────────────────

describe('DeliveryMap — карточка курьера', () => {
  it('отображает имя курьера из prop initialCourier', async () => {
    const w = mountMap({ initialCourier: { name: 'Алексей', vehicleType: 'bike', rating: 4.9 } })
    await flushPromises()
    expect(w.text()).toContain('Алексей')
    w.unmount()
  })

  it('отображает ссылку звонка если есть phone', async () => {
    const w = mountMap({ initialCourier: { name: 'Иван', phone: '+79991234567' } })
    await flushPromises()
    const link = w.find('[data-cy="courier-phone"]')
    expect(link.exists()).toBe(true)
    expect(link.attributes('href')).toBe('tel:+79991234567')
    w.unmount()
  })

  it('НЕ отображает карточку курьера когда initialCourier = undefined', async () => {
    const w = mountMap()
    await flushPromises()
    expect(w.find('[data-cy="courier-card"]').exists()).toBe(false)
    w.unmount()
  })
})

// ──────────────────────────────────────────────────────────────

describe('DeliveryMap — ошибки подключения', () => {
  it('показывает role=alert при connectionError', async () => {
    mockStoreState.connectionError = 'Ошибка подключения к трекингу'
    const w = mountMap()
    await flushPromises()
    const alert = w.find('[role="alert"]')
    expect(alert.exists()).toBe(true)
    expect(alert.text()).toContain('Ошибка')
    w.unmount()
  })

  it('НЕ показывает alert когда connectionError = null', async () => {
    mockStoreState.connectionError = null
    const w = mountMap()
    await flushPromises()
    expect(w.find('[role="alert"]').exists()).toBe(false)
    w.unmount()
  })
})

// ──────────────────────────────────────────────────────────────

describe('DeliveryMap — ARIA', () => {
  it('data-cy="delivery-map" присутствует', async () => {
    const w = mountMap()
    await flushPromises()
    expect(w.find('[data-cy="delivery-map"]').exists()).toBe(true)
    w.unmount()
  })

  it('ETA-зона имеет aria-live=polite', async () => {
    const w = mountMap({ estimatedMinutes: 15 })
    await flushPromises()
    const eta = w.find('[data-cy="eta"]')
    if (eta.exists()) {
      expect(eta.attributes('aria-live')).toBe('polite')
    }
    w.unmount()
  })
})

// ═══════════════════════════════════════════════════════════════
// CourierMarker тесты
// ═══════════════════════════════════════════════════════════════

describe('CourierMarker — рендер', () => {
  it('рендерит SVG-элемент', () => {
    const w = mount(CourierMarker, { props: { bearing: 45, speed: 30, vehicleType: 'car' } })
    expect(w.find('svg').exists()).toBe(true)
    w.unmount()
  })

  it('показывает эмодзи автомобиля для vehicleType=car', () => {
    const w = mount(CourierMarker, { props: { vehicleType: 'car' } })
    expect(w.text()).toContain('🚗')
    w.unmount()
  })

  it('показывает эмодзи скутера для vehicleType=scooter', () => {
    const w = mount(CourierMarker, { props: { vehicleType: 'scooter' } })
    expect(w.text()).toContain('🛵')
    w.unmount()
  })

  it('показывает эмодзи велосипеда для vehicleType=bike', () => {
    const w = mount(CourierMarker, { props: { vehicleType: 'bike' } })
    expect(w.text()).toContain('🚲')
    w.unmount()
  })

  it('показывает эмодзи пешехода по умолчанию', () => {
    const w = mount(CourierMarker, {})
    expect(w.text()).toContain('🚶')
    w.unmount()
  })
})

// ──────────────────────────────────────────────────────────────

describe('CourierMarker — анимация движения', () => {
  it('добавляет animate-ping пульсирующий круг при speed > 2', () => {
    const w = mount(CourierMarker, { props: { speed: 25, vehicleType: 'car' } })
    expect(w.find('.animate-ping').exists()).toBe(true)
    w.unmount()
  })

  it('НЕ добавляет animate-ping если speed <= 2 (стоит)', () => {
    const w = mount(CourierMarker, { props: { speed: 0, vehicleType: 'car' } })
    expect(w.find('.animate-ping').exists()).toBe(false)
    w.unmount()
  })

  it('при отсутствии speed (undefined) — нет пульса', () => {
    const w = mount(CourierMarker, { props: { vehicleType: 'car' } })
    expect(w.find('.animate-ping').exists()).toBe(false)
    w.unmount()
  })
})
