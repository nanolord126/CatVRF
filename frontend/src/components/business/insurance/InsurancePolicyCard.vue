<template>
  <div class="insurance-policy-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ policy.name }}</h3>
          <p class="text-sm text-gray-500">{{ policy.type }}</p>
        </div>
        <span :class="getStatusClass(policy.status)" class="px-3 py-1 rounded-full text-xs font-medium">
          {{ policy.status }}
        </span>
      </div>
    </div>

    <!-- Policy Details -->
    <div class="space-y-3 mb-4">
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Policy Number:</span>
        <span class="font-medium">{{ policy.policyNumber }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Coverage:</span>
        <span class="font-medium">{{ formatPrice(policy.coverage) }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Premium:</span>
        <span class="font-medium">{{ formatPrice(policy.premium) }}/{{ policy.premiumPeriod }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Deductible:</span>
        <span class="font-medium">{{ formatPrice(policy.deductible) }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Valid Until:</span>
        <span class="font-medium">{{ formatDate(policy.endDate) }}</span>
      </div>
    </div>

    <!-- Coverage Types -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Coverage Types</p>
      <div class="flex flex-wrap gap-2">
        <span 
          v-for="coverage in policy.coverageTypes" 
          :key="coverage"
          class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs"
        >
          {{ coverage }}
        </span>
      </div>
    </div>

    <!-- Progress Bar (Time Remaining) -->
    <div class="mb-4">
      <div class="flex justify-between text-xs text-gray-500 mb-1">
        <span>Time Remaining</span>
        <span>{{ daysRemaining }} days</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div 
          class="bg-blue-600 h-2 rounded-full transition-all" 
          :style="{ width: timeRemainingPercent + '%' }"
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
        @click="fileClaim"
        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        File Claim
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface InsurancePolicy {
  id: string
  name: string
  type: string
  policyNumber: string
  status: 'Active' | 'Expired' | 'Pending' | 'Cancelled'
  coverage: number
  premium: number
  premiumPeriod: 'month' | 'year'
  deductible: number
  startDate: string
  endDate: string
  coverageTypes: string[]
}

const props = defineProps<{
  policy: InsurancePolicy
}>()

const emit = defineEmits<{
  viewDetails: [policy: InsurancePolicy]
  fileClaim: [policy: InsurancePolicy]
}>()

const daysRemaining = computed(() => {
  const today = new Date()
  const end = new Date(props.policy.endDate)
  const diff = end.getTime() - today.getTime()
  return Math.ceil(diff / (1000 * 60 * 60 * 24))
})

const timeRemainingPercent = computed(() => {
  const start = new Date(props.policy.startDate).getTime()
  const end = new Date(props.policy.endDate).getTime()
  const today = new Date().getTime()
  const total = end - start
  const elapsed = today - start
  return Math.max(0, Math.min(100, ((total - elapsed) / total) * 100))
})

const getStatusClass = (status: string) => {
  const classes = {
    'Active': 'bg-green-100 text-green-900',
    'Expired': 'bg-red-100 text-red-900',
    'Pending': 'bg-yellow-100 text-yellow-900',
    'Cancelled': 'bg-gray-100 text-gray-900',
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const viewDetails = () => {
  emit('viewDetails', props.policy)
}

const fileClaim = () => {
  emit('fileClaim', props.policy)
}
</script>
