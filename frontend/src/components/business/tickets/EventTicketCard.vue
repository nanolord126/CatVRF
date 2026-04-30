<template>
  <div class="event-ticket-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-48 bg-gradient-to-br from-purple-500 to-pink-500">
      <img 
        :src="event.imageUrl" 
        :alt="event.name"
        class="w-full h-full object-cover opacity-80"
      />
      <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
      
      <!-- Date Badge -->
      <div class="absolute top-4 left-4 bg-white rounded-lg p-2 text-center">
        <p class="text-xs text-gray-500 uppercase">{{ eventDate.day }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ eventDate.date }}</p>
        <p class="text-xs text-gray-500 uppercase">{{ eventDate.month }}</p>
      </div>

      <!-- Category Badge -->
      <span class="absolute top-4 right-4 bg-white/90 text-purple-900 px-3 py-1 rounded-full text-xs font-medium">
        {{ event.category }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Name -->
      <h3 class="text-xl font-bold text-gray-900 mb-2">{{ event.name }}</h3>
      
      <!-- Location -->
      <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
        </svg>
        <span>{{ event.venue }}</span>
      </div>

      <!-- Time -->
      <div class="flex items-center gap-2 text-sm text-gray-600 mb-3">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>{{ event.time }}</span>
      </div>

      <!-- Price Range -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-lg font-bold text-purple-600">{{ formatPrice(event.minPrice) }}</span>
        <span class="text-gray-400">-</span>
        <span class="text-lg font-bold text-purple-600">{{ formatPrice(event.maxPrice) }}</span>
      </div>

      <!-- Available Tickets -->
      <div class="flex items-center justify-between text-sm mb-4">
        <span class="text-gray-600">Available tickets:</span>
        <span :class="getTicketsClass(event.availableTickets)" class="font-medium">
          {{ event.availableTickets }}
        </span>
      </div>

      <!-- Features -->
      <div class="flex flex-wrap gap-2 mb-4">
        <span 
          v-for="feature in event.features" 
          :key="feature"
          class="px-2 py-1 bg-purple-50 text-purple-700 rounded text-xs"
        >
          {{ feature }}
        </span>
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
          @click="buyTickets"
          :disabled="event.availableTickets === 0"
          class="flex-1 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Buy Tickets
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Event {
  id: string
  name: string
  category: string
  description: string
  imageUrl: string
  venue: string
  date: string
  time: string
  minPrice: number
  maxPrice: number
  availableTickets: number
  features: string[]
}

const props = defineProps<{
  event: Event
}>()

const emit = defineEmits<{
  viewDetails: [event: Event]
  buyTickets: [event: Event]
}>()

const eventDate = computed(() => {
  const date = new Date(props.event.date)
  return {
    day: date.toLocaleDateString('ru-RU', { weekday: 'short' }),
    date: date.getDate(),
    month: date.toLocaleDateString('ru-RU', { month: 'short' }),
  }
})

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getTicketsClass = (tickets: number) => {
  if (tickets === 0) return 'text-red-600'
  if (tickets < 50) return 'text-yellow-600'
  return 'text-green-600'
}

const viewDetails = () => {
  emit('viewDetails', props.event)
}

const buyTickets = () => {
  emit('buyTickets', props.event)
}
</script>
