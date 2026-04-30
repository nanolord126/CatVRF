<template>
  <div class="insights-widget bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <!-- Loading State -->
    <div v-if="loading" class="animate-pulse">
      <div class="h-4 bg-gray-200 rounded w-1/3 mb-4"></div>
      <div class="space-y-3">
        <div class="h-16 bg-gray-200 rounded"></div>
        <div class="h-16 bg-gray-200 rounded"></div>
        <div class="h-16 bg-gray-200 rounded"></div>
      </div>
    </div>

    <!-- Content -->
    <div v-else>
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">AI Insights</h3>
        <span class="text-xs text-gray-500">{{ displayedInsights.length }} insights</span>
      </div>

      <!-- No Insights -->
      <div v-if="displayedInsights.length === 0" class="text-center py-8">
        <div class="text-gray-400 text-4xl mb-2">💡</div>
        <p class="text-gray-500">No insights available for this period</p>
      </div>

      <!-- Insights List -->
      <div v-else class="space-y-3">
        <div
          v-for="insight in displayedInsights"
          :key="`${insight.type}-${insight.title}`"
          :class="[
            'border-l-4 rounded-lg p-4',
            getSeverityColor(insight.severity)
          ]"
        >
          <!-- Header -->
          <div class="flex items-start justify-between mb-2">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-semibold uppercase tracking-wide" :class="getSeverityTextColor(insight.severity)">
                  {{ insight.severity }}
                </span>
                <span v-if="insight.ml_model" class="text-xs text-gray-500">
                  • {{ insight.ml_model }}
                </span>
              </div>
              <h4 class="font-medium text-gray-900">{{ insight.title }}</h4>
            </div>
          </div>

          <!-- Message -->
          <p class="text-sm text-gray-700 mb-3">{{ insight.message }}</p>

          <!-- Metrics -->
          <div v-if="Object.keys(insight.metrics).length > 0" class="flex flex-wrap gap-2 mb-3">
            <span
              v-for="(value, key) in insight.metrics"
              :key="key"
              class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700"
            >
              {{ key }}: {{ formatMetricValue(value) }}
            </span>
          </div>

          <!-- Recommendations -->
          <div v-if="insight.recommendations.length > 0">
            <p class="text-xs font-medium text-gray-600 mb-1">Recommendations:</p>
            <ul class="text-xs text-gray-700 space-y-1">
              <li v-for="(rec, index) in insight.recommendations" :key="index" class="flex items-start gap-2">
                <span class="text-gray-400">•</span>
                <span>{{ rec }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Show More/Less -->
      <div v-if="insights.length > maxItems" class="mt-4 text-center">
        <button
          @click="showAll = !showAll"
          class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
        >
          {{ showAll ? 'Show less' : `Show all ${insights.length} insights` }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { SellerInsight } from '@/types/analytics'

interface Props {
  insights: SellerInsight[]
  loading?: boolean
  maxItems?: number
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  maxItems: 5
})

const showAll = ref(false)

const displayedInsights = computed(() => {
  if (showAll.value) {
    return props.insights
  }
  return props.insights.slice(0, props.maxItems)
})

const getSeverityColor = (severity: string): string => {
  switch (severity) {
    case 'critical':
      return 'border-red-500 bg-red-50'
    case 'warning':
      return 'border-yellow-500 bg-yellow-50'
    case 'opportunity':
      return 'border-green-500 bg-green-50'
    default:
      return 'border-gray-200 bg-gray-50'
  }
}

const getSeverityTextColor = (severity: string): string => {
  switch (severity) {
    case 'critical':
      return 'text-red-800'
    case 'warning':
      return 'text-yellow-800'
    case 'opportunity':
      return 'text-green-800'
    default:
      return 'text-gray-800'
  }
}

const formatMetricValue = (value: number): string => {
  if (value >= 1000000) {
    return `${(value / 1000000).toFixed(1)}M`
  }
  if (value >= 1000) {
    return `${(value / 1000).toFixed(1)}K`
  }
  return value.toFixed(1)
}
</script>
