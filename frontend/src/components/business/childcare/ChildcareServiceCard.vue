<template>
  <div class="childcare-service-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-gradient-to-r from-pink-400 to-rose-400">
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
      
      <!-- Facility Info -->
      <div class="flex items-center gap-2 mb-4">
        <div class="w-10 h-10 bg-pink-100 rounded-full flex items-center justify-center">
          <span class="text-pink-600 font-bold">{{ facility.initials }}</span>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">{{ facility.name }}</p>
          <p class="text-xs text-gray-500">{{ facility.location }}</p>
        </div>
      </div>

      <!-- Age Group -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Age Group</p>
        <p class="text-sm text-gray-600">{{ service.ageGroup }}</p>
      </div>

      <!-- Hours -->
      <div class="bg-gray-50 rounded-lg p-3 mb-4">
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Operating Hours:</span>
            <span class="font-medium">{{ service.hours }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Capacity:</span>
            <span class="font-medium">{{ service.capacity }} children</span>
          </div>
        </div>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-4">
        <span class="text-xl font-bold text-pink-600">{{ formatPrice(service.price) }}</span>
        <span class="text-xs text-gray-400">/day</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(facility.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ facility.rating }} ({{ facility.reviews }} reviews)</span>
      </div>

      <!-- Features -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Features</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="feature in service.features.slice(0, 3)" 
            :key="feature"
            class="px-2 py-0.5 bg-pink-50 text-pink-700 rounded text-xs"
          >
            {{ feature }}
          </span>
          <span v-if="service.features.length > 3" class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
            +{{ service.features.length - 3 }}
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
          @click="bookTour"
          class="flex-1 bg-pink-600 hover:bg-pink-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Book Tour
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Facility {
  name: string
  initials: string
  location: string
  rating: number
  reviews: number
}

interface ChildcareService {
  id: string
  name: string
  type: 'Daycare' | 'Preschool' | 'After School'
  imageUrl: string
  ageGroup: string
  hours: string
  capacity: number
  price: number
  features: string[]
}

const props = defineProps<{
  service: ChildcareService
  facility: Facility
}>()

const emit = defineEmits<{
  viewDetails: [service: ChildcareService]
  bookTour: [service: ChildcareService]
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
    'Daycare': 'text-blue-900',
    'Preschool': 'text-green-900',
    'After School': 'text-purple-900',
  }
  return classes[type as keyof typeof classes] || 'text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.service)
}

const bookTour = () => {
  emit('bookTour', props.service)
}
</script>
