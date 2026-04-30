<template>
  <div class="communication-service-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ service.name }}</h3>
          <p class="text-sm text-gray-500">{{ service.type }}</p>
        </div>
        <div class="text-right">
          <p class="font-bold text-indigo-600">{{ formatPrice(service.monthlyPrice) }}/mo</p>
          <p class="text-xs text-gray-400">{{ service.rating }} ⭐</p>
        </div>
      </div>
    </div>

    <!-- Features -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Features</p>
      <div class="space-y-2">
        <div v-for="feature in service.features" :key="feature" class="flex items-center gap-2 text-sm text-gray-600">
          <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
          </svg>
          <span>{{ feature }}</span>
        </div>
      </div>
    </div>

    <!-- Plan Details -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Users:</span>
          <span class="font-medium">{{ service.usersIncluded }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Storage:</span>
          <span class="font-medium">{{ service.storage }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Bandwidth:</span>
          <span class="font-medium">{{ service.bandwidth }}</span>
        </div>
      </div>
    </div>

    <!-- Free Trial -->
    <div v-if="service.freeTrialDays" class="flex items-center gap-2 mb-4 text-sm text-indigo-600">
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
      </svg>
      <span>{{ service.freeTrialDays }} days free trial</span>
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
        @click="startTrial"
        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Start Free Trial
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface CommunicationService {
  id: string
  name: string
  type: string
  description: string
  monthlyPrice: number
  rating: number
  usersIncluded: number
  storage: string
  bandwidth: string
  features: string[]
  freeTrialDays?: number
}

const props = defineProps<{
  service: CommunicationService
}>()

const emit = defineEmits<{
  viewDetails: [service: CommunicationService]
  startTrial: [service: CommunicationService]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const viewDetails = () => {
  emit('viewDetails', props.service)
}

const startTrial = () => {
  emit('startTrial', props.service)
}
</script>
