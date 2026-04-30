<template>
  <div class="legal-service-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ service.name }}</h3>
          <p class="text-sm text-gray-500">{{ service.category }}</p>
        </div>
        <div class="text-right">
          <p class="font-bold text-purple-600">{{ formatPrice(service.hourlyRate) }}/hr</p>
          <p class="text-xs text-gray-400">{{ service.rating }} ⭐</p>
        </div>
      </div>
    </div>

    <!-- Lawyer Info -->
    <div class="flex items-center gap-3 mb-4">
      <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
        <span class="text-purple-600 font-bold">{{ service.lawyerInitials }}</span>
      </div>
      <div>
        <p class="font-medium text-gray-900">{{ service.lawyerName }}</p>
        <p class="text-sm text-gray-500">{{ service.experience }} years experience</p>
      </div>
    </div>

    <!-- Specialization -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Specializations</p>
      <div class="flex flex-wrap gap-2">
        <span 
          v-for="spec in service.specializations" 
          :key="spec"
          class="px-2 py-1 bg-purple-50 text-purple-700 rounded text-xs"
        >
          {{ spec }}
        </span>
      </div>
    </div>

    <!-- Description -->
    <p class="text-sm text-gray-600 mb-4">{{ service.description }}</p>

    <!-- Availability -->
    <div class="flex items-center gap-2 mb-4 text-sm text-gray-600">
      <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
      </svg>
      <span>{{ service.availability }}</span>
    </div>

    <!-- Consultation Options -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <p class="text-xs text-gray-500 mb-2">Consultation Options</p>
      <div class="space-y-2">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
          </svg>
          <span class="text-sm">Phone</span>
        </div>
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
          </svg>
          <span class="text-sm">Video</span>
        </div>
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          <span class="text-sm">In-Person</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="viewProfile"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        View Profile
      </button>
      <button 
        @click="bookConsultation"
        class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Book Consultation
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface LegalService {
  id: string
  name: string
  category: string
  description: string
  hourlyRate: number
  rating: number
  lawyerName: string
  lawyerInitials: string
  experience: number
  specializations: string[]
  availability: string
}

const props = defineProps<{
  service: LegalService
}>()

const emit = defineEmits<{
  viewProfile: [service: LegalService]
  bookConsultation: [service: LegalService]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const viewProfile = () => {
  emit('viewProfile', props.service)
}

const bookConsultation = () => {
  emit('bookConsultation', props.service)
}
</script>
