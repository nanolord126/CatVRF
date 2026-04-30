<template>
  <div class="course-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-gradient-to-br from-indigo-500 to-purple-500">
      <img 
        :src="course.imageUrl" 
        :alt="course.name"
        class="w-full h-full object-cover opacity-90"
      />
      <span 
        :class="getLevelClass(course.level)"
        class="absolute top-2 right-2 px-2 py-1 rounded text-xs font-medium"
      >
        {{ course.level }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Category -->
      <p class="text-xs text-gray-500 mb-1 uppercase font-medium">{{ course.category }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ course.name }}</h3>
      
      <!-- Instructor -->
      <div class="flex items-center gap-2 mb-3">
        <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
          <span class="text-indigo-600 font-bold text-xs">{{ course.instructorInitials }}</span>
        </div>
        <span class="text-sm text-gray-600">{{ course.instructor }}</span>
      </div>

      <!-- Duration & Lessons -->
      <div class="flex items-center gap-4 mb-3 text-sm text-gray-600">
        <div class="flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span>{{ course.duration }}</span>
        </div>
        <div class="flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
          </svg>
          <span>{{ course.lessons }} lessons</span>
        </div>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-indigo-600">{{ formatPrice(course.price) }}</span>
        <span 
          v-if="course.originalPrice"
          class="text-sm text-gray-400 line-through"
        >
          {{ formatPrice(course.originalPrice) }}
        </span>
      </div>

      <!-- Rating & Students -->
      <div class="flex items-center justify-between mb-4 text-sm">
        <div class="flex items-center gap-1">
          <div class="flex text-yellow-400">
            <span v-for="i in Math.floor(course.rating)" :key="i">★</span>
          </div>
          <span class="text-gray-600">{{ course.rating }}</span>
        </div>
        <span class="text-gray-500">{{ course.studentsEnrolled }} students</span>
      </div>

      <!-- Features -->
      <div class="flex flex-wrap gap-2 mb-4">
        <span 
          v-for="feature in course.features" 
          :key="feature"
          class="px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-xs"
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
          @click="enroll"
          class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Enroll
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Course {
  id: string
  name: string
  category: string
  instructor: string
  instructorInitials: string
  description: string
  imageUrl: string
  price: number
  originalPrice?: number
  level: 'Beginner' | 'Intermediate' | 'Advanced'
  duration: string
  lessons: number
  rating: number
  studentsEnrolled: number
  features: string[]
}

const props = defineProps<{
  course: Course
}>()

const emit = defineEmits<{
  viewDetails: [course: Course]
  enroll: [course: Course]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getLevelClass = (level: string) => {
  const classes = {
    'Beginner': 'bg-green-100 text-green-900',
    'Intermediate': 'bg-yellow-100 text-yellow-900',
    'Advanced': 'bg-red-100 text-red-900',
  }
  return classes[level as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.course)
}

const enroll = () => {
  emit('enroll', props.course)
}
</script>
