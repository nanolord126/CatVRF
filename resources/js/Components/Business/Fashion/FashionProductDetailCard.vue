<template>
  <div class="fashion-product-detail-card">
    <div class="detail-header">
      <div class="brand-info">
        <span class="brand">{{ product.brand }}</span>
        <span v-if="product.collection" class="collection">{{ product.collection }}</span>
      </div>
      <div class="badges">
        <span v-if="product.isNew" class="badge new">NEW</span>
        <span v-if="product.isExclusive" class="badge exclusive">EXCLUSIVE</span>
        <span v-if="product.isLimited" class="badge limited">LIMITED</span>
      </div>
    </div>

    <div class="product-gallery">
      <div class="main-image-container">
        <img
          :src="currentImage"
          :alt="product.name"
          class="main-image"
          @error="handleImageError"
        />
        <div v-if="imageQuality" :class="['quality-indicator', imageQuality.level]">
          <span class="quality-stars">{{ imageQuality.stars }}</span>
          <span class="quality-score">{{ imageQuality.score }}/100</span>
        </div>
        <button @click="toggleZoom" class="zoom-btn" title="Увеличить">
          🔍
        </button>
      </div>
      <div v-if="product.images && product.images.length > 1" class="thumbnails">
        <button
          v-for="(image, index) in product.images"
          :key="index"
          :class="['thumbnail', { active: currentImageIndex === index }]"
          @click="currentImageIndex = index"
        >
          <img :src="image" :alt="`${product.name} - ${index + 1}`" />
        </button>
      </div>
    </div>

    <div class="product-details">
      <h1 class="product-name">{{ product.name }}</h1>
      <p v-if="product.description" class="product-description">{{ product.description }}</p>

      <div class="price-section">
        <div class="prices">
          <span v-if="hasDiscount" class="old-price">{{ formatPrice(product.oldPrice) }}</span>
          <span class="current-price">{{ formatPrice(product.price) }}</span>
        </div>
        <div v-if="hasDiscount" class="discount-info">
          <span class="discount-percent">-{{ discountPercent }}%</span>
          <span class="discount-amount">Экономия: {{ formatPrice(product.oldPrice! - product.price) }}</span>
        </div>
      </div>

      <div class="product-specs">
        <div class="spec-group">
          <h4>Материал</h4>
          <p>{{ product.material }}</p>
        </div>
        <div class="spec-group">
          <h4>Цвет</h4>
          <div class="color-options">
            <span
              v-for="color in product.colors"
              :key="color.name"
              :class="['color-option', { active: selectedColor === color.name }]"
              :style="{ backgroundColor: color.hex }"
              :title="color.name"
              @click="selectedColor = color.name"
            />
            <span class="color-name">{{ selectedColor }}</span>
          </div>
        </div>
        <div class="spec-group">
          <h4>Размер</h4>
          <div class="size-options">
            <button
              v-for="size in product.sizes"
              :key="size"
              :class="['size-option', { active: selectedSize === size, unavailable: !isSizeAvailable(size) }]"
              :disabled="!isSizeAvailable(size)"
              @click="selectedSize = size"
            >
              {{ size }}
            </button>
          </div>
        </div>
      </div>

      <div class="product-meta">
        <div class="meta-item">
          <span class="meta-label">Артикул:</span>
          <span class="meta-value">{{ product.sku }}</span>
        </div>
        <div class="meta-item">
          <span class="meta-label">Категория:</span>
          <span class="meta-value">{{ product.category }}</span>
        </div>
        <div class="meta-item">
          <span class="meta-label">Сезон:</span>
          <span class="meta-value">{{ product.season }}</span>
        </div>
        <div class="meta-item">
          <span class="meta-label">Страна:</span>
          <span class="meta-value">{{ product.country }}</span>
        </div>
      </div>

      <div class="stock-info">
        <span v-if="isInStock" class="stock-status in-stock">
          ✓ В наличии ({{ product.stockQuantity }} шт.)
        </span>
        <span v-else class="stock-status out-of-stock">✗ Нет в наличии</span>
        <span v-if="product.lowStock" class="stock-warning">⚠ Осталось мало!</span>
      </div>

      <div class="action-buttons">
        <button
          @click="addToCart"
          class="btn btn-primary"
          :disabled="!isInStock || !selectedSize"
        >
          🛒 В корзину
        </button>
        <button
          @click="$emit('add-to-wishlist', product)"
          class="btn btn-secondary"
        >
          ❤️ В избранное
        </button>
        <button
          @click="shareProduct"
          class="btn btn-tertiary"
        >
          🔗 Поделиться
        </button>
      </div>

      <div v-if="product.features && product.features.length > 0" class="features">
        <h4>Особенности:</h4>
        <ul>
          <li v-for="feature in product.features" :key="feature">{{ feature }}</li>
        </ul>
      </div>

      <div class="care-instructions" v-if="product.careInstructions">
        <h4>Уход:</h4>
        <p>{{ product.careInstructions }}</p>
      </div>
    </div>

    <div v-if="showZoom" class="zoom-modal" @click="toggleZoom">
      <img :src="currentImage" :alt="product.name" class="zoomed-image" />
      <button class="close-zoom" @click.stop="toggleZoom">✕</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

