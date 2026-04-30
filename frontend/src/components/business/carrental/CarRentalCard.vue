<template>
  <div class="car-rental-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-blue-50">
      <img 
        :src="car.imageUrl" 
        :alt="car.name"
        class="w-full h-full object-contain p-4"
      />
      <span 
        :class="getTransmissionClass(car.transmission)"
        class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium"
      >
        {{ car.transmission }}
      </span>
      <span 
        v-if="car.isAvailable"
        class="absolute top-2 right-2 bg-green-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        AVAILABLE
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Brand & Model -->
      <p class="text-xs text-gray-500 mb-1 uppercase font-medium">{{ car.brand }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ car.name }}</h3>
      
      <!-- Specs -->
      <div class="flex items-center gap-4 mb-3 text-sm text-gray-600">
        <div class="flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span>{{ car.year }}</span>
        </div>
        <div class="flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path d="M13 10V3L4 14h7v7l9-11h-7z"></path>
          </svg>
          <span>{{ car.fuelType }}</span>
        </div>
        <div class="flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
          </svg>
          <span>{{ car.seats }} seats</span>
        </div>
      </div>

      <!-- Features -->
      <div class="flex flex-wrap gap-2 mb-3">
        <span 
          v-for="feature in car.features.slice(0, 4)" 
          :key="feature"
          class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs"
        >
          {{ feature }}
        </span>
        <span v-if="car.features.length > 4" class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">
          +{{ car.features.length - 4 }}
        </span>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-blue-600">{{ formatPrice(car.price) }}</span>
        <span class="text-xs text-gray-400">/ day</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(car.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ car.rating }} ({{ car.tripsCompleted }} trips)</span>
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
          @click="bookCar"
          :disabled="!car.isAvailable"
          class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Book Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface CarRental {
  id: string
  name: string
  brand: string
  description: string
  imageUrl: string
  price: number
  rating: number
  tripsCompleted: number
  year: number
  fuelType: string
  transmission: 'Automatic' | 'Manual'
  seats: number
  features: string[]
  isAvailable: boolean
}

const props = defineProps<{
  car: CarRental
}>()

const emit = defineEmits<{
  viewDetails: [car: CarRental]
  bookCar: [car: CarRental]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getTransmissionClass = (transmission: string) => {
  const classes = {
    'Automatic': 'bg-blue-100 text-blue-900',
    'Manual': 'bg-gray-100 text-gray-900',
  }
  return classes[transmission as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.car)
}

const bookCar = () => {
  emit('bookCar', props.car)
}
</script>
