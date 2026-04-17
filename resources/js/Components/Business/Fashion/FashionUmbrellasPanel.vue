<template>
  <div class="fashion-umbrellas-panel">
    <div class="panel-header">
      <h2>Зонты и дождевики</h2>
      <p class="subtitle">Стильная защита от непогоды</p>
    </div>

    <div class="umbrella-types">
      <div
        v-for="type in umbrellaTypes"
        :key="type.id"
        :class="['type-card', { active: selectedType === type.id }]"
        @click="selectType(type.id)"
      >
        <div class="type-icon">{{ type.icon }}</div>
        <h3>{{ type.name }}</h3>
        <p>{{ type.description }}</p>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <div v-else class="products-section">
      <div class="products-grid">
        <div v-for="product in filteredProducts" :key="product.id" class="product-card">
          <div class="product-image">
            <img :src="product.image" :alt="product.name" />
            <div class="badges">
              <span v-if="product.windproof" class="badge windproof">💨 Ветрозащита</span>
              <span v-if="product.compact" class="badge compact">📏 Компактный</span>
            </div>
          </div>
          <div class="product-info">
            <h3>{{ product.name }}</h3>
            <p class="brand">{{ product.brand }}</p>
            <div class="product-specs">
              <div class="spec-item">
                <span class="spec-label">Диаметр:</span>
                <span class="spec-value">{{ product.diameter }} см</span>
              </div>
              <div class="spec-item">
                <span class="spec-label">Вес:</span>
                <span class="spec-value">{{ product.weight }} г</span>
              </div>
              <div class="spec-item">
                <span class="spec-label">Материал:</span>
                <span class="spec-value">{{ product.material }}</span>
              </div>
            </div>
            <div class="product-price">
              <span class="current-price">{{ formatPrice(product.price) }}</span>
            </div>
            <button @click="addToCart(product)" class="add-to-cart-btn">
              🛒 В корзину
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="umbrella-guide">
      <h3>📖 Руководство по выбору зонта</h3>
      <div class="guide-grid">
        <div class="guide-card">
          <div class="guide-icon">☂️</div>
          <h4>Классический</h4>
          <p>Полный диаметр 100-120 см. Идеален для прогулок, надежная защита.</p>
        </div>
        <div class="guide-card">
          <div class="guide-icon">📏</div>
          <h4>Складной</h4>
          <p>Компактный размер в сложенном виде. Удобен для сумки и кармана.</p>
        </div>
        <div class="guide-card">
          <div class="guide-icon">💨</div>
          <h4>Ветростойкий</h4>
          <p>Усиленный каркас и спицы. Выдерживает сильный ветер.</p>
        </div>
        <div class="guide-card">
          <div class="guide-icon">👔</div>
          <h4>Деловой</h4>
          <p>Элегантный дизайн, темные цвета. Подходит для офиса.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Product {
  id: number
  name: string
  brand: string
  price: number
  image: string
  windproof: boolean
  compact: boolean
  diameter: number
  weight: number
  material: string
  typeId: string
}

const props = defineProps<{
  userId: number
}>()

const emit = defineEmits(['add-to-cart'])

const loading = ref(false)
const products = ref<Product[]>([])
const selectedType = ref('all')

const umbrellaTypes = [
  { id: 'all', name: 'Все', icon: '☂️', description: 'Вся коллекция' },
  { id: 'classic', name: 'Классические', icon: '🎩', description: 'Полный размер' },
  { id: 'compact', name: 'Складные', icon: '📏', description: 'Компактные' },
  { id: 'windproof', name: 'Ветростойкие', icon: '💨', description: 'Для сильного ветра' },
  { id: 'transparent', name: 'Прозрачные', icon: '✨', description: 'Трендовые' },
]

const filteredProducts = computed(() => {
  if (selectedType.value === 'all') {
    return products.value
  }
  return products.value.filter(p => p.typeId === selectedType.value)
})

const selectType = (typeId: string) => {
  selectedType.value = typeId
}

const loadProducts = async () => {
  loading.value = true
  try {
    const response = await fetch(`/api/v1/fashion/stylist/umbrellas?user_id=${props.userId}`)
    const data = await response.json()
    products.value = data.recommendations || []
  } catch (error) {
    console.error('Failed to load umbrellas:', error)
  } finally {
    loading.value = false
  }
}

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(price)
}

const addToCart = (product: Product) => {
  emit('add-to-cart', product)
}

onMounted(() => {
  loadProducts()
})
</script>

<style scoped>
.fashion-umbrellas-panel {
  padding: 2rem;
}

.panel-header {
  text-align: center;
  margin-bottom: 2rem;
}

.panel-header h2 {
  font-size: 2rem;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 0.5rem;
}

.subtitle {
  color: #666;
  font-size: 1.1rem;
}

.umbrella-types {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.type-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  text-align: center;
  cursor: pointer;
  border: 2px solid #e0e0e0;
  transition: all 0.3s;
}

.type-card:hover {
  border-color: #6366f1;
  transform: translateY(-2px);
}

.type-card.active {
  border-color: #6366f1;
  background: #f5f5ff;
}

.type-icon {
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
}

.type-card h3 {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: #1a1a1a;
}

.type-card p {
  font-size: 0.85rem;
  color: #666;
}

.loading-state {
  display: flex;
  justify-content: center;
  padding: 3rem;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.products-section {
  margin-bottom: 3rem;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
}

.product-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s, box-shadow 0.3s;
}

.product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.product-image {
  position: relative;
  height: 250px;
  overflow: hidden;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.badges {
  position: absolute;
  top: 10px;
  left: 10px;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
}

.badge.windproof {
  background: #3b82f6;
  color: white;
}

.badge.compact {
  background: #10b981;
  color: white;
}

.product-info {
  padding: 1rem;
}

.product-info h3 {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: #1a1a1a;
}

.brand {
  color: #666;
  font-size: 0.9rem;
  margin-bottom: 0.75rem;
}

.product-specs {
  margin-bottom: 0.75rem;
}

.spec-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  margin-bottom: 0.25rem;
}

.spec-label {
  color: #666;
}

.spec-value {
  color: #1a1a1a;
  font-weight: 500;
}

.product-price {
  margin-bottom: 1rem;
}

.current-price {
  font-size: 1.2rem;
  font-weight: 700;
  color: #6366f1;
}

.add-to-cart-btn {
  width: 100%;
  padding: 0.75rem;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.3s;
}

.add-to-cart-btn:hover {
  background: #5558e3;
}

.umbrella-guide {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  padding: 2rem;
  border-radius: 12px;
}

.umbrella-guide h3 {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  text-align: center;
}

.guide-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.guide-card {
  background: rgba(255, 255, 255, 0.1);
  padding: 1.5rem;
  border-radius: 8px;
  backdrop-filter: blur(10px);
}

.guide-icon {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
}

.guide-card h4 {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.guide-card p {
  font-size: 0.95rem;
  line-height: 1.5;
  opacity: 0.9;
  margin: 0;
}
</style>
