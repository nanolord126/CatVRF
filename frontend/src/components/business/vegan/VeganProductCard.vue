<template>
  <div class="vegan-product-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-green-50">
      <img 
        :src="product.imageUrl" 
        :alt="product.name"
        class="w-full h-full object-contain p-4"
      />
      <span class="absolute top-2 left-2 bg-green-500 text-white px-2 py-1 rounded text-xs font-bold">
        VEGAN
      </span>
      <span 
        v-if="product.isOrganic"
        class="absolute top-2 right-2 bg-emerald-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        ORGANIC
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Brand -->
      <p class="text-xs text-gray-500 mb-1">{{ product.brand }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ product.name }}</h3>
      
      <!-- Description -->
      <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ product.description }}</p>

      <!-- Certifications -->
      <div class="flex flex-wrap gap-2 mb-3">
        <span 
          v-for="cert in product.certifications" 
          :key="cert"
          class="px-2 py-1 bg-green-50 text-green-700 rounded text-xs"
        >
          {{ cert }}
        </span>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-green-600">{{ formatPrice(product.price) }}</span>
        <span class="text-xs text-gray-400">{{ product.weight }}</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(product.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ product.rating }} ({{ product.reviews }} reviews)</span>
      </div>

      <!-- Nutritional Info -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Nutritional Info</p>
        <div class="grid grid-cols-3 gap-2 text-center text-xs">
          <div class="bg-gray-50 rounded p-1">
            <p class="font-bold text-gray-900">{{ product.calories }} kcal</p>
          </div>
          <div class="bg-gray-50 rounded p-1">
            <p class="font-bold text-gray-900">{{ product.protein }}g protein</p>
          </div>
          <div class="bg-gray-50 rounded p-1">
            <p class="font-bold text-gray-900">{{ product.fat }}g fat</p>
          </div>
        </div>
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
          class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Add to Cart
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface VeganProduct {
  id: string
  name: string
  brand: string
  description: string
  imageUrl: string
  price: number
  weight: string
  rating: number
  reviews: number
  certifications: string[]
  isOrganic: boolean
  calories: number
  protein: number
  fat: number
}

const props = defineProps<{
  product: VeganProduct
}>()

const emit = defineEmits<{
  viewDetails: [product: VeganProduct]
  addToCart: [product: VeganProduct]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price)
}

const viewDetails = () => {
  emit('viewDetails', props.product)
}

const addToCart = () => {
  emit('addToCart', props.product)
}
</script>
