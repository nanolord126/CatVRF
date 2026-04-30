<template>
  <div class="construction-service-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-48 bg-gray-100">
      <img 
        :src="service.imageUrl" 
        :alt="service.name"
        class="w-full h-full object-cover"
      />
      <span 
        :class="getStatusClass(service.status)"
        class="absolute top-2 right-2 px-3 py-1 rounded-full text-xs font-medium"
      >
        {{ service.status }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Category -->
      <p class="text-xs text-gray-500 mb-1 uppercase font-medium">{{ service.category }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ service.name }}</h3>
      
      <!-- Description -->
      <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ service.description }}</p>

      <!-- Project Details -->
      <div class="space-y-2 mb-3 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Area:</span>
          <span class="font-medium">{{ service.area }} m²</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Duration:</span>
          <span class="font-medium">{{ service.duration }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Location:</span>
          <span class="font-medium">{{ service.location }}</span>
        </div>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-orange-600">{{ formatPrice(service.price) }}</span>
        <span class="text-xs text-gray-400">estimated</span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(service.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ service.rating }} ({{ service.projectsCompleted }} projects)</span>
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
          @click="requestQuote"
          class="flex-1 bg-orange-600 hover:bg-orange-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Request Quote
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface ConstructionService {
  id: string
  name: string
  category: string
  description: string
  imageUrl: string
  price: number
  rating: number
  projectsCompleted: number
  status: 'Available' | 'Busy' | 'On Leave'
  area: number
  duration: string
  location: string
}

const props = defineProps<{
  service: ConstructionService
}>()

const emit = defineEmits<{
  viewPortfolio: [service: ConstructionService]
  requestQuote: [service: ConstructionService]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getStatusClass = (status: string) => {
  const classes = {
    'Available': 'bg-green-100 text-green-900',
    'Busy': 'bg-yellow-100 text-yellow-900',
    'On Leave': 'bg-gray-100 text-gray-900',
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewPortfolio = () => {
  emit('viewPortfolio', props.service)
}

const requestQuote = () => {
  emit('requestQuote', props.service)
}
</script>