interface Color {
  name: string
  hex: string
}

interface FashionProductDetail {
  id: number
  name: string
  brand: string
  collection?: string
  description?: string
  price: number
  oldPrice?: number
  sku: string
  category: string
  season: string
  country: string
  material: string
  colors: Color[]
  sizes: string[]
  stockQuantity: number
  images: string[]
  isNew?: boolean
  isExclusive?: boolean
  isLimited?: boolean
  lowStock?: boolean
  features?: string[]
  careInstructions?: string
  sizeAvailability?: Record<string, number>
}

interface Props {
  product: FashionProductDetail
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'add-to-cart': [product: FashionProductDetail, size: string, color: string]
  'add-to-wishlist': [product: FashionProductDetail]
  'share': [product: FashionProductDetail]
  'image-quality': [quality: ImageQuality]
}>()

interface ImageQuality {
  level: 'high' | 'medium' | 'low'
  stars: string
  score: number
  criteria: string[]
}

const currentImageIndex = ref(0)
const selectedColor = ref(props.product.colors[0]?.name || '')
const selectedSize = ref('')
const showZoom = ref(false)
const imageQuality = ref<ImageQuality | null>(null)

const currentImage = computed(() => {
  return props.product.images[currentImageIndex.value] || props.product.images[0]
})

const hasDiscount = computed(() => {
  return props.product.oldPrice && props.product.oldPrice > props.product.price
})

const discountPercent = computed(() => {
  if (!hasDiscount.value) return 0
  return Math.round(((props.product.oldPrice! - props.product.price) / props.product.oldPrice!) * 100)
})

const isInStock = computed(() => {
  return props.product.stockQuantity > 0
})

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(price)
}

const isSizeAvailable = (size: string): boolean => {
  if (!props.product.sizeAvailability) return true
  return (props.product.sizeAvailability[size] || 0) > 0
}

const addToCart = () => {
  if (isInStock.value && selectedSize.value) {
    emit('add-to-cart', props.product, selectedSize.value, selectedColor.value)
  }
}

const shareProduct = () => {
  emit('share', props.product)
}

const toggleZoom = () => {
  showZoom.value = !showZoom.value
}

const handleImageError = () => {
  console.warn(`Failed to load image for product ${props.product.id}`)
}

const validateImageQuality = (img: HTMLImageElement) => {
  const width = img.naturalWidth
  const height = img.naturalHeight
  const sizeKB = (img.src.length * 0.75) / 1024

  let score = 0
  const criteria = []

  // Критерии качества для Fashion фото
  if (width >= 1200 && height >= 1200) {
    score += 35
    criteria.push('Высокое разрешение (1200px+)')
  } else if (width >= 800 && height >= 800) {
    score += 25
    criteria.push('Хорошее разрешение (800px+)')
  } else if (width >= 500 && height >= 500) {
    score += 15
    criteria.push('Среднее разрешение (500px+)')
  } else {
    criteria.push('Низкое разрешение')
  }

  const aspectRatio = width / height
  if (aspectRatio >= 0.9 && aspectRatio <= 1.1) {
    score += 25
    criteria.push('Идеальные пропорции')
  } else if (aspectRatio >= 0.7 && aspectRatio <= 1.3) {
    score += 15
    criteria.push('Хорошие пропорции')
  } else {
    criteria.push('Пропорции требуют улучшения')
  }

  if (sizeKB >= 100 && sizeKB <= 800) {
    score += 20
    criteria.push('Оптимальный размер файла')
  } else if (sizeKB >= 50 && sizeKB <= 1500) {
    score += 10
    criteria.push('Приемлемый размер файла')
  } else {
    criteria.push('Размер файла требует оптимизации')
  }

  if (width >= 1500 && height >= 1500) {
    score += 20
    criteria.push('Максимальная детализация')
  }

  let level: 'high' | 'medium' | 'low'
  let stars: string

  if (score >= 80) {
    level = 'high'
    stars = '★★★★★'
  } else if (score >= 50) {
    level = 'medium'
    stars = '★★★☆☆'
  } else {
    level = 'low'
    stars = '★★☆☆☆'
  }

  imageQuality.value = { level, stars, score, criteria }
  emit('image-quality', imageQuality.value)
}

// Validate main image on mount
import { onMounted } from 'vue'
onMounted(() => {
  const img = new Image()
  img.onload = () => validateImageQuality(img)
  img.onerror = handleImageError
  img.src = currentImage.value
})
</script>

