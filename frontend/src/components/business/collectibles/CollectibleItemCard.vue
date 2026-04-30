<template>
  <div class="collectible-item-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-48 bg-gradient-to-br from-yellow-50 to-amber-50">
      <img 
        :src="item.imageUrl" 
        :alt="item.name"
        class="w-full h-full object-contain p-4"
      />
      <span 
        :class="getConditionClass(item.condition)"
        class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-medium"
      >
        {{ item.condition }}
      </span>
      <span 
        v-if="item.isRare"
        class="absolute top-2 right-2 bg-amber-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        RARE
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Category -->
      <p class="text-xs text-gray-500 mb-1 uppercase font-medium">{{ item.category }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ item.name }}</h3>
      
      <!-- Era/Period -->
      <div v-if="item.era" class="text-sm text-gray-600 mb-2">{{ item.era }}</div>
      
      <!-- Description -->
      <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ item.description }}</p>

      <!-- Authenticity -->
      <div v-if="item.authenticated" class="flex items-center gap-2 mb-3 text-xs text-amber-600">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        <span>Authenticated by expert</span>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-amber-600">{{ formatPrice(item.price) }}</span>
        <span 
          v-if="item.estimatedValue"
          class="text-xs text-gray-400"
        >
          Est: {{ formatPrice(item.estimatedValue) }}
        </span>
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
          @click="makeOffer"
          class="flex-1 bg-amber-600 hover:bg-amber-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Make Offer
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface CollectibleItem {
  id: string
  name: string
  category: string
  description: string
  imageUrl: string
  price: number
  estimatedValue?: number
  condition: 'Mint' | 'Excellent' | 'Good' | 'Fair' | 'Poor'
  era?: string
  authenticated: boolean
  isRare: boolean
}

const props = defineProps<{
  item: CollectibleItem
}>()

const emit = defineEmits<{
  viewDetails: [item: CollectibleItem]
  makeOffer: [item: CollectibleItem]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getConditionClass = (condition: string) => {
  const classes = {
    'Mint': 'bg-green-100 text-green-900',
    'Excellent': 'bg-blue-100 text-blue-900',
    'Good': 'bg-yellow-100 text-yellow-900',
    'Fair': 'bg-orange-100 text-orange-900',
    'Poor': 'bg-red-100 text-red-900',
  }
  return classes[condition as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.item)
}

const makeOffer = () => {
  emit('makeOffer', props.item)
}
</script>
