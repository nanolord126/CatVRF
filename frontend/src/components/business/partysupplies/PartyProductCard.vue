<template>
  <div class="party-product-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-pink-50">
      <img 
        :src="product.imageUrl" 
        :alt="product.name"
        class="w-full h-full object-contain p-4"
      />
      <span 
        :class="getCategoryClass(product.category)"
        class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium"
      >
        {{ product.category }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ product.name }}</h3>
      
      <!-- Theme -->
      <p class="text-sm text-gray-500 mb-3">{{ product.theme }}</p>

      <!-- Quantity per pack -->
      <div class="flex items-center gap-2 mb-3 text-sm text-gray-600">
        <span>{{ product.quantityPerPack }} per pack</span>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-pink-600">{{ formatPrice(product.price) }}</span>
        <span class="text-xs text-gray-400">/ pack</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(product.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ product.rating }} ({{ product.reviews }} reviews)</span>
      </div>

      <!-- Colors Available -->
      <div v-if="product.colors && product.colors.length" class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Colors:</p>
        <div class="flex gap-1">
          <span 
            v-for="color in product.colors" 
            :key="color"
            class="w-6 h-6 rounded-full border border-gray-300"
            :style="{ backgroundColor: color }"
          ></span>
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
          class="flex-1 bg-pink-600 hover:bg-pink-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Add to Cart
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface PartyProduct {
  id: string
  name: string
  category: string
  theme: string
  description: string
  imageUrl: string
  price: number
  rating: number
  reviews: number
  quantityPerPack: number
  colors?: string[]
}

const props = defineProps<{
  product: PartyProduct
}>()

const emit = defineEmits<{
  viewDetails: [product: PartyProduct]
  addToCart: [product: PartyProduct]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getCategoryClass = (category: string) => {
  const classes = {
    'Balloons': 'bg-pink-100 text-pink-900',
    'Decorations': 'bg-purple-100 text-purple-900',
    'Tableware': 'bg-blue-100 text-blue-900',
    'Games': 'bg-green-100 text-green-900',
    'Costumes': 'bg-yellow-100 text-yellow-900',
  }
  return classes[category as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.product)
}

const addToCart = () => {
  emit('addToCart', props.product)
}
</script>
