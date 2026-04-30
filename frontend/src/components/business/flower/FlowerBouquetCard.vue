<template>
  <div class="flower-bouquet-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-48 bg-pink-50">
      <img 
        :src="bouquet.imageUrl" 
        :alt="bouquet.name"
        class="w-full h-full object-cover"
      />
      <span 
        v-if="bouquet.isSeasonal"
        class="absolute top-2 left-2 bg-pink-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        SEASONAL
      </span>
      <span 
        v-if="bouquet.discount"
        class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        -{{ bouquet.discount }}%
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ bouquet.name }}</h3>
      
      <!-- Flower Types -->
      <div class="mb-3">
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="flower in bouquet.flowers.slice(0, 3)" 
            :key="flower"
            class="px-2 py-0.5 bg-pink-50 text-pink-700 rounded text-xs"
          >
            {{ flower }}
          </span>
          <span v-if="bouquet.flowers.length > 3" class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
            +{{ bouquet.flowers.length - 3 }}
          </span>
        </div>
      </div>

      <!-- Size -->
      <div class="mb-3">
        <p class="text-sm text-gray-600">Size: {{ bouquet.size }}</p>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-pink-600">{{ formatPrice(bouquet.price) }}</span>
        <span 
          v-if="bouquet.originalPrice"
          class="text-sm text-gray-400 line-through"
        >
          {{ formatPrice(bouquet.originalPrice) }}
        </span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(bouquet.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ bouquet.rating }} ({{ bouquet.reviews }} reviews)</span>
      </div>

      <!-- Delivery Options -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Delivery</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="option in bouquet.deliveryOptions" 
            :key="option"
            class="px-2 py-0.5 bg-green-50 text-green-700 rounded text-xs"
          >
            {{ option }}
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
          class="flex-1 bg-pink-600 hover:bg-pink-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Add to Cart
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface FlowerBouquet {
  id: string
  name: string
  flowers: string[]
  size: string
  imageUrl: string
  price: number
  originalPrice?: number
  rating: number
  reviews: number
  discount?: number
  isSeasonal: boolean
  deliveryOptions: string[]
}

const props = defineProps<{
  bouquet: FlowerBouquet
}>()

const emit = defineEmits<{
  viewDetails: [bouquet: FlowerBouquet]
  addToCart: [bouquet: FlowerBouquet]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const viewDetails = () => {
  emit('viewDetails', props.bouquet)
}

const addToCart = () => {
  emit('addToCart', props.bouquet)
}
</script>
