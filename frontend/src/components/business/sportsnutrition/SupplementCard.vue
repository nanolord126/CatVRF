<template>
  <div class="supplement-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-40 bg-gradient-to-br from-red-50 to-orange-50">
      <img 
        :src="supplement.imageUrl" 
        :alt="supplement.name"
        class="w-full h-full object-contain p-4"
      />
      <span 
        :class="getTypeClass(supplement.type)"
        class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium"
      >
        {{ supplement.type }}
      </span>
      <span 
        v-if="supplement.isBestseller"
        class="absolute top-2 right-2 bg-orange-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        BESTSELLER
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Brand -->
      <p class="text-xs text-gray-500 mb-1 uppercase font-medium">{{ supplement.brand }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ supplement.name }}</h3>
      
      <!-- Flavor & Size -->
      <div class="flex items-center gap-4 mb-3 text-sm text-gray-600">
        <span v-if="supplement.flavor">{{ supplement.flavor }}</span>
        <span>•</span>
        <span>{{ supplement.size }}</span>
        <span>•</span>
        <span>{{ supplement.servings }} servings</span>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-red-600">{{ formatPrice(supplement.price) }}</span>
        <span class="text-xs text-gray-400">{{ formatPricePerServing(supplement.price, supplement.servings) }}/serving</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(supplement.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ supplement.rating }} ({{ supplement.reviews }} reviews)</span>
      </div>

      <!-- Key Ingredients -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Key Ingredients</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="ingredient in supplement.ingredients.slice(0, 3)" 
            :key="ingredient"
            class="px-2 py-0.5 bg-red-50 text-red-700 rounded text-xs"
          >
            {{ ingredient }}
          </span>
          <span v-if="supplement.ingredients.length > 3" class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
            +{{ supplement.ingredients.length - 3 }}
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
          class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Add to Cart
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Supplement {
  id: string
  name: string
  brand: string
  type: 'Protein' | 'Creatine' | 'Pre-Workout' | 'Vitamins' | 'Other'
  description: string
  imageUrl: string
  price: number
  rating: number
  reviews: number
  flavor?: string
  size: string
  servings: number
  ingredients: string[]
  isBestseller: boolean
}

const props = defineProps<{
  supplement: Supplement
}>()

const emit = defineEmits<{
  viewDetails: [supplement: Supplement]
  addToCart: [supplement: Supplement]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const formatPricePerServing = (price: number, servings: number) => {
  return formatPrice(price / servings)
}

const getTypeClass = (type: string) => {
  const classes = {
    'Protein': 'bg-blue-100 text-blue-900',
    'Creatine': 'bg-purple-100 text-purple-900',
    'Pre-Workout': 'bg-red-100 text-red-900',
    'Vitamins': 'bg-green-100 text-green-900',
    'Other': 'bg-gray-100 text-gray-900',
  }
  return classes[type as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.supplement)
}

const addToCart = () => {
  emit('addToCart', props.supplement)
}
</script>
