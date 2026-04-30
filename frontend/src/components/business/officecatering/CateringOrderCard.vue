<template>
  <div class="catering-order-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ catering.name }}</h3>
          <p class="text-sm text-gray-500">{{ catering.type }}</p>
        </div>
        <div class="text-right">
          <p class="font-bold text-orange-600">{{ formatPrice(catering.pricePerPerson) }}/person</p>
          <p class="text-xs text-gray-400">{{ catering.rating }} ⭐</p>
        </div>
      </div>
    </div>

    <!-- Caterer Info -->
    <div class="flex items-center gap-3 mb-4">
      <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
        <span class="text-orange-600 font-bold">{{ catering.catererInitials }}</span>
      </div>
      <div>
        <p class="font-medium text-gray-900">{{ catering.catererName }}</p>
        <p class="text-sm text-gray-500">{{ catering.location }}</p>
      </div>
    </div>

    <!-- Menu Types -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Menu Options</p>
      <div class="flex flex-wrap gap-2">
        <span 
          v-for="menu in catering.menuTypes" 
          :key="menu"
          class="px-2 py-1 bg-orange-50 text-orange-700 rounded text-xs"
        >
          {{ menu }}
        </span>
      </div>
    </div>

    <!-- Details -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Min guests:</span>
          <span class="font-medium">{{ catering.minGuests }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Max guests:</span>
          <span class="font-medium">{{ catering.maxGuests }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Setup time:</span>
          <span class="font-medium">{{ catering.setupTime }}</span>
        </div>
      </div>
    </div>

    <!-- Dietary Options -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-1">Dietary Options</p>
      <div class="flex flex-wrap gap-1">
        <span 
          v-for="option in catering.dietaryOptions" 
          :key="option"
          class="px-2 py-0.5 bg-green-50 text-green-700 rounded text-xs"
        >
          {{ option }}
        </span>
      </div>
    </div>

    <!-- Includes -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-1">Includes</p>
      <div class="space-y-1">
        <div v-for="item in catering.includes" :key="item" class="flex items-center gap-2 text-sm text-gray-600">
          <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
          </svg>
          <span>{{ item }}</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="viewMenu"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        View Menu
      </button>
      <button 
        @click="requestQuote"
        class="flex-1 bg-orange-600 hover:bg-orange-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Request Quote
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface CateringOrder {
  id: string
  name: string
  type: string
  catererName: string
  catererInitials: string
  location: string
  pricePerPerson: number
  rating: number
  menuTypes: string[]
  minGuests: number
  maxGuests: number
  setupTime: string
  dietaryOptions: string[]
  includes: string[]
}

const props = defineProps<{
  catering: CateringOrder
}>()

const emit = defineEmits<{
  viewMenu: [catering: CateringOrder]
  requestQuote: [catering: CateringOrder]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const viewMenu = () => {
  emit('viewMenu', props.catering)
}

const requestQuote = () => {
  emit('requestQuote', props.catering)
}
</script>
