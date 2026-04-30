<template>
  <div class="ai-recommendations bg-white rounded-xl shadow-md p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Рекомендовано для вас</h3>
      <div class="flex items-center gap-2 text-sm text-gray-500">
        <Sparkles class="w-4 h-4 text-purple-600" />
        <span>AI-powered</span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <Loader class="w-8 h-8 text-indigo-600 animate-spin" />
    </div>

    <!-- Recommendations Grid -->
    <div v-else-if="recommendations.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <div 
        v-for="product in recommendations" 
        :key="product.id"
        class="group cursor-pointer"
        @click="viewProduct(product)"
      >
        <div class="relative aspect-square overflow-hidden rounded-lg mb-2">
          <img 
            :src="product.image || '/placeholder-product.jpg'"
            :alt="product.name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
          />
          
          <!-- AI Match Score -->
          <div class="absolute top-2 right-2 bg-purple-600 text-white px-2 py-1 rounded-full text-xs font-bold">
            {{ product.match_score }}%
          </div>
          
          <!-- Cold Chain Badge -->
          <div v-if="product.requires_cold_chain" class="absolute top-2 left-2 bg-blue-500 text-white p-1.5 rounded-full">
            <Snowflake class="w-3 h-3" />
          </div>
        </div>
        
        <h4 class="text-sm font-medium text-gray-900 line-clamp-2 mb-1">{{ product.name }}</h4>
        <p class="text-xs text-gray-500 mb-2">{{ product.reason }}</p>
        <div class="flex items-center justify-between">
          <p class="text-sm font-bold text-gray-900">{{ formatPrice(product.price) }}</p>
          <button 
            @click.stop="addToCart(product)"
            class="bg-indigo-600 hover:bg-indigo-700 text-white p-2 rounded-lg transition-colors"
          >
            <Plus class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="text-center py-8">
      <Sparkles class="w-12 h-12 text-gray-400 mx-auto mb-2" />
      <p class="text-sm text-gray-500">Рекомендации появятся после нескольких заказов</p>
    </div>

    <!-- Taste Profile Info -->
    <div v-if="tasteProfile" class="mt-4 pt-4 border-t border-gray-200">
      <div class="flex items-center gap-2 text-sm text-gray-600">
        <Info class="w-4 h-4" />
        <p>Основано на вашем вкусовом профиле: {{ tasteProfile.join(', ') }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Sparkles, Loader, Snowflake, Plus, Info } from 'lucide-vue-next'
import { useSupermarketApi } from '@/composables/useSupermarketApi'

interface RecommendedProduct {
  id: string
  name: string
  price: number
  image?: string
  requires_cold_chain: boolean
  match_score: number
  reason: string
}

const api = useSupermarketApi()

const loading = ref(true)
const recommendations = ref<RecommendedProduct[]>([])
const tasteProfile = ref<string[]>([])

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(price)
}

const viewProduct = (product: RecommendedProduct) => {
  window.location.href = `/supermarket/product/${product.id}`
}

const addToCart = async (product: RecommendedProduct) => {
  await api.addToCart(product.id)
}

const loadRecommendations = async () => {
  loading.value = true
  try {
    const data = await api.fetchAIRecommendations()
    recommendations.value = data.products || []
    tasteProfile.value = data.taste_profile || []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadRecommendations()
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
