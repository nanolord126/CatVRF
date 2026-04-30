<template>
  <div class="gardening-service-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-green-100">
      <img 
        :src="service.imageUrl" 
        :alt="service.name"
        class="w-full h-full object-cover"
      />
      <span 
        :class="getSeasonClass(service.season)"
        class="absolute top-2 right-2 px-2 py-1 rounded text-xs font-medium"
      >
        {{ service.season }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Category -->
      <p class="text-xs text-gray-500 mb-1 uppercase font-medium">{{ service.category }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ service.name }}</h3>
      
      <!-- Description -->
      <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ service.description }}</p>

      <!-- Service Details -->
      <div class="space-y-2 mb-3 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Area size:</span>
          <span class="font-medium">{{ service.minArea }} - {{ service.maxArea }} m²</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Duration:</span>
          <span class="font-medium">{{ service.duration }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Frequency:</span>
          <span class="font-medium">{{ service.frequency }}</span>
        </div>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-green-600">{{ formatPrice(service.price) }}</span>
        <span class="text-xs text-gray-400">per {{ service.priceUnit }}</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(service.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ service.rating }} ({{ service.reviews }} reviews)</span>
      </div>

      <!-- Included Services -->
      <div class="mb-4">
        <p class="text-sm font-medium text-gray-700 mb-2">Included</p>
        <div class="flex flex-wrap gap-2">
          <span 
            v-for="included in service.included" 
            :key="included"
            class="px-2 py-1 bg-green-50 text-green-700 rounded text-xs"
          >
            {{ included }}
          </span>
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
          @click="bookService"
          class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Book Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface GardeningService {
  id: string
  name: string
  category: string
  description: string
  imageUrl: string
  price: number
  priceUnit: 'visit' | 'hour' | 'month'
  rating: number
  reviews: number
  season: 'All Year' | 'Spring' | 'Summer' | 'Fall' | 'Winter'
  minArea: number
  maxArea: number
  duration: string
  frequency: string
  included: string[]
}

const props = defineProps<{
  service: GardeningService
}>()

const emit = defineEmits<{
  viewDetails: [service: GardeningService]
  bookService: [service: GardeningService]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getSeasonClass = (season: string) => {
  const classes = {
    'All Year': 'bg-green-100 text-green-900',
    'Spring': 'bg-pink-100 text-pink-900',
    'Summer': 'bg-yellow-100 text-yellow-900',
    'Fall': 'bg-orange-100 text-orange-900',
    'Winter': 'bg-blue-100 text-blue-900',
  }
  return classes[season as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.service)
}

const bookService = () => {
  emit('bookService', props.service)
}
</script>
