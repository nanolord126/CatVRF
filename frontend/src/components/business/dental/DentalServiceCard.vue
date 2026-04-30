<template>
  <div class="dental-service-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Header -->
    <div class="relative h-32 bg-gradient-to-r from-cyan-500 to-teal-500">
      <img 
        :src="clinic.imageUrl" 
        :alt="clinic.name"
        class="w-full h-full object-cover opacity-90"
      />
      <span 
        v-if="service.isEmergency"
        class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        24/7
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Clinic Info -->
      <div class="mb-4">
        <h3 class="text-lg font-bold text-gray-900">{{ clinic.name }}</h3>
        <p class="text-sm text-gray-500">{{ clinic.location }}</p>
      </div>

      <!-- Service Details -->
      <div class="bg-gray-50 rounded-lg p-3 mb-4">
        <p class="font-medium text-gray-900 mb-2">{{ service.name }}</p>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Duration:</span>
            <span class="font-medium">{{ service.duration }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Type:</span>
            <span class="font-medium">{{ service.type }}</span>
          </div>
        </div>
      </div>

      <!-- Dentist Info -->
      <div class="flex items-center gap-2 mb-4">
        <div class="w-10 h-10 bg-cyan-100 rounded-full flex items-center justify-center">
          <span class="text-cyan-600 font-bold">{{ dentist.initials }}</span>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">{{ dentist.name }}</p>
          <p class="text-xs text-gray-500">{{ dentist.specialization }}</p>
        </div>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-4">
        <span class="text-xl font-bold text-cyan-600">{{ formatPrice(service.price) }}</span>
        <span class="text-xs text-gray-400">starting from</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(clinic.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ clinic.rating }} ({{ clinic.reviews }} reviews)</span>
      </div>

      <!-- Insurance -->
      <div v-if="service.insuranceAccepted && service.insuranceAccepted.length" class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Insurance Accepted</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="insurance in service.insuranceAccepted.slice(0, 2)" 
            :key="insurance"
            class="px-2 py-0.5 bg-cyan-50 text-cyan-700 rounded text-xs"
          >
            {{ insurance }}
          </span>
          <span v-if="service.insuranceAccepted.length > 2" class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
            +{{ service.insuranceAccepted.length - 2 }}
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
          @click="bookAppointment"
          class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
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
  location: string
  imageUrl: string
  rating: number
  reviews: number
}

interface Dentist {
  name: string
  initials: string
  specialization: string
}

interface DentalService {
  id: string
  name: string
  type: string
  duration: string
  price: number
  isEmergency: boolean
  insuranceAccepted?: string[]
}

const props = defineProps<{
  clinic: Clinic
  dentist: Dentist
  service: DentalService
}>()

const emit = defineEmits<{
  viewDetails: [service: DentalService]
  bookAppointment: [service: DentalService]
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

const bookAppointment = () => {
  emit('bookAppointment', props.service)
}
</script>
