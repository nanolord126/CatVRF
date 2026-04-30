<template>
  <div class="book-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-48 bg-amber-50">
      <img 
        :src="book.imageUrl" 
        :alt="book.name"
        class="w-full h-full object-contain p-4"
      />
      <span 
        v-if="book.isBestseller"
        class="absolute top-2 left-2 bg-amber-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        BESTSELLER
      </span>
      <span 
        v-if="book.isNew"
        class="absolute top-2 right-2 bg-green-500 text-white px-2 py-1 rounded text-xs font-bold"
      >
        NEW
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Author -->
      <p class="text-xs text-gray-500 mb-1">{{ book.author }}</p>
      
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ book.name }}</h3>
      
      <!-- Genre -->
      <div class="flex flex-wrap gap-2 mb-3">
        <span 
          v-for="genre in book.genres" 
          :key="genre"
          class="px-2 py-1 bg-amber-50 text-amber-700 rounded text-xs"
        >
          {{ genre }}
        </span>
      </div>

      <!-- Details -->
      <div class="space-y-1 mb-3 text-sm text-gray-600">
        <div><span class="font-medium">Format:</span> {{ book.format }}</div>
        <div><span class="font-medium">Pages:</span> {{ book.pages }}</div>
        <div><span class="font-medium">Language:</span> {{ book.language }}</div>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2 mb-3">
        <span class="text-xl font-bold text-amber-600">{{ formatPrice(book.price) }}</span>
        <span 
          v-if="book.originalPrice"
          class="text-sm text-gray-400 line-through"
        >
          {{ formatPrice(book.originalPrice) }}
        </span>
      </div>

      <!-- Rating -->
      <div class="flex items-center gap-2 mb-4 text-sm">
        <div class="flex text-yellow-400">
          <span v-for="i in Math.floor(book.rating)" :key="i">★</span>
        </div>
        <span class="text-gray-600">{{ book.rating }} ({{ book.reviews }} reviews)</span>
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
          class="flex-1 bg-amber-600 hover:bg-amber-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Add to Cart
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Book {
  id: string
  name: string
  author: string
  genres: string[]
  description: string
  imageUrl: string
  price: number
  originalPrice?: number
  rating: number
  reviews: number
  format: string
  pages: number
  language: string
  isBestseller: boolean
  isNew: boolean
}

const props = defineProps<{
  book: Book
}>()

const emit = defineEmits<{
  viewDetails: [book: Book]
  addToCart: [book: Book]
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
  emit('viewDetails', props.book)
}

const addToCart = () => {
  emit('addToCart', props.book)
}
</script>
