<template>
  <div class="artwork-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-56 bg-gray-100">
      <img 
        :src="artwork.imageUrl" 
        :alt="artwork.name"
        class="w-full h-full object-cover"
      />
      <span 
        :class="getCategoryClass(artwork.category)"
        class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium"
      >
        {{ artwork.category }}
      </span>
      <span 
        v-if="artwork.isOriginal"
        class="absolute top-2 right-2 bg-purple-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        ORIGINAL
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Artist -->
      <p class="text-xs text-gray-500 mb-1">{{ artwork.artist }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ artwork.name }}</h3>
      
      <!-- Year & Medium -->
      <div class="flex items-center gap-4 mb-3 text-sm text-gray-600">
        <span>{{ artwork.year }}</span>
        <span>•</span>
        <span>{{ artwork.medium }}</span>
        <span>•</span>
        <span>{{ artwork.dimensions }}</span>
      </div>

      <!-- Description -->
      <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ artwork.description }}</p>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-purple-600">{{ formatPrice(artwork.price) }}</span>
        <span v-if="!artwork.isOriginal" class="text-xs text-gray-400">print</span>
      </div>

      <!-- Certificate of Authenticity -->
      <div v-if="artwork.hasCertificate" class="flex items-center gap-2 mb-4 text-xs text-purple-600">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        <span>Certificate of Authenticity included</span>
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
          @click="inquire"
          class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Inquire
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Artwork {
  id: string
  name: string
  artist: string
  category: 'Painting' | 'Sculpture' | 'Photography' | 'Digital' | 'Print'
  description: string
  imageUrl: string
  price: number
  year: number
  medium: string
  dimensions: string
  isOriginal: boolean
  hasCertificate: boolean
}

const props = defineProps<{
  artwork: Artwork
}>()

const emit = defineEmits<{
  viewDetails: [artwork: Artwork]
  inquire: [artwork: Artwork]
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
    'Painting': 'bg-blue-100 text-blue-900',
    'Sculpture': 'bg-amber-100 text-amber-900',
    'Photography': 'bg-green-100 text-green-900',
    'Digital': 'bg-purple-100 text-purple-900',
    'Print': 'bg-pink-100 text-pink-900',
  }
  return classes[category as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.artwork)
}

const inquire = () => {
  emit('inquire', props.artwork)
}
</script>
