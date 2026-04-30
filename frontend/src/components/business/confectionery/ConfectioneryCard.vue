<template>
  <div class="confectionery-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-amber-50">
      <img 
        :src="product.imageUrl" 
        :alt="product.name"
        class="w-full h-full object-contain p-4"
      />
      <span 
        v-if="product.isHandmade"
        class="absolute top-2 left-2 bg-amber-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        HANDMADE
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
      <p class="text-xs text-gray-500 mb-1 uppercase font-medium">{{ product.brand }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ product.name }}</h3>
      
      <!-- Type -->
      <p class="text-sm text-gray-500 mb-3">{{ product.type }}</p>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-amber-600">{{ formatPrice(product.price) }}</span>
        <span class="text-xs text-gray-400">{{ product.weight }}</span>
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

      <!-- Flavors -->
      <div v-if="product.flavors && product.flavors.length" class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Available Flavors:</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="flavor in product.flavors.slice(0, 3)" 
            :key="flavor"
            class="px-2 py-0.5 bg-amber-50 text-amber-700 rounded text-xs"
          >
            {{ flavor }}
          </span>
          <span v-if="product.flavors.length > 3" class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
            +{{ product.flavors.length - 3 }}
          </span>
        </div>
      </div>

      <!-- Allergens -->
      <div v-if="product.allergens && product.allergens.length" class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Contains:</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="allergen in product.allergens" 
            :key="allergen"
            class="px-2 py-0.5 bg-red-50 text-red-700 rounded text-xs"
          >
            {{ allergen }}
          </span>
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
          class="flex-1 bg-amber-600 hover:bg-amber-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Add to Cart
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Confectionery {
  id: string
  name: string
  brand: string
  type: string
  description: string
  imageUrl: string
  price: number
  originalPrice?: number
  weight: string
  rating: number
  reviews: number
  discount?: number
  isHandmade: boolean
  flavors?: string[]
  allergens?: string[]
}

const props = defineProps<{
  product: Confectionery
}>()

const emit = defineEmits<{
  viewDetails: [product: Confectionery]
  addToCart: [product: Confectionery]
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
