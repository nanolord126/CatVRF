/**
 * Delivery Tracking Pinia Store
 * CatVRF — Real-time courier geo-tracking via Laravel Echo + WebSocket
 *
 * Uses: window.Echo (configured in resources/js/echo.js)
 * Channel: private delivery.{deliveryOrderId}
 * Event: CourierLocationUpdated
 */
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

// ──────────────────────────── Types ────────────────────────────

export interface CourierLocation {
    lat: number
    lon: number
    speed: number   // km/h
    bearing: number // degrees 0–360
}

export interface CourierLocationUpdatedEvent {
    courierId: number
    lat: number
    lon: number
    speed: number
    bearing: number
    trackedAt: string
}

// ──────────────────────────── Store ────────────────────────────

export const useDeliveryTrackingStore = defineStore('deliveryTracking', () => {
    // State
    const courierLocation = ref<CourierLocation | null>(null)
    const isTracking = ref<boolean>(false)
    const deliveryOrderId = ref<number | null>(null)
    const lastUpdatedAt = ref<string | null>(null)
    const connectionError = ref<string | null>(null)

    // Computed
    const hasLocation = computed<boolean>(() => courierLocation.value !== null)

    const formattedSpeed = computed<string>(() => {
        if (!courierLocation.value) return '—'
        return `${Math.round(courierLocation.value.speed)} км/ч`
    })

    // Actions

    /**
     * Begin listening for courier location updates on a private Echo channel.
     * Requires window.Echo to be initialised (resources/js/echo.js).
     */
    function startTracking(orderId: number): void {
        if (isTracking.value && deliveryOrderId.value === orderId) {
            // Already tracking this order
            return
        }

        // Clean up previous subscription if switching orders
        if (isTracking.value) {
            stopTracking()
        }

        deliveryOrderId.value = orderId
        isTracking.value = true
        connectionError.value = null

        try {
            window.Echo
                .private(`delivery.${orderId}`)
                .listen('CourierLocationUpdated', (e: CourierLocationUpdatedEvent) => {
                    courierLocation.value = {
                        lat: e.lat,
                        lon: e.lon,
                        speed: e.speed ?? 0,
                        bearing: e.bearing ?? 0,
                    }
                    lastUpdatedAt.value = e.trackedAt ?? new Date().toISOString()
                })
                .error((err: unknown) => {
                    console.error('[DeliveryTracking] Echo error:', err)
                    connectionError.value = 'Ошибка подключения к трекингу'
                })
        } catch (err) {
            console.error('[DeliveryTracking] Failed to start:', err)
            connectionError.value = 'Не удалось запустить трекинг'
            isTracking.value = false
        }
    }

    /**
     * Stop tracking and leave the Echo channel.
     */
    function stopTracking(): void {
        if (deliveryOrderId.value !== null) {
            try {
                window.Echo.leave(`delivery.${deliveryOrderId.value}`)
            } catch (_) {
                // Ignore cleanup errors
            }
        }

        isTracking.value = false
        courierLocation.value = null
        deliveryOrderId.value = null
        lastUpdatedAt.value = null
        connectionError.value = null
    }

    /**
     * Manually update the courier location (useful for optimistic updates / testing).
     */
    function setLocation(location: CourierLocation): void {
        courierLocation.value = location
        lastUpdatedAt.value = new Date().toISOString()
    }

    return {
        // State
        courierLocation,
        isTracking,
        deliveryOrderId,
        lastUpdatedAt,
        connectionError,
        // Computed
        hasLocation,
        formattedSpeed,
        // Actions
        startTracking,
        stopTracking,
        setLocation,
    }
})
