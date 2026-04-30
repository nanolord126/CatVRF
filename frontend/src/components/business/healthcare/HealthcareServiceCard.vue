<template>
  <div class="healthcare-service-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-gradient-to-r from-teal-500 to-cyan-500">
      <img 
        :src="service.imageUrl" 
        :alt="service.name"
        class="w-full h-full object-cover opacity-90"
      />
      <span 
        v-if="service.isEmergency"
        class="absolute top-2 left-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        24/7
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Service Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ service.name }}</h3>
      
      <!-- Category -->
      <p class="text-sm text-gray-500 mb-3">{{ service.category }}</p>

      <!-- Clinic Info -->
      <div class="flex items-center gap-2 mb-4">
        <div class="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center">
          <span class="text-teal-600 font-bold">{{ clinic.initials }}</span>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">{{ clinic.name }}</p>
          <p class="text-xs text-gray-500">{{ clinic.location }}</p>
        </div>
      </div>

      <!-- Doctor Info -->
      <div class="flex items-center gap-2 mb-4">
        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
          <span class="text-blue-600 font-bold text-xs">{{ doctor.initials }}</span>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">{{ doctor.name }}</p>
          <p class="text-xs text-gray-500">{{ doctor.specialization }}</p>
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
          <span v-for="i in Math.floor(clinic.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ clinic.rating }} ({{ clinic.reviews }} reviews)</span>
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
          class="flex-1 bg-teal-600 hover:bg-teal-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Book Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Clinic {
  name: string
  initials: string
  location: string
  rating: number
  reviews: number
}

interface Doctor {
  name: string
  initials: string
  specialization: string
}

interface HealthcareService {
  id: string
  name: string
  category: string
  imageUrl: string
  duration: string
  price: number
  isEmergency: boolean
}

const props = defineProps<{
  service: HealthcareService
  clinic: Clinic
  doctor: Doctor
}>()

const emit = defineEmits<{
  viewDetails: [service: HealthcareService]
  book: [service: HealthcareService]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const viewDetails = () => {
  emit('viewDetails', props.service)
}

const book = () => {
  emit('book', props.service)
}
</script>
