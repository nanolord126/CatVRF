<template>
  <div class="recommendation-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-gradient-to-r from-indigo-500 to-purple-500">
      <img 
        :src="item.imageUrl" 
        :alt="item.name"
        class="w-full h-full object-cover opacity-90"
      />
      <span class="absolute top-2 left-2 bg-white/90 text-indigo-900 px-2 py-1 rounded text-xs font-bold">
        {{ item.matchScore }}% Match
      </span>
      <span 
        v-if="item.isPersonalized"
        class="absolute top-2 right-2 bg-indigo-600 text-white px-2 py-1 rounded text-xs font-bold"
      >
        FOR YOU
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ item.name }}</h3>
      
      <!-- Category -->
      <p class="text-sm text-gray-500 mb-3">{{ item.category }}</p>

      <!-- Why Recommended -->
      <div class="mb-3">
        <p class="text-xs font-medium text-gray-700 mb-1">Why recommended</p>
        <p class="text-sm text-gray-600">{{ item.reason }}</p>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-indigo-600">{{ formatPrice(item.price) }}</span>
        <span 
          v-if="item.originalPrice"
          class="text-sm text-gray-400 line-through"
        >
          {{ formatPrice(item.originalPrice) }}
        </span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(item.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ item.rating }} ({{ item.reviews }} reviews)</span>
      </div>

      <!-- Similar Items -->
      <div v-if="item.similarItems && item.similarItems.length" class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Similar to your recent views</p>
        <div class="flex gap-2">
          <img 
            v-for="similar in item.similarItems.slice(0, 3)" 
            :key="similar"
            :src="similar"
            class="w-12 h-12 rounded object-cover bg-gray-100"
          />
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="dismiss"
          class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-sm transition-colors"
        >
          ✕
        </button>
        <button 
          @click="viewDetails"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Details
        </button>
        <button 
          @click="addToCart"
          class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Add to Cart
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface RecommendationItem {
  id: string
  name: string
  category: string
  imageUrl: string
  price: number
  originalPrice?: number
  rating: number
  reviews: number
  matchScore: number
  reason: string
  isPersonalized: boolean
  similarItems?: string[]
}

const props = defineProps<{
  item: RecommendationItem
}>()

const emit = defineEmits<{
  viewDetails: [item: RecommendationItem]
  addToCart: [item: RecommendationItem]
  dismiss: [item: RecommendationItem]
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
  emit('viewDetails', props.item)
}

const addToCart = () => {
  emit('addToCart', props.item)
}

const dismiss = () => {
  emit('dismiss', props.item)
}
</script>
