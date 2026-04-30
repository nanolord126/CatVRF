<template>
  <div class="music-instrument-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-48 bg-purple-50">
      <img 
        :src="instrument.imageUrl" 
        :alt="instrument.name"
        class="w-full h-full object-contain p-4"
      />
      <span 
        v-if="instrument.condition === 'New'"
        class="absolute top-2 left-2 bg-green-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        NEW
      </span>
      <span 
        v-if="instrument.condition === 'Used'"
        class="absolute top-2 left-2 bg-blue-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        USED
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Category & Brand -->
      <div class="flex justify-between items-start mb-1">
        <p class="text-xs text-gray-500 uppercase font-medium">{{ instrument.category }}</p>
        <p class="text-xs text-gray-500 font-medium">{{ instrument.brand }}</p>
      </div>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ instrument.name }}</h3>
      
      <!-- Condition -->
      <div class="flex items-center gap-2 mb-3 text-sm">
        <span :class="getConditionClass(instrument.condition)" class="px-2 py-1 rounded text-xs font-medium">
          {{ instrument.condition }}
        </span>
        <span v-if="instrument.condition === 'Used'" class="text-gray-500">{{ instrument.conditionRating }}/10</span>
      </div>

      <!-- Specs -->
      <div class="space-y-1 mb-3 text-sm text-gray-600">
        <div v-if="instrument.type"><span class="font-medium">Type:</span> {{ instrument.type }}</div>
        <div v-if="instrument.material"><span class="font-medium">Material:</span> {{ instrument.material }}</div>
        <div v-if="instrument.color"><span class="font-medium">Color:</span> {{ instrument.color }}</div>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-purple-600">{{ formatPrice(instrument.price) }}</span>
        <span 
          v-if="instrument.originalPrice"
          class="text-sm text-gray-400 line-through"
        >
          {{ formatPrice(instrument.originalPrice) }}
        </span>
      </div>

      <!-- Includes -->
      <div v-if="instrument.includes && instrument.includes.length" class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Includes:</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="item in instrument.includes" 
            :key="item"
            class="px-2 py-0.5 bg-purple-50 text-purple-700 rounded text-xs"
          >
            {{ item }}
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
          @click="buyNow"
          class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Buy Now
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface MusicInstrument {
  id: string
  name: string
  brand: string
  category: string
  description: string
  imageUrl: string
  price: number
  originalPrice?: number
  condition: 'New' | 'Used' | 'Vintage'
  conditionRating?: number
  type?: string
  material?: string
  color?: string
  includes?: string[]
}

const props = defineProps<{
  instrument: MusicInstrument
}>()

const emit = defineEmits<{
  viewDetails: [instrument: MusicInstrument]
  buyNow: [instrument: MusicInstrument]
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
    'New': 'bg-green-100 text-green-900',
    'Used': 'bg-blue-100 text-blue-900',
    'Vintage': 'bg-amber-100 text-amber-900',
  }
  return classes[condition as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.instrument)
}

const buyNow = () => {
  emit('buyNow', props.instrument)
}
</script>
