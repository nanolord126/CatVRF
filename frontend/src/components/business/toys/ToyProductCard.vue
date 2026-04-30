<template>
  <div class="toy-product-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-yellow-50">
      <img 
        :src="toy.imageUrl" 
        :alt="toy.name"
        class="w-full h-full object-contain p-4"
      />
      <span 
        v-if="toy.ageGroup"
        :class="getAgeClass(toy.ageGroup)"
        class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium"
      >
        {{ toy.ageGroup }}
      </span>
      <span 
        v-if="toy.isEducational"
        class="absolute top-2 right-2 bg-blue-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        EDUCATIONAL
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Brand -->
      <p class="text-xs text-gray-500 mb-1 uppercase font-medium">{{ toy.brand }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ toy.name }}</h3>
      
      <!-- Category -->
      <p class="text-xs text-gray-500 mb-3">{{ toy.category }}</p>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-yellow-600">{{ formatPrice(toy.price) }}</span>
        <span 
          v-if="toy.originalPrice"
          class="text-sm text-gray-400 line-through"
        >
          {{ formatPrice(toy.originalPrice) }}
        </span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(toy.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ toy.rating }} ({{ toy.reviews }} reviews)</span>
      </div>

      <!-- Safety Info -->
      <div class="flex items-center gap-2 mb-4 text-xs text-green-600">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        <span>Safe certified • Non-toxic</span>
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
          @click="addToCart"
          class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Add to Cart
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface ToyProduct {
  id: string
  name: string
  brand: string
  category: string
  description: string
  imageUrl: string
  price: number
  originalPrice?: number
  rating: number
  reviews: number
  ageGroup: '0-2' | '3-5' | '6-8' | '9-12' | '13+'
  isEducational: boolean
}

const props = defineProps<{
  toy: ToyProduct
}>()

const emit = defineEmits<{
  viewDetails: [toy: ToyProduct]
  addToCart: [toy: ToyProduct]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getAgeClass = (ageGroup: string) => {
  const classes = {
    '0-2': 'bg-pink-100 text-pink-900',
    '3-5': 'bg-blue-100 text-blue-900',
    '6-8': 'bg-green-100 text-green-900',
    '9-12': 'bg-purple-100 text-purple-900',
    '13+': 'bg-orange-100 text-orange-900',
  }
  return classes[ageGroup as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.toy)
}

const addToCart = () => {
  emit('addToCart', props.toy)
}
</script>
