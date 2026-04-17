<template>
  <div class="fashion-scarves-panel">
    <div class="panel-header">
      <h2>Шарфы и Шали</h2>
      <p class="subtitle">Элегантные аксессуары для любого сезона</p>
    </div>

    <div class="filter-section">
      <div class="filter-group">
        <label>Материал:</label>
        <select v-model="selectedMaterial" @change="filterProducts">
          <option value="">Все</option>
          <option value="silk">Шелк</option>
          <option value="wool">Шерсть</option>
          <option value="cashmere">Кашемир</option>
          <option value="cotton">Хлопок</option>
          <option value="synthetic">Синтетика</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Сезон:</label>
        <select v-model="selectedSeason" @change="filterProducts">
          <option value="">Все</option>
          <option value="winter">Зима</option>
          <option value="summer">Лето</option>
          <option value="all-season">Всесезонные</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Стиль:</label>
        <select v-model="selectedStyle" @change="filterProducts">
          <option value="">Все</option>
          <option value="classic">Классика</option>
          <option value="modern">Современный</option>
          <option value="vintage">Винтаж</option>
          <option value="ethnic">Этника</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
    </div>

    <div v-else class="products-grid">
      <div v-for="product in filteredProducts" :key="product.id" class="product-card">
        <div class="product-image">
          <img :src="product.image" :alt="product.name" />
          <div class="product-overlay">
            <button @click="quickView(product)" class="overlay-btn">
              <EyeIcon />
            </button>
            <button @click="addToWishlist(product)" class="overlay-btn">
              <HeartIcon />
            </button>
          </div>
        </div>
        <div class="product-info">
          <h3>{{ product.name }}</h3>
          <p class="brand">{{ product.brand }}</p>
          <div class="product-meta">
            <span class="material">{{ product.material }}</span>
            <span class="season">{{ product.season }}</span>
          </div>
          <div class="product-price">
            <span class="current-price">{{ formatPrice(product.price) }}</span>
            <span v-if="product.oldPrice" class="old-price">{{ formatPrice(product.oldPrice) }}</span>
          </div>
          <button @click="addToCart(product)" class="add-to-cart-btn">
            <ShoppingCartIcon />
            В корзину
          </button>
        </div>
      </div>
    </div>

    <div class="style-tips-section">
      <h3>💡 Советы по выбору шарфов</h3>
      <div class="tips-grid">
        <div class="tip-card">
          <div class="tip-icon">🎨</div>
          <h4>Цветовая гамма</h4>
          <p>Нейтральные цвета подходят к любой одежде. Яркие оттенки создают акцент.</p>
        </div>
        <div class="tip-card">
          <div class="tip-icon">📏</div>
          <h4>Размер и длина</h4>
          <p>Длинные шарфы универсальны, короткие подходят для аккуратных узлов.</p>
        </div>
        <div class="tip-card">
          <div class="tip-icon">🔗</div>
          <h4>Узлы и завязки</h4>
          <p>Экспериментируйте с разными способами завязывания для новых образов.</p>
        </div>
        <div class="tip-card">
          <div class="tip-icon">🧵</div>
          <h4>Качество материала</h4>
          <p>Натуральные ткани лучше дышат и дольше служат.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Eye as EyeIcon, Heart as HeartIcon, ShoppingCart as ShoppingCartIcon } from 'lucide-vue-next'

interface Product {
  id: number
  name: string
  brand: string
  price: number
  oldPrice?: number
  image: string
  material: string
  season: string
  style: string
}

const props = defineProps<{
  userId: number
}>()

const emit = defineEmits(['add-to-cart', 'add-to-wishlist', 'quick-view'])

const loading = ref(false)
const products = ref<Product[]>([])
const selectedMaterial = ref('')
const selectedSeason = ref('')
const selectedStyle = ref('')

const filteredProducts = computed(() => {
  return products.value.filter(product => {
    if (selectedMaterial.value && product.material !== selectedMaterial.value) return false
    if (selectedSeason.value && product.season !== selectedSeason.value) return false
    if (selectedStyle.value && product.style !== selectedStyle.value) return false
    return true
  })
})

const loadProducts = async () => {
  loading.value = true
  try {
    const response = await fetch(`/api/v1/fashion/stylist/scarves?user_id=${props.userId}`)
    const data = await response.json()
    products.value = data.recommendations || []
  } catch (error) {
    console.error('Failed to load scarves:', error)
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

const addToWishlist = (product: Product) => {
  emit('add-to-wishlist', product)
}

const quickView = (product: Product) => {
  emit('quick-view', product)
}

const filterProducts = () => {
  // Filter logic is handled by computed property
}

onMounted(() => {
  loadProducts()
})
</script>

<style scoped>
.fashion-scarves-panel {
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

.filter-section {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  justify-content: center;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 500;
  color: #333;
}

.filter-group select {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
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

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
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
  height: 280px;
  overflow: hidden;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  opacity: 0;
  transition: opacity 0.3s;
}

.product-card:hover .product-overlay {
  opacity: 1;
}

.overlay-btn {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.3s, background 0.3s;
}

.overlay-btn:hover {
  transform: scale(1.1);
  background: #6366f1;
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

.product-meta {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.material,
.season {
  font-size: 0.8rem;
  padding: 0.25rem 0.5rem;
  background: #f5f5f5;
  border-radius: 4px;
  color: #666;
}

.product-price {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.current-price {
  font-size: 1.2rem;
  font-weight: 700;
  color: #6366f1;
}

.old-price {
  font-size: 1rem;
  color: #999;
  text-decoration: line-through;
}

.add-to-cart-btn {
  width: 100%;
  padding: 0.75rem;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-weight: 500;
  transition: background 0.3s;
}

.add-to-cart-btn:hover {
  background: #5558e3;
}

.style-tips-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  border-radius: 12px;
}

.style-tips-section h3 {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  text-align: center;
}

.tips-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.tip-card {
  background: rgba(255, 255, 255, 0.1);
  padding: 1.5rem;
  border-radius: 8px;
  backdrop-filter: blur(10px);
}

.tip-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.tip-card h4 {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.tip-card p {
  font-size: 0.95rem;
  line-height: 1.5;
  opacity: 0.9;
}
</style>
