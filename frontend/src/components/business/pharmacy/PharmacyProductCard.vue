<template>
  <div class="pharmacy-product-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-green-50">
      <img 
        :src="product.imageUrl" 
        :alt="product.name"
        class="w-full h-full object-contain p-4"
      />
      <span 
        v-if="product.prescriptionRequired"
        class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        Rx Required
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
      <h3 class="text-lg font-bold text-gray-900 mb-1">{{ product.name }}</h3>
      
      <!-- Manufacturer -->
      <p class="text-xs text-gray-500 mb-2">{{ product.manufacturer }}</p>
      
      <!-- Description -->
      <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ product.description }}</p>

      <!-- Dosage -->
      <div class="flex items-center gap-2 mb-3 text-sm text-gray-600">
        <span class="font-medium">{{ product.dosage }}</span>
        <span>•</span>
        <span>{{ product.quantity }} units</span>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-green-600">{{ formatPrice(product.price) }}</span>
        <span 
          v-if="product.originalPrice"
          class="text-sm text-gray-400 line-through"
        >
          {{ formatPrice(product.originalPrice) }}
        </span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-3 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in 5" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ product.rating }}</span>
      </div>

      <!-- Delivery Info -->
      <div class="flex items-center gap-2 text-xs text-gray-500 mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
        </svg>
        <span>{{ product.deliveryTime }}</span>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="addToCart"
          :disabled="product.stock === 'Out of Stock'"
          class="flex-1 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Add to Cart
        </button>
        <button 
          v-if="!product.prescriptionRequired"
          @click="buyNow"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Buy Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface PharmacyProduct {
  id: string
  name: string
  category: string
  description: string
  manufacturer: string
  imageUrl: string
  price: number
  originalPrice?: number
  rating: number
  stock: 'In Stock' | 'Low Stock' | 'Out of Stock'
  prescriptionRequired: boolean
  dosage: string
  quantity: number
  deliveryTime: string
}

const props = defineProps<{
  product: PharmacyProduct
}>()

const emit = defineEmits<{
  addToCart: [product: PharmacyProduct]
  buyNow: [product: PharmacyProduct]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
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

const addToCart = () => {
  emit('addToCart', props.product)
}

const buyNow = () => {
  emit('buyNow', props.product)
}
</script>
