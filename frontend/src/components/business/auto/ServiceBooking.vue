<template>
  <div class="auto-service-booking bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 mb-2">Book Auto Service</h2>
      <p class="text-gray-600">Schedule your vehicle maintenance or repair</p>
    </div>

    <!-- Vehicle Selection -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Select Vehicle</label>
      <select 
        v-model="selectedVehicle"
        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
      >
        <option value="">Choose a vehicle...</option>
        <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
          {{ vehicle.make }} {{ vehicle.model }} ({{ vehicle.licensePlate }})
        </option>
      </select>
      <button @click="addVehicle" class="mt-2 text-purple-600 hover:text-purple-700 text-sm font-medium">
        + Add new vehicle
      </button>
    </div>

    <!-- Service Type -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Service Type</label>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
        <button 
          v-for="service in serviceTypes"
          :key="service.id"
          @click="selectService(service)"
          :class="[
            'p-4 rounded-lg border-2 transition-all text-left',
            selectedService?.id === service.id 
              ? 'border-purple-500 bg-purple-50' 
              : 'border-gray-200 hover:border-purple-300'
          ]"
        >
          <component :is="service.icon" class="w-6 h-6 mb-2 text-gray-700" />
          <p class="font-medium text-gray-900">{{ service.name }}</p>
          <p class="text-sm text-gray-500">from {{ formatPrice(service.minPrice) }}</p>
        </button>
      </div>
    </div>

    <!-- Service Details -->
    <div v-if="selectedService" class="mb-6 p-4 bg-gray-50 rounded-lg">
      <h3 class="font-semibold text-gray-900 mb-2">{{ selectedService.name }}</h3>
      <p class="text-sm text-gray-600 mb-3">{{ selectedService.description }}</p>
      <div class="flex items-center gap-2 text-sm text-gray-500">
        <Clock class="w-4 h-4" />
        <span>Estimated duration: {{ selectedService.duration }}</span>
      </div>
    </div>

    <!-- Date & Time Selection -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <!-- Date -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Date</label>
        <input 
          v-model="selectedDate"
          type="date"
          :min="minDate"
          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
        />
      </div>

      <!-- Time -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Time</label>
        <select 
          v-model="selectedTime"
          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
        >
          <option value="">Select time...</option>
          <option v-for="time in availableTimes" :key="time" :value="time">
            {{ time }}
          </option>
        </select>
      </div>
    </div>

    <!-- Location -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Service Location</label>
      <div class="space-y-2">
        <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
          <input 
            v-model="locationType" 
            type="radio" 
            value="center"
            class="text-purple-600 focus:ring-purple-500"
          />
          <div class="flex-1">
            <p class="font-medium text-gray-900">Service Center</p>
            <p class="text-sm text-gray-500">Visit our auto service center</p>
          </div>
          <MapPin class="w-5 h-5 text-gray-400" />
        </label>
        <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
          <input 
            v-model="locationType" 
            type="radio" 
            value="mobile"
            class="text-purple-600 focus:ring-purple-500"
          />
          <div class="flex-1">
            <p class="font-medium text-gray-900">Mobile Service</p>
            <p class="text-sm text-gray-500">We come to your location</p>
          </div>
          <Truck class="w-5 h-5 text-gray-400" />
        </label>
      </div>
    </div>

    <!-- Additional Notes -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes (optional)</label>
      <textarea 
        v-model="notes"
        rows="3"
        placeholder="Describe any issues or special requests..."
        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
      />
    </div>

    <!-- Summary -->
    <div v-if="selectedService && selectedDate && selectedTime" class="mb-6 p-4 bg-purple-50 rounded-lg">
      <h3 class="font-semibold text-gray-900 mb-3">Booking Summary</h3>
      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Service:</span>
          <span class="font-medium">{{ selectedService.name }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Date:</span>
          <span class="font-medium">{{ formatDate(selectedDate) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Time:</span>
          <span class="font-medium">{{ selectedTime }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Location:</span>
          <span class="font-medium">{{ locationType === 'center' ? 'Service Center' : 'Mobile Service' }}</span>
        </div>
        <div class="border-t pt-2 mt-2 flex justify-between">
          <span class="text-gray-600 font-medium">Estimated Total:</span>
          <span class="font-bold text-purple-600">{{ formatPrice(selectedService.minPrice) }}</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-3">
      <button 
        @click="cancel"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-3 px-6 rounded-lg font-medium transition-colors"
      >
        Cancel
      </button>
      <button 
        @click="confirmBooking"
        :disabled="!canConfirm"
        class="flex-1 bg-purple-600 hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-3 px-6 rounded-lg font-medium transition-colors"
      >
        Confirm Booking
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Clock, MapPin, Truck, Wrench, Droplets, Zap, Cog } from 'lucide-vue-next'

interface Vehicle {
  id: string
  make: string
  model: string
  licensePlate: string
}

interface ServiceType {
  id: string
  name: string
  description: string
  minPrice: number
  duration: string
  icon: any
}

const emit = defineEmits<{
  cancel: []
  confirmBooking: [booking: any]
  addVehicle: []
}>()

const selectedVehicle = ref<string>()
const selectedService = ref<ServiceType>()
const selectedDate = ref<string>()
const selectedTime = ref<string>()
const locationType = ref<'center' | 'mobile'>('center')
const notes = ref('')

const vehicles = ref<Vehicle[]>([
  { id: '1', make: 'Toyota', model: 'Camry', licensePlate: 'A123BC' },
  { id: '2', make: 'BMW', model: 'X5', licensePlate: 'X456YZ' },
])

const serviceTypes = ref<ServiceType[]>([
  { id: '1', name: 'Oil Change', description: 'Full oil change with filter replacement', minPrice: 3000, duration: '30-45 min', icon: Droplets },
  { id: '2', name: 'Brake Service', description: 'Brake inspection and pad replacement', minPrice: 5000, duration: '1-2 hours', icon: Wrench },
  { id: '3', name: 'Battery Check', description: 'Battery testing and replacement if needed', minPrice: 1500, duration: '20-30 min', icon: Zap },
  { id: '4', name: 'General Service', description: 'Comprehensive vehicle inspection', minPrice: 8000, duration: '2-3 hours', icon: Cog },
])

const availableTimes = ref([
  '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00',
])

const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const canConfirm = computed(() => {
  return selectedVehicle.value && selectedService.value && selectedDate.value && selectedTime.value
})

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(price)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const selectService = (service: ServiceType) => {
  selectedService.value = service
}

const addVehicle = () => {
  emit('addVehicle')
}

const cancel = () => {
  emit('cancel')
}

const confirmBooking = () => {
  if (canConfirm.value) {
    emit('confirmBooking', {
      vehicleId: selectedVehicle.value,
      serviceId: selectedService.value?.id,
      date: selectedDate.value,
      time: selectedTime.value,
      locationType: locationType.value,
      notes: notes.value,
    })
  }
}
</script>
