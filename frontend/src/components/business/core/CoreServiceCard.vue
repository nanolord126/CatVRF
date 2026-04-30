<template>
  <div class="core-service-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <h3 class="text-lg font-bold text-gray-900">{{ service.name }}</h3>
      <p class="text-sm text-gray-500">{{ service.description }}</p>
    </div>

    <!-- Service Status -->
    <div class="mb-4">
      <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-700">Status</span>
        <span :class="getStatusClass()" class="text-xs font-medium px-2 py-1 rounded-full">
          {{ service.status }}
        </span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div 
          class="bg-indigo-600 h-2 rounded-full transition-all"
          :style="{ width: service.uptime + '%' }"
        ></div>
      </div>
      <p class="text-xs text-gray-500 mt-1">Uptime: {{ service.uptime }}%</p>
    </div>

    <!-- Metrics -->
    <div class="grid grid-cols-2 gap-3 mb-4">
      <div class="bg-gray-50 rounded-lg p-3">
        <p class="text-xs text-gray-600 mb-1">Requests/sec</p>
        <p class="text-lg font-bold text-gray-900">{{ service.metrics.rps }}</p>
      </div>
      <div class="bg-gray-50 rounded-lg p-3">
        <p class="text-xs text-gray-600 mb-1">Latency</p>
        <p class="text-lg font-bold text-gray-900">{{ service.metrics.latency }}ms</p>
      </div>
      <div class="bg-gray-50 rounded-lg p-3">
        <p class="text-xs text-gray-600 mb-1">Error Rate</p>
        <p class="text-lg font-bold" :class="service.metrics.errorRate > 5 ? 'text-red-600' : 'text-green-600'">
          {{ service.metrics.errorRate }}%
        </p>
      </div>
      <div class="bg-gray-50 rounded-lg p-3">
        <p class="text-xs text-gray-600 mb-1">Memory</p>
        <p class="text-lg font-bold text-gray-900">{{ service.metrics.memory }}MB</p>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="viewLogs"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        View Logs
      </button>
      <button 
        @click="restartService"
        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Restart
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Metrics {
  rps: number
  latency: number
  errorRate: number
  memory: number
}

interface CoreService {
  name: string
  description: string
  status: 'Running' | 'Stopped' | 'Degraded'
  uptime: number
  metrics: Metrics
}

const props = defineProps<{
  service: CoreService
}>()

const emit = defineEmits<{
  viewLogs: []
  restartService: []
}>()

const getStatusClass = () => {
  switch (props.service.status) {
    case 'Running':
      return 'bg-green-100 text-green-800'
    case 'Stopped':
      return 'bg-red-100 text-red-800'
    case 'Degraded':
      return 'bg-yellow-100 text-yellow-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const viewLogs = () => {
  emit('viewLogs')
}

const restartService = () => {
  emit('restartService')
}
</script>
