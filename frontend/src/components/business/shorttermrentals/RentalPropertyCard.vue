<template>
  <div class="rental-property-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-48 bg-blue-50">
      <img 
        :src="property.imageUrl" 
        :alt="property.name"
        class="w-full h-full object-cover"
      />
      <span 
        :class="getTypeClass(property.type)"
        class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium"
      >
        {{ property.type }}
      </span>
      <button 
        @click="toggleFavorite"
        class="absolute top-2 right-2 w-8 h-8 bg-white/90 rounded-full flex items-center justify-center hover:bg-white transition-colors"
      >
        <span :class="isFavorite ? 'text-red-500' : 'text-gray-400'">{{ isFavorite ? '♥' : '♡' }}</span>
      </button>
      <div class="absolute bottom-2 right-2 bg-black/70 text-white px-2 py-1 rounded text-xs">
        {{ property.imagesCount }} photos
      </div>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Location -->
      <div class="flex items-center gap-1 text-sm text-gray-500 mb-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <span>{{ property.location }}</span>
      </div>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ property.name }}</h3>
      
      <!-- Specs -->
      <div class="flex items-center gap-4 mb-3 text-sm text-gray-600">
        <div class="flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
          </svg>
          <span>{{ property.bedrooms }} beds</span>
        </div>
        <div class="flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
          </svg>
          <span>{{ property.bathrooms }} baths</span>
        </div>
        <div class="flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
          </svg>
          <span>{{ property.area }} m²</span>
        </div>
      </div>

      <!-- Amenities -->
      <div class="flex flex-wrap gap-2 mb-3">
        <span 
          v-for="amenity in property.amenities.slice(0, 4)" 
          :key="amenity"
          class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs"
        >
          {{ amenity }}
        </span>
        <span v-if="property.amenities.length > 4" class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
          +{{ property.amenities.length - 4 }}
        </span>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-blue-600">{{ formatPrice(property.price) }}</span>
        <span class="text-xs text-gray-400">/ night</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(property.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ property.rating }} ({{ property.reviews }} reviews)</span>
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
          @click="bookNow"
          class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Book Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface RentalProperty {
  id: string
  name: string
  type: 'Apartment' | 'House' | 'Studio' | 'Villa'
  location: string
  description: string
  imageUrl: string
  imagesCount: number
  price: number
  rating: number
  reviews: number
  bedrooms: number
  bathrooms: number
  area: number
  amenities: string[]
}

const props = defineProps<{
  property: RentalProperty
}>()

const emit = defineEmits<{
  viewDetails: [property: RentalProperty]
  bookNow: [property: RentalProperty]
  toggleFavorite: [property: RentalProperty]
}>()

const isFavorite = ref(false)

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getTypeClass = (type: string) => {
  const classes = {
    'Apartment': 'bg-blue-100 text-blue-900',
    'House': 'bg-green-100 text-green-900',
    'Studio': 'bg-purple-100 text-purple-900',
    'Villa': 'bg-amber-100 text-amber-900',
  }
  return classes[type as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const toggleFavorite = () => {
  isFavorite.value = !isFavorite.value
  emit('toggleFavorite', props.property)
}

const viewDetails = () => {
  emit('viewDetails', props.property)
}

const bookNow = () => {
  emit('bookNow', props.property)
}
</script>
