<template>
  <div class="content-creator-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Header Image -->
    <div class="relative h-32 bg-gradient-to-r from-purple-500 to-pink-500">
      <img 
        :src="creator.coverImageUrl" 
        :alt="creator.name"
        class="w-full h-full object-cover opacity-90"
      />
    </div>

    <!-- Content -->
    <div class="p-4 -mt-12 relative">
      <!-- Avatar -->
      <div class="w-24 h-24 bg-white rounded-full border-4 border-white shadow-lg overflow-hidden mb-3">
        <img 
          :src="creator.avatarUrl" 
          :alt="creator.name"
          class="w-full h-full object-cover"
        />
      </div>

      <!-- Name & Stats -->
      <div class="mb-4">
        <h3 class="text-lg font-bold text-gray-900">{{ creator.name }}</h3>
        <p class="text-sm text-gray-500">{{ creator.niche }}</p>
        <div class="flex items-center gap-4 mt-2 text-sm">
          <div class="flex items-center gap-1">
            <span class="font-bold text-gray-900">{{ creator.followers }}</span>
            <span class="text-gray-500">followers</span>
          </div>
          <div class="flex items-center gap-1">
            <span class="font-bold text-gray-900">{{ creator.avgViews }}</span>
            <span class="text-gray-500">avg views</span>
          </div>
        </div>
      </div>

      <!-- Services -->
      <div class="mb-4">
        <p class="text-sm font-medium text-gray-700 mb-2">Services</p>
        <div class="flex flex-wrap gap-2">
          <span 
            v-for="service in creator.services" 
            :key="service"
            class="px-2 py-1 bg-purple-50 text-purple-700 rounded text-xs"
          >
            {{ service }}
          </span>
        </div>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-4">
        <span class="text-xl font-bold text-purple-600">{{ formatPrice(creator.price) }}</span>
        <span class="text-xs text-gray-400">/ collaboration</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(creator.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ creator.rating }} ({{ creator.collaborations }} collaborations)</span>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="viewPortfolio"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Portfolio
        </button>
        <button 
          @click="contact"
          class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Contact
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface ContentCreator {
  id: string
  name: string
  niche: string
  avatarUrl: string
  coverImageUrl: string
  followers: string
  avgViews: string
  services: string[]
  price: number
  rating: number
  collaborations: number
}

const props = defineProps<{
  creator: ContentCreator
}>()

const emit = defineEmits<{
  viewPortfolio: [creator: ContentCreator]
  contact: [creator: ContentCreator]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const viewPortfolio = () => {
  emit('viewPortfolio', props.creator)
}

const contact = () => {
  emit('contact', props.creator)
}
</script>
