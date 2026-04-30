<template>
  <div class="luxury-product-card bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
    <!-- Image -->
    <div class="relative h-56 bg-gradient-to-br from-gray-100 to-gray-200">
      <img 
        :src="product.imageUrl" 
        :alt="product.name"
        class="w-full h-full object-cover"
      />
      <span class="absolute top-2 left-2 bg-black/70 text-white px-3 py-1 rounded text-xs font-medium uppercase tracking-wider">
        Exclusive
      </span>
      <button 
        @click="toggleFavorite"
        class="absolute top-2 right-2 w-8 h-8 bg-white/90 rounded-full flex items-center justify-center hover:bg-white transition-colors"
      >
        <span :class="isFavorite ? 'text-red-500' : 'text-gray-400'">{{ isFavorite ? '♥' : '♡' }}</span>
      </button>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Brand -->
      <p class="text-xs text-gray-500 mb-1 uppercase tracking-wider font-medium">{{ product.brand }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ product.name }}</h3>
      
      <!-- Description -->
      <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ product.description }}</p>

      <!-- Specs -->
      <div class="flex flex-wrap gap-2 mb-3">
        <span 
          v-for="spec in product.specs" 
          :key="spec"
          class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs"
        >
          {{ spec }}
        </span>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-gray-900">{{ formatPrice(product.price) }}</span>
      </div>

      <!-- Limited Edition Badge -->
      <div v-if="product.limitedEdition" class="flex items-center gap-2 mb-4 text-xs text-amber-600">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
        </svg>
        <span>Limited Edition - {{ product.stockLeft }} left</span>
      </div>

      <!-- Delivery Info -->
      <div class="flex items-center gap-2 text-xs text-gray-500 mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
        </svg>
        <span>Complimentary delivery</span>
        <span class="mx-1">•</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
        </svg>
        <span>Authenticity guaranteed</span>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="requestConsultation"
          class="flex-1 bg-gray-900 hover:bg-gray-800 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Request Consultation
        </button>
        <button 
          @click="addToCart"
          class="flex-1 border border-gray-900 hover:bg-gray-50 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Add to Cart
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface LuxuryProduct {
  id: string
  name: string
  brand: string
  description: string
  imageUrl: string
  price: number
  specs: string[]
  limitedEdition: boolean
  stockLeft: number
}

const props = defineProps<{
  product: LuxuryProduct
}>()

const emit = defineEmits<{
  addToCart: [product: LuxuryProduct]
  requestConsultation: [product: LuxuryProduct]
  toggleFavorite: [product: LuxuryProduct]
}>()

const isFavorite = ref(false)

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const toggleFavorite = () => {
  isFavorite.value = !isFavorite.value
  emit('toggleFavorite', props.product)
}

const addToCart = () => {
  emit('addToCart', props.product)
}

const requestConsultation = () => {
  emit('requestConsultation', props.product)
}
</script>
