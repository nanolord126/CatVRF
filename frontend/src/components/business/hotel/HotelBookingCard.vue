<template>
  <div class="hotel-booking-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40">
      <img 
        :src="hotel.imageUrl" 
        :alt="hotel.name"
        class="w-full h-full object-cover"
      />
      <span :class="getStatusClass(booking.status)" class="absolute top-2 right-2 px-3 py-1 rounded-full text-xs font-bold bg-white/90">
        {{ booking.status }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Hotel Info -->
      <div class="mb-4">
        <h3 class="text-lg font-bold text-gray-900">{{ hotel.name }}</h3>
        <p class="text-sm text-gray-500">{{ hotel.location }}</p>
        <div class="flex items-center gap-1 mt-1">
          <span class="text-yellow-400">★</span>
          <span class="text-sm text-gray-600">{{ hotel.rating }} ({{ hotel.reviews }} reviews)</span>
        </div>
      </div>

      <!-- Booking Details -->
      <div class="bg-gray-50 rounded-lg p-3 mb-4">
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Booking #:</span>
            <span class="font-medium">{{ booking.bookingNumber }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Check-in:</span>
            <span class="font-medium">{{ booking.checkIn }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Check-out:</span>
            <span class="font-medium">{{ booking.checkOut }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Nights:</span>
            <span class="font-medium">{{ booking.nights }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Room:</span>
            <span class="font-medium">{{ booking.roomType }} ({{ booking.guests }} guests)</span>
          </div>
        </div>
      </div>

      <!-- Amenities -->
      <div class="mb-4">
        <p class="text-sm font-medium text-gray-700 mb-1">Room Amenities</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="amenity in booking.amenities.slice(0, 4)" 
            :key="amenity"
            class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-xs"
          >
            {{ amenity }}
          </span>
          <span v-if="booking.amenities.length > 4" class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
            +{{ booking.amenities.length - 4 }}
          </span>
        </div>
      </div>

      <!-- Price -->
      <div class="flex items-center justify-between mb-4">
        <div>
          <span class="text-2xl font-bold text-blue-600">{{ formatPrice(booking.totalPrice) }}</span>
          <span class="text-xs text-gray-400 ml-1">total</span>
        </div>
        <p class="text-xs text-gray-500">{{ formatPrice(booking.pricePerNight) }}/night</p>
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
          v-if="booking.status === 'Confirmed'"
          @click="modify"
          class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Modify
        </button>
        <button 
          v-else
          @click="bookAgain"
          class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Book Again
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Hotel {
  name: string
  location: string
  imageUrl: string
  rating: number
  reviews: number
}

interface Booking {
  id: string
  bookingNumber: string
  status: 'Confirmed' | 'Checked In' | 'Completed' | 'Cancelled'
  checkIn: string
  checkOut: string
  nights: number
  roomType: string
  guests: number
  amenities: string[]
  pricePerNight: number
  totalPrice: number
}

const props = defineProps<{
  hotel: Hotel
  booking: Booking
}>()

const emit = defineEmits<{
  viewDetails: [booking: Booking]
  modify: [booking: Booking]
  bookAgain: [booking: Booking]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getStatusClass = (status: string) => {
  const classes = {
    'Confirmed': 'text-blue-900',
    'Checked In': 'text-green-900',
    'Completed': 'text-gray-900',
    'Cancelled': 'text-red-900',
  }
  return classes[status as keyof typeof classes] || 'text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.booking)
}

const modify = () => {
  emit('modify', props.booking)
}

const bookAgain = () => {
  emit('bookAgain', props.booking)
}
</script>
