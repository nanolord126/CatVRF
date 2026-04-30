<template>
  <div class="veterinary-appointment-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Header -->
    <div class="relative h-32 bg-gradient-to-r from-emerald-500 to-teal-500">
      <img 
        :src="clinic.imageUrl" 
        :alt="clinic.name"
        class="w-full h-full object-cover opacity-90"
      />
      <span :class="getStatusClass(appointment.status)" class="absolute top-2 right-2 px-3 py-1 rounded-full text-xs font-bold bg-white/90">
        {{ appointment.status }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Clinic Info -->
      <div class="flex items-center gap-3 mb-4">
        <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
          <span class="text-emerald-600 font-bold">{{ clinic.initials }}</span>
        </div>
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ clinic.name }}</h3>
          <p class="text-sm text-gray-500">{{ clinic.location }}</p>
        </div>
      </div>

      <!-- Appointment Details -->
      <div class="bg-gray-50 rounded-lg p-3 mb-4">
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Date:</span>
            <span class="font-medium">{{ appointment.date }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Time:</span>
            <span class="font-medium">{{ appointment.time }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Pet:</span>
            <span class="font-medium">{{ appointment.petName }} ({{ appointment.petType }})</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Service:</span>
            <span class="font-medium">{{ appointment.service }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Vet:</span>
            <span class="font-medium">{{ appointment.vetName }}</span>
          </div>
        </div>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-4">
        <span class="text-xl font-bold text-emerald-600">{{ formatPrice(appointment.price) }}</span>
        <span class="text-xs text-gray-400">estimated</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(clinic.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ clinic.rating }} ({{ clinic.reviews }} reviews)</span>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="reschedule"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Reschedule
        </button>
        <button 
          v-if="appointment.status === 'Scheduled'"
          @click="cancel"
          class="flex-1 bg-red-100 hover:bg-red-200 text-red-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Cancel
        </button>
        <button 
          v-else
          @click="bookAgain"
          class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Book Again
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Clinic {
  name: string
  initials: string
  location: string
  imageUrl: string
  rating: number
  reviews: number
}

interface Appointment {
  id: string
  status: 'Scheduled' | 'Completed' | 'Cancelled'
  date: string
  time: string
  petName: string
  petType: string
  service: string
  vetName: string
  price: number
}

const props = defineProps<{
  clinic: Clinic
  appointment: Appointment
}>()

const emit = defineEmits<{
  reschedule: [appointment: Appointment]
  cancel: [appointment: Appointment]
  bookAgain: [appointment: Appointment]
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
    'Scheduled': 'text-blue-900',
    'Completed': 'text-green-900',
    'Cancelled': 'text-red-900',
  }
  return classes[status as keyof typeof classes] || 'text-gray-900'
}

const reschedule = () => {
  emit('reschedule', props.appointment)
}

const cancel = () => {
  emit('cancel', props.appointment)
}

const bookAgain = () => {
  emit('bookAgain', props.appointment)
}
</script>
