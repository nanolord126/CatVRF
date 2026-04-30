<template>
  <div class="household-product-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-blue-50">
      <img 
        :src="product.imageUrl" 
        :alt="product.name"
        class="w-full h-full object-contain p-4"
      />
      <span 
        v-if="product.isEcoFriendly"
        class="absolute top-2 left-2 bg-green-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        ECO
      </span>
      <span 
        v-if="product.bestSeller"
        class="absolute top-2 right-2 bg-orange-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        BESTSELLER
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Brand -->
      <p class="text-xs text-gray-500 mb-1">{{ product.brand }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ product.name }}</h3>
      
      <!-- Category -->
      <p class="text-sm text-gray-500 mb-3">{{ product.category }}</p>

      <!-- Specs -->
      <div class="space-y-1 mb-3 text-sm text-gray-600">
        <div v-if="product.material"><span class="font-medium">Material:</span> {{ product.material }}</div>
        <div v-if="product.dimensions"><span class="font-medium">Size:</span> {{ product.dimensions }}</div>
        <div v-if="product.color"><span class="font-medium">Color:</span> {{ product.color }}</div>
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

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(product.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ product.rating }} ({{ product.reviews }} reviews)</span>
      </div>

      <!-- Stock Status -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <span :class="getStockClass(product.stock)" class="font-medium">
          {{ product.stock }}
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
          @click="addToCart"
          :disabled="product.stock === 'Out of Stock'"
          class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Add to Cart
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface HouseholdProduct {
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
  stock: 'In Stock' | 'Low Stock' | 'Out of Stock'
  isEcoFriendly: boolean
  bestSeller: boolean
  material?: string
  dimensions?: string
  color?: string
}

const props = defineProps<{
  product: HouseholdProduct
}>()

const emit = defineEmits<{
  viewDetails: [product: HouseholdProduct]
  addToCart: [product: HouseholdProduct]
}>()

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

const viewDetails = () => {
  emit('viewDetails', props.product)
}

const addToCart = () => {
  emit('addToCart', props.product)
}
</script>
