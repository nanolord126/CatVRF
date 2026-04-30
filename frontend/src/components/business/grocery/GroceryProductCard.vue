<template>
  <div class="grocery-product-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-green-50">
      <img 
        :src="product.imageUrl" 
        :alt="product.name"
        class="w-full h-full object-contain p-4"
      />
      <span 
        v-if="product.isOrganic"
        class="absolute top-2 left-2 bg-green-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        ORGANIC
      </span>
      <span 
        v-if="product.discount"
        class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        -{{ product.discount }}%
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Brand -->
      <p class="text-xs text-gray-500 mb-1">{{ product.brand }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-1">{{ product.name }}</h3>
      
      <!-- Weight -->
      <p class="text-sm text-gray-500 mb-3">{{ product.weight }}</p>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-green-600">{{ formatPrice(product.price) }}</span>
        <span class="text-xs text-gray-400">{{ formatPricePerUnit(product.price, product.weight) }}</span>
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
          @click="addToCart"
          :disabled="product.stock === 'Out of Stock'"
          class="flex-1 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Add to Cart
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface GroceryProduct {
  id: string
  name: string
  brand: string
  weight: string
  imageUrl: string
  price: number
  discount?: number
  stock: 'In Stock' | 'Low Stock' | 'Out of Stock'
  isOrganic: boolean
}

const props = defineProps<{
  product: GroceryProduct
}>()

const emit = defineEmits<{
  addToCart: [product: GroceryProduct]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price)
}

const formatPricePerUnit = (price: number, weight: string) => {
  return formatPrice(price)
}

const getStockClass = (stock: string) => {
  const classes = {
    'In Stock': 'text-green-600',
    'Low Stock': 'text-yellow-600',
    'Out of Stock': 'text-red-600',
  }
  return classes[stock as keyof typeof classes] || 'text-gray-600'
}

const addToCart = () => {
  emit('addToCart', props.product)
}
</script>
