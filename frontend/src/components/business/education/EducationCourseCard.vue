<template>
  <div class="education-course-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-48">
      <img 
        :src="course.imageUrl" 
        :alt="course.name"
        class="w-full h-full object-cover"
      />
      <span 
        v-if="course.isBestseller"
        class="absolute top-2 left-2 bg-orange-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        BESTSELLER
      </span>
      <span 
        v-if="course.discount"
        class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        -{{ course.discount }}%
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Instructor -->
      <div class="flex items-center gap-2 mb-3">
        <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
          <span class="text-indigo-600 font-bold text-xs">{{ instructor.initials }}</span>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">{{ instructor.name }}</p>
          <p class="text-xs text-gray-500">{{ instructor.title }}</p>
        </div>
      </div>

      <!-- Course Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ course.name }}</h3>
      
      <!-- Category -->
      <p class="text-sm text-gray-500 mb-3">{{ course.category }}</p>

      <!-- Course Stats -->
      <div class="flex items-center gap-4 mb-3 text-sm text-gray-600">
        <span class="flex items-center gap-1">
          <span>📚</span>
          {{ course.lessons }} lessons
        </span>
        <span class="flex items-center gap-1">
          <span>⏱️</span>
          {{ course.duration }}
        </span>
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
        <span class="text-gray-500">{{ course.students.toLocaleString() }} students</span>
      </div>

      <!-- Certificate -->
      <div class="mb-4">
        <span class="px-2 py-1 bg-green-50 text-green-700 rounded text-xs">
          ✓ Certificate included
        </span>
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
interface Instructor {
  name: string
  initials: string
  title: string
}

interface EducationCourse {
  id: string
  name: string
  category: string
  imageUrl: string
  price: number
  originalPrice?: number
  rating: number
  students: number
  lessons: number
  duration: string
  discount?: number
  isBestseller: boolean
}

const props = defineProps<{
  course: EducationCourse
  instructor: Instructor
}>()

const emit = defineEmits<{
  viewDetails: [course: EducationCourse]
  enroll: [course: EducationCourse]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const viewDetails = () => {
  emit('viewDetails', props.course)
}

const enroll = () => {
  emit('enroll', props.course)
}
</script>
