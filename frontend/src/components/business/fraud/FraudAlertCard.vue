<template>
  <div class="fraud-alert-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ alert.type }}</h3>
          <p class="text-sm text-gray-500">{{ alert.id }}</p>
        </div>
        <span :class="getSeverityClass(alert.severity)" class="px-3 py-1 rounded-full text-xs font-bold">
          {{ alert.severity }}
        </span>
      </div>
    </div>

    <!-- Alert Details -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Risk Score:</span>
          <span :class="getRiskScoreClass(alert.riskScore)" class="font-bold">
            {{ alert.riskScore }}%
          </span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Detected:</span>
          <span class="font-medium">{{ alert.detectedAt }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Entity:</span>
          <span class="font-medium">{{ alert.entityType }} #{{ alert.entityId }}</span>
        </div>
      </div>
      <!-- Risk Score Progress Bar -->
      <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
        <div 
          :class="getRiskBarClass(alert.riskScore)"
          class="h-2 rounded-full" 
          :style="{ width: alert.riskScore + '%' }"
        ></div>
      </div>
    </div>

    <!-- Indicators -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Risk Indicators</p>
      <div class="flex flex-wrap gap-2">
        <span 
          v-for="indicator in alert.indicators" 
          :key="indicator"
          class="px-2 py-1 bg-red-50 text-red-700 rounded text-xs"
        >
          {{ indicator }}
        </span>
      </div>
    </div>

    <!-- Description -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-1">Description</p>
      <p class="text-sm text-gray-600">{{ alert.description }}</p>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="investigate"
        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Investigate
      </button>
      <button 
        @click="dismiss"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Dismiss
      </button>
      <button 
        @click="block"
        class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Block
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface FraudAlert {
  id: string
  type: string
  severity: 'Low' | 'Medium' | 'High' | 'Critical'
  riskScore: number
  detectedAt: string
  entityType: string
  entityId: string
  indicators: string[]
  description: string
}

const props = defineProps<{
  alert: FraudAlert
}>()

const emit = defineEmits<{
  investigate: [alert: FraudAlert]
  dismiss: [alert: FraudAlert]
  block: [alert: FraudAlert]
}>()

const getSeverityClass = (severity: string) => {
  const classes = {
    'Low': 'bg-green-100 text-green-900',
    'Medium': 'bg-yellow-100 text-yellow-900',
    'High': 'bg-orange-100 text-orange-900',
    'Critical': 'bg-red-100 text-red-900',
  }
  return classes[severity as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const getRiskScoreClass = (score: number) => {
  if (score >= 80) return 'text-red-600'
  if (score >= 50) return 'text-orange-600'
  if (score >= 30) return 'text-yellow-600'
  return 'text-green-600'
}

const getRiskBarClass = (score: number) => {
  if (score >= 80) return 'bg-red-600'
  if (score >= 50) return 'bg-orange-600'
  if (score >= 30) return 'bg-yellow-600'
  return 'bg-green-600'
}

const investigate = () => {
  emit('investigate', props.alert)
}

const dismiss = () => {
  emit('dismiss', props.alert)
}

const block = () => {
  emit('block', props.alert)
}
</script>
