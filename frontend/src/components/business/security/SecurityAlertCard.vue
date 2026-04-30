<template>
  <div class="security-alert-card bg-white rounded-xl shadow-md p-6">
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
          <span class="text-gray-600">Detected:</span>
          <span class="font-medium">{{ alert.detectedAt }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Source:</span>
          <span class="font-medium">{{ alert.source }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">IP Address:</span>
          <span class="font-medium">{{ alert.ipAddress }}</span>
        </div>
      </div>
    </div>

    <!-- Description -->
    <div class="mb-4">
      <p class="text-sm text-gray-600">{{ alert.description }}</p>
    </div>

    <!-- Affected User -->
    <div v-if="alert.affectedUser" class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-1">Affected User</p>
      <p class="text-sm text-gray-600">{{ alert.affectedUser }}</p>
    </div>

    <!-- Actions Taken -->
    <div v-if="alert.actionsTaken && alert.actionsTaken.length" class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-1">Actions Taken</p>
      <div class="space-y-1">
        <div 
          v-for="action in alert.actionsTaken" 
          :key="action"
          class="flex items-center gap-2 text-sm text-gray-600"
        >
          <span class="text-green-500">✓</span>
          <span>{{ action }}</span>
        </div>
      </div>
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
    </div>
  </div>
</template>

<script setup lang="ts">
interface SecurityAlert {
  id: string
  type: string
  severity: 'Low' | 'Medium' | 'High' | 'Critical'
  detectedAt: string
  source: string
  ipAddress: string
  description: string
  affectedUser?: string
  actionsTaken?: string[]
}

const props = defineProps<{
  alert: SecurityAlert
}>()

const emit = defineEmits<{
  investigate: [alert: SecurityAlert]
  dismiss: [alert: SecurityAlert]
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

const investigate = () => {
  emit('investigate', props.alert)
}

const dismiss = () => {
  emit('dismiss', props.alert)
}
</script>
