<template>
  <div class="cleaning-service-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ service.name }}</h3>
          <p class="text-sm text-gray-500">{{ service.category }}</p>
        </div>
        <div class="text-right">
          <p class="font-bold text-green-600">{{ formatPrice(service.price) }}</p>
          <p class="text-xs text-gray-400">{{ service.rating }} ⭐</p>
        </div>
      </div>
    </div>

    <!-- Service Details -->
    <div class="space-y-2 mb-4">
      <div class="flex items-center gap-2 text-sm text-gray-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <span>{{ service.location }}</span>
      </div>
      <div class="flex items-center gap-2 text-sm text-gray-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>{{ service.duration }}</span>
      </div>
      <div class="flex items-center gap-2 text-sm text-gray-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span>{{ service.availability }}</span>
      </div>
    </div>

    <!-- Description -->
    <p class="text-sm text-gray-600 mb-4">{{ service.description }}</p>

    <!-- Features -->
    <div class="flex flex-wrap gap-2 mb-4">
      <span 
        v-for="feature in service.features" 
        :key="feature"
        class="px-2 py-1 bg-green-50 text-green-700 rounded text-xs"
      >
        {{ feature }}
      </span>
    </div>

    <!-- Price Breakdown -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <div class="flex justify-between text-sm mb-1">
        <span class="text-gray-600">Base price:</span>
        <span>{{ formatPrice(service.basePrice) }}</span>
      </div>
      <div class="flex justify-between text-sm mb-1">
        <span class="text-gray-600">Area ({{ service.area }}m²):</span>
        <span>{{ formatPrice(service.areaPrice) }}</span>
      </div>
      <div class="flex justify-between text-sm font-medium border-t pt-1 mt-1">
        <span>Total:</span>
        <span class="text-green-600">{{ formatPrice(service.price) }}</span>
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
        @click="book"
        class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Book Now
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface CleaningService {
  id: string
  name: string
  category: string
  description: string
  price: number
  basePrice: number
  areaPrice: number
  area: number
  rating: number
  location: string
  duration: string
  availability: string
  features: string[]
}

const props = defineProps<{
  service: CleaningService
}>()

const emit = defineEmits<{
  viewDetails: [service: CleaningService]
  book: [service: CleaningService]
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

const book = () => {
  emit('book', props.service)
}
</script>
