<template>
  <div class="kpi-card bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
    <!-- Loading State -->
    <div v-if="loading" class="animate-pulse">
      <div class="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
      <div class="h-8 bg-gray-200 rounded w-1/2 mb-2"></div>
      <div class="h-4 bg-gray-200 rounded w-1/4"></div>
    </div>

    <!-- Content -->
    <div v-else>
      <!-- Header with Label and Icon -->
      <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-medium text-gray-600">{{ kpi.label }}</span>
        <div v-if="kpi.icon" class="text-gray-400">
          <span class="text-xl">{{ kpi.icon }}</span>
        </div>
      </div>

      <!-- Value -->
      <div class="mb-2">
        <span class="text-2xl font-bold text-gray-900">{{ formattedValue }}</span>
      </div>

      <!-- Trend and Growth Rate -->
      <div class="flex items-center gap-2">
        <span :class="trendColor" class="text-sm font-medium flex items-center gap-1">
          <span>{{ trendIcon }}</span>
          <span v-if="kpi.growth_rate !== undefined">
            {{ Math.abs(kpi.growth_rate).toFixed(1) }}%
          </span>
        </span>
        <span v-if="kpi.previous_value !== undefined" class="text-xs text-gray-500">
          vs {{ formatValue(kpi.previous_value, kpi.format) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { KPICard as KPICardType } from '@/types/analytics'

interface Props {
  kpi: KPICardType
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  loading: false
})

const formatValue = (value: number, format: string): string => {
  switch (format) {
    case 'currency':
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
      }).format(value)
    case 'percentage':
      return `${value.toFixed(1)}%`
    case 'number':
      return new Intl.NumberFormat('en-US').format(value)
    default:
      return value.toString()
  }
}

const formattedValue = computed(() => formatValue(props.kpi.value, props.kpi.format))

const trendIcon = computed(() => {
  switch (props.kpi.trend) {
    case 'up':
      return '↑'
    case 'down':
      return '↓'
    default:
      return '→'
  }
})

const trendColor = computed(() => {
  switch (props.kpi.trend) {
    case 'up':
      return 'text-green-600'
    case 'down':
      return 'text-red-600'
    default:
      return 'text-gray-600'
  }
})
</script>
