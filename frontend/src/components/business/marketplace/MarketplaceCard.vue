<template>
  <div class="marketplace-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <h3 class="text-lg font-bold text-gray-900">{{ marketplace.name }}</h3>
      <p class="text-sm text-gray-500">{{ marketplace.description }}</p>
    </div>

    <!-- Category Selection -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
      <div class="flex flex-wrap gap-2">
        <button 
          v-for="category in categories"
          :key="category.id"
          @click="selectedCategory = category.id"
          :class="selectedCategory === category.id ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-900'"
          class="px-3 py-1 rounded-full text-xs font-medium transition-colors"
        >
          {{ category.name }}
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-3 mb-4">
      <div class="text-center">
        <p class="text-lg font-bold text-gray-900">{{ marketplace.stats.products }}</p>
        <p class="text-xs text-gray-500">Products</p>
      </div>
      <div class="text-center">
        <p class="text-lg font-bold text-gray-900">{{ marketplace.stats.sellers }}</p>
        <p class="text-xs text-gray-500">Sellers</p>
      </div>
      <div class="text-center">
        <p class="text-lg font-bold text-gray-900">{{ marketplace.stats.orders }}</p>
        <p class="text-xs text-gray-500">Orders</p>
      </div>
    </div>

    <!-- Featured Products -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Featured Products</p>
      <div class="flex gap-2 overflow-x-auto pb-2">
        <div 
          v-for="product in featuredProducts"
          :key="product.id"
          class="flex-shrink-0 w-20 bg-gray-50 rounded-lg p-2"
        >
          <div class="w-full h-16 bg-gray-200 rounded mb-1"></div>
          <p class="text-xs text-gray-700 truncate">{{ product.name }}</p>
          <p class="text-xs font-bold text-indigo-600">{{ product.price }}</p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="browseMarketplace"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Browse
      </button>
      <button 
        @click="sellProduct"
        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Sell
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Category {
  id: string
  name: string
}

interface Product {
  id: string
  name: string
  price: string
}

interface Stats {
  products: number
  sellers: number
  orders: number
}

interface Marketplace {
  name: string
  description: string
  stats: Stats
}

const props = defineProps<{
  marketplace: Marketplace
  categories: Category[]
  featuredProducts: Product[]
}>()

const emit = defineEmits<{
  browseMarketplace: []
  sellProduct: []
}>()

const selectedCategory = ref(props.categories[0]?.id || '')

const browseMarketplace = () => {
  emit('browseMarketplace')
}

const sellProduct = () => {
  emit('sellProduct')
}
</script>
