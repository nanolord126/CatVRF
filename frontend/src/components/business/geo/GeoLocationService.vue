<template>
  <div class="geo-location-service bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-6">
      <h2 class="text-xl font-bold text-gray-900 mb-2">Location Services</h2>
      <p class="text-gray-600">Find nearby businesses and services</p>
    </div>

    <!-- Search -->
    <div class="mb-6">
      <div class="flex gap-2">
        <input 
          v-model="searchQuery"
          type="text"
          placeholder="Search location or service..."
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"
        />
        <button 
          @click="search"
          class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2 rounded-lg font-medium transition-colors"
        >
          Search
        </button>
      </div>
    </div>

    <!-- Category Filter -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
      <div class="flex gap-2 flex-wrap">
        <button 
          v-for="category in categories"
          :key="category.id"
          @click="selectCategory(category)"
          :class="[
            'px-4 py-2 rounded-full text-sm font-medium transition-all',
            selectedCategory?.id === category.id 
              ? 'bg-teal-600 text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ category.name }}
        </button>
      </div>
    </div>

    <!-- Results -->
    <div v-if="results.length > 0" class="space-y-3">
      <div 
        v-for="result in results" 
        :key="result.id"
        class="p-4 border border-gray-200 rounded-lg hover:border-teal-500 transition-colors cursor-pointer"
        @click="selectLocation(result)"
      >
        <div class="flex justify-between items-start">
          <div>
            <h4 class="font-medium text-gray-900">{{ result.name }}</h4>
            <p class="text-sm text-gray-500">{{ result.category }}</p>
            <p class="text-sm text-gray-600 mt-1">{{ result.address }}</p>
          </div>
          <div class="text-right">
            <p class="font-bold text-teal-600">{{ result.distance }} km</p>
            <p class="text-xs text-gray-400">{{ result.duration }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2 mt-2">
          <div class="flex text-yellow-400 text-sm">
            <span v-for="i in Math.floor(result.rating)" :key="i">★</span>
          </div>
          <span class="text-sm text-gray-600">{{ result.rating }}</span>
          <span v-if="result.isOpen" class="text-xs text-green-600">Open now</span>
          <span v-else class="text-xs text-red-600">Closed</span>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8 text-gray-500">
      <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
      </svg>
      <p>Search for locations nearby</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Category {
  id: string
  name: string
}

interface LocationResult {
  id: string
  name: string
  category: string
  address: string
  distance: number
  duration: string
  rating: number
  isOpen: boolean
}

const emit = defineEmits<{
  selectLocation: [location: LocationResult]
}>()

const searchQuery = ref('')
const selectedCategory = ref<Category>()
const results = ref<LocationResult[]>([])

const categories = ref<Category[]>([
  { id: '1', name: 'Restaurants' },
  { id: '2', name: 'Shops' },
  { id: '3', name: 'Services' },
  { id: '4', name: 'Healthcare' },
  { id: '5', name: 'Entertainment' },
])

const selectCategory = (category: Category) => {
  selectedCategory.value = category
  search()
}

const search = () => {
  // Mock search results
  results.value = [
    {
      id: '1',
      name: 'Sample Business',
      category: selectedCategory.value?.name || 'General',
      address: '123 Main Street',
      distance: 1.5,
      duration: '5 min',
      rating: 4.5,
      isOpen: true,
    }
  ]
}

const selectLocation = (location: LocationResult) => {
  emit('selectLocation', location)
}
</script>
