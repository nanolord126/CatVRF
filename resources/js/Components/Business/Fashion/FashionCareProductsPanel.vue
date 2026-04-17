<template>
  <div class="fashion-care-products-panel">
    <div class="panel-header">
      <h2>Средства по уходу</h2>
      <p class="subtitle">Профессиональный уход за одеждой и обувью</p>
    </div>

    <div class="care-categories">
      <div
        v-for="category in careCategories"
        :key="category.id"
        :class="['care-category-card', { active: selectedCategory === category.id }]"
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
      <div class="products-grid">
        <div v-for="product in filteredProducts" :key="product.id" class="product-card">
          <div class="product-image">
            <img :src="product.image" :alt="product.name" />
            <div class="product-type-badge">{{ product.type }}</div>
          </div>
          <div class="product-info">
            <h3>{{ product.name }}</h3>
            <p class="brand">{{ product.brand }}</p>
            <div class="product-details">
              <div class="detail-item">
                <span class="detail-label">Объем:</span>
                <span class="detail-value">{{ product.volume }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Для:</span>
                <span class="detail-value">{{ product.forMaterial }}</span>
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

    <div class="care-tips-section">
      <h3>💡 Советы по уходу</h3>
      <div class="tips-accordion">
        <div
          v-for="(tip, index) in careTips"
          :key="index"
          :class="['tip-item', { active: expandedTip === index }]"
        >
          <div class="tip-header" @click="toggleTip(index)">
            <span class="tip-icon">{{ tip.icon }}</span>
            <h4>{{ tip.title }}</h4>
            <span class="expand-icon">{{ expandedTip === index ? '−' : '+' }}</span>
          </div>
          <div v-if="expandedTip === index" class="tip-content">
            <p>{{ tip.content }}</p>
          </div>
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
  type: string
  volume: string
  forMaterial: string
  categoryId: string
}

const props = defineProps<{
  userId: number
}>()

const emit = defineEmits(['add-to-cart'])

const loading = ref(false)
const products = ref<Product[]>([])
const selectedCategory = ref('all')
const expandedTip = ref<number | null>(null)

const careCategories = [
  { id: 'all', name: 'Все', icon: '🧴', description: 'Вся продукция' },
  { id: 'fabric_care', name: 'Уход за тканями', icon: '👕', description: 'Средства для стирки' },
  { id: 'leather_care', name: 'Уход за кожей', icon: '👜', description: 'Кремы и пропитки' },
  { id: 'shoe_care', name: 'Уход за обувью', icon: '👞', description: 'Кремы и щетки' },
  { id: 'detergents', name: 'Средства для стирки', icon: '🧼', description: 'Порошки и гели' },
  { id: 'stain_removers', name: 'Пятновыводители', icon: '✨', description: 'Удаление пятен' },
]

const filteredProducts = computed(() => {
  if (selectedCategory.value === 'all') {
    return products.value
  }
  return products.value.filter(p => p.categoryId === selectedCategory.value)
})

const careTips = [
  {
    icon: '👕',
    title: 'Уход за хлопком',
    content: 'Стирайте хлопок при температуре 30-40°C. Используйте мягкие средства для стирки. Гладьте слегка влажным.',
  },
  {
    icon: '🧥',
    title: 'Уход за шерстью',
    content: 'Стирайте только в холодной воде или вручную. Используйте специальные средства для шерсти. Не выкручивайте - сушите на горизонтальной поверхности.',
  },
  {
    icon: '👜',
    title: 'Уход за кожей',
    content: 'Регулярно очищайте кожу мягкой щеткой. Используйте специальные кремы для питания. Храните в сухом месте, защищенном от прямых солнечных лучей.',
  },
  {
    icon: '👞',
    title: 'Уход за обувью',
    content: 'Очищайте обувь после каждого использования. Используйте водоотталкивающие спреи. Наполняйте обувь бумагой при сушке для сохранения формы.',
  },
]

const selectCategory = (categoryId: string) => {
  selectedCategory.value = categoryId
}

const toggleTip = (index: number) => {
  expandedTip.value = expandedTip.value === index ? null : index
}

const loadProducts = async () => {
  loading.value = true
  try {
    const response = await fetch(`/api/v1/fashion/stylist/care-products?user_id=${props.userId}`)
    const data = await response.json()
    products.value = data.recommendations || []
  } catch (error) {
    console.error('Failed to load care products:', error)
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
.fashion-care-products-panel {
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

.care-categories {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.care-category-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  text-align: center;
  cursor: pointer;
  border: 2px solid #e0e0e0;
  transition: all 0.3s;
}

.care-category-card:hover {
  border-color: #6366f1;
  transform: translateY(-2px);
}

.care-category-card.active {
  border-color: #6366f1;
  background: #f5f5ff;
}

.category-icon {
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
}

.care-category-card h3 {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.25rem;
  color: #1a1a1a;
}

.care-category-card p {
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
  height: 220px;
  overflow: hidden;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-type-badge {
  position: absolute;
  top: 10px;
  left: 10px;
  background: rgba(99, 102, 241, 0.9);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
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

.product-details {
  margin-bottom: 0.75rem;
}

.detail-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  margin-bottom: 0.25rem;
}

.detail-label {
  color: #666;
}

.detail-value {
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

.care-tips-section {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  padding: 2rem;
  border-radius: 12px;
}

.care-tips-section h3 {
  font-size: 1.5rem;
  font-weight: 600;
  margin-bottom: 1.5rem;
  text-align: center;
}

.tips-accordion {
  max-width: 800px;
  margin: 0 auto;
}

.tip-item {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  margin-bottom: 0.75rem;
  overflow: hidden;
  backdrop-filter: blur(10px);
}

.tip-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  cursor: pointer;
  transition: background 0.3s;
}

.tip-header:hover {
  background: rgba(255, 255, 255, 0.1);
}

.tip-icon {
  font-size: 1.5rem;
}

.tip-header h4 {
  flex: 1;
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0;
}

.expand-icon {
  font-size: 1.5rem;
  font-weight: bold;
}

.tip-content {
  padding: 0 1rem 1rem 3.5rem;
}

.tip-content p {
  margin: 0;
  line-height: 1.6;
  opacity: 0.95;
}
</style>
