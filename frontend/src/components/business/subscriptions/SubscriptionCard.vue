<template>
  <div class="subscription-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Header -->
    <div class="relative h-24 bg-gradient-to-r from-indigo-500 to-purple-500">
      <span 
        v-if="subscription.isPopular"
        class="absolute top-2 right-2 bg-white/90 text-indigo-900 px-3 py-1 rounded text-xs font-bold"
      >
        POPULAR
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Plan Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ subscription.name }}</h3>
      
      <!-- Price -->
      <div class="mb-4">
        <span class="text-3xl font-bold text-indigo-600">{{ formatPrice(subscription.price) }}</span>
        <span class="text-sm text-gray-500">/{{ subscription.billingPeriod }}</span>
      </div>

      <!-- Features -->
      <div class="mb-4">
        <ul class="space-y-2">
          <li 
            v-for="feature in subscription.features" 
            :key="feature"
            class="flex items-center gap-2 text-sm text-gray-600"
          >
            <span class="text-green-500">✓</span>
            <span>{{ feature }}</span>
          </li>
        </ul>
      </div>

      <!-- Trial Info -->
      <div v-if="subscription.trialDays" class="mb-4">
        <p class="text-xs text-gray-500">
          {{ subscription.trialDays }} days free trial, then {{ formatPrice(subscription.price) }}/{{ subscription.billingPeriod }}
        </p>
      </div>

      <!-- Current Status -->
      <div v-if="subscription.isActive" class="mb-4">
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 bg-green-500 rounded-full"></span>
          <span class="text-sm text-gray-600">Active until {{ subscription.renewsAt }}</span>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="viewDetails"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Details
        </button>
        <button 
          @click="subscribe"
          class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          {{ subscription.isActive ? 'Manage' : 'Subscribe' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Subscription {
  id: string
  name: string
  price: number
  billingPeriod: 'month' | 'year'
  features: string[]
  trialDays?: number
  isPopular: boolean
  isActive?: boolean
  renewsAt?: string
}

const props = defineProps<{
  subscription: Subscription
}>()

const emit = defineEmits<{
  viewDetails: [subscription: Subscription]
  subscribe: [subscription: Subscription]
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
  emit('viewDetails', props.subscription)
}

const subscribe = () => {
  emit('subscribe', props.subscription)
}
</script>
