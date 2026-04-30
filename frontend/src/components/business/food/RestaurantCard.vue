<template>
  <div class="restaurant-card group bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
    <!-- Image -->
    <div class="relative aspect-video overflow-hidden">
      <img 
        :src="restaurant.image" 
        :alt="restaurant.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
      
      <!-- Badges -->
      <div class="absolute top-2 left-2 flex gap-1">
        <span v-if="restaurant.isFeatured" class="px-2 py-1 bg-yellow-500 text-white text-xs font-semibold rounded-full">
          Featured
        </span>
        <span v-if="restaurant.deliveryTime" class="px-2 py-1 bg-green-500 text-white text-xs font-semibold rounded-full flex items-center gap-1">
          <Clock class="w-3 h-3" />
          {{ restaurant.deliveryTime }}
        </span>
      </div>

      <!-- Rating -->
      <div class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm rounded-lg px-2 py-1 flex items-center gap-1">
        <Star class="w-4 h-4 fill-yellow-400 text-yellow-400" />
        <span class="text-sm font-bold">{{ restaurant.rating }}</span>
      </div>

      <!-- Delivery Fee -->
      <div v-if="restaurant.deliveryFee === 0" class="absolute bottom-2 left-2 bg-green-500 text-white px-2 py-1 rounded-lg text-xs font-semibold">
        Free Delivery
      </div>
    </div>

    <!-- Restaurant Info -->
    <div class="p-4">
      <!-- Name & Cuisine -->
      <div class="mb-2">
        <h3 class="font-semibold text-gray-900 text-lg line-clamp-1">{{ restaurant.name }}</h3>
        <p class="text-gray-500 text-sm">{{ restaurant.cuisine }}</p>
      </div>

      <!-- Location -->
      <div class="flex items-center gap-1 text-gray-500 text-sm mb-2">
        <MapPin class="w-4 h-4" />
        <span>{{ restaurant.location }}</span>
        <span class="text-gray-300">•</span>
        <span>{{ restaurant.distance }} km</span>
      </div>

      <!-- Price Range -->
      <div class="flex items-center gap-1 mb-3">
        <span 
          v-for="i in 4" 
          :key="i"
          class="text-lg"
          :class="i <= restaurant.priceRange ? 'text-green-600' : 'text-gray-300'"
        >
          $
        </span>
      </div>

      <!-- Popular Dishes -->
      <div v-if="restaurant.popularDishes && restaurant.popularDishes.length" class="mb-3">
        <p class="text-sm font-medium text-gray-700 mb-2">Popular</p>
        <div class="flex gap-2 overflow-x-auto pb-2">
          <div 
            v-for="dish in restaurant.popularDishes.slice(0, 3)" 
            :key="dish.name"
            class="flex-shrink-0 w-20"
          >
            <img :src="dish.image" :alt="dish.name" class="w-full aspect-square rounded-lg object-cover mb-1" />
            <p class="text-xs text-gray-600 truncate">{{ dish.name }}</p>
            <p class="text-xs font-semibold text-gray-900">{{ formatPrice(dish.price) }}</p>
          </div>
        </div>
      </div>

      <!-- Offers -->
      <div v-if="restaurant.offers && restaurant.offers.length" class="mb-4">
        <div 
          v-for="offer in restaurant.offers.slice(0, 2)" 
          :key="offer.id"
          class="flex items-center gap-2 p-2 bg-purple-50 rounded-lg mb-2"
        >
          <Tag class="w-4 h-4 text-purple-600" />
          <span class="text-sm text-purple-700">{{ offer.description }}</span>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="viewMenu"
          class="flex-1 bg-gray-900 hover:bg-gray-800 text-white py-2 px-4 rounded-lg font-medium transition-colors"
        >
          View Menu
        </button>
        <button 
          @click="orderNow"
          class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg font-medium transition-colors"
        >
          Order Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Star, Clock, MapPin, Tag } from 'lucide-vue-next'

interface Dish {
  name: string
  image: string
  price: number
}

interface Offer {
  id: string
  description: string
}

interface Restaurant {
  id: string
  name: string
  cuisine: string
  location: string
  image: string
  rating: number
  distance: number
  priceRange: number
  deliveryTime?: string
  deliveryFee: number
  isFeatured?: boolean
  popularDishes?: Dish[]
  offers?: Offer[]
}

const props = defineProps<{
  restaurant: Restaurant
}>()

const emit = defineEmits<{
  viewMenu: [restaurant: Restaurant]
  orderNow: [restaurant: Restaurant]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(price)
}

const viewMenu = () => {
  emit('viewMenu', props.restaurant)
}

const orderNow = () => {
  emit('orderNow', props.restaurant)
}
</script>

<style scoped>
.line-clamp-1 {
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
