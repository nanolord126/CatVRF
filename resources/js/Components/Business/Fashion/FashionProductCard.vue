<template>
  <div class="fashion-product-card" :class="{ 'has-discount': hasDiscount, 'out-of-stock': !isInStock }">
    <div class="product-image-container">
      <img
        :src="product.image"
        :alt="product.name"
        class="product-image"
        @error="handleImageError"
        @load="handleImageLoad"
      />
      <div v-if="product.isNew" class="badge new">NEW</div>
      <div v-if="hasDiscount" class="badge discount">-{{ discountPercent }}%</div>
      <div v-if="product.isTrending" class="badge trending">🔥</div>
      <div v-if="imageQuality" :class="['quality-badge', imageQuality.level]">
        {{ imageQuality.label }}
      </div>
      <div class="image-actions">
        <button @click="$emit('quick-view', product)" class="action-btn" title="Быстрый просмотр">
          👁️
        </button>
        <button @click="$emit('add-to-wishlist', product)" class="action-btn" title="В избранное">
          ❤️
        </button>
      </div>
    </div>

    <div class="product-info">
      <div class="product-brand">{{ product.brand }}</div>
      <h3 class="product-name">{{ product.name }}</h3>
      <div class="product-meta">
        <span v-if="product.material" class="meta-tag">{{ product.material }}</span>
        <span v-if="product.color" class="meta-tag color-tag" :style="{ backgroundColor: product.colorHex }">
          {{ product.color }}
        </span>
        <span v-if="product.size" class="meta-tag">{{ product.size }}</span>
      </div>
      <div class="product-prices">
        <span v-if="hasDiscount" class="old-price">{{ formatPrice(product.oldPrice) }}</span>
        <span class="current-price">{{ formatPrice(product.price) }}</span>
      </div>
      <div class="product-stock">
        <span v-if="isInStock" class="stock-status in-stock">
          ✓ В наличии ({{ product.stockQuantity }} шт.)
        </span>
        <span v-else class="stock-status out-of-stock">✗ Нет в наличии</span>
      </div>
      <button
        @click="addToCart"
        class="add-to-cart-btn"
        :disabled="!isInStock"
      >
        🛒 {{ isInStock ? 'В корзину' : 'Нет в наличии' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

interface FashionProduct {
  id: number
  name: string
  brand: string
  price: number
  oldPrice?: number
  image: string
  material?: string
  color?: string
  colorHex?: string
  size?: string
  stockQuantity: number
  isNew?: boolean
  isTrending?: boolean
  imageWidth?: number
  imageHeight?: number
  imageSize?: number
}

interface Props {
  product: FashionProduct
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'add-to-cart': [product: FashionProduct]
  'add-to-wishlist': [product: FashionProduct]
  'quick-view': [product: FashionProduct]
  'image-quality': [quality: ImageQuality]
}>()

interface ImageQuality {
  level: 'high' | 'medium' | 'low'
  label: string
  score: number
}

const imageQuality = ref<ImageQuality | null>(null)

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

const addToCart = () => {
  if (isInStock.value) {
    emit('add-to-cart', props.product)
  }
}

const handleImageLoad = (event: Event) => {
  const img = event.target as HTMLImageElement
  validateImageQuality(img)
}

const handleImageError = () => {
  console.warn(`Failed to load image for product ${props.product.id}`)
}

const validateImageQuality = (img: HTMLImageElement) => {
  const width = img.naturalWidth
  const height = img.naturalHeight
  const sizeKB = (img.src.length * 0.75) / 1024 // Примерная оценка размера

  let score = 0
  const criteria = []

  // Критерий 1: Минимальное разрешение
  if (width >= 800 && height >= 800) {
    score += 30
    criteria.push('Разрешение: Отличное')
  } else if (width >= 500 && height >= 500) {
    score += 20
    criteria.push('Разрешение: Хорошее')
  } else if (width >= 300 && height >= 300) {
    score += 10
    criteria.push('Разрешение: Приемлемое')
  } else {
    criteria.push('Разрешение: Низкое')
  }

  // Критерий 2: Пропорции (близко к квадрату для Fashion)
  const aspectRatio = width / height
  if (aspectRatio >= 0.8 && aspectRatio <= 1.2) {
    score += 25
    criteria.push('Пропорции: Идеальные')
  } else if (aspectRatio >= 0.6 && aspectRatio <= 1.5) {
    score += 15
    criteria.push('Пропорции: Хорошие')
  } else {
    criteria.push('Пропорции: Требуют улучшения')
  }

  // Критерий 3: Размер файла
  if (sizeKB >= 50 && sizeKB <= 500) {
    score += 25
    criteria.push('Размер: Оптимальный')
  } else if (sizeKB >= 20 && sizeKB <= 1000) {
    score += 15
    criteria.push('Размер: Приемлемый')
  } else {
    criteria.push('Размер: Требует оптимизации')
  }

  // Критерий 4: Соотношение сторон для детализации
  if (width >= 1000 && height >= 1000) {
    score += 20
    criteria.push('Детализация: Высокая')
  } else if (width >= 600 && height >= 600) {
    score += 10
    criteria.push('Детализация: Средняя')
  }

  let level: 'high' | 'medium' | 'low'
  let label: string

  if (score >= 80) {
    level = 'high'
    label = '★★★★★'
  } else if (score >= 50) {
    level = 'medium'
    label = '★★★☆☆'
  } else {
    level = 'low'
    label = '★★☆☆☆'
  }

  imageQuality.value = { level, label, score }
  emit('image-quality', imageQuality.value)
}
</script>

<style scoped>
.fashion-product-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  cursor: pointer;
}

.fashion-product-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.fashion-product-card.has-discount {
  border: 2px solid #ef4444;
}

.fashion-product-card.out-of-stock {
  opacity: 0.7;
}

.product-image-container {
  position: relative;
  aspect-ratio: 1;
  overflow: hidden;
  background: #f5f5f5;
}

.product-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.fashion-product-card:hover .product-image {
  transform: scale(1.05);
}

.badge {
  position: absolute;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge.new {
  top: 10px;
  left: 10px;
  background: #10b981;
  color: white;
}

.badge.discount {
  top: 10px;
  right: 10px;
  background: #ef4444;
  color: white;
}

.badge.trending {
  bottom: 10px;
  right: 10px;
  background: #f59e0b;
  color: white;
  font-size: 1rem;
}

.quality-badge {
  position: absolute;
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
}

.quality-badge.high {
  color: #10b981;
  border: 1px solid #10b981;
}

.quality-badge.medium {
  color: #f59e0b;
  border: 1px solid #f59e0b;
}

.quality-badge.low {
  color: #ef4444;
  border: 1px solid #ef4444;
}

.image-actions {
  position: absolute;
  bottom: 10px;
  left: 10px;
  display: flex;
  gap: 0.5rem;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.fashion-product-card:hover .image-actions {
  opacity: 1;
}

.action-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: white;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  transition: all 0.3s ease;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.action-btn:hover {
  transform: scale(1.1);
  background: #6366f1;
  color: white;
}

.product-info {
  padding: 1.25rem;
}

.product-brand {
  font-size: 0.85rem;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.product-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 0.75rem 0;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.meta-tag {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
  background: #f5f5f5;
  border-radius: 4px;
  color: #666;
  font-weight: 500;
}

.color-tag {
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.color-tag::before {
  content: '';
  width: 12px;
  height: 12px;
  border-radius: 50%;
  border: 1px solid #ddd;
}

.product-prices {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.old-price {
  font-size: 0.95rem;
  color: #999;
  text-decoration: line-through;
}

.current-price {
  font-size: 1.3rem;
  font-weight: 700;
  color: #6366f1;
}

.product-stock {
  margin-bottom: 1rem;
}

.stock-status {
  font-size: 0.85rem;
  font-weight: 500;
}

.stock-status.in-stock {
  color: #10b981;
}

.stock-status.out-of-stock {
  color: #ef4444;
}

.add-to-cart-btn {
  width: 100%;
  padding: 0.875rem;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.add-to-cart-btn:hover:not(:disabled) {
  background: #5558e3;
  transform: translateY(-1px);
}

.add-to-cart-btn:disabled {
  background: #d1d5db;
  cursor: not-allowed;
}
</style>
