<template>
  <div class="fashion-accessory-card" :class="[`category-${accessory.category}`, { 'has-discount': hasDiscount }]">
    <div class="accessory-image-container">
      <img
        :src="accessory.image"
        :alt="accessory.name"
        class="accessory-image"
        @error="handleImageError"
        @load="handleImageLoad"
      />
      <div class="accessory-type-badge">
        {{ getAccessoryTypeLabel(accessory.category) }}
      </div>
      <div v-if="hasDiscount" class="badge discount">-{{ discountPercent }}%</div>
      <div v-if="accessory.isBestseller" class="badge bestseller">ХИТ</div>
      <div v-if="imageQuality" :class="['quality-badge', imageQuality.level]">
        {{ imageQuality.stars }}
      </div>
    </div>

    <div class="accessory-info">
      <div class="accessory-brand">{{ accessory.brand }}</div>
      <h3 class="accessory-name">{{ accessory.name }}</h3>
      
      <div class="accessory-specs">
        <div v-if="accessory.material" class="spec-item">
          <span class="spec-icon">🧵</span>
          <span>{{ accessory.material }}</span>
        </div>
        <div v-if="accessory.color" class="spec-item">
          <span class="spec-icon">🎨</span>
          <span>{{ accessory.color }}</span>
        </div>
        <div v-if="accessory.size" class="spec-item">
          <span class="spec-icon">📏</span>
          <span>{{ accessory.size }}</span>
        </div>
      </div>

      <div class="accessory-features" v-if="accessory.features && accessory.features.length > 0">
        <span v-for="feature in accessory.features.slice(0, 3)" :key="feature" class="feature-tag">
          {{ feature }}
        </span>
      </div>

      <div class="accessory-prices">
        <span v-if="hasDiscount" class="old-price">{{ formatPrice(accessory.oldPrice) }}</span>
        <span class="current-price">{{ formatPrice(accessory.price) }}</span>
      </div>

      <div class="accessory-actions">
        <button @click="$emit('add-to-wishlist', accessory)" class="action-btn wishlist">
          ❤️
        </button>
        <button @click="addToCart" class="action-btn cart" :disabled="!isInStock">
          🛒
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

interface FashionAccessory {
  id: number
  name: string
  brand: string
  price: number
  oldPrice?: number
  image: string
  category: 'scarves' | 'headwear' | 'care_products' | 'umbrellas' | 'accessories'
  material?: string
  color?: string
  size?: string
  stockQuantity: number
  isBestseller?: boolean
  features?: string[]
  imageWidth?: number
  imageHeight?: number
}

interface Props {
  accessory: FashionAccessory
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'add-to-cart': [accessory: FashionAccessory]
  'add-to-wishlist': [accessory: FashionAccessory]
  'image-quality': [quality: ImageQuality]
}>()

interface ImageQuality {
  level: 'high' | 'medium' | 'low'
  stars: string
  score: number
}

const imageQuality = ref<ImageQuality | null>(null)

const hasDiscount = computed(() => {
  return props.accessory.oldPrice && props.accessory.oldPrice > props.accessory.price
})

const discountPercent = computed(() => {
  if (!hasDiscount.value) return 0
  return Math.round(((props.accessory.oldPrice! - props.accessory.price) / props.accessory.oldPrice!) * 100)
})

const isInStock = computed(() => {
  return props.accessory.stockQuantity > 0
})

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(price)
}

const getAccessoryTypeLabel = (category: string): string => {
  const labels: Record<string, string> = {
    scarves: '🧣 Шарфы',
    headwear: '🎩 Головные уборы',
    care_products: '🧴 Уход',
    umbrellas: '☂️ Зонты',
    accessories: '👜 Аксессуары',
  }
  return labels[category] || 'Аксессуары'
}

const addToCart = () => {
  if (isInStock.value) {
    emit('add-to-cart', props.accessory)
  }
}

