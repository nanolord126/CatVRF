<template>
  <div class="analytics-dashboard-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <h3 class="text-lg font-bold text-gray-900">{{ dashboard.name }}</h3>
      <p class="text-sm text-gray-500">{{ dashboard.description }}</p>
    </div>

    <!-- Time Range -->
    <div class="mb-4">
      <select 
        v-model="selectedTimeRange"
        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
      >
        <option value="today">Today</option>
        <option value="week">This Week</option>
        <option value="month">This Month</option>
        <option value="quarter">This Quarter</option>
      </select>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-2 gap-3 mb-4">
      <div class="bg-blue-50 rounded-lg p-3">
        <p class="text-xs text-blue-700 mb-1">{{ metrics[0].label }}</p>
        <p class="text-xl font-bold text-blue-900">{{ metrics[0].value }}</p>
        <p :class="metrics[0].trend > 0 ? 'text-green-600' : 'text-red-600'" class="text-xs">
          {{ metrics[0].trend > 0 ? '+' : '' }}{{ metrics[0].trend }}%
        </p>
      </div>
      <div class="bg-green-50 rounded-lg p-3">
        <p class="text-xs text-green-700 mb-1">{{ metrics[1].label }}</p>
        <p class="text-xl font-bold text-green-900">{{ metrics[1].value }}</p>
        <p :class="metrics[1].trend > 0 ? 'text-green-600' : 'text-red-600'" class="text-xs">
          {{ metrics[1].trend > 0 ? '+' : '' }}{{ metrics[1].trend }}%
        </p>
      </div>
      <div class="bg-purple-50 rounded-lg p-3">
        <p class="text-xs text-purple-700 mb-1">{{ metrics[2].label }}</p>
        <p class="text-xl font-bold text-purple-900">{{ metrics[2].value }}</p>
        <p :class="metrics[2].trend > 0 ? 'text-green-600' : 'text-red-600'" class="text-xs">
          {{ metrics[2].trend > 0 ? '+' : '' }}{{ metrics[2].trend }}%
        </p>
      </div>
      <div class="bg-orange-50 rounded-lg p-3">
        <p class="text-xs text-orange-700 mb-1">{{ metrics[3].label }}</p>
        <p class="text-xl font-bold text-orange-900">{{ metrics[3].value }}</p>
        <p :class="metrics[3].trend > 0 ? 'text-green-600' : 'text-red-600'" class="text-xs">
          {{ metrics[3].trend > 0 ? '+' : '' }}{{ metrics[3].trend }}%
        </p>
      </div>
    </div>

    <!-- Chart Preview -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Trend</p>
      <div class="h-24 bg-gray-50 rounded-lg flex items-end justify-between px-2 pb-2">
        <div 
          v-for="(value, index) in chartData" 
          :key="index"
          class="w-6 bg-indigo-500 rounded-t"
          :style="{ height: value + '%' }"
        ></div>
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
        @click="exportData"
        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Export
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Metric {
  label: string
  value: string
  trend: number
}

interface Dashboard {
  name: string
  description: string
}

const props = defineProps<{
  dashboard: Dashboard
  metrics: Metric[]
}>()

const emit = defineEmits<{
  viewDetails: []
  exportData: []
}>()

const selectedTimeRange = ref('week')
const chartData = [30, 45, 35, 60, 50, 70, 55]

const viewDetails = () => {
  emit('viewDetails')
}

const exportData = () => {
  emit('exportData')
}
</script>
