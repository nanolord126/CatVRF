<template>
  <div class="fitness-class-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-gradient-to-r from-blue-500 to-cyan-500">
      <img 
        :src="classData.imageUrl" 
        :alt="classData.name"
        class="w-full h-full object-cover opacity-90"
      />
      <span 
        :class="getIntensityClass(classData.intensity)"
        class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-bold bg-white/90"
      >
        {{ classData.intensity }}
      </span>
      <span 
        v-if="classData.isPopular"
        class="absolute top-2 right-2 bg-orange-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        POPULAR
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Class Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ classData.name }}</h3>
      
      <!-- Type -->
      <p class="text-sm text-gray-500 mb-3">{{ classData.type }}</p>

      <!-- Duration & Level -->
      <div class="flex items-center gap-4 mb-3 text-sm text-gray-600">
        <span class="flex items-center gap-1">
          <span>⏱️</span>
          {{ classData.duration }}
        </span>
        <span class="flex items-center gap-1">
          <span>📊</span>
          {{ classData.level }}
        </span>
      </div>

      <!-- Instructor -->
      <div class="flex items-center gap-2 mb-3">
        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
          <span class="text-blue-600 font-bold">{{ instructor.initials }}</span>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">{{ instructor.name }}</p>
          <p class="text-xs text-gray-500">{{ instructor.specialty }}</p>
        </div>
      </div>

      <!-- Gym Info -->
      <div class="mb-3">
        <p class="text-sm text-gray-600">📍 {{ gym.name }} • {{ gym.location }}</p>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-blue-600">{{ formatPrice(classData.price) }}</span>
        <span class="text-xs text-gray-400">/ class</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(classData.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ classData.rating }} ({{ classData.reviews }} reviews)</span>
      </div>

      <!-- What to Bring -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">What to Bring</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="item in classData.whatToBring" 
            :key="item"
            class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-xs"
          >
            {{ item }}
          </span>
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
          @click="bookClass"
          class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Book Class
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Instructor {
  name: string
  initials: string
  specialty: string
}

interface Gym {
  name: string
  location: string
}

interface FitnessClass {
  id: string
  name: string
  type: string
  intensity: 'Low' | 'Medium' | 'High'
  duration: string
  level: string
  imageUrl: string
  price: number
  rating: number
  reviews: number
  isPopular: boolean
  whatToBring: string[]
}

const props = defineProps<{
  classData: FitnessClass
  instructor: Instructor
  gym: Gym
}>()

const emit = defineEmits<{
  viewDetails: [classData: FitnessClass]
  bookClass: [classData: FitnessClass]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getIntensityClass = (intensity: string) => {
  const classes = {
    'Low': 'text-green-900',
    'Medium': 'text-yellow-900',
    'High': 'text-red-900',
  }
  return classes[intensity as keyof typeof classes] || 'text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.classData)
}

const bookClass = () => {
  emit('bookClass', props.classData)
}
</script>
