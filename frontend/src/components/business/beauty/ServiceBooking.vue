<template>
  <div class="beauty-service-booking bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 mb-2">Book Beauty Service</h2>
      <p class="text-gray-600">Schedule your appointment at the best salons</p>
    </div>

    <!-- Salon Selection -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Select Salon</label>
      <select 
        v-model="selectedSalon"
        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
      >
        <option value="">Choose a salon...</option>
        <option v-for="salon in salons" :key="salon.id" :value="salon.id">
          {{ salon.name }} - {{ salon.location }} ({{ salon.rating }} ⭐)
        </option>
      </select>
    </div>

    <!-- Service Category -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Service Category</label>
      <div class="flex gap-2 flex-wrap">
        <button 
          v-for="category in serviceCategories"
          :key="category.id"
          @click="selectCategory(category)"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-all',
            selectedCategory?.id === category.id 
              ? 'bg-purple-600 text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ category.name }}
        </button>
      </div>
    </div>

    <!-- Services -->
    <div v-if="selectedCategory" class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Select Service</label>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <button 
          v-for="service in filteredServices"
          :key="service.id"
          @click="selectService(service)"
          :class="[
            'p-4 rounded-lg border-2 transition-all text-left',
            selectedService?.id === service.id 
              ? 'border-purple-500 bg-purple-50' 
              : 'border-gray-200 hover:border-purple-300'
          ]"
        >
          <div class="flex justify-between items-start mb-2">
            <div>
              <p class="font-medium text-gray-900">{{ service.name }}</p>
              <p class="text-sm text-gray-500">{{ service.duration }}</p>
            </div>
            <p class="font-bold text-purple-600">{{ formatPrice(service.price) }}</p>
          </div>
          <p class="text-xs text-gray-400">{{ service.description }}</p>
        </button>
      </div>
    </div>

    <!-- Master Selection -->
    <div v-if="selectedService" class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Select Master (optional)</label>
      <select 
        v-model="selectedMaster"
        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
      >
        <option value="">Any available master</option>
        <option v-for="master in masters" :key="master.id" :value="master.id">
          {{ master.name }} - {{ master.specialization }}
        </option>
      </select>
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

    <!-- Time Slots Grid -->
    <div v-if="selectedDate" class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Available Time Slots</label>
      <div class="grid grid-cols-4 md:grid-cols-6 gap-2">
        <button 
          v-for="slot in timeSlots"
          :key="slot.time"
          @click="selectTimeSlot(slot)"
          :disabled="!slot.available"
          :class="[
            'py-2 px-3 rounded-lg text-sm font-medium transition-all',
            selectedTime === slot.time && slot.available
              ? 'bg-purple-600 text-white'
              : !slot.available
              ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ slot.time }}
        </button>
      </div>
    </div>

    <!-- Additional Notes -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes (optional)</label>
      <textarea 
        v-model="notes"
        rows="3"
        placeholder="Any special requests or preferences..."
        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
      />
    </div>

    <!-- Summary -->
    <div v-if="selectedService && selectedDate && selectedTime" class="mb-6 p-4 bg-purple-50 rounded-lg">
      <h3 class="font-semibold text-gray-900 mb-3">Booking Summary</h3>
      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Salon:</span>
          <span class="font-medium">{{ selectedSalonName }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Service:</span>
          <span class="font-medium">{{ selectedService.name }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Master:</span>
          <span class="font-medium">{{ selectedMasterName || 'Any available' }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Date:</span>
          <span class="font-medium">{{ formatDate(selectedDate) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Time:</span>
          <span class="font-medium">{{ selectedTime }}</span>
        </div>
        <div class="border-t pt-2 mt-2 flex justify-between">
          <span class="text-gray-600 font-medium">Total:</span>
          <span class="font-bold text-purple-600">{{ formatPrice(selectedService.price) }}</span>
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
        Book Appointment
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Salon {
  id: string
  name: string
  location: string
  rating: number
}

interface ServiceCategory {
  id: string
  name: string
}

interface Service {
  id: string
  categoryId: string
  name: string
  description: string
  price: number
  duration: string
}

interface Master {
  id: string
  name: string
  specialization: string
}

interface TimeSlot {
  time: string
  available: boolean
}

const emit = defineEmits<{
  cancel: []
  confirmBooking: [booking: any]
}>()

const selectedSalon = ref<string>()
const selectedCategory = ref<ServiceCategory>()
const selectedService = ref<Service>()
const selectedMaster = ref<string>()
const selectedDate = ref<string>()
const selectedTime = ref<string>()
const notes = ref('')

const salons = ref<Salon[]>([
  { id: '1', name: 'Luxury Beauty Salon', location: 'Downtown', rating: 4.8 },
  { id: '2', name: 'Glamour Studio', location: 'City Center', rating: 4.6 },
  { id: '3', name: 'Beauty Haven', location: 'West Side', rating: 4.7 },
])

const serviceCategories = ref<ServiceCategory[]>([
  { id: '1', name: 'Hair' },
  { id: '2', name: 'Nails' },
  { id: '3', name: 'Facial' },
  { id: '4', name: 'Massage' },
  { id: '5', name: 'Makeup' },
])

const services = ref<Service[]>([
  { id: '1', categoryId: '1', name: 'Haircut & Styling', description: 'Professional haircut and styling', price: 2500, duration: '45 min' },
  { id: '2', categoryId: '1', name: 'Hair Coloring', description: 'Full hair coloring service', price: 5000, duration: '2 hours' },
  { id: '3', categoryId: '2', name: 'Manicure', description: 'Classic manicure', price: 1500, duration: '30 min' },
  { id: '4', categoryId: '2', name: 'Pedicure', description: 'Classic pedicure', price: 2000, duration: '45 min' },
  { id: '5', categoryId: '3', name: 'Deep Cleansing Facial', description: 'Deep pore cleansing facial', price: 3500, duration: '60 min' },
  { id: '6', categoryId: '4', name: 'Swedish Massage', description: 'Relaxing full body massage', price: 4000, duration: '60 min' },
])

const masters = ref<Master[]>([
  { id: '1', name: 'Anna Petrova', specialization: 'Hair Stylist' },
  { id: '2', name: 'Maria Ivanova', specialization: 'Nail Technician' },
  { id: '3', name: 'Elena Smirnova', specialization: 'Esthetician' },
])

const timeSlots = ref<TimeSlot[]>([
  { time: '09:00', available: true },
  { time: '10:00', available: true },
  { time: '11:00', available: false },
  { time: '12:00', available: true },
  { time: '13:00', available: true },
  { time: '14:00', available: false },
  { time: '15:00', available: true },
  { time: '16:00', available: true },
  { time: '17:00', available: true },
  { time: '18:00', available: false },
  { time: '19:00', available: true },
  { time: '20:00', available: true },
])

const availableTimes = ref([
  '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00',
])

const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const filteredServices = computed(() => {
  if (!selectedCategory.value) return []
  return services.value.filter(s => s.categoryId === selectedCategory.value?.id)
})

const selectedSalonName = computed(() => {
  return salons.value.find(s => s.id === selectedSalon.value)?.name || ''
})

const selectedMasterName = computed(() => {
  return masters.value.find(m => m.id === selectedMaster.value)?.name || ''
})

const canConfirm = computed(() => {
  return selectedSalon.value && selectedService.value && selectedDate.value && selectedTime.value
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

const selectCategory = (category: ServiceCategory) => {
  selectedCategory.value = category
  selectedService.value = undefined
}

const selectService = (service: Service) => {
  selectedService.value = service
}

const selectTimeSlot = (slot: TimeSlot) => {
  if (slot.available) {
    selectedTime.value = slot.time
  }
}

const cancel = () => {
  emit('cancel')
}

const confirmBooking = () => {
  if (canConfirm.value) {
    emit('confirmBooking', {
      salonId: selectedSalon.value,
      serviceId: selectedService.value?.id,
      masterId: selectedMaster.value,
      date: selectedDate.value,
      time: selectedTime.value,
      notes: notes.value,
    })
  }
}
</script>
