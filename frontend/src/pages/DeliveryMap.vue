<script setup lang="ts">
/**
 * DeliveryMap — страница реал-тайм отслеживания доставки.
 *
 * Использует Pinia store `useDeliveryTrackingStore` + Laravel Echo.
 * Курьер-маркер обновляется каждые 3 секунды через WebSocket.
 *
 * Для рендеринга карты требуется Leaflet (опциональная зависимость).
 * Если Leaflet не загружен — отображается стилизованный placeholder
 * с координатами и реал-тайм статусом.
 *
 * @see frontend/src/stores/deliveryTracking.ts
 * @see app/Services/GeotrackingService.php
 */
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useDeliveryTrackingStore } from '../stores/deliveryTracking'
import CourierMarker from '../components/CourierMarker.vue'

// ──────────────────────────── Props / Route ────────────────────────────

const props = defineProps<{
  deliveryOrderId: number
  /** Optional initial delivery info for instant render before WS arrives */
  initialStatus?: string
  initialCourier?: {
    name: string
    phone?: string
    vehicleType?: 'car' | 'scooter' | 'bike' | 'walk'
    rating?: number
  }
  estimatedMinutes?: number
}>()

// ──────────────────────────── Store ────────────────────────────

const tracking = useDeliveryTrackingStore()

// ──────────────────────────── Local state ────────────────────────────

const mapContainer = ref<HTMLDivElement | null>(null)
const leafletMap   = ref<unknown | null>(null)
const leafletMarker = ref<unknown | null>(null)
const isLeafletAvailable = ref<boolean>(false)
const connectionStatus = ref<'connecting' | 'live' | 'error'>('connecting')
const lastSeenAgo = ref<number>(0)        // seconds since last update
let lastSeenTimer: ReturnType<typeof setInterval> | null = null

// ──────────────────────────── Computed ────────────────────────────

const statusMeta = computed(() => {
  const map: Record<string, { label: string; color: string; dot: string }> = {
    pending:    { label: 'Ожидает курьера', color: 'text-yellow-400',  dot: 'bg-yellow-400' },
    assigned:   { label: 'Курьер назначен', color: 'text-blue-400',    dot: 'bg-blue-400' },
    picked_up:  { label: 'Заказ забран',    color: 'text-blue-400',    dot: 'bg-blue-400 animate-pulse' },
    in_transit: { label: 'В пути',          color: 'text-indigo-400',  dot: 'bg-indigo-400 animate-pulse' },
    delivered:  { label: 'Доставлен',       color: 'text-emerald-400', dot: 'bg-emerald-400' },
    failed:     { label: 'Ошибка доставки', color: 'text-red-400',     dot: 'bg-red-400' },
    cancelled:  { label: 'Отменён',         color: 'text-white/40',    dot: 'bg-white/20' },
  }
  return map[props.initialStatus ?? 'in_transit'] ?? map['in_transit']
})

const etaText = computed<string>(() => {
  if (!props.estimatedMinutes) return '...'
  if (props.estimatedMinutes < 1) return 'Прибывает'
  return `~${props.estimatedMinutes} мин`
})

// ──────────────────────────── Leaflet integration ────────────────────────────

async function tryInitLeaflet(): Promise<void> {
  try {
    // Dynamically import Leaflet if available
    const L = await import('leaflet' as string).catch(() => null)
    if (!L || !mapContainer.value) return

    isLeafletAvailable.value = true

    leafletMap.value = (L as any).map(mapContainer.value, {
      zoomControl: true,
      attributionControl: false,
      zoom: 15,
      center: tracking.courierLocation
        ? [tracking.courierLocation.lat, tracking.courierLocation.lon]
        : [55.751244, 37.618423],
    })

    ;(L as any).tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18,
    }).addTo(leafletMap.value)

    if (tracking.courierLocation) {
      addOrMoveLeafletMarker(L, tracking.courierLocation.lat, tracking.courierLocation.lon)
    }
  } catch {
    // Leaflet not available — use placeholder
    isLeafletAvailable.value = false
  }
}

function addOrMoveLeafletMarker(L: unknown, lat: number, lon: number): void {
  if (!leafletMap.value) return
  const point = [(L as any).latLng(lat, lon)]

  if (leafletMarker.value) {
    ;(leafletMarker.value as any).setLatLng(point[0])
    ;(leafletMap.value as any).panTo(point[0], { animate: true, duration: 0.8 })
  } else {
    leafletMarker.value = (L as any).marker(point[0]).addTo(leafletMap.value)
  }
}

// ──────────────────────────── Lifecycle ────────────────────────────

onMounted(async () => {
  tracking.startTracking(props.deliveryOrderId)
  connectionStatus.value = 'live'
  await tryInitLeaflet()

  lastSeenTimer = setInterval(() => {
    if (tracking.lastUpdatedAt) {
      const diff = Math.round((Date.now() - new Date(tracking.lastUpdatedAt).getTime()) / 1000)
      lastSeenAgo.value = diff
    }
  }, 1000)
})

onUnmounted(() => {
  tracking.stopTracking()
  if (lastSeenTimer) clearInterval(lastSeenTimer)
})

// Update map when location changes
watch(() => tracking.courierLocation, async (loc) => {
  if (!loc) return
  if (leafletMap.value) {
    const L = await import('leaflet' as string).catch(() => null)
    if (L) addOrMoveLeafletMarker(L, loc.lat, loc.lon)
  }
})

watch(() => tracking.connectionError, (err) => {
  if (err) connectionStatus.value = 'error'
})
</script>

