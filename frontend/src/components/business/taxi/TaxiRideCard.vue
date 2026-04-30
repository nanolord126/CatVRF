<template>
  <div class="taxi-ride-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Map Preview -->
    <div class="relative h-40 bg-gradient-to-r from-yellow-400 to-amber-500">
      <div class="absolute inset-0 flex items-center justify-center">
        <div class="text-center text-white">
          <span class="text-4xl">🚕</span>
          <p class="text-sm font-medium mt-1">{{ ride.distance }} km</p>
        </div>
      </div>
      <span :class="getStatusClass(ride.status)" class="absolute top-2 right-2 px-3 py-1 rounded-full text-xs font-bold bg-white/90">
        {{ ride.status }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Route -->
      <div class="mb-4">
        <div class="flex items-start gap-2">
          <div class="flex flex-col items-center">
            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
            <div class="w-0.5 h-8 bg-gray-300"></div>
            <div class="w-2 h-2 bg-red-500 rounded-full"></div>
          </div>
          <div class="flex-1">
            <p class="text-sm font-medium text-gray-900">{{ ride.pickupAddress }}</p>
            <p class="text-sm font-medium text-gray-900 mt-4">{{ ride.dropoffAddress }}</p>
          </div>
        </div>
      </div>

      <!-- Ride Details -->
      <div class="bg-gray-50 rounded-lg p-3 mb-4">
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Ride ID:</span>
            <span class="font-medium">{{ ride.rideId }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Driver:</span>
            <span class="font-medium">{{ ride.driverName }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Car:</span>
            <span class="font-medium">{{ ride.carModel }} ({{ ride.carColor }})</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Plate:</span>
            <span class="font-medium">{{ ride.plateNumber }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Duration:</span>
            <span class="font-medium">{{ ride.duration }}</span>
          </div>
        </div>
      </div>

      <!-- Price -->
      <div class="flex items-center justify-between mb-4">
        <div>
          <span class="text-2xl font-bold text-yellow-600">{{ formatPrice(ride.price) }}</span>
          <span class="text-xs text-gray-400 ml-1">estimated</span>
        </div>
        <div class="flex items-center gap-1 text-sm">
          <span class="text-yellow-400">★</span>
          <span class="text-gray-600">{{ ride.driverRating }}</span>
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
          v-if="ride.status === 'In Progress'"
          @click="contactDriver"
          class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Contact
        </button>
        <button 
          v-else-if="ride.status === 'Completed'"
          @click="rateDriver"
          class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Rate Driver
        </button>
        <button 
          v-else
          @click="cancel"
          class="flex-1 bg-red-100 hover:bg-red-200 text-red-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface TaxiRide {
  id: string
  rideId: string
  status: 'Requested' | 'In Progress' | 'Completed' | 'Cancelled'
  pickupAddress: string
  dropoffAddress: string
  distance: number
  duration: string
  driverName: string
  driverRating: number
  carModel: string
  carColor: string
  plateNumber: string
  price: number
}

const props = defineProps<{
  ride: TaxiRide
}>()

const emit = defineEmits<{
  viewDetails: [ride: TaxiRide]
  contactDriver: [ride: TaxiRide]
  rateDriver: [ride: TaxiRide]
  cancel: [ride: TaxiRide]
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
    'Requested': 'text-blue-900',
    'In Progress': 'text-yellow-900',
    'Completed': 'text-green-900',
    'Cancelled': 'text-red-900',
  }
  return classes[status as keyof typeof classes] || 'text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.ride)
}

const contactDriver = () => {
  emit('contactDriver', props.ride)
}

const rateDriver = () => {
  emit('rateDriver', props.ride)
}

const cancel = () => {
  emit('cancel', props.ride)
}
</script>
