<template>
  <div class="consulting-service-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ service.name }}</h3>
          <p class="text-sm text-gray-500">{{ service.firm }}</p>
        </div>
        <div class="text-right">
          <p class="font-bold text-indigo-600">{{ formatPrice(service.hourlyRate) }}/hr</p>
          <p class="text-xs text-gray-400">{{ service.rating }} ⭐</p>
        </div>
      </div>
    </div>

    <!-- Consultant Info -->
    <div class="flex items-center gap-3 mb-4">
      <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
        <span class="text-indigo-600 font-bold">{{ service.consultantInitials }}</span>
      </div>
      <div>
        <p class="font-medium text-gray-900">{{ service.consultantName }}</p>
        <p class="text-sm text-gray-500">{{ service.title }}</p>
      </div>
    </div>

    <!-- Expertise Areas -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Expertise</p>
      <div class="flex flex-wrap gap-2">
        <span 
          v-for="area in service.expertise" 
          :key="area"
          class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-xs"
        >
          {{ area }}
        </span>
      </div>
    </div>

    <!-- Description -->
    <p class="text-sm text-gray-600 mb-4">{{ service.description }}</p>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-2 mb-4 text-center">
      <div class="bg-gray-50 rounded p-2">
        <p class="text-lg font-bold text-gray-900">{{ service.clientsServed }}</p>
        <p class="text-xs text-gray-500">Clients</p>
      </div>
      <div class="bg-gray-50 rounded p-2">
        <p class="text-lg font-bold text-gray-900">{{ service.yearsExperience }}</p>
        <p class="text-xs text-gray-500">Years</p>
      </div>
      <div class="bg-gray-50 rounded p-2">
        <p class="text-lg font-bold text-gray-900">{{ service.projectsCompleted }}</p>
        <p class="text-xs text-gray-500">Projects</p>
      </div>
    </div>

    <!-- Availability -->
    <div class="flex items-center gap-2 mb-4 text-sm text-gray-600">
      <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
      </svg>
      <span>{{ service.availability }}</span>
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
        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Book Session
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface ConsultingService {
  id: string
  name: string
  firm: string
  description: string
  hourlyRate: number
  rating: number
  consultantName: string
  consultantInitials: string
  title: string
  expertise: string[]
  clientsServed: number
  yearsExperience: number
  projectsCompleted: number
  availability: string
}

const props = defineProps<{
  service: ConsultingService
}>()

const emit = defineEmits<{
  viewProfile: [service: ConsultingService]
  bookConsultation: [service: ConsultingService]
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
