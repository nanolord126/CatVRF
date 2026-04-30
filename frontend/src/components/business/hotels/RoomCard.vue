<template>
  <div class="room-card group bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
    <!-- Image Gallery -->
    <div class="relative aspect-video overflow-hidden">
      <img 
        :src="room.mainImage" 
        :alt="room.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
      
      <!-- Badges -->
      <div class="absolute top-2 left-2 flex gap-1">
        <span v-if="room.isAvailable" class="px-2 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">
          Available
        </span>
        <span v-if="room.isSuite" class="px-2 py-1 bg-purple-500 text-white text-xs font-semibold rounded-full">
          Suite
        </span>
      </div>

      <!-- Price per Night -->
      <div class="absolute bottom-2 left-2 bg-white/90 backdrop-blur-sm rounded-lg px-3 py-2">
        <span class="text-sm text-gray-500">from</span>
        <span class="text-lg font-bold text-gray-900 ml-1">{{ formatPrice(room.price) }}</span>
        <span class="text-xs text-gray-400">/night</span>
      </div>
    </div>

    <!-- Room Info -->
    <div class="p-4">
      <!-- Name -->
      <h3 class="font-semibold text-gray-900 text-lg mb-2">{{ room.name }}</h3>

      <!-- Capacity -->
      <div class="flex items-center gap-2 text-gray-600 text-sm mb-3">
        <Users class="w-4 h-4" />
        <span>Up to {{ room.maxGuests }} guests</span>
        <span class="text-gray-300">•</span>
        <span>{{ room.beds }} {{ room.beds === 1 ? 'bed' : 'beds' }}</span>
      </div>

      <!-- Size -->
      <div class="flex items-center gap-2 text-gray-600 text-sm mb-3">
        <Maximize class="w-4 h-4" />
        <span>{{ room.area }} m²</span>
      </div>

      <!-- Amenities -->
      <div class="flex flex-wrap gap-2 mb-4">
        <div 
          v-for="amenity in room.amenities.slice(0, 6)" 
          :key="amenity.id"
          class="flex items-center gap-1 text-gray-600 text-xs"
        >
          <component :is="amenity.icon" class="w-4 h-4" />
          <span>{{ amenity.name }}</span>
        </div>
        <span v-if="room.amenities.length > 6" class="text-gray-400 text-xs">
          +{{ room.amenities.length - 6 }}
        </span>
      </div>

      <!-- Bed Type -->
      <div class="mb-4">
        <p class="text-sm font-medium text-gray-700 mb-1">Bed Type</p>
        <p class="text-sm text-gray-600">{{ room.bedType }}</p>
      </div>

      <!-- Cancellation Policy -->
      <div v-if="room.freeCancellation" class="flex items-center gap-2 p-2 bg-green-50 rounded-lg mb-4">
        <CheckCircle class="w-4 h-4 text-green-600" />
        <span class="text-sm text-green-700">Free cancellation</span>
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
          @click="bookRoom"
          :disabled="!room.isAvailable"
          class="flex-1 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-2 px-4 rounded-lg font-medium transition-colors"
        >
          Book Room
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Users, Maximize, CheckCircle, Wifi, Tv, Coffee, Snowflake, Wind, Car } from 'lucide-vue-next'

interface Amenity {
  id: string
  name: string
  icon: any
}

interface Room {
  id: string
  name: string
  mainImage: string
  price: number
  maxGuests: number
  beds: number
  area: number
  bedType: string
  amenities: Amenity[]
  isAvailable: boolean
  isSuite?: boolean
  freeCancellation?: boolean
}

const props = defineProps<{
  room: Room
}>()

const emit = defineEmits<{
  viewDetails: [room: Room]
  bookRoom: [room: Room]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(price)
}

const viewDetails = () => {
  emit('viewDetails', props.room)
}

const bookRoom = () => {
  emit('bookRoom', props.room)
}
</script>
