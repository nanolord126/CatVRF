<template>
  <div class="home-service-booking bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 mb-2">Book Home Service</h2>
      <p class="text-gray-600">Professional home services at your doorstep</p>
    </div>

    <!-- Service Category -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Service Category</label>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
        <button 
          v-for="category in serviceCategories"
          :key="category.id"
          @click="selectCategory(category)"
          :class="[
            'p-3 rounded-lg border-2 transition-all text-center',
            selectedCategory?.id === category.id 
              ? 'border-amber-500 bg-amber-50' 
              : 'border-gray-200 hover:border-amber-300'
          ]"
        >
          <div class="text-2xl mb-1">{{ category.icon }}</div>
          <p class="text-xs font-medium">{{ category.name }}</p>
        </button>
      </div>
    </div>

    <!-- Services -->
    <div v-if="selectedCategory" class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Select Service</label>
      <div class="space-y-3">
        <button 
          v-for="service in filteredServices"
          :key="service.id"
          @click="selectService(service)"
          :class="[
            'p-4 rounded-lg border-2 transition-all text-left w-full',
            selectedService?.id === service.id 
              ? 'border-amber-500 bg-amber-50' 
              : 'border-gray-200 hover:border-amber-300'
          ]"
        >
          <div class="flex justify-between items-start mb-2">
            <div>
              <p class="font-medium text-gray-900">{{ service.name }}</p>
              <p class="text-sm text-gray-500">{{ service.duration }}</p>
            </div>
            <p class="font-bold text-amber-600">{{ formatPrice(service.price) }}</p>
          </div>
          <p class="text-xs text-gray-400">{{ service.description }}</p>
        </button>
      </div>
    </div>

    <!-- Date & Time -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Date</label>
        <input 
          v-model="selectedDate"
          type="date"
          :min="minDate"
          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Preferred Time</label>
        <select 
          v-model="selectedTime"
          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
        >
          <option value="">Select time...</option>
          <option v-for="time in timeSlots" :key="time" :value="time">
            {{ time }}
          </option>
        </select>
      </div>
    </div>

    <!-- Address -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Service Address</label>
      <input 
        v-model="address"
        type="text"
        placeholder="Enter your address"
        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
      />
    </div>

    <!-- Additional Notes -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
      <textarea 
        v-model="notes"
        rows="3"
        placeholder="Any special requests or instructions..."
        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-amber-500 focus:border-transparent"
      />
    </div>

    <!-- Summary -->
    <div v-if="selectedService && selectedDate" class="mb-6 p-4 bg-amber-50 rounded-lg">
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
          <span class="font-medium">{{ selectedTime || 'Not selected' }}</span>
        </div>
        <div class="border-t pt-2 mt-2 flex justify-between">
          <span class="text-gray-600 font-medium">Total:</span>
          <span class="font-bold text-amber-600">{{ formatPrice(selectedService.price) }}</span>
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
        class="flex-1 bg-amber-600 hover:bg-amber-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-3 px-6 rounded-lg font-medium transition-colors"
      >
        Book Service
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface ServiceCategory {
  id: string
  name: string
  icon: string
}

interface HomeService {
  id: string
  categoryId: string
  name: string
  description: string
  price: number
  duration: string
}

const emit = defineEmits<{
  cancel: []
  confirmBooking: [booking: any]
}>()

const selectedCategory = ref<ServiceCategory>()
const selectedService = ref<HomeService>()
const selectedDate = ref<string>()
const selectedTime = ref<string>()
const address = ref('')
const notes = ref('')

const serviceCategories = ref<ServiceCategory[]>([
  { id: '1', name: 'Plumbing', icon: '🔧' },
  { id: '2', name: 'Electrical', icon: '⚡' },
  { id: '3', name: 'Cleaning', icon: '🧹' },
  { id: '4', name: 'HVAC', icon: '❄️' },
  { id: '5', name: 'Painting', icon: '🎨' },
  { id: '6', name: 'Carpentry', icon: '🔨' },
  { id: '7', name: 'Appliance', icon: '🔌' },
  { id: '8', name: 'Pest Control', icon: '🐛' },
])

const services = ref<HomeService[]>([
  { id: '1', categoryId: '1', name: 'Pipe Repair', description: 'Fix leaking pipes and fittings', price: 3500, duration: '1-2 hours' },
  { id: '2', categoryId: '1', name: 'Drain Cleaning', description: 'Unclog drains and pipes', price: 2500, duration: '1 hour' },
  { id: '3', categoryId: '2', name: 'Outlet Installation', description: 'Install electrical outlets', price: 1500, duration: '30 min' },
  { id: '4', categoryId: '2', name: 'Light Fixture', description: 'Install or repair light fixtures', price: 2000, duration: '1 hour' },
  { id: '5', categoryId: '3', name: 'Deep Cleaning', description: 'Complete home deep cleaning', price: 8000, duration: '4-6 hours' },
  { id: '6', categoryId: '3', name: 'Regular Cleaning', description: 'Standard home cleaning', price: 4000, duration: '2-3 hours' },
])

const timeSlots = ref([
  '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00',
])

const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const filteredServices = computed(() => {
  if (!selectedCategory.value) return []
  return services.value.filter(s => s.categoryId === selectedCategory.value?.id)
})

const canConfirm = computed(() => {
  return selectedService.value && selectedDate.value && selectedTime.value && address.value
})

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
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

const selectCategory = (category: ServiceCategory) => {
  selectedCategory.value = category
  selectedService.value = undefined
}

const selectService = (service: HomeService) => {
  selectedService.value = service
}

const cancel = () => {
  emit('cancel')
}

const confirmBooking = () => {
  if (canConfirm.value) {
    emit('confirmBooking', {
      serviceId: selectedService.value?.id,
      date: selectedDate.value,
      time: selectedTime.value,
      address: address.value,
      notes: notes.value,
    })
  }
}
</script>
