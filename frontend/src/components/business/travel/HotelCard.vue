<template>
  <div class="hotel-card group bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
    <!-- Image Gallery -->
    <div class="relative aspect-video overflow-hidden">
      <img 
        :src="hotel.mainImage" 
        :alt="hotel.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
      
      <!-- Badges -->
      <div class="absolute top-2 left-2 flex gap-1">
        <span v-if="hotel.isVerified" class="px-2 py-1 bg-blue-500 text-white text-xs font-semibold rounded-full flex items-center gap-1">
          <CheckCircle class="w-3 h-3" />
          Verified
        </span>
        <span v-if="hotel.discount" class="px-2 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">
          -{{ hotel.discount }}%
        </span>
      </div>

      <!-- Rating -->
      <div class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm rounded-lg px-2 py-1 flex items-center gap-1">
        <Star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
        <span class="text-sm font-bold">{{ hotel.rating }}</span>
      </div>

      <!-- Image Navigation -->
      <div class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1">
        <button 
          v-for="(image, index) in hotel.images.slice(0, 5)"
          :key="index"
          @click="selectImage(index)"
          :class="[
            'w-2 h-2 rounded-full transition-all',
            currentImageIndex === index ? 'bg-white scale-125' : 'bg-white/50'
          ]"
        />
      </div>
    </div>

    <!-- Hotel Info -->
    <div class="p-4">
      <!-- Location -->
      <div class="flex items-center gap-1 text-gray-500 text-sm mb-1">
        <MapPin class="w-4 h-4" />
        <span>{{ hotel.location }}</span>
      </div>

      <!-- Name -->
      <h3 class="font-semibold text-gray-900 text-lg mb-2 line-clamp-1">{{ hotel.name }}</h3>

      <!-- Amenities -->
      <div class="flex gap-2 mb-3">
        <span 
          v-for="amenity in hotel.amenities.slice(0, 4)" 
          :key="amenity"
          class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full"
        >
          {{ amenity }}
        </span>
        <span v-if="hotel.amenities.length > 4" class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
          +{{ hotel.amenities.length - 4 }}
        </span>
      </div>

      <!-- Price -->
      <div class="flex items-end gap-2 mb-3">
        <span class="text-2xl font-bold text-gray-900">
          {{ formatPrice(hotel.price) }}
        </span>
        <span class="text-gray-400 text-sm mb-1">/ night</span>
        <span v-if="hotel.originalPrice" class="text-gray-400 line-through text-sm mb-1">
          {{ formatPrice(hotel.originalPrice) }}
        </span>
      </div>

      <!-- Reviews -->
      <div class="flex items-center gap-2 mb-4">
        <div class="flex">
          <Star v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= hotel.rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'" />
        </div>
        <span class="text-sm text-gray-600">{{ hotel.reviews }} reviews</span>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="viewDetails"
          class="flex-1 bg-gray-900 hover:bg-gray-800 text-white py-2 px-4 rounded-lg font-medium transition-colors"
        >
          View Details
        </button>
        <button 
          @click="bookNow"
          class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg font-medium transition-colors"
        >
          Book Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { CheckCircle, Star, MapPin } from 'lucide-vue-next'

interface Hotel {
  id: string
  name: string
  location: string
  mainImage: string
  images: string[]
  rating: number
  reviews: number
  price: number
  originalPrice?: number
  discount?: number
  isVerified?: boolean
  amenities: string[]
}

const props = defineProps<{
  hotel: Hotel
}>()

const emit = defineEmits<{
  viewDetails: [hotel: Hotel]
  bookNow: [hotel: Hotel]
}>()

const currentImageIndex = ref(0)

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(price)
}

const selectImage = (index: number) => {
  currentImageIndex.value = index
}

const viewDetails = () => {
  emit('viewDetails', props.hotel)
}

const bookNow = () => {
  emit('bookNow', props.hotel)
}
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
