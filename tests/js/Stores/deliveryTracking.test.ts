/**
 * deliveryTracking.test.ts — Vitest unit-тесты для Pinia store.
 *
 * Покрытие:
 *  - startTracking() — подписывается на Echo-канал, обновляет состояние
 *  - stopTracking()  — отписывается, сбрасывает состояние
 *  - setLocation()   — ручное обновление координат
 *  - hasLocation     — computed
 *  - formattedSpeed  — computed (форматирование км/ч)
 *  - connectionError — обрабатывается при ошибке Echo
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useDeliveryTrackingStore } from '@frontend/stores/deliveryTracking'

// ──────────────────────────── Mock window.Echo ────────────────────────────

type EchoListener = (data: unknown) => void
type EchoErrorHandler = (err: unknown) => void

interface MockChannel {
  listen: ReturnType<typeof vi.fn>
  error:  ReturnType<typeof vi.fn>
}

let lastChannel: MockChannel | null = null
let capturedListeners: Record<string, EchoListener> = {}
let capturedErrorHandler: EchoErrorHandler | null = null

const mockLeave = vi.fn()

function makeMockChannel(): MockChannel {
  const ch: MockChannel = {
    listen: vi.fn((eventName: string, handler: EchoListener) => {
      capturedListeners[eventName] = handler
      return ch
    }),
    error: vi.fn((handler: EchoErrorHandler) => {
      capturedErrorHandler = handler
      return ch
    }),
  }
  return ch
}

const mockEcho = {
  private: vi.fn((channelName: string) => {
    lastChannel = makeMockChannel()
    return lastChannel
  }),
  leave: mockLeave,
}

// Inject mock into global before any test
beforeEach(() => {
  vi.stubGlobal('Echo', mockEcho)
  setActivePinia(createPinia())
  capturedListeners = {}
  capturedErrorHandler = null
  lastChannel = null
  mockEcho.private.mockClear()
  mockLeave.mockClear()
})

afterEach(() => {
  vi.unstubAllGlobals()
})

// ──────────────────────────── Tests ────────────────────────────

describe('useDeliveryTrackingStore — initial state', () => {
  it('starts with null location and not tracking', () => {
    const store = useDeliveryTrackingStore()
    expect(store.courierLocation).toBeNull()
    expect(store.isTracking).toBe(false)
    expect(store.deliveryOrderId).toBeNull()
    expect(store.lastUpdatedAt).toBeNull()
    expect(store.connectionError).toBeNull()
  })

  it('hasLocation is false when no location', () => {
    const store = useDeliveryTrackingStore()
    expect(store.hasLocation).toBe(false)
  })

  it('formattedSpeed returns "—" when no location', () => {
    const store = useDeliveryTrackingStore()
    expect(store.formattedSpeed).toBe('—')
  })
})

// ──────────────────────────────────────────────────────────────

describe('startTracking()', () => {
  it('subscribes to private Echo channel delivery.{orderId}', () => {
    const store = useDeliveryTrackingStore()
    store.startTracking(42)

    expect(mockEcho.private).toHaveBeenCalledOnce()
    expect(mockEcho.private).toHaveBeenCalledWith('delivery.42')
  })

  it('sets isTracking = true and deliveryOrderId', () => {
    const store = useDeliveryTrackingStore()
    store.startTracking(7)

    expect(store.isTracking).toBe(true)
    expect(store.deliveryOrderId).toBe(7)
  })

  it('registers CourierLocationUpdated listener on the channel', () => {
    const store = useDeliveryTrackingStore()
    store.startTracking(1)

    expect(lastChannel!.listen).toHaveBeenCalledWith(
      'CourierLocationUpdated',
      expect.any(Function),
    )
  })

  it('updates courierLocation when event fires', () => {
    const store = useDeliveryTrackingStore()
    store.startTracking(1)

    // Simulate event from Echo
    const handler = capturedListeners['CourierLocationUpdated']
    expect(handler).toBeDefined()

    handler({ courierId: 5, lat: 55.75, lon: 37.61, speed: 28.5, bearing: 180, trackedAt: '2026-04-13T12:00:00Z' })

    expect(store.courierLocation).toEqual({ lat: 55.75, lon: 37.61, speed: 28.5, bearing: 180 })
    expect(store.lastUpdatedAt).toBe('2026-04-13T12:00:00Z')
  })

  it('sets hasLocation = true after first event', () => {
    const store = useDeliveryTrackingStore()
    store.startTracking(1)

    capturedListeners['CourierLocationUpdated']({ lat: 55.0, lon: 37.0, speed: 0, bearing: 0 })
    expect(store.hasLocation).toBe(true)
  })

  it('calling startTracking again with the same orderId does NOT re-subscribe', () => {
    const store = useDeliveryTrackingStore()
    store.startTracking(3)
    store.startTracking(3) // same id → skip

    expect(mockEcho.private).toHaveBeenCalledOnce()
  })

  it('switching to different orderId leaves old channel first', () => {
    const store = useDeliveryTrackingStore()
    store.startTracking(1)
    store.startTracking(2) // different id → leave 1, subscribe 2

    expect(mockLeave).toHaveBeenCalledWith('delivery.1')
    expect(mockEcho.private).toHaveBeenCalledWith('delivery.2')
  })
})

// ──────────────────────────────────────────────────────────────

describe('stopTracking()', () => {
  it('leaves the Echo channel', () => {
    const store = useDeliveryTrackingStore()
    store.startTracking(10)
    store.stopTracking()

    expect(mockLeave).toHaveBeenCalledWith('delivery.10')
  })

  it('resets all state to null/false', () => {
    const store = useDeliveryTrackingStore()
    store.startTracking(10)
    capturedListeners['CourierLocationUpdated']({ lat: 55.0, lon: 37.0, speed: 5, bearing: 90 })
    store.stopTracking()

    expect(store.isTracking).toBe(false)
    expect(store.courierLocation).toBeNull()
    expect(store.deliveryOrderId).toBeNull()
    expect(store.lastUpdatedAt).toBeNull()
    expect(store.connectionError).toBeNull()
  })
})

// ──────────────────────────────────────────────────────────────

describe('setLocation()', () => {
  it('manually updates courierLocation', () => {
    const store = useDeliveryTrackingStore()
    store.setLocation({ lat: 55.1, lon: 37.2, speed: 10, bearing: 45 })

    expect(store.courierLocation).toEqual({ lat: 55.1, lon: 37.2, speed: 10, bearing: 45 })
  })

  it('updates lastUpdatedAt to current time', () => {
    const store = useDeliveryTrackingStore()
    const before = Date.now()
    store.setLocation({ lat: 0, lon: 0, speed: 0, bearing: 0 })
    const after = Date.now()

    const ts = new Date(store.lastUpdatedAt!).getTime()
    expect(ts).toBeGreaterThanOrEqual(before)
    expect(ts).toBeLessThanOrEqual(after)
  })
})

// ──────────────────────────────────────────────────────────────

describe('computed: formattedSpeed', () => {
  it('returns formatted speed in km/h', () => {
    const store = useDeliveryTrackingStore()
    store.setLocation({ lat: 0, lon: 0, speed: 28.7, bearing: 0 })
    expect(store.formattedSpeed).toBe('29 км/ч')
  })

  it('rounds to nearest integer', () => {
    const store = useDeliveryTrackingStore()
    store.setLocation({ lat: 0, lon: 0, speed: 0.4, bearing: 0 })
    expect(store.formattedSpeed).toBe('0 км/ч')
  })
})

// ──────────────────────────────────────────────────────────────

describe('connectionError', () => {
  it('sets connectionError when Echo .error() fires', () => {
    const store = useDeliveryTrackingStore()
    store.startTracking(99)

    // Simulate Echo error callback
    capturedErrorHandler?.({ message: 'Unauthorized' })

    expect(store.connectionError).toBe('Ошибка подключения к трекингу')
  })

  it('connectionError is cleared on stopTracking', () => {
    const store = useDeliveryTrackingStore()
    store.startTracking(99)
    capturedErrorHandler?.({ message: 'err' })
    store.stopTracking()

    expect(store.connectionError).toBeNull()
  })
})
