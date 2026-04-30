<template>
  <div class="customer-profile-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Header -->
    <div class="relative h-24 bg-gradient-to-r from-cyan-500 to-blue-500">
      <div class="absolute -bottom-8 left-4">
        <div class="w-20 h-20 bg-white rounded-full border-4 border-white shadow-lg flex items-center justify-center">
          <span class="text-2xl font-bold text-cyan-600">{{ customer.initials }}</span>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4 pt-10">
      <!-- Name & Info -->
      <div class="mb-4">
        <h3 class="text-lg font-bold text-gray-900">{{ customer.name }}</h3>
        <p class="text-sm text-gray-500">{{ customer.email }}</p>
        <p class="text-sm text-gray-500">{{ customer.phone }}</p>
      </div>

      <!-- Customer Stats -->
      <div class="grid grid-cols-3 gap-2 mb-4">
        <div class="bg-cyan-50 rounded p-2 text-center">
          <p class="text-lg font-bold text-cyan-900">{{ customer.totalOrders }}</p>
          <p class="text-xs text-cyan-700">Orders</p>
        </div>
        <div class="bg-green-50 rounded p-2 text-center">
          <p class="text-lg font-bold text-green-900">{{ formatPrice(customer.totalSpent) }}</p>
          <p class="text-xs text-green-700">Spent</p>
        </div>
        <div class="bg-purple-50 rounded p-2 text-center">
          <p class="text-lg font-bold text-purple-900">{{ customer.loyaltyPoints }}</p>
          <p class="text-xs text-purple-700">Points</p>
        </div>
      </div>

      <!-- Segment -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Segment</p>
        <span 
          :class="getSegmentClass(customer.segment)"
          class="px-2 py-1 rounded text-xs font-medium"
        >
          {{ customer.segment }}
        </span>
      </div>

      <!-- Last Activity -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Last Activity</p>
        <p class="text-sm text-gray-600">{{ customer.lastActivity }}</p>
      </div>

      <!-- Tags -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Tags</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="tag in customer.tags" 
            :key="tag"
            class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs"
          >
            {{ tag }}
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="viewProfile"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          View Profile
        </button>
        <button 
          @click="contact"
          class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Contact
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Customer {
  id: string
  name: string
  initials: string
  email: string
  phone: string
  totalOrders: number
  totalSpent: number
  loyaltyPoints: number
  segment: 'VIP' | 'Regular' | 'New' | 'At Risk'
  lastActivity: string
  tags: string[]
}

const props = defineProps<{
  customer: Customer
}>()

const emit = defineEmits<{
  viewProfile: [customer: Customer]
  contact: [customer: Customer]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getSegmentClass = (segment: string) => {
  const classes = {
    'VIP': 'bg-purple-100 text-purple-900',
    'Regular': 'bg-blue-100 text-blue-900',
    'New': 'bg-green-100 text-green-900',
    'At Risk': 'bg-red-100 text-red-900',
  }
  return classes[segment as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewProfile = () => {
  emit('viewProfile', props.customer)
}

const contact = () => {
  emit('contact', props.customer)
}
</script>
