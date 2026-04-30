<template>
  <div class="grooming-service-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40">
      <img 
        :src="service.imageUrl" 
        :alt="service.name"
        class="w-full h-full object-cover"
      />
      <span 
        :class="getTypeClass(service.type)"
        class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium bg-white/90"
      >
        {{ service.type }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Service Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ service.name }}</h3>
      
      <!-- Pet Types -->
      <div class="mb-3">
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="petType in service.petTypes" 
            :key="petType"
            class="px-2 py-0.5 bg-amber-50 text-amber-700 rounded text-xs"
          >
            {{ petType }}
          </span>
        </div>
      </div>

      <!-- Groomer Info -->
      <div class="flex items-center gap-2 mb-4">
        <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center">
          <span class="text-amber-600 font-bold">{{ groomer.initials }}</span>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">{{ groomer.name }}</p>
          <p class="text-xs text-gray-500">{{ groomer.experience }} experience</p>
        </div>
      </div>

      <!-- Duration & Price -->
      <div class="flex items-center gap-4 mb-4 text-sm text-gray-600">
        <span class="flex items-center gap-1">
          <span>⏱️</span>
          {{ service.duration }}
        </span>
        <span class="flex items-center gap-1">
          <span>💰</span>
          {{ formatPrice(service.price) }}
        </span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(groomer.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ groomer.rating }} ({{ groomer.reviews }} reviews)</span>
      </div>

      <!-- Includes -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Includes</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="item in service.includes.slice(0, 3)" 
            :key="item"
            class="px-2 py-0.5 bg-amber-50 text-amber-700 rounded text-xs"
          >
            {{ item }}
          </span>
          <span v-if="service.includes.length > 3" class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
            +{{ service.includes.length - 3 }}
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
          class="flex-1 bg-amber-600 hover:bg-amber-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Book Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Groomer {
  name: string
  initials: string
  experience: string
  rating: number
  reviews: number
}

interface GroomingService {
  id: string
  name: string
  type: 'Bath' | 'Haircut' | 'Full Grooming' | 'Nail Trimming'
  imageUrl: string
  duration: string
  price: number
  petTypes: string[]
  includes: string[]
}

const props = defineProps<{
  service: GroomingService
  groomer: Groomer
}>()

const emit = defineEmits<{
  viewDetails: [service: GroomingService]
  book: [service: GroomingService]
}>()

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
    'Bath': 'bg-blue-100 text-blue-900',
    'Haircut': 'bg-purple-100 text-purple-900',
    'Full Grooming': 'bg-green-100 text-green-900',
    'Nail Trimming': 'bg-orange-100 text-orange-900',
  }
  return classes[type as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.service)
}

const book = () => {
  emit('book', props.service)
}
</script>
