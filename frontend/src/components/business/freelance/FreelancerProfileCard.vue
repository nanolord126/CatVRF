<template>
  <div class="freelancer-profile-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Header -->
    <div class="relative h-32 bg-gradient-to-r from-teal-500 to-cyan-500">
      <div class="absolute -bottom-12 left-4">
        <div class="w-24 h-24 bg-white rounded-full border-4 border-white shadow-lg flex items-center justify-center">
          <span class="text-2xl font-bold text-teal-600">{{ freelancer.initials }}</span>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="pt-14 p-4">
      <!-- Name & Title -->
      <div class="mb-3">
        <h3 class="text-lg font-bold text-gray-900">{{ freelancer.name }}</h3>
        <p class="text-sm text-gray-500">{{ freelancer.title }}</p>
      </div>

      <!-- Location & Rate -->
      <div class="flex items-center gap-4 mb-3 text-sm text-gray-600">
        <div class="flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          <span>{{ freelancer.location }}</span>
        </div>
        <div class="flex items-center gap-1">
          <span class="font-bold text-teal-600">{{ formatPrice(freelancer.hourlyRate) }}/hr</span>
        </div>
      </div>

      <!-- Skills -->
      <div class="mb-3">
        <p class="text-sm font-medium text-gray-700 mb-2">Skills</p>
        <div class="flex flex-wrap gap-2">
          <span 
            v-for="skill in freelancer.skills" 
            :key="skill"
            class="px-2 py-1 bg-teal-50 text-teal-700 rounded text-xs"
          >
            {{ skill }}
          </span>
        </div>
      </div>

      <!-- Bio -->
      <p class="text-sm text-gray-600 mb-4 line-clamp-3">{{ freelancer.bio }}</p>

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-2 mb-4 text-center">
        <div class="bg-gray-50 rounded p-2">
          <p class="text-lg font-bold text-gray-900">{{ freelancer.jobsCompleted }}</p>
          <p class="text-xs text-gray-500">Jobs</p>
        </div>
        <div class="bg-gray-50 rounded p-2">
          <p class="text-lg font-bold text-gray-900">{{ freelancer.rating }}</p>
          <p class="text-xs text-gray-500">Rating</p>
        </div>
        <div class="bg-gray-50 rounded p-2">
          <p class="text-lg font-bold text-gray-900">{{ freelancer.onTimeRate }}%</p>
          <p class="text-xs text-gray-500">On Time</p>
        </div>
      </div>

      <!-- Availability -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <span 
          :class="freelancer.available ? 'bg-green-100 text-green-900' : 'bg-gray-100 text-gray-900'"
          class="px-2 py-1 rounded text-xs font-medium"
        >
          {{ freelancer.available ? 'Available' : 'Busy' }}
        </span>
        <span class="text-gray-500">{{ freelancer.responseTime }} response time</span>
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
          @click="hire"
          :disabled="!freelancer.available"
          class="flex-1 bg-teal-600 hover:bg-teal-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Hire
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Freelancer {
  id: string
  name: string
  initials: string
  title: string
  bio: string
  hourlyRate: number
  location: string
  skills: string[]
  jobsCompleted: number
  rating: number
  onTimeRate: number
  available: boolean
  responseTime: string
}

const props = defineProps<{
  freelancer: Freelancer
}>()

const emit = defineEmits<{
  viewProfile: [freelancer: Freelancer]
  hire: [freelancer: Freelancer]
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
  emit('viewProfile', props.freelancer)
}

const hire = () => {
  emit('hire', props.freelancer)
}
</script>
