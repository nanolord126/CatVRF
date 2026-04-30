<template>
  <div class="event-planner-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-48 bg-gradient-to-br from-pink-500 to-rose-500">
      <img 
        :src="planner.imageUrl" 
        :alt="planner.name"
        class="w-full h-full object-cover opacity-90"
      />
      <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
      
      <!-- Rating Badge -->
      <div class="absolute top-2 right-2 bg-white/90 rounded-lg p-1 px-2 text-center">
        <p class="text-xs text-gray-500">Rating</p>
        <p class="text-lg font-bold text-gray-900">{{ planner.rating }}</p>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Name & Company -->
      <div class="mb-2">
        <h3 class="text-lg font-bold text-gray-900">{{ planner.name }}</h3>
        <p class="text-sm text-gray-500">{{ planner.company }}</p>
      </div>

      <!-- Specialization -->
      <div class="mb-3">
        <p class="text-sm font-medium text-gray-700 mb-1">Specializes In</p>
        <div class="flex flex-wrap gap-2">
          <span 
            v-for="spec in planner.specializations" 
            :key="spec"
            class="px-2 py-1 bg-pink-50 text-pink-700 rounded text-xs"
          >
            {{ spec }}
          </span>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-2 mb-3 text-center">
        <div class="bg-gray-50 rounded p-2">
          <p class="text-lg font-bold text-gray-900">{{ planner.eventsPlanned }}</p>
          <p class="text-xs text-gray-500">Events</p>
        </div>
        <div class="bg-gray-50 rounded p-2">
          <p class="text-lg font-bold text-gray-900">{{ planner.yearsExperience }}</p>
          <p class="text-xs text-gray-500">Years</p>
        </div>
        <div class="bg-gray-50 rounded p-2">
          <p class="text-lg font-bold text-gray-900">{{ planner.teamSize }}</p>
          <p class="text-xs text-gray-500">Team</p>
        </div>
      </div>

      <!-- Price Range -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-lg font-bold text-pink-600">{{ formatPrice(planner.minPrice) }}</span>
        <span class="text-gray-400">-</span>
        <span class="text-lg font-bold text-pink-600">{{ formatPrice(planner.maxPrice) }}</span>
      </div>

      <!-- Services Included -->
      <div class="mb-4">
        <p class="text-sm font-medium text-gray-700 mb-1">Services</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="service in planner.services.slice(0, 4)" 
            :key="service"
            class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs"
          >
            {{ service }}
          </span>
          <span v-if="planner.services.length > 4" class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
            +{{ planner.services.length - 4 }}
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="viewPortfolio"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Portfolio
        </button>
        <button 
          @click="contactPlanner"
          class="flex-1 bg-pink-600 hover:bg-pink-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Contact
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface EventPlanner {
  id: string
  name: string
  company: string
  description: string
  imageUrl: string
  rating: number
  eventsPlanned: number
  yearsExperience: number
  teamSize: number
  minPrice: number
  maxPrice: number
  specializations: string[]
  services: string[]
}

const props = defineProps<{
  planner: EventPlanner
}>()

const emit = defineEmits<{
  viewPortfolio: [planner: EventPlanner]
  contactPlanner: [planner: EventPlanner]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const viewPortfolio = () => {
  emit('viewPortfolio', props.planner)
}

const contactPlanner = () => {
  emit('contactPlanner', props.planner)
}
</script>
