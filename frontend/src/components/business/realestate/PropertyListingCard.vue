<template>
  <div class="property-listing-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-48">
      <img 
        :src="property.imageUrl" 
        :alt="property.title"
        class="w-full h-full object-cover"
      />
      <span 
        :class="getTypeClass(property.type)"
        class="absolute top-2 left-2 px-3 py-1 rounded-full text-xs font-bold bg-white/90"
      >
        {{ property.type }}
      </span>
      <span 
        v-if="property.isFeatured"
        class="absolute top-2 right-2 bg-purple-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        FEATURED
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Price -->
      <p class="text-2xl font-bold text-purple-600 mb-2">{{ formatPrice(property.price) }}</p>
      
      <!-- Title -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ property.title }}</h3>
      
      <!-- Address -->
      <p class="text-sm text-gray-500 mb-3">{{ property.address }}</p>

      <!-- Specs -->
      <div class="grid grid-cols-3 gap-2 mb-4">
        <div class="bg-gray-50 rounded p-2 text-center">
          <p class="text-lg font-bold text-gray-900">{{ property.bedrooms }}</p>
          <p class="text-xs text-gray-500">Beds</p>
        </div>
        <div class="bg-gray-50 rounded p-2 text-center">
          <p class="text-lg font-bold text-gray-900">{{ property.bathrooms }}</p>
          <p class="text-xs text-gray-500">Baths</p>
        </div>
        <div class="bg-gray-50 rounded p-2 text-center">
          <p class="text-lg font-bold text-gray-900">{{ property.area }}</p>
          <p class="text-xs text-gray-500">m²</p>
        </div>
      </div>

      <!-- Agent Info -->
      <div class="flex items-center gap-2 mb-4">
        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
          <span class="text-purple-600 font-bold">{{ agent.initials }}</span>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">{{ agent.name }}</p>
          <p class="text-xs text-gray-500">{{ agent.agency }}</p>
        </div>
      </div>

      <!-- Amenities -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Key Features</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="amenity in property.amenities.slice(0, 4)" 
            :key="amenity"
            class="px-2 py-0.5 bg-purple-50 text-purple-700 rounded text-xs"
          >
            {{ amenity }}
          </span>
          <span v-if="property.amenities.length > 4" class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-xs">
            +{{ property.amenities.length - 4 }}
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="viewDetails"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          View Details
        </button>
        <button 
          @click="contactAgent"
          class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Contact Agent
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Agent {
  name: string
  initials: string
  agency: string
}

interface Property {
  id: string
  title: string
  type: 'Sale' | 'Rent' | 'New Development'
  address: string
  imageUrl: string
  price: number
  bedrooms: number
  bathrooms: number
  area: number
  isFeatured: boolean
  amenities: string[]
}

const props = defineProps<{
  property: Property
  agent: Agent
}>()

const emit = defineEmits<{
  viewDetails: [property: Property]
  contactAgent: [property: Property]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getTypeClass = (type: string) => {
  const classes = {
    'Sale': 'text-blue-900',
    'Rent': 'text-green-900',
    'New Development': 'text-purple-900',
  }
  return classes[type as keyof typeof classes] || 'text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.property)
}

const contactAgent = () => {
  emit('contactAgent', props.property)
}
</script>
