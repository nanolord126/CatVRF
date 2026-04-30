<template>
  <div class="furniture-product-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-48 bg-gray-100">
      <img 
        :src="product.imageUrl" 
        :alt="product.name"
        class="w-full h-full object-cover"
      />
      <span 
        v-if="product.discount"
        class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        -{{ product.discount }}%
      </span>
      <span 
        :class="getStockClass(product.stock)"
        class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium"
      >
        {{ product.stock }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Category -->
      <p class="text-xs text-gray-500 mb-1">{{ product.category }}</p>
      
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
        <span 
          v-if="product.originalPrice"
          class="text-sm text-gray-400 line-through"
        >
          {{ formatPrice(product.originalPrice) }}
        </span>
      </div>

      <!-- Rating & Reviews -->
      <div class="flex items-center gap-2 mb-3 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in 5" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ product.rating }} ({{ product.reviews }} reviews)</span>
      </div>

      <!-- Delivery Info -->
      <div class="flex items-center gap-2 text-xs text-gray-500 mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
        </svg>
        <span>{{ product.deliveryTime }}</span>
        <span class="mx-1">•</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>{{ product.warranty }}</span>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="toggleFavorite"
          :class="[
            'flex-1 py-2 px-4 rounded-lg text-sm font-medium transition-colors',
            isFavorite 
              ? 'bg-red-100 text-red-700 hover:bg-red-200' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ isFavorite ? '♥ Saved' : '♡ Save' }}
        </button>
        <button 
          @click="addToCart"
          class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Add to Cart
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface FurnitureProduct {
  id: string
  name: string
  category: string
  description: string
  imageUrl: string
  price: number
  originalPrice?: number
  discount?: number
  rating: number
  reviews: number
  stock: 'In Stock' | 'Low Stock' | 'Out of Stock'
  specs: string[]
  deliveryTime: string
  warranty: string
}

const props = defineProps<{
  product: FurnitureProduct
}>()

const emit = defineEmits<{
  addToCart: [product: FurnitureProduct]
  toggleFavorite: [product: FurnitureProduct]
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

const getStockClass = (stock: string) => {
  const classes = {
    'In Stock': 'bg-green-100 text-green-900',
    'Low Stock': 'bg-yellow-100 text-yellow-900',
    'Out of Stock': 'bg-red-100 text-red-900',
  }
  return classes[stock as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const toggleFavorite = () => {
  isFavorite.value = !isFavorite.value
  emit('toggleFavorite', props.product)
}

const addToCart = () => {
  emit('addToCart', props.product)
}
</script>
