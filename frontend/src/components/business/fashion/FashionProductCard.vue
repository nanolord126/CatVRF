<template>
  <div class="fashion-product-card group relative bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
    <!-- Product Image -->
    <div class="relative aspect-[3/4] overflow-hidden bg-gray-100">
      <img 
        :src="product.image" 
        :alt="product.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
        @error="handleImageError"
      />
      
      <!-- Badges -->
      <div class="absolute top-2 left-2 flex gap-1">
        <span v-if="product.isNew" class="px-2 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">
          New
        </span>
        <span v-if="product.discount" class="px-2 py-1 bg-red-500 text-white text-xs font-semibold rounded-full">
          -{{ product.discount }}%
        </span>
      </div>

      <!-- Wishlist Button -->
      <button 
        @click="toggleWishlist"
        class="absolute top-2 right-2 p-2 bg-white/90 hover:bg-white rounded-full shadow-md transition-colors"
      >
        <Heart 
          :class="isInWishlist ? 'fill-red-500 text-red-500' : 'text-gray-600'"
          class="w-5 h-5"
        />
      </button>

      <!-- Quick View Button -->
      <button 
        @click="quickView"
        class="absolute bottom-2 right-2 p-2 bg-white/90 hover:bg-white rounded-full shadow-md opacity-0 group-hover:opacity-100 transition-all"
      >
        <Eye class="w-5 h-5 text-gray-600" />
      </button>
    </div>

    <!-- Product Info -->
    <div class="p-4">
      <!-- Brand -->
      <p class="text-sm text-gray-500 mb-1">{{ product.brand }}</p>
      
      <!-- Name -->
      <h3 class="font-semibold text-gray-900 mb-2 line-clamp-2">{{ product.name }}</h3>
      
      <!-- Price -->
      <div class="flex items-center gap-2 mb-2">
        <span class="text-lg font-bold text-gray-900">
          {{ formatPrice(product.price) }}
        </span>
        <span v-if="product.originalPrice" class="text-sm text-gray-400 line-through">
          {{ formatPrice(product.originalPrice) }}
        </span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-1 mb-3">
        <Star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
        <span class="text-sm font-medium text-gray-700">{{ product.rating }}</span>
        <span class="text-sm text-gray-400">({{ product.reviews }})</span>
      </div>

      <!-- Colors -->
      <div v-if="product.colors && product.colors.length" class="flex gap-1 mb-3">
        <button 
          v-for="color in product.colors.slice(0, 4)"
          :key="color.id"
          :style="{ backgroundColor: color.hex }"
          :title="color.name"
          class="w-5 h-5 rounded-full border-2 border-white shadow-sm hover:scale-110 transition-transform"
          @click="selectColor(color)"
        />
        <span v-if="product.colors.length > 4" class="text-xs text-gray-500 ml-1">
          +{{ product.colors.length - 4 }}
        </span>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="addToCart"
          class="flex-1 bg-gray-900 hover:bg-gray-800 text-white py-2 px-4 rounded-lg font-medium transition-colors"
        >
          Add to Cart
        </button>
        <button 
          @click="tryOn"
          class="p-2 border-2 border-gray-900 hover:bg-gray-900 hover:text-white rounded-lg transition-colors"
          title="Virtual Try-On"
        >
          <Shirt class="w-5 h-5" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Heart, Eye, Star, Shirt } from 'lucide-vue-next'

interface ProductColor {
  id: string
  name: string
  hex: string
}

interface FashionProduct {
  id: string
  name: string
  brand: string
  image: string
  price: number
  originalPrice?: number
  discount?: number
  rating: number
  reviews: number
  isNew?: boolean
  colors?: ProductColor[]
}

const props = defineProps<{
  product: FashionProduct
}>()

const emit = defineEmits<{
  addToCart: [product: FashionProduct]
  toggleWishlist: [product: FashionProduct]
  quickView: [product: FashionProduct]
  tryOn: [product: FashionProduct]
  selectColor: [color: ProductColor]
}>()

const isInWishlist = ref(false)

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(price)
}

const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.src = '/images/placeholder-product.png'
}

const toggleWishlist = () => {
  isInWishlist.value = !isInWishlist.value
  emit('toggleWishlist', props.product)
}

const quickView = () => {
  emit('quickView', props.product)
}

const addToCart = () => {
  emit('addToCart', props.product)
}

const tryOn = () => {
  emit('tryOn', props.product)
}

const selectColor = (color: ProductColor) => {
  emit('selectColor', color)
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
