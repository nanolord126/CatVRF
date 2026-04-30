<template>
  <div class="auto-service-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Header -->
    <div class="relative h-32 bg-gradient-to-r from-slate-500 to-gray-600">
      <img 
        :src="service.imageUrl" 
        :alt="service.name"
        class="w-full h-full object-cover opacity-90"
      />
      <span :class="getTypeClass(service.type)" class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-bold bg-white/90">
        {{ service.type }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Service Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ service.name }}</h3>
      
      <!-- Workshop Info -->
      <div class="flex items-center gap-2 mb-4">
        <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center">
          <span class="text-slate-600 font-bold">{{ workshop.initials }}</span>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">{{ workshop.name }}</p>
          <p class="text-xs text-gray-500">{{ workshop.location }}</p>
        </div>
      </div>

      <!-- Service Details -->
      <div class="bg-gray-50 rounded-lg p-3 mb-4">
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Duration:</span>
            <span class="font-medium">{{ service.duration }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Warranty:</span>
            <span class="font-medium">{{ service.warranty }}</span>
          </div>
        </div>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-4">
        <span class="text-xl font-bold text-slate-600">{{ formatPrice(service.price) }}</span>
        <span class="text-xs text-gray-400">starting from</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(workshop.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ workshop.rating }} ({{ workshop.reviews }} reviews)</span>
      </div>

      <!-- Car Brands -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Works with</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="brand in service.brands.slice(0, 4)" 
            :key="brand"
            class="px-2 py-0.5 bg-slate-50 text-slate-700 rounded text-xs"
          >
            {{ brand }}
          </span>
          <span v-if="service.brands.length > 4" class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
            +{{ service.brands.length - 4 }}
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
          class="flex-1 bg-slate-600 hover:bg-slate-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Book Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Workshop {
  name: string
  initials: string
  location: string
  rating: number
  reviews: number
}

interface AutoService {
  id: string
  name: string
  type: 'Repair' | 'Maintenance' | 'Inspection' | 'Tires'
  imageUrl: string
  duration: string
  warranty: string
  price: number
  brands: string[]
}

const props = defineProps<{
  service: AutoService
  workshop: Workshop
}>()

const emit = defineEmits<{
  viewDetails: [service: AutoService]
  bookService: [service: AutoService]
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
    'Repair': 'text-red-900',
    'Maintenance': 'text-blue-900',
    'Inspection': 'text-green-900',
    'Tires': 'text-orange-900',
  }
  return classes[type as keyof typeof classes] || 'text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.service)
}

const bookService = () => {
  emit('bookService', props.service)
}
</script>
