<template>
  <div class="gym-card group bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
    <!-- Image -->
    <div class="relative aspect-video overflow-hidden">
      <img 
        :src="gym.image" 
        :alt="gym.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
      
      <!-- Badges -->
      <div class="absolute top-2 left-2 flex gap-1">
        <span v-if="gym.isOpen24h" class="px-2 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">
          24/7
        </span>
        <span v-if="gym.isPremium" class="px-2 py-1 bg-yellow-500 text-white text-xs font-semibold rounded-full">
          Premium
        </span>
      </div>

      <!-- Rating -->
      <div class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm rounded-lg px-2 py-1 flex items-center gap-1">
        <Star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
        <span class="text-sm font-bold">{{ gym.rating }}</span>
      </div>

      <!-- Current Capacity -->
      <div class="absolute bottom-2 left-2 bg-white/90 backdrop-blur-sm rounded-lg px-2 py-1">
        <div class="flex items-center gap-2">
          <div class="w-16 bg-gray-200 rounded-full h-2">
            <div 
              class="bg-green-500 h-2 rounded-full transition-all"
              :style="{ width: (gym.currentCapacity / gym.maxCapacity * 100) + '%' }"
            />
          </div>
          <span class="text-xs text-gray-600">{{ gym.currentCapacity }}/{{ gym.maxCapacity }}</span>
        </div>
      </div>
    </div>

    <!-- Gym Info -->
    <div class="p-4">
      <!-- Name & Location -->
      <div class="mb-2">
        <h3 class="font-semibold text-gray-900 text-lg line-clamp-1">{{ gym.name }}</h3>
        <div class="flex items-center gap-1 text-gray-500 text-sm">
          <MapPin class="w-4 h-4" />
          <span>{{ gym.location }}</span>
        </div>
      </div>

      <!-- Distance -->
      <div class="flex items-center gap-2 text-gray-600 text-sm mb-3">
        <Navigation class="w-4 h-4" />
        <span>{{ gym.distance }} km away</span>
      </div>

      <!-- Equipment -->
      <div class="mb-3">
        <p class="text-sm font-medium text-gray-700 mb-2">Equipment</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="equipment in gym.equipment.slice(0, 4)" 
            :key="equipment"
            class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full"
          >
            {{ equipment }}
          </span>
          <span v-if="gym.equipment.length > 4" class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
            +{{ gym.equipment.length - 4 }}
          </span>
        </div>
      </div>

      <!-- Classes -->
      <div v-if="gym.classes && gym.classes.length" class="mb-4">
        <p class="text-sm font-medium text-gray-700 mb-2">Classes</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="classItem in gym.classes.slice(0, 3)" 
            :key="classItem"
            class="px-2 py-1 bg-purple-50 text-purple-600 text-xs rounded-full"
          >
            {{ classItem }}
          </span>
          <span v-if="gym.classes.length > 3" class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
            +{{ gym.classes.length - 3 }}
          </span>
        </div>
      </div>

      <!-- Pricing -->
      <div class="flex items-center justify-between mb-4">
        <div>
          <p class="text-sm text-gray-500">Monthly</p>
          <p class="text-xl font-bold text-gray-900">{{ formatPrice(gym.monthlyPrice) }}</p>
        </div>
        <div v-if="gym.dayPassPrice" class="text-right">
          <p class="text-sm text-gray-500">Day Pass</p>
          <p class="text-lg font-bold text-purple-600">{{ formatPrice(gym.dayPassPrice) }}</p>
        </div>
      </div>

      <!-- Amenities -->
      <div class="flex flex-wrap gap-2 mb-4">
        <div 
          v-for="amenity in gym.amenities.slice(0, 4)" 
          :key="amenity.id"
          class="flex items-center gap-1 text-gray-600 text-xs"
        >
          <component :is="amenity.icon" class="w-4 h-4" />
          <span>{{ amenity.name }}</span>
        </div>
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
          @click="joinNow"
          class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg font-medium transition-colors"
        >
          Join Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Star, MapPin, Navigation, Dumbbell, Shower, Lock, Wifi, Car } from 'lucide-vue-next'

interface Amenity {
  id: string
  name: string
  icon: any
}

interface Gym {
  id: string
  name: string
  location: string
  image: string
  rating: number
  distance: number
  equipment: string[]
  classes?: string[]
  monthlyPrice: number
  dayPassPrice?: number
  amenities: Amenity[]
  currentCapacity: number
  maxCapacity: number
  isOpen24h?: boolean
  isPremium?: boolean
}

const props = defineProps<{
  gym: Gym
}>()

const emit = defineEmits<{
  viewDetails: [gym: Gym]
  joinNow: [gym: Gym]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(price)
}

const viewDetails = () => {
  emit('viewDetails', props.gym)
}

const joinNow = () => {
  emit('joinNow', props.gym)
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
