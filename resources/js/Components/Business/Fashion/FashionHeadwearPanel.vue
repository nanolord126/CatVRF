<template>
  <div class="fashion-headwear-panel">
    <div class="panel-header">
      <h2>Головные уборы</h2>
      <p class="subtitle">От классических шляп до современных кепок</p>
    </div>

    <div class="category-grid">
      <div
        v-for="category in categories"
        :key="category.id"
        :class="['category-card', { active: selectedCategory === category.id }]"
        @click="selectCategory(category.id)"
      >
        <div class="category-icon">{{ category.icon }}</div>
        <h3>{{ category.name }}</h3>
        <p>{{ category.description }}</p>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <div v-else class="products-section">
      <div class="products-header">
        <h3>{{ getCategoryName(selectedCategory) }}</h3>
        <div class="sort-options">
          <select v-model="sortBy" @change="sortProducts">
            <option value="popular">По популярности</option>
            <option value="price-asc">Цена: по возрастанию</option>
            <option value="price-desc">Цена: по убыванию</option>
            <option value="newest">Новинки</option>
          </select>
        </div>
      </div>

      <div class="products-grid">
        <div v-for="product in sortedProducts" :key="product.id" class="product-card">
          <div class="product-image">
            <img :src="product.image" :alt="product.name" />
            <div class="badges">
              <span v-if="product.isNew" class="badge new">NEW</span>
              <span v-if="product.isBestseller" class="badge bestseller">ХИТ</span>
            </div>
          </div>
          <div class="product-info">
            <h3>{{ product.name }}</h3>
            <p class="brand">{{ product.brand }}</p>
            <div class="product-features">
              <span v-if="product.waterproof" class="feature">💧 Водостойкий</span>
              <span v-if="product.breathable" class="feature">🌬️ Дышащий</span>
              <span v-if="product.uvProtection" class="feature">☀️ UV защита</span>
            </div>
            <div class="product-price">
              <span class="current-price">{{ formatPrice(product.price) }}</span>
            </div>
            <button @click="addToCart(product)" class="add-to-cart-btn">
              В корзину
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="size-guide">
      <h3>📏 Таблица размеров</h3>
      <table>
        <thead>
          <tr>
            <th>Размер</th>
            <th>Окружность головы (см)</th>
            <th>Описание</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>XS</td>
            <td>53-54</td>
            <td>Очень маленький</td>
          </tr>
          <tr>
            <td>S</td>
            <td>55-56</td>
            <td>Маленький</td>
          </tr>
          <tr>
            <td>M</td>
            <td>57-58</td>
            <td>Средний</td>
          </tr>
          <tr>
            <td>L</td>
            <td>59-60</td>
            <td>Большой</td>
          </tr>
          <tr>
            <td>XL</td>
            <td>61-62</td>
            <td>Очень большой</td>
          </tr>
        </tbody>
      </table>
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
  isNew: boolean
  isBestseller: boolean
  waterproof: boolean
  breathable: boolean
  uvProtection: boolean
  categoryId: string
}

const props = defineProps<{
  userId: number
}>()

const emit = defineEmits(['add-to-cart'])

const loading = ref(false)
const products = ref<Product[]>([])
const selectedCategory = ref('all')
const sortBy = ref('popular')

const categories = [
  { id: 'all', name: 'Все', icon: '🎩', description: 'Вся коллекция' },
  { id: 'hats', name: 'Шляпы', icon: '👒', description: 'Классические и современные' },
  { id: 'caps', name: 'Кепки', icon: '🧢', description: 'Спортивный стиль' },
  { id: 'beanies', name: 'Шапки', icon: '🧶', description: 'Уютные и теплые' },
  { id: 'berets', name: 'Береты', icon: '🎀', description: 'Элегантный акцент' },
  { id: 'headbands', name: 'Ободки', icon: '👑', description: 'Для волос' },
]

const sortedProducts = computed(() => {
  let result = [...products.value]
  
  if (selectedCategory.value !== 'all') {
    result = result.filter(p => p.categoryId === selectedCategory.value)
  }

  switch (sortBy.value) {
    case 'price-asc':
      result.sort((a, b) => a.price - b.price)
      break
    case 'price-desc':
      result.sort((a, b) => b.price - a.price)
      break
    case 'newest':
      result.sort((a, b) => (b.isNew ? 1 : 0) - (a.isNew ? 1 : 0))
      break
    case 'popular':
    default:
      result.sort((a, b) => (b.isBestseller ? 1 : 0) - (a.isBestseller ? 1 : 0))
      break
  }

  return result
})

const selectCategory = (categoryId: string) => {
  selectedCategory.value = categoryId
}

const getCategoryName = (categoryId: string): string => {
  const category = categories.find(c => c.id === categoryId)
  return category?.name || 'Все'
}

const loadProducts = async () => {
  loading.value = true
  try {
    const response = await fetch(`/api/v1/fashion/stylist/headwear?user_id=${props.userId}`)
    const data = await response.json()
    products.value = data.recommendations || []
  } catch (error) {
    console.error('Failed to load headwear:', error)
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

const sortProducts = () => {
  // Sorting handled by computed property
}

onMounted(() => {
  loadProducts()
})
</script>

<style scoped>
.fashion-headwear-panel {
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

.category-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.category-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  text-align: center;
  cursor: pointer;
  border: 2px solid #e0e0e0;
  transition: all 0.3s;
}

.category-card:hover {
  border-color: #6366f1;
  transform: translateY(-2px);
}

.category-card.active {
  border-color: #6366f1;
  background: #f5f5ff;
}

.category-icon {
  font-size: 3rem;
  margin-bottom: 0.5rem;
}

.category-card h3 {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: #1a1a1a;
}

.category-card p {
  font-size: 0.9rem;
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

.products-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.products-header h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a1a1a;
}

.sort-options select {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
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
  gap: 0.5rem;
}

.badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge.new {
  background: #10b981;
  color: white;
}

.badge.bestseller {
  background: #f59e0b;
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
  margin-bottom: 0.5rem;
}

.product-features {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.feature {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  background: #f5f5f5;
  border-radius: 4px;
  color: #666;
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

.size-guide {
  background: #f9fafb;
  padding: 2rem;
  border-radius: 12px;
  margin-top: 3rem;
}

.size-guide h3 {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: #1a1a1a;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #e0e0e0;
}

th {
  font-weight: 600;
  color: #1a1a1a;
  background: #f5f5f5;
}

td {
  color: #666;
}
</style>
