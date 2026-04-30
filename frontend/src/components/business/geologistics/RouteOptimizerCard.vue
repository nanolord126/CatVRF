<template>
  <div class="route-optimizer-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Route Optimizer</h2>
      <p class="text-gray-600">Optimize delivery routes for efficiency</p>
    </div>

    <!-- Route Points -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">Delivery Points</label>
      <div class="space-y-2 max-h-48 overflow-y-auto">
        <div 
          v-for="(point, index) in routePoints" 
          :key="index"
          class="flex items-center gap-3 p-2 bg-gray-50 rounded"
        >
          <span class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold">
            {{ index + 1 }}
          </span>
          <div class="flex-1">
            <p class="text-sm font-medium text-gray-900">{{ point.address }}</p>
            <p class="text-xs text-gray-500">{{ point.timeWindow }}</p>
          </div>
          <button 
            @click="removePoint(index)"
            class="text-red-500 hover:text-red-700"
          >
            ✕
          </button>
        </div>
      </div>
      <button 
        @click="addPoint"
        class="mt-2 w-full border-2 border-dashed border-gray-300 text-gray-500 py-2 rounded-lg text-sm hover:border-blue-500 hover:text-blue-500 transition-colors"
      >
        + Add Delivery Point
      </button>
    </div>

    <!-- Vehicle Type -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">Vehicle Type</label>
      <select 
        v-model="selectedVehicle"
        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
      >
        <option value="">Select vehicle...</option>
        <option value="van">Van (10 parcels max)</option>
        <option value="truck">Truck (50 parcels max)</option>
        <option value="bike">Bike (5 parcels max)</option>
      </select>
    </div>

    <!-- Optimization Options -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">Optimization Priority</label>
      <div class="space-y-2">
        <label class="flex items-center gap-2">
          <input type="radio" v-model="priority" value="time" class="text-blue-600">
          <span class="text-sm text-gray-700">Minimize Time</span>
        </label>
        <label class="flex items-center gap-2">
          <input type="radio" v-model="priority" value="distance" class="text-blue-600">
          <span class="text-sm text-gray-700">Minimize Distance</span>
        </label>
        <label class="flex items-center gap-2">
          <input type="radio" v-model="priority" value="cost" class="text-blue-600">
          <span class="text-sm text-gray-700">Minimize Cost</span>
        </label>
      </div>
    </div>

    <!-- Estimated Stats -->
    <div v-if="optimizedRoute" class="bg-blue-50 rounded-lg p-3 mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Estimated Route</p>
      <div class="space-y-1 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Total Distance:</span>
          <span class="font-medium">{{ optimizedRoute.distance }} km</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Estimated Time:</span>
          <span class="font-medium">{{ optimizedRoute.time }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Fuel Cost:</span>
          <span class="font-medium">{{ formatPrice(optimizedRoute.fuelCost) }}</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="optimize"
        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Optimize Route
      </button>
      <button 
        @click="exportRoute"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Export
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface RoutePoint {
  address: string
  timeWindow: string
}

interface OptimizedRoute {
  distance: number
  time: string
  fuelCost: number
}

const emit = defineEmits<{
  optimize: [points: RoutePoint[], vehicle: string, priority: string]
  export: []
}>()

const routePoints = ref<RoutePoint[]>([
  { address: '123 Main St', timeWindow: '09:00 - 10:00' },
  { address: '456 Oak Ave', timeWindow: '10:00 - 11:00' },
])

const selectedVehicle = ref('')
const priority = ref('time')
const optimizedRoute = ref<OptimizedRoute | null>(null)

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const addPoint = () => {
  routePoints.value.push({ address: '', timeWindow: '' })
}

const removePoint = (index: number) => {
  routePoints.value.splice(index, 1)
}

const optimize = () => {
  optimizedRoute.value = {
    distance: 45.2,
    time: '2h 15min',
    fuelCost: 1250,
  }
  emit('optimize', routePoints.value, selectedVehicle.value, priority.value)
}

const exportRoute = () => {
  emit('export')
}
</script>
