<template>
  <div class="hobby-kit-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-48 bg-purple-50">
      <img 
        :src="kit.imageUrl" 
        :alt="kit.name"
        class="w-full h-full object-cover"
      />
      <span 
        :class="getDifficultyClass(kit.difficulty)"
        class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium"
      >
        {{ kit.difficulty }}
      </span>
      <span 
        v-if="kit.includesVideo"
        class="absolute top-2 right-2 bg-purple-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        VIDEO TUTORIAL
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Category -->
      <p class="text-xs text-gray-500 mb-1 uppercase font-medium">{{ kit.category }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ kit.name }}</h3>
      
      <!-- Description -->
      <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ kit.description }}</p>

      <!-- What's Included -->
      <div class="mb-3">
        <p class="text-xs font-medium text-gray-700 mb-1">Includes:</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="item in kit.includes.slice(0, 4)" 
            :key="item"
            class="px-2 py-0.5 bg-purple-50 text-purple-700 rounded text-xs"
          >
            {{ item }}
          </span>
          <span v-if="kit.includes.length > 4" class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
            +{{ kit.includes.length - 4 }}
          </span>
        </div>
      </div>

      <!-- Estimated Time -->
      <div class="flex items-center gap-2 mb-3 text-sm text-gray-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>{{ kit.estimatedTime }}</span>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-purple-600">{{ formatPrice(kit.price) }}</span>
        <span 
          v-if="kit.originalPrice"
          class="text-sm text-gray-400 line-through"
        >
          {{ formatPrice(kit.originalPrice) }}
        </span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(kit.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ kit.rating }} ({{ kit.reviews }} reviews)</span>
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
          @click="buyNow"
          class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Buy Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface HobbyKit {
  id: string
  name: string
  category: string
  description: string
  imageUrl: string
  price: number
  originalPrice?: number
  rating: number
  reviews: number
  difficulty: 'Beginner' | 'Intermediate' | 'Advanced'
  estimatedTime: string
  includes: string[]
  includesVideo: boolean
}

const props = defineProps<{
  kit: HobbyKit
}>()

const emit = defineEmits<{
  viewDetails: [kit: HobbyKit]
  buyNow: [kit: HobbyKit]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getDifficultyClass = (difficulty: string) => {
  const classes = {
    'Beginner': 'bg-green-100 text-green-900',
    'Intermediate': 'bg-yellow-100 text-yellow-900',
    'Advanced': 'bg-red-100 text-red-900',
  }
  return classes[difficulty as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.kit)
}

const buyNow = () => {
  emit('buyNow', props.kit)
}
</script>
