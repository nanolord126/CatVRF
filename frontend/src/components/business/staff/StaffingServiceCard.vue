<template>
  <div class="staffing-service-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ service.name }}</h3>
          <p class="text-sm text-gray-500">{{ service.category }}</p>
        </div>
        <div class="text-right">
          <p class="font-bold text-cyan-600">{{ formatPrice(service.hourlyRate) }}/hr</p>
          <p class="text-xs text-gray-400">{{ service.rating }} ⭐</p>
        </div>
      </div>
    </div>

    <!-- Company Info -->
    <div class="flex items-center gap-3 mb-4">
      <div class="w-12 h-12 bg-cyan-100 rounded-full flex items-center justify-center">
        <span class="text-cyan-600 font-bold">{{ service.companyInitials }}</span>
      </div>
      <div>
        <p class="font-medium text-gray-900">{{ service.companyName }}</p>
        <p class="text-sm text-gray-500">{{ service.location }}</p>
      </div>
    </div>

    <!-- Roles Available -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Roles Available</p>
      <div class="flex flex-wrap gap-2">
        <span 
          v-for="role in service.roles" 
          :key="role"
          class="px-2 py-1 bg-cyan-50 text-cyan-700 rounded text-xs"
        >
          {{ role }}
        </span>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-2 mb-4 text-center">
      <div class="bg-gray-50 rounded p-2">
        <p class="text-lg font-bold text-gray-900">{{ service.activeWorkers }}</p>
        <p class="text-xs text-gray-500">Active</p>
      </div>
      <div class="bg-gray-50 rounded p-2">
        <p class="text-lg font-bold text-gray-900">{{ service.placements }}</p>
        <p class="text-xs text-gray-500">Placements</p>
      </div>
      <div class="bg-gray-50 rounded p-2">
        <p class="text-lg font-bold text-gray-900">{{ service.satisfactionRate }}%</p>
        <p class="text-xs text-gray-500">Satisfaction</p>
      </div>
    </div>

    <!-- Features -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Includes</p>
      <div class="space-y-1">
        <div v-for="feature in service.features" :key="feature" class="flex items-center gap-2 text-sm text-gray-600">
          <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
          </svg>
          <span>{{ feature }}</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="viewWorkers"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        View Workers
      </button>
      <button 
        @click="requestStaff"
        class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Request Staff
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface StaffingService {
  id: string
  name: string
  category: string
  companyName: string
  companyInitials: string
  location: string
  hourlyRate: number
  rating: number
  activeWorkers: number
  placements: number
  satisfactionRate: number
  roles: string[]
  features: string[]
}

const props = defineProps<{
  service: StaffingService
}>()

const emit = defineEmits<{
  viewWorkers: [service: StaffingService]
  requestStaff: [service: StaffingService]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const viewWorkers = () => {
  emit('viewWorkers', props.service)
}

const requestStaff = () => {
  emit('requestStaff', props.service)
}
</script>
