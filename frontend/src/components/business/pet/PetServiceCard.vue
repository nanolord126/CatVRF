<template>
  <div class="pet-service-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-orange-50">
      <img 
        :src="service.imageUrl" 
        :alt="service.name"
        class="w-full h-full object-cover"
      />
      <span class="absolute top-2 left-2 bg-orange-500 text-white px-2 py-1 rounded text-xs font-bold">
        {{ service.petType }}
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
      <div class="space-y-1 mb-3 text-sm text-gray-600">
        <div v-if="service.duration"><span class="font-medium">Duration:</span> {{ service.duration }}</div>
        <div v-if="service.breeds"><span class="font-medium">Breeds:</span> {{ service.breeds }}</div>
        <div v-if="service.size"><span class="font-medium">Size:</span> {{ service.size }}</div>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-orange-600">{{ formatPrice(service.price) }}</span>
        <span class="text-xs text-gray-400">per {{ service.priceUnit }}</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(service.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ service.rating }} ({{ service.reviews }} reviews)</span>
      </div>

      <!-- Vaccination Required -->
      <div v-if="service.vaccinationRequired" class="flex items-center gap-2 mb-4 text-xs text-orange-600">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
        </svg>
        <span>Vaccination records required</span>
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
          class="flex-1 bg-orange-600 hover:bg-orange-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Book Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface PetService {
  id: string
  name: string
  category: string
  description: string
  imageUrl: string
  price: number
  priceUnit: 'visit' | 'hour' | 'day'
  rating: number
  reviews: number
  petType: 'Dog' | 'Cat' | 'Bird' | 'Other'
  duration?: string
  breeds?: string
  size?: string
  vaccinationRequired: boolean
}

const props = defineProps<{
  service: PetService
}>()

const emit = defineEmits<{
  viewDetails: [service: PetService]
  bookService: [service: PetService]
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

const bookService = () => {
  emit('bookService', props.service)
}
</script>
