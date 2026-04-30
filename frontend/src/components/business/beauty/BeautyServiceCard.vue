<template>
  <div class="beauty-service-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40">
      <img 
        :src="service.imageUrl" 
        :alt="service.name"
        class="w-full h-full object-cover"
      />
      <span 
        :class="getCategoryClass(service.category)"
        class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium"
      >
        {{ service.category }}
      </span>
      <span 
        v-if="service.isPopular"
        class="absolute top-2 right-2 bg-pink-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        POPULAR
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Service Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ service.name }}</h3>
      
      <!-- Duration & Price -->
      <div class="flex items-center gap-4 mb-3 text-sm text-gray-600">
        <span class="flex items-center gap-1">
          <span>⏱️</span>
          {{ service.duration }}
        </span>
        <span class="flex items-center gap-1">
          <span>💰</span>
          {{ formatPrice(service.price) }}
        </span>
      </div>

      <!-- Salon Info -->
      <div class="flex items-center gap-2 mb-3">
        <div class="w-8 h-8 bg-pink-100 rounded-full flex items-center justify-center">
          <span class="text-pink-600 font-bold text-xs">{{ salon.initials }}</span>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">{{ salon.name }}</p>
          <p class="text-xs text-gray-500">{{ salon.location }}</p>
        </div>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(salon.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ salon.rating }} ({{ salon.reviews }} reviews)</span>
      </div>

      <!-- Included -->
      <div v-if="service.included && service.included.length" class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Includes</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="item in service.included.slice(0, 3)" 
            :key="item"
            class="px-2 py-0.5 bg-pink-50 text-pink-700 rounded text-xs"
          >
            {{ item }}
          </span>
          <span v-if="service.included.length > 3" class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
            +{{ service.included.length - 3 }}
          </span>
        </div>
      </div>

      <!-- Available Slots -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Available Today</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="slot in service.availableSlots.slice(0, 4)" 
            :key="slot"
            class="px-2 py-0.5 bg-green-50 text-green-700 rounded text-xs cursor-pointer hover:bg-green-100"
          >
            {{ slot }}
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
          @click="book"
          class="flex-1 bg-pink-600 hover:bg-pink-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Book Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Salon {
  name: string
  initials: string
  location: string
  rating: number
  reviews: number
}

interface BeautyService {
  id: string
  name: string
  category: string
  description: string
  imageUrl: string
  duration: string
  price: number
  isPopular: boolean
  included?: string[]
  availableSlots: string[]
}

const props = defineProps<{
  service: BeautyService
  salon: Salon
}>()

const emit = defineEmits<{
  viewDetails: [service: BeautyService]
  book: [service: BeautyService]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getCategoryClass = (category: string) => {
  const classes = {
    'Hair': 'bg-purple-100 text-purple-900',
    'Nails': 'bg-pink-100 text-pink-900',
    'Facial': 'bg-blue-100 text-blue-900',
    'Massage': 'bg-green-100 text-green-900',
    'Makeup': 'bg-orange-100 text-orange-900',
  }
  return classes[category as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.service)
}

const book = () => {
  emit('book', props.service)
}
</script>
