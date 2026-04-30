<template>
  <div class="doctor-card group bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
    <!-- Header -->
    <div class="relative">
      <!-- Cover Image -->
      <div class="h-24 bg-gradient-to-r from-blue-500 to-purple-500"></div>
      
      <!-- Avatar -->
      <div class="absolute -bottom-8 left-4">
        <img 
          :src="doctor.avatar" 
          :alt="doctor.name"
          class="w-20 h-20 rounded-full border-4 border-white shadow-lg"
        />
      </div>

      <!-- Verified Badge -->
      <div v-if="doctor.isVerified" class="absolute top-2 right-2 bg-white/90 rounded-full p-1">
        <CheckCircle class="w-5 h-5 text-blue-500" />
      </div>
    </div>

    <!-- Doctor Info -->
    <div class="p-4 pt-10">
      <!-- Name & Specialization -->
      <div class="mb-3">
        <h3 class="font-semibold text-gray-900 text-lg">{{ doctor.name }}</h3>
        <p class="text-purple-600 font-medium">{{ doctor.specialization }}</p>
      </div>

      <!-- Rating & Reviews -->
      <div class="flex items-center gap-2 mb-3">
        <div class="flex">
          <Star v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= doctor.rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'" />
        </div>
        <span class="text-sm font-medium text-gray-700">{{ doctor.rating }}</span>
        <span class="text-sm text-gray-400">({{ doctor.reviews }} reviews)</span>
      </div>

      <!-- Experience -->
      <div class="flex items-center gap-2 text-gray-600 text-sm mb-3">
        <Briefcase class="w-4 h-4" />
        <span>{{ doctor.experience }} years experience</span>
      </div>

      <!-- Location -->
      <div class="flex items-center gap-2 text-gray-600 text-sm mb-3">
        <MapPin class="w-4 h-4" />
        <span>{{ doctor.location }}</span>
      </div>

      <!-- Services -->
      <div class="mb-4">
        <p class="text-sm font-medium text-gray-700 mb-2">Services</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="service in doctor.services.slice(0, 3)" 
            :key="service"
            class="px-2 py-1 bg-blue-50 text-blue-600 text-xs rounded-full"
          >
            {{ service }}
          </span>
          <span v-if="doctor.services.length > 3" class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
            +{{ doctor.services.length - 3 }}
          </span>
        </div>
      </div>

      <!-- Availability -->
      <div class="flex items-center gap-2 mb-4 p-2 bg-green-50 rounded-lg">
        <Clock class="w-4 h-4 text-green-600" />
        <span class="text-sm text-green-700">Available today</span>
      </div>

      <!-- Price -->
      <div class="flex items-center justify-between mb-4">
        <div>
          <p class="text-sm text-gray-500">Consultation</p>
          <p class="text-xl font-bold text-gray-900">{{ formatPrice(doctor.consultationPrice) }}</p>
        </div>
        <div v-if="doctor.onlinePrice" class="text-right">
          <p class="text-sm text-gray-500">Online</p>
          <p class="text-lg font-bold text-purple-600">{{ formatPrice(doctor.onlinePrice) }}</p>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="bookAppointment"
          class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg font-medium transition-colors"
        >
          Book Appointment
        </button>
        <button 
          @click="viewProfile"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg font-medium transition-colors"
        >
          View Profile
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { CheckCircle, Star, Briefcase, MapPin, Clock } from 'lucide-vue-next'

interface Doctor {
  id: string
  name: string
  specialization: string
  avatar: string
  rating: number
  reviews: number
  experience: number
  location: string
  services: string[]
  consultationPrice: number
  onlinePrice?: number
  isVerified?: boolean
}

const props = defineProps<{
  doctor: Doctor
}>()

const emit = defineEmits<{
  bookAppointment: [doctor: Doctor]
  viewProfile: [doctor: Doctor]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(price)
}

const bookAppointment = () => {
  emit('bookAppointment', props.doctor)
}

const viewProfile = () => {
  emit('viewProfile', props.doctor)
}
</script>