<style scoped>
.fashion-product-detail-card {
  background: white;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.brand-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.brand {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1a1a1a;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.collection {
  font-size: 0.9rem;
  color: #666;
  font-style: italic;
}

.badges {
  display: flex;
  gap: 0.5rem;
}

.badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge.new {
  background: #10b981;
  color: white;
}

.badge.exclusive {
  background: #8b5cf6;
  color: white;
}

.badge.limited {
  background: #f59e0b;
  color: white;
}

.product-gallery {
  padding: 1.5rem;
}

.main-image-container {
  position: relative;
  aspect-ratio: 1;
  overflow: hidden;
  background: #f9fafb;
  border-radius: 12px;
  margin-bottom: 1rem;
}

.main-image {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.quality-indicator {
  position: absolute;
  top: 1rem;
  left: 1rem;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.quality-indicator.high {
  border: 1px solid #10b981;
  color: #10b981;
}

.quality-indicator.medium {
  border: 1px solid #f59e0b;
  color: #f59e0b;
}

.quality-indicator.low {
  border: 1px solid #ef4444;
  color: #ef4444;
}

.quality-stars {
  font-size: 1rem;
  font-weight: 700;
  display: block;
}

.quality-score {
  font-size: 0.75rem;
  display: block;
  margin-top: 0.25rem;
}

.zoom-btn {
  position: absolute;
  bottom: 1rem;
  right: 1rem;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: white;
  border: none;
  cursor: pointer;
  font-size: 1.25rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
}

.zoom-btn:hover {
  transform: scale(1.1);
  background: #6366f1;
  color: white;
}

.thumbnails {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
  padding-bottom: 0.5rem;
}

.thumbnail {
  width: 80px;
  height: 80px;
  border-radius: 8px;
  overflow: hidden;
  border: 2px solid transparent;
  cursor: pointer;
  flex-shrink: 0;
  transition: all 0.3s ease;
}

.thumbnail:hover {
  border-color: #6366f1;
}

.thumbnail.active {
  border-color: #6366f1;
}

.thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-details {
  padding: 1.5rem;
}

.product-name {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0 0 1rem 0;
  line-height: 1.3;
}

.product-description {
  color: #666;
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.price-section {
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 12px;
  margin-bottom: 1.5rem;
}

.prices {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.old-price {
  font-size: 1.25rem;
  color: #999;
  text-decoration: line-through;
}

.current-price {
  font-size: 2rem;
  font-weight: 700;
  color: #6366f1;
}

.discount-info {
  display: flex;
  gap: 1rem;
}

.discount-percent {
  color: #ef4444;
  font-weight: 600;
}

.discount-amount {
  color: #10b981;
  font-weight: 500;
}

.product-specs {
  margin-bottom: 1.5rem;
}

.spec-group {
  margin-bottom: 1.5rem;
}

.spec-group h4 {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.color-options {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.color-option {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2px solid #e5e7eb;
  cursor: pointer;
  transition: all 0.3s ease;
}

.color-option:hover {
  transform: scale(1.1);
}

.color-option.active {
  border-color: #6366f1;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

.color-name {
  font-size: 0.9rem;
  color: #666;
}

.size-options {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.size-option {
  padding: 0.5rem 1rem;
  border: 2px solid #e5e7eb;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
}

.size-option:hover:not(:disabled) {
  border-color: #6366f1;
}

.size-option.active {
  background: #6366f1;
  color: white;
  border-color: #6366f1;
}

.size-option.unavailable {
  opacity: 0.5;
  cursor: not-allowed;
  text-decoration: line-through;
}

.product-meta {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.meta-item {
  display: flex;
  flex-direction: column;
}

.meta-label {
  font-size: 0.75rem;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 0.25rem;
}

.meta-value {
  font-weight: 500;
  color: #1a1a1a;
}

.stock-info {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stock-status {
  font-weight: 500;
}

.stock-status.in-stock {
  color: #10b981;
}

.stock-status.out-of-stock {
  color: #ef4444;
}

.stock-warning {
  color: #f59e0b;
  font-weight: 500;
}

.action-buttons {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.btn {
  flex: 1;
  padding: 1rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  border: none;
}

.btn-primary {
  background: #6366f1;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #5558e3;
  transform: translateY(-2px);
}

.btn-primary:disabled {
  background: #d1d5db;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  border: 2px solid #e5e7eb;
  color: #1a1a1a;
}

.btn-secondary:hover {
  border-color: #6366f1;
  color: #6366f1;
}

.btn-tertiary {
  background: #f3f4f6;
  color: #1a1a1a;
}

.btn-tertiary:hover {
  background: #e5e7eb;
}

.features,
.care-instructions {
  margin-bottom: 1.5rem;
}

.features h4,
.care-instructions h4 {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.features ul {
  list-style: none;
  padding: 0;
}

.features li {
  padding: 0.5rem 0;
  padding-left: 1.5rem;
  position: relative;
}

.features li::before {
  content: '✓';
  position: absolute;
  left: 0;
  color: #10b981;
  font-weight: bold;
}

.care-instructions p {
  color: #666;
  line-height: 1.6;
}

.zoom-modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 2rem;
}

.zoomed-image {
  max-width: 90%;
  max-height: 90%;
  object-fit: contain;
}

.close-zoom {
  position: absolute;
  top: 2rem;
  right: 2rem;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: white;
  border: none;
  cursor: pointer;
  font-size: 1.5rem;
}
</style>
