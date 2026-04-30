<template>
  <div class="photography-service-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-48 bg-gray-100">
      <img 
        :src="service.imageUrl" 
        :alt="service.name"
        class="w-full h-full object-cover"
      />
      <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-3">
        <p class="text-white text-sm font-medium">{{ service.photographer }}</p>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Category -->
      <p class="text-xs text-gray-500 mb-1 uppercase font-medium">{{ service.category }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ service.name }}</h3>
      
      <!-- Description -->
      <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ service.description }}</p>

      <!-- Package Details -->
      <div class="space-y-1 mb-3 text-sm text-gray-600">
        <div><span class="font-medium">Duration:</span> {{ service.duration }}</div>
        <div><span class="font-medium">Photos:</span> {{ service.photosIncluded }}</div>
        <div><span class="font-medium">Edited:</span> {{ service.photosEdited }}</div>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-rose-600">{{ formatPrice(service.price) }}</span>
        <span class="text-xs text-gray-400">per package</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(service.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ service.rating }} ({{ service.sessionsCompleted }} sessions)</span>
      </div>

      <!-- Includes -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Includes:</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="item in service.includes" 
            :key="item"
            class="px-2 py-0.5 bg-rose-50 text-rose-700 rounded text-xs"
          >
            {{ item }}
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="viewPortfolio"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Portfolio
        </button>
        <button 
          @click="bookSession"
          class="flex-1 bg-rose-600 hover:bg-rose-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Book Session
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface PhotographyService {
  id: string
  name: string
  category: string
  description: string
  imageUrl: string
  photographer: string
  price: number
  rating: number
  sessionsCompleted: number
  duration: string
  photosIncluded: number
  photosEdited: number
  includes: string[]
}

const props = defineProps<{
  service: PhotographyService
}>()

const emit = defineEmits<{
  viewPortfolio: [service: PhotographyService]
  bookSession: [service: PhotographyService]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const viewPortfolio = () => {
  emit('viewPortfolio', props.service)
}

const bookSession = () => {
  emit('bookSession', props.service)
}
</script>