<template>
  <div class="flex flex-col h-full min-h-screen bg-[#080810] text-white" data-cy="delivery-map">

    <!-- ─── Header ─── -->
    <header class="flex items-center gap-3 px-4 py-3 bg-black/40 backdrop-blur-xl border-b border-white/5 sticky top-0 z-20">
      <a
        href="/orders"
        class="w-8 h-8 rounded-full bg-white/5 hover:bg-white/10 flex items-center justify-center transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400 shrink-0"
        aria-label="Назад к заказам"
      >
        <svg class="w-4 h-4 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
      </a>
      <div class="flex-1 min-w-0">
        <p class="text-xs text-white/40">Заказ #{{ deliveryOrderId }}</p>
        <h1 class="text-sm font-semibold text-white truncate">Отслеживание доставки</h1>
      </div>
      <!-- Connection indicator -->
      <div class="flex items-center gap-1.5" :aria-label="`Статус подключения: ${connectionStatus}`">
        <span
          class="w-1.5 h-1.5 rounded-full"
          :class="{
            'bg-emerald-400 animate-pulse': connectionStatus === 'live',
            'bg-yellow-400 animate-pulse': connectionStatus === 'connecting',
            'bg-red-400': connectionStatus === 'error',
          }"
          aria-hidden="true"
        />
        <span class="text-[10px] text-white/40">
          <span v-if="connectionStatus === 'live'">Live</span>
          <span v-else-if="connectionStatus === 'connecting'">Подключение...</span>
          <span v-else>Ошибка</span>
        </span>
      </div>
    </header>

    <!-- ─── Map / Placeholder ─── -->
    <div class="relative flex-1 min-h-[50vh]">

      <!-- Leaflet map container (filled by Leaflet if available) -->
      <div
        ref="mapContainer"
        class="absolute inset-0"
        :class="{ 'hidden': !isLeafletAvailable }"
        aria-label="Карта с положением курьера"
        role="region"
      />

      <!-- Placeholder (no Leaflet / waiting for location) -->
      <div
        v-if="!isLeafletAvailable"
        class="absolute inset-0 flex flex-col items-center justify-center bg-[#0d0d1a] overflow-hidden"
        data-cy="map-fallback"
        aria-label="Карта недоступна — показаны данные курьера"
        role="region"
      >
        <!-- Grid pattern -->
        <div class="absolute inset-0 opacity-[0.03]"
             style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 40px 40px;"
             aria-hidden="true"/>

        <!-- Glow center -->
        <div class="absolute w-64 h-64 rounded-full bg-indigo-600/5 blur-3xl" aria-hidden="true"/>

        <!-- Courier marker (centered placeholder) -->
        <div v-if="tracking.hasLocation" class="relative z-10 flex flex-col items-center gap-4">
          <CourierMarker
            :lat="tracking.courierLocation!.lat"
            :lon="tracking.courierLocation!.lon"
            :bearing="tracking.courierLocation!.bearing"
            :speed="tracking.courierLocation!.speed"
            :vehicle-type="initialCourier?.vehicleType ?? 'car'"
            :size="56"
          />
          <div class="text-center">
            <p class="text-xs font-medium text-white/70">{{ tracking.formattedSpeed }}</p>
            <p class="text-[10px] text-white/30 mt-0.5">
              {{ tracking.courierLocation!.lat.toFixed(5) }}, {{ tracking.courierLocation!.lon.toFixed(5) }}
            </p>
          </div>
        </div>

        <div v-else class="relative z-10 flex flex-col items-center gap-3 text-center px-8" data-cy="map-placeholder">
          <div class="w-10 h-10 rounded-2xl bg-indigo-600/10 flex items-center justify-center" aria-hidden="true">
            <svg class="w-5 h-5 text-indigo-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
          </div>
          <p class="text-xs text-white/40">Ожидаем координаты курьера...</p>
        </div>
      </div>
    </div>

    <!-- ─── Bottom info card ─── -->
    <div class="bg-[#0d0d1a]/95 backdrop-blur-xl border-t border-white/5 px-4 pt-4 pb-6 space-y-4">

      <!-- Status + ETA -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full shrink-0" :class="statusMeta.dot" aria-hidden="true"/>
          <span :class="['text-sm font-semibold', statusMeta.color]">{{ statusMeta.label }}</span>
        </div>
        <div class="text-right" aria-live="polite" data-cy="eta">
          <p class="text-lg font-bold text-white">{{ etaText }}</p>
          <p v-if="lastSeenAgo > 0" class="text-[10px] text-white/30">Обновлено {{ lastSeenAgo }}с назад</p>
        </div>
      </div>

      <!-- Connection error -->
      <div
        v-if="tracking.connectionError"
        role="alert"
        class="p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-xs text-red-300"
      >
        {{ tracking.connectionError }}
      </div>

      <!-- Courier info -->
      <div v-if="initialCourier" class="flex items-center gap-3 p-3 bg-white/[0.03] rounded-2xl border border-white/5" data-cy="courier-card">
        <div class="w-10 h-10 rounded-xl bg-indigo-600/20 flex items-center justify-center shrink-0 text-lg" aria-hidden="true">
          {{ initialCourier.vehicleType === 'bike' ? '🚲' : initialCourier.vehicleType === 'scooter' ? '🛵' : '🚗' }}
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-white truncate">{{ initialCourier.name }}</p>
          <div v-if="initialCourier.rating" class="flex items-center gap-1 mt-0.5">
            <svg class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
            <span class="text-xs text-white/50">{{ initialCourier.rating }}</span>
          </div>
        </div>
        <a
          v-if="initialCourier.phone"
          :href="`tel:${initialCourier.phone}`"
          data-cy="courier-phone"
          class="w-9 h-9 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 flex items-center justify-center transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 shrink-0"
          :aria-label="`Позвонить курьеру ${initialCourier.name}`"
        >
          <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.947V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
        </a>
      </div>

    </div>
  </div>
</template>
