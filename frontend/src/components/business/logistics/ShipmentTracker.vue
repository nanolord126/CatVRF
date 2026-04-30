<template>
  <div class="shipment-tracker bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Track Shipment</h2>
      <p class="text-gray-600">Track your shipment in real-time</p>
    </div>

    <!-- Search -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Tracking Number</label>
      <div class="flex gap-2">
        <input 
          v-model="trackingNumber"
          type="text"
          placeholder="Enter tracking number"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        />
        <button 
          @click="track"
          class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors"
        >
          Track
        </button>
      </div>
    </div>

    <!-- Shipment Info -->
    <div v-if="shipment" class="mb-6">
      <div class="bg-gray-50 rounded-lg p-4 mb-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-500">From</p>
            <p class="font-medium">{{ shipment.origin }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">To</p>
            <p class="font-medium">{{ shipment.destination }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Status</p>
            <span :class="getStatusClass(shipment.status)" class="px-2 py-1 rounded text-xs font-medium">
              {{ shipment.status }}
            </span>
          </div>
          <div>
            <p class="text-xs text-gray-500">ETA</p>
            <p class="font-medium">{{ formatDate(shipment.eta) }}</p>
          </div>
        </div>
      </div>

      <!-- Progress Timeline -->
      <div class="relative">
        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
        
        <div 
          v-for="(event, index) in shipment.events" 
          :key="index"
          class="relative pl-10 pb-6 last:pb-0"
        >
          <div 
            :class="[
              'absolute left-2 w-5 h-5 rounded-full border-2',
              event.completed ? 'bg-green-500 border-green-500' : 'bg-white border-gray-300'
            ]"
          ></div>
          <div>
            <p class="font-medium text-gray-900">{{ event.title }}</p>
            <p class="text-sm text-gray-500">{{ event.location }}</p>
            <p class="text-xs text-gray-400">{{ formatDateTime(event.timestamp) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Map Placeholder -->
    <div v-if="shipment" class="mb-6">
      <div class="bg-blue-50 rounded-lg p-8 text-center">
        <svg class="w-12 h-12 mx-auto text-blue-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
        </svg>
        <p class="text-gray-600">Live map view would be displayed here</p>
      </div>
    </div>

    <!-- Actions -->
    <div v-if="shipment" class="flex gap-2">
      <button 
        @click="contactCarrier"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Contact Carrier
      </button>
      <button 
        @click="scheduleDelivery"
        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Schedule Delivery
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface ShipmentEvent {
  title: string
  location: string
  timestamp: string
  completed: boolean
}

interface Shipment {
  trackingNumber: string
  origin: string
  destination: string
  status: 'In Transit' | 'Delivered' | 'Out for Delivery' | 'Pending'
  eta: string
  events: ShipmentEvent[]
}

const trackingNumber = ref('')
const shipment = ref<Shipment | null>(null)

const track = () => {
  // Mock data - in real app, this would be an API call
  shipment.value = {
    trackingNumber: trackingNumber.value,
    origin: 'Moscow, Russia',
    destination: 'Saint Petersburg, Russia',
    status: 'In Transit',
    eta: '2026-04-27',
    events: [
      { title: 'Order Placed', location: 'Moscow', timestamp: '2026-04-24T10:00:00', completed: true },
      { title: 'Picked Up', location: 'Moscow', timestamp: '2026-04-24T14:00:00', completed: true },
      { title: 'In Transit', location: 'Tver', timestamp: '2026-04-25T08:00:00', completed: true },
      { title: 'Out for Delivery', location: 'Saint Petersburg', timestamp: '2026-04-27T09:00:00', completed: false },
      { title: 'Delivered', location: 'Saint Petersburg', timestamp: '2026-04-27T14:00:00', completed: false },
    ]
  }
}

const getStatusClass = (status: string) => {
  const classes = {
    'In Transit': 'bg-blue-100 text-blue-900',
    'Delivered': 'bg-green-100 text-green-900',
    'Out for Delivery': 'bg-yellow-100 text-yellow-900',
    'Pending': 'bg-gray-100 text-gray-900',
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const formatDateTime = (date: string) => {
  return new Date(date).toLocaleString('ru-RU', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const contactCarrier = () => {
  // Emit event or navigate to contact page
}

const scheduleDelivery = () => {
  // Emit event or navigate to scheduling page
}
</script>