const handleImageLoad = (event: Event) => {
  const img = event.target as HTMLImageElement
  validateImageQuality(img)
}

const handleImageError = () => {
  console.warn(`Failed to load image for accessory ${props.accessory.id}`)
}

const validateImageQuality = (img: HTMLImageElement) => {
  const width = img.naturalWidth
  const height = img.naturalHeight
  const sizeKB = (img.src.length * 0.75) / 1024

  let score = 0

  // Специальные критерии для аксессуаров
  if (width >= 600 && height >= 600) {
    score += 35
  } else if (width >= 400 && height >= 400) {
    score += 25
  } else if (width >= 300 && height >= 300) {
    score += 15
  }

  const aspectRatio = width / height
  if (aspectRatio >= 0.8 && aspectRatio <= 1.2) {
    score += 30
  } else if (aspectRatio >= 0.6 && aspectRatio <= 1.5) {
    score += 20
  }

  if (sizeKB >= 30 && sizeKB <= 400) {
    score += 25
  } else if (sizeKB >= 20 && sizeKB <= 800) {
    score += 15
  }

  if (width >= 800 && height >= 800) {
    score += 10
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

  imageQuality.value = { level, stars, score }
  emit('image-quality', imageQuality.value)
}
</script>

<style scoped>
.fashion-accessory-card {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
  cursor: pointer;
}

.fashion-accessory-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.fashion-accessory-card.has-discount {
  border: 2px solid #ef4444;
}

/* Цветовые схемы для разных категорий аксессуаров */
.fashion-accessory-card.category-scarves {
  border-top: 4px solid #8b5cf6;
}

.fashion-accessory-card.category-headwear {
  border-top: 4px solid #f59e0b;
}

.fashion-accessory-card.category-care_products {
  border-top: 4px solid #10b981;
}

.fashion-accessory-card.category-umbrellas {
  border-top: 4px solid #3b82f6;
}

.fashion-accessory-card.category-accessories {
  border-top: 4px solid #ec4899;
}

.accessory-image-container {
  position: relative;
  aspect-ratio: 1;
  overflow: hidden;
  background: #f9fafb;
}

.accessory-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.fashion-accessory-card:hover .accessory-image {
  transform: scale(1.05);
}

.accessory-type-badge {
  position: absolute;
  top: 10px;
  left: 10px;
  padding: 0.375rem 0.75rem;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  backdrop-filter: blur(10px);
}

.badge {
  position: absolute;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge.discount {
  top: 10px;
  right: 10px;
  background: #ef4444;
  color: white;
}

.badge.bestseller {
  bottom: 10px;
  right: 10px;
  background: #f59e0b;
  color: white;
}

.quality-badge {
  position: absolute;
  bottom: 10px;
  left: 10px;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.7rem;
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

.accessory-info {
  padding: 1.25rem;
}

.accessory-brand {
  font-size: 0.8rem;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.accessory-name {
  font-size: 1rem;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 0.75rem 0;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.accessory-specs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.spec-item {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.8rem;
  color: #666;
}

.spec-icon {
  font-size: 0.9rem;
}

.accessory-features {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.feature-tag {
  font-size: 0.7rem;
  padding: 0.25rem 0.5rem;
  background: #f3f4f6;
  border-radius: 4px;
  color: #666;
  font-weight: 500;
}

.accessory-prices {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.old-price {
  font-size: 0.85rem;
  color: #999;
  text-decoration: line-through;
}

.current-price {
  font-size: 1.2rem;
  font-weight: 700;
  color: #6366f1;
}

.accessory-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  flex: 1;
  padding: 0.625rem;
  border: 2px solid #e5e7eb;
  background: white;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1rem;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-btn:hover:not(:disabled) {
  border-color: #6366f1;
  background: #f5f5ff;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.action-btn.cart {
  background: #6366f1;
  color: white;
  border-color: #6366f1;
}

.action-btn.cart:hover:not(:disabled) {
  background: #5558e3;
}
</style>
