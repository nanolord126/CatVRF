<script setup lang="ts">
/**
 * CourierMarker — SVG-маркер курьера на карте.
 * Показывает иконку с направлением движения и пульсацию при движении.
 *
 * Props:
 *   lat, lon    — координаты (используются внешним map-контейнером через $el)
 *   bearing     — направление движения (0–360 deg)
 *   speed       — скорость км/ч (> 0 → показываем анимацию)
 *   vehicleType — тип транспорта
 */
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  lat?: number
  lon?: number
  bearing?: number
  speed?: number
  vehicleType?: 'car' | 'scooter' | 'bike' | 'walk'
  size?: number
}>(), {
  bearing: 0,
  speed: 0,
  vehicleType: 'walk',
  size: 40,
})

const isMoving = computed<boolean>(() => props.speed > 2)

const vehicleEmoji = computed<string>(() => {
  const map: Record<string, string> = { car: '🚗', scooter: '🛵', bike: '🚲', walk: '🚶' }
  return map[props.vehicleType] ?? '🚗'
})

// rotate arrow based on bearing
const arrowRotation = computed<string>(() => `rotate(${props.bearing}deg)`)
</script>

<template>
  <div
    class="courier-marker relative inline-flex items-center justify-center select-none"
    :style="{ width: `${size}px`, height: `${size}px` }"
    :aria-label="`Курьер — ${vehicleEmoji}, ${Math.round(speed)} км/ч`"
    role="img"
  >
    <!-- Pulse ring (only when moving) -->
    <span
      v-if="isMoving"
      class="absolute inset-0 rounded-full bg-indigo-500/30 animate-ping"
      aria-hidden="true"
    />

    <!-- Outer circle -->
    <div
      class="absolute inset-0 rounded-full border-2 transition-colors"
      :class="isMoving ? 'border-indigo-400 bg-indigo-600/20' : 'border-white/20 bg-black/40'"
      aria-hidden="true"
    />

    <!-- Direction arrow (rotates with bearing) -->
    <svg
      class="absolute -top-2 left-1/2 -translate-x-1/2 transition-transform duration-700 ease-out"
      :style="{ transform: `translateX(-50%) ${arrowRotation}`, transformOrigin: `50% calc(50% + ${size / 2}px)` }"
      width="10" height="12" viewBox="0 0 10 12"
      fill="none"
      aria-hidden="true"
    >
      <path d="M5 0L10 10H0L5 0Z" fill="rgb(129,140,248)" fill-opacity="0.9"/>
    </svg>

    <!-- Vehicle emoji -->
    <span
      class="relative z-10 leading-none"
      :style="{ fontSize: `${size * 0.45}px` }"
      aria-hidden="true"
    >{{ vehicleEmoji }}</span>
  </div>
</template>
