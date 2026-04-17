<template>
  <div class="fashion-accessories-panel">
    <div class="accessories-header">
      <h2>Онлайн Стилист - Аксессуары</h2>
      <p class="subtitle">Персональные рекомендации по аксессуарам</p>
    </div>

    <div class="accessories-tabs">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        :class="['tab-button', { active: activeTab === tab.id }]"
        @click="activeTab = tab.id"
      >
        <span class="tab-icon">{{ tab.icon }}</span>
        {{ tab.label }}
      </button>
    </div>

    <div class="accessories-content">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Загрузка рекомендаций...</p>
      </div>

      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button @click="loadRecommendations" class="retry-button">Повторить</button>
      </div>

      <div v-else class="recommendations-grid">
        <div v-for="item in recommendations" :key="item.id" class="recommendation-card">
          <div class="card-image">
            <img :src="item.image" :alt="item.name" />
            <div class="card-badge" v-if="item.trending">🔥 Тренд</div>
          </div>
          <div class="card-content">
            <h3>{{ item.name }}</h3>
            <p class="brand">{{ item.brand }}</p>
            <p class="price">{{ formatPrice(item.price) }}</p>
            <div class="card-actions">
              <button @click="addToWishlist(item)" class="action-btn wishlist">
                ❤️
              </button>
              <button @click="addToCart(item)" class="action-btn cart">
                🛒
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="styleTips.length > 0" class="style-tips">
        <h3>💡 Советы по стилю</h3>
        <ul>
          <li v-for="(tip, index) in styleTips" :key="index">{{ tip }}</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'

interface Product {
  id: number
  name: string
  brand: string
  price: number
  image: string
  trending: boolean
}

interface Props {
  userId: number
}

const props = defineProps<Props>()
const emit = defineEmits(['add-to-cart', 'add-to-wishlist'])

const activeTab = ref('scarves')
const loading = ref(false)
const error = ref<string | null>(null)
const recommendations = ref<Product[]>([])
const styleTips = ref<string[]>([])

const tabs = [
  { id: 'scarves', label: 'Шарфы', icon: '🧣' },
  { id: 'headwear', label: 'Головные уборы', icon: '🎩' },
  { id: 'care_products', label: 'Уход', icon: '🧴' },
  { id: 'umbrellas', label: 'Зонты', icon: '☂️' },
  { id: 'mens_accessories', label: 'Мужские аксессуары', icon: '👔' },
  { id: 'womens_accessories', label: 'Женские аксессуары', icon: '👛' },
]

const loadRecommendations = async () => {
  loading.value = true
  error.value = null

  try {
    const endpoint = getEndpointForTab(activeTab.value)
    const response = await fetch(`/api/v1/fashion/stylist/${endpoint}`, {
      headers: {
        'Content-Type': 'application/json',
      },
    })

    if (!response.ok) {
      throw new Error('Не удалось загрузить рекомендации')
    }

    const data = await response.json()
    recommendations.value = data.recommendations || []
    styleTips.value = data.style_tips || []
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Произошла ошибка'
  } finally {
    loading.value = false
  }
}

const getEndpointForTab = (tab: string): string => {
  const endpoints: Record<string, string> = {
    scarves: 'scarves',
    headwear: 'headwear',
    care_products: 'care-products',
    umbrellas: 'umbrellas',
    mens_accessories: 'mens-accessories',
    womens_accessories: 'womens-accessories',
  }
  return endpoints[tab] || tab
}

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(price)
}

const addToWishlist = (item: Product) => {
  emit('add-to-wishlist', item)
}

const addToCart = (item: Product) => {
  emit('add-to-cart', item)
}

// Watch tab changes
import { watch } from 'vue'
watch(activeTab, () => {
  loadRecommendations()
})

onMounted(() => {
  loadRecommendations()
})
</script>

<style scoped>
.fashion-accessories-panel {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.accessories-header {
  text-align: center;
  margin-bottom: 2rem;
}

.accessories-header h2 {
  font-size: 2rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.5rem;
}

.subtitle {
  color: #666;
  font-size: 1.1rem;
}

.accessories-tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  justify-content: center;
}

.tab-button {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: 2px solid #e0e0e0;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 1rem;
  font-weight: 500;
}

.tab-button:hover {
  border-color: #6366f1;
  background: #f5f5ff;
}

.tab-button.active {
  border-color: #6366f1;
  background: #6366f1;
  color: white;
}

.tab-icon {
  width: 20px;
  height: 20px;
}

.loading-state,
.error-state {
  text-align: center;
  padding: 3rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.retry-button {
  padding: 0.75rem 1.5rem;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  margin-top: 1rem;
}

.recommendations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.recommendation-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s, box-shadow 0.3s;
}

.recommendation-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.card-image {
  position: relative;
  height: 250px;
  overflow: hidden;
}

.card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.card-badge {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #ef4444;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
}

.card-content {
  padding: 1rem;
}

.card-content h3 {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: #1a1a1a;
}

.brand {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

.price {
  font-size: 1.2rem;
  font-weight: 700;
  color: #6366f1;
  margin-bottom: 1rem;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  flex: 1;
  padding: 0.5rem;
  border: 2px solid #e0e0e0;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s;
}

.action-btn:hover {
  border-color: #6366f1;
  background: #f5f5ff;
}

.style-tips {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1.5rem;
  border-radius: 12px;
}

.style-tips h3 {
  font-size: 1.3rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.style-tips ul {
  list-style: none;
  padding: 0;
}

.style-tips li {
  padding: 0.5rem 0;
  padding-left: 1.5rem;
  position: relative;
}

.style-tips li::before {
  content: '✓';
  position: absolute;
  left: 0;
  font-weight: bold;
}
</style>
