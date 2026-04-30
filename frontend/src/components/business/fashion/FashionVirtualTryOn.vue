<template>
  <div class="fashion-try-on relative bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl overflow-hidden">
    <!-- Header -->
    <div class="p-4 bg-white/80 backdrop-blur-sm border-b">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">Virtual Try-On</h2>
          <p class="text-sm text-gray-500">See how it looks on you</p>
        </div>
        <button 
          @click="close"
          class="p-2 hover:bg-gray-100 rounded-full transition-colors"
        >
          <X class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-6">
      <!-- Left: Upload/Preview -->
      <div class="space-y-4">
        <div 
          class="relative aspect-[3/4] bg-gray-100 rounded-xl overflow-hidden border-2 border-dashed border-gray-300 hover:border-purple-400 transition-colors"
          @dragover.prevent="handleDragOver"
          @dragleave.prevent="handleDragLeave"
          @drop.prevent="handleDrop"
          @click="triggerFileInput"
        >
          <!-- Uploaded Image Preview -->
          <img 
            v-if="userImage" 
            :src="userImage" 
            alt="Your photo"
            class="w-full h-full object-cover"
          />
          
          <!-- Upload Placeholder -->
          <div v-else class="absolute inset-0 flex flex-col items-center justify-center text-gray-400">
            <Upload class="w-12 h-12 mb-2" />
            <p class="text-sm font-medium">Upload your photo</p>
            <p class="text-xs">or drag and drop</p>
          </div>

          <!-- Loading Overlay -->
          <div v-if="isProcessing" class="absolute inset-0 bg-black/50 flex items-center justify-center">
            <Loader2 class="w-8 h-8 animate-spin text-white" />
          </div>
        </div>

        <!-- File Input -->
        <input 
          ref="fileInput"
          type="file"
          accept="image/*"
          class="hidden"
          @change="handleFileSelect"
        />

        <!-- Controls -->
        <div class="flex gap-2">
          <button 
            @click="triggerFileInput"
            class="flex-1 bg-white border border-gray-300 hover:bg-gray-50 py-2 px-4 rounded-lg font-medium transition-colors"
          >
            <Upload class="w-4 h-4 inline mr-2" />
            Upload Photo
          </button>
          <button 
            @click="useCamera"
            class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-lg font-medium transition-colors"
          >
            <Camera class="w-4 h-4 inline mr-2" />
            Use Camera
          </button>
        </div>
      </div>

      <!-- Right: Product Selection & Result -->
      <div class="space-y-4">
        <!-- Product Selector -->
        <div class="bg-white rounded-xl p-4 shadow-sm">
          <h3 class="font-semibold text-gray-900 mb-3">Select Product</h3>
          <div class="grid grid-cols-3 gap-2">
            <button 
              v-for="item in products"
              :key="item.id"
              @click="selectProduct(item)"
              :class="[
                'relative rounded-lg overflow-hidden border-2 transition-all',
                selectedProduct?.id === item.id 
                  ? 'border-purple-500 ring-2 ring-purple-200' 
                  : 'border-gray-200 hover:border-purple-300'
              ]"
            >
              <img :src="item.image" :alt="item.name" class="w-full aspect-square object-cover" />
              <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent p-2">
                <p class="text-xs text-white font-medium truncate">{{ item.name }}</p>
              </div>
            </button>
          </div>
        </div>

        <!-- Try-On Result -->
        <div v-if="tryOnResult" class="bg-white rounded-xl p-4 shadow-sm">
          <h3 class="font-semibold text-gray-900 mb-3">Result</h3>
          <div class="relative aspect-[3/4] bg-gray-100 rounded-lg overflow-hidden">
            <img :src="tryOnResult.image" alt="Try-on result" class="w-full h-full object-cover" />
            
            <!-- Confidence Score -->
            <div class="absolute bottom-4 left-4 right-4 bg-white/90 backdrop-blur-sm rounded-lg p-3">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Fit Score</span>
                <span class="text-sm font-bold text-green-600">{{ tryOnResult.fitScore }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div 
                  class="bg-green-500 h-2 rounded-full transition-all"
                  :style="{ width: tryOnResult.fitScore + '%' }"
                />
              </div>
              <p class="text-xs text-gray-500 mt-2">{{ tryOnResult.recommendation }}</p>
            </div>
          </div>
        </div>

        <!-- Size Recommendation -->
        <div v-if="tryOnResult?.recommendedSize" class="bg-purple-50 rounded-xl p-4">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-purple-100 rounded-lg">
              <Ruler class="w-6 h-6 text-purple-600" />
            </div>
            <div>
              <p class="text-sm text-gray-600">Recommended Size</p>
              <p class="text-lg font-bold text-purple-900">{{ tryOnResult.recommendedSize }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer Actions -->
    <div class="p-4 bg-white/80 backdrop-blur-sm border-t">
      <button 
        @click="addToCart"
        :disabled="!tryOnResult"
        class="w-full bg-purple-600 hover:bg-purple-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-3 px-6 rounded-xl font-semibold transition-colors"
      >
        Add to Cart
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { X, Upload, Camera, Loader2, Ruler } from 'lucide-vue-next'

interface Product {
  id: string
  name: string
  image: string
}

interface TryOnResult {
  image: string
  fitScore: number
  recommendation: string
  recommendedSize?: string
}

const emit = defineEmits<{
  close: []
  addToCart: [result: TryOnResult]
}>()

const fileInput = ref<HTMLInputElement>()
const userImage = ref<string>()
const isProcessing = ref(false)
const selectedProduct = ref<Product>()
const tryOnResult = ref<TryOnResult>()

const products = ref<Product[]>([
  { id: '1', name: 'Summer Dress', image: '/images/products/fashion/dress1.jpg' },
  { id: '2', name: 'Blouse', image: '/images/products/fashion/blouse1.jpg' },
  { id: '3', name: 'Jeans', image: '/images/products/fashion/jeans1.jpg' },
  { id: '4', name: 'Skirt', image: '/images/products/fashion/skirt1.jpg' },
  { id: '5', name: 'Jacket', image: '/images/products/fashion/jacket1.jpg' },
  { id: '6', name: 'Sweater', image: '/images/products/fashion/sweater1.jpg' },
])

const handleDragOver = (e: DragEvent) => {
  e.preventDefault()
  e.currentTarget?.classList.add('border-purple-400', 'bg-purple-50')
}

const handleDragLeave = (e: DragEvent) => {
  e.preventDefault()
  e.currentTarget?.classList.remove('border-purple-400', 'bg-purple-50')
}

const handleDrop = (e: DragEvent) => {
  e.preventDefault()
  e.currentTarget?.classList.remove('border-purple-400', 'bg-purple-50')
  
  const files = e.dataTransfer?.files
  if (files && files.length > 0) {
    processFile(files[0])
  }
}

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileSelect = (e: Event) => {
  const target = e.target as HTMLInputElement
  const files = target.files
  if (files && files.length > 0) {
    processFile(files[0])
  }
}

const processFile = (file: File) => {
  const reader = new FileReader()
  reader.onload = (e) => {
    userImage.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

const useCamera = () => {
  // Implement camera capture
  console.log('Camera capture')
}

const selectProduct = (product: Product) => {
  selectedProduct.value = product
  generateTryOn()
}

const generateTryOn = async () => {
  if (!userImage.value || !selectedProduct.value) return
  
  isProcessing.value = true
  
  // Simulate API call
  await new Promise(resolve => setTimeout(resolve, 2000))
  
  tryOnResult.value = {
    image: userImage.value, // In real app, this would be the AI-generated image
    fitScore: 92,
    recommendation: 'This item fits you perfectly!',
    recommendedSize: 'M'
  }
  
  isProcessing.value = false
}

const close = () => {
  emit('close')
}

const addToCart = () => {
  if (tryOnResult.value) {
    emit('addToCart', tryOnResult.value)
  }
}
</script>
