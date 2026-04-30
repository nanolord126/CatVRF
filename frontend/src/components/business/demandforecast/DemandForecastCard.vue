<template>
  <div class="demand-forecast-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ forecast.productName }}</h3>
          <p class="text-sm text-gray-500">{{ forecast.sku }}</p>
        </div>
        <span :class="getAccuracyClass(forecast.accuracy)" class="px-3 py-1 rounded-full text-xs font-bold">
          {{ forecast.accuracy }}% Accuracy
        </span>
      </div>
    </div>

    <!-- Forecast Period -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-1">Forecast Period</p>
      <p class="text-sm text-gray-600">{{ forecast.period }}</p>
    </div>

    <!-- Forecast Stats -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Predicted Demand:</span>
          <span class="font-bold text-teal-600">{{ forecast.predictedDemand.toLocaleString() }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Current Stock:</span>
          <span class="font-medium">{{ forecast.currentStock.toLocaleString() }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Stockout Risk:</span>
          <span :class="getRiskClass(forecast.stockoutRisk)" class="font-medium">
            {{ forecast.stockoutRisk }}%
          </span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Reorder Suggested:</span>
          <span :class="forecast.needsReorder ? 'font-bold text-red-600' : 'font-medium text-green-600'">
            {{ forecast.needsReorder ? 'Yes (' + forecast.reorderQty + ')' : 'No' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Trend -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Demand Trend</p>
      <div class="flex items-center gap-2">
        <span :class="getTrendClass(forecast.trend)" class="text-2xl">
          {{ getTrendIcon(forecast.trend) }}
        </span>
        <div>
          <p class="font-medium text-gray-900">{{ forecast.trend }}</p>
          <p class="text-xs text-gray-500">{{ forecast.trendChange }} vs last period</p>
        </div>
      </div>
    </div>

    <!-- Factors -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-1">Key Influencing Factors</p>
      <div class="flex flex-wrap gap-2">
        <span 
          v-for="factor in forecast.factors" 
          :key="factor"
          class="px-2 py-1 bg-teal-50 text-teal-700 rounded text-xs"
        >
          {{ factor }}
        </span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="viewDetails"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        View Details
      </button>
      <button 
        @click="adjustForecast"
        class="flex-1 bg-teal-600 hover:bg-teal-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Adjust
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface DemandForecast {
  id: string
  productName: string
  sku: string
  period: string
  predictedDemand: number
  currentStock: number
  stockoutRisk: number
  needsReorder: boolean
  reorderQty: number
  accuracy: number
  trend: 'Increasing' | 'Stable' | 'Decreasing'
  trendChange: string
  factors: string[]
}

const props = defineProps<{
  forecast: DemandForecast
}>()

const emit = defineEmits<{
  viewDetails: [forecast: DemandForecast]
  adjustForecast: [forecast: DemandForecast]
}>()

const getAccuracyClass = (accuracy: number) => {
  if (accuracy >= 90) return 'bg-green-100 text-green-900'
  if (accuracy >= 80) return 'bg-blue-100 text-blue-900'
  if (accuracy >= 70) return 'bg-yellow-100 text-yellow-900'
  return 'bg-red-100 text-red-900'
}

const getRiskClass = (risk: number) => {
  if (risk >= 70) return 'text-red-600'
  if (risk >= 40) return 'text-yellow-600'
  return 'text-green-600'
}

const getTrendClass = (trend: string) => {
  const classes = {
    'Increasing': 'text-green-600',
    'Stable': 'text-gray-600',
    'Decreasing': 'text-red-600',
  }
  return classes[trend as keyof typeof classes] || 'text-gray-600'
}

const getTrendIcon = (trend: string) => {
  const icons = {
    'Increasing': '📈',
    'Stable': '➡️',
    'Decreasing': '📉',
  }
  return icons[trend as keyof typeof icons] || '➡️'
}

const viewDetails = () => {
  emit('viewDetails', props.forecast)
}

const adjustForecast = () => {
  emit('adjustForecast', props.forecast)
}
</script>
