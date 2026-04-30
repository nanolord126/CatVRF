<template>
  <div class="electronics-product-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-48 bg-gray-100">
      <img 
        :src="product.imageUrl" 
        :alt="product.name"
        class="w-full h-full object-contain p-4"
      />
      <span 
        v-if="product.discount"
        class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        -{{ product.discount }}%
      </span>
      <span 
        v-if="product.isNew"
        class="absolute top-2 left-2 bg-blue-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        NEW
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Brand -->
      <p class="text-xs text-gray-500 mb-1 font-medium uppercase">{{ product.brand }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ product.name }}</h3>
      
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

      <!-- Rating & Reviews -->
      <div class="flex items-center gap-2 mb-3 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(product.rating)" :key="i">★</span>
          <span v-if="product.rating % 1 !== 0">☆</span>
        </div>
        <span class="text-gray-600">{{ product.rating }} ({{ product.reviews }} reviews)</span>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-blue-600">{{ formatPrice(product.price) }}</span>
        <span 
          v-if="product.originalPrice"
          class="text-sm text-gray-400 line-through"
        >
          {{ formatPrice(product.originalPrice) }}
        </span>
      </div>

      <!-- Stock & Delivery -->
      <div class="flex items-center justify-between text-xs text-gray-500 mb-4">
        <span :class="getStockClass(product.stock)">{{ product.stock }}</span>
        <span>{{ product.deliveryTime }}</span>
      </div>

      <!-- Warranty -->
      <div class="flex items-center gap-2 text-xs text-gray-600 mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
        </svg>
        <span>{{ product.warranty }} warranty</span>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="toggleCompare"
          :class="[
            'py-2 px-4 rounded-lg text-sm font-medium transition-colors',
            isComparing 
              ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ isComparing ? '✓ Comparing' : 'Compare' }}
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

interface ElectronicsProduct {
  id: string
  name: string
  brand: string
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
  isNew: boolean
}

const props = defineProps<{
  product: ElectronicsProduct
}>()

const emit = defineEmits<{
  addToCart: [product: ElectronicsProduct]
  toggleCompare: [product: ElectronicsProduct]
}>()

const isComparing = ref(false)

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
    'In Stock': 'text-green-600',
    'Low Stock': 'text-yellow-600',
    'Out of Stock': 'text-red-600',
  }
  return classes[stock as keyof typeof classes] || 'text-gray-600'
}

const toggleCompare = () => {
  isComparing.value = !isComparing.value
  emit('toggleCompare', props.product)
}

const addToCart = () => {
  emit('addToCart', props.product)
}
</script>
