<template>
  <div class="property-card group bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
    <!-- Image Gallery -->
    <div class="relative aspect-video overflow-hidden">
      <img 
        :src="property.mainImage" 
        :alt="property.title"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
      
      <!-- Badges -->
      <div class="absolute top-2 left-2 flex gap-1">
        <span v-if="property.isNew" class="px-2 py-1 bg-green-500 text-white text-xs font-semibold rounded-full">
          New
        </span>
        <span v-if="property.isVerified" class="px-2 py-1 bg-blue-500 text-white text-xs font-semibold rounded-full flex items-center gap-1">
          <CheckCircle class="w-3 h-3" />
          Verified
        </span>
        <span v-if="property.isHot" class="px-2 py-1 bg-red-500 text-white text-xs font-semibold rounded-full">
          Hot
        </span>
      </div>

      <!-- Price -->
      <div class="absolute bottom-2 left-2 bg-white/90 backdrop-blur-sm rounded-lg px-3 py-2">
        <span class="text-lg font-bold text-gray-900">{{ formatPrice(property.price) }}</span>
      </div>

      <!-- Favorite Button -->
      <button 
        @click="toggleFavorite"
        class="absolute top-2 right-2 p-2 bg-white/90 hover:bg-white rounded-full shadow-md transition-colors"
      >
        <Heart 
          :class="isFavorite ? 'fill-red-500 text-red-500' : 'text-gray-600'"
          class="w-5 h-5"
        />
      </button>

      <!-- Virtual Tour Button -->
      <button 
        @click="startVirtualTour"
        class="absolute bottom-2 right-2 p-2 bg-purple-600 hover:bg-purple-700 text-white rounded-full shadow-md transition-colors"
        title="Virtual Tour"
      >
        <Monitor class="w-5 h-5" />
      </button>
    </div>

    <!-- Property Info -->
    <div class="p-4">
      <!-- Location -->
      <div class="flex items-center gap-1 text-gray-500 text-sm mb-1">
        <MapPin class="w-4 h-4" />
        <span>{{ property.location }}</span>
      </div>

      <!-- Title -->
      <h3 class="font-semibold text-gray-900 text-lg mb-2 line-clamp-2">{{ property.title }}</h3>

      <!-- Specifications -->
      <div class="grid grid-cols-3 gap-2 mb-3">
        <div class="flex items-center gap-1 text-gray-600 text-sm">
          <Bed class="w-4 h-4" />
          <span>{{ property.bedrooms }} beds</span>
        </div>
        <div class="flex items-center gap-1 text-gray-600 text-sm">
          <Bath class="w-4 h-4" />
          <span>{{ property.bathrooms }} baths</span>
        </div>
        <div class="flex items-center gap-1 text-gray-600 text-sm">
          <Maximize class="w-4 h-4" />
          <span>{{ property.area }} m²</span>
        </div>
      </div>

      <!-- Features -->
      <div class="flex flex-wrap gap-1 mb-3">
        <span 
          v-for="feature in property.features.slice(0, 4)" 
          :key="feature"
          class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full"
        >
          {{ feature }}
        </span>
        <span v-if="property.features.length > 4" class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full">
          +{{ property.features.length - 4 }}
        </span>
      </div>

      <!-- Agent Info -->
      <div class="flex items-center gap-3 mb-4 p-2 bg-gray-50 rounded-lg">
        <img :src="property.agent.avatar" :alt="property.agent.name" class="w-10 h-10 rounded-full" />
        <div class="flex-1">
          <p class="text-sm font-medium text-gray-900">{{ property.agent.name }}</p>
          <p class="text-xs text-gray-500">{{ property.agent.agency }}</p>
        </div>
        <button 
          @click="contactAgent"
          class="p-2 bg-purple-100 hover:bg-purple-200 text-purple-600 rounded-full transition-colors"
        >
          <MessageSquare class="w-4 h-4" />
        </button>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="viewDetails"
          class="flex-1 bg-gray-900 hover:bg-gray-800 text-white py-2 px-4 rounded-lg font-medium transition-colors"
        >
          View Details
        </button>
        <button 
          @click="scheduleViewing"
          class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg font-medium transition-colors"
        >
          Schedule Viewing
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { CheckCircle, Heart, Monitor, MapPin, Bed, Bath, Maximize, MessageSquare } from 'lucide-vue-next'

interface Agent {
  name: string
  agency: string
  avatar: string
}

interface Property {
  id: string
  title: string
  location: string
  mainImage: string
  price: number
  bedrooms: number
  bathrooms: number
  area: number
  features: string[]
  isNew?: boolean
  isVerified?: boolean
  isHot?: boolean
  agent: Agent
}

const props = defineProps<{
  property: Property
}>()

const emit = defineEmits<{
  viewDetails: [property: Property]
  scheduleViewing: [property: Property]
  startVirtualTour: [property: Property]
  contactAgent: [property: Property]
  toggleFavorite: [property: Property]
}>()

const isFavorite = ref(false)

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const toggleFavorite = () => {
  isFavorite.value = !isFavorite.value
  emit('toggleFavorite', props.property)
}

const viewDetails = () => {
  emit('viewDetails', props.property)
}

const scheduleViewing = () => {
  emit('scheduleViewing', props.property)
}

const startVirtualTour = () => {
  emit('startVirtualTour', props.property)
}

const contactAgent = () => {
  emit('contactAgent', props.property)
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
