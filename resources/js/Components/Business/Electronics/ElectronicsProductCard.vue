<template>
  <div class="electronics-product-card" :class="{ 'card-hover': hoverEffect, 'card-selected': isSelected }">
    <!-- Badges -->
    <div class="badges">
      <span v-if="product.is_new" class="badge badge-new">NEW</span>
      <span v-if="product.is_bestseller" class="badge badge-bestseller">ХИТ</span>
      <span v-if="discountPercentage > 0" class="badge badge-discount">-{{ discountPercentage }}%</span>
      <span v-if="product.availability_status === 'low_stock'" class="badge badge-low-stock">Мало</span>
    </div>

    <!-- Image Gallery -->
    <div class="image-container" @mouseenter="showThumbnails = true" @mouseleave="showThumbnails = false">
      <div class="main-image">
        <img :src="currentImage" :alt="product.name" @error="handleImageError" />
        <button v-if="images.length > 1" @click.stop="prevImage" class="image-nav image-prev">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <button v-if="images.length > 1" @click.stop="nextImage" class="image-nav image-next">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
      
      <!-- Thumbnails -->
      <div v-if="showThumbnails && images.length > 1" class="thumbnails">
        <div
          v-for="(img, idx) in images.slice(0, 5)"
          :key="idx"
          class="thumbnail"
          :class="{ active: currentImageIndex === idx }"
          @click.stop="currentImageIndex = idx"
        >
          <img :src="img" :alt="`${product.name} ${idx + 1}`" />
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <button @click.stop="toggleWishlist" :class="{ active: isInWishlist }" class="action-btn" title="В избранное">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" />
          </svg>
        </button>
        <button @click.stop="toggleCompare" :class="{ active: isInCompare }" class="action-btn" title="Сравнить">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 19c-5 1.5-5-2.5-7-3m14 6v-3.87a3.37 3.37 0 0 0-.94-2.61c3.14-.35 6.44-1.54 6.44-7A5.44 5.44 0 0 0 20 4.77 5.07 5.07 0 0 0 19.91 1S18.73.65 16 2.48a13.38 13.38 0 0 0-7 0C6.27.65 5.09 1 5.09 1A5.07 5.07 0 0 0 5 4.77a5.44 5.44 0 0 0 1.5 3.78c0 5.42 3.3 6.61 6.44 7A3.37 3.37 0 0 0 9 18.13V22" />
          </svg>
        </button>
        <button @click.stop="showQuickView" class="action-btn" title="Быстрый просмотр">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Product Info -->
    <div class="product-info">
      <!-- Brand -->
      <div class="brand">{{ product.brand }}</div>
      
      <!-- Name -->
      <h3 class="product-name" @click="goToProduct">{{ product.name }}</h3>
      
      <!-- Specs Preview -->
      <div v-if="keySpecs.length > 0" class="specs-preview">
        <span v-for="(spec, idx) in keySpecs.slice(0, 3)" :key="idx" class="spec-item">
          {{ spec }}
        </span>
        <span v-if="keySpecs.length > 3" class="spec-more">+{{ keySpecs.length - 3 }}</span>
      </div>

      <!-- Rating -->
      <div class="rating-container">
        <div class="stars">
          <svg v-for="star in 5" :key="star" width="16" height="16" viewBox="0 0 24 24" :class="getStarClass(star)">
            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
          </svg>
        </div>
        <span class="rating-value">{{ product.rating }}</span>
        <span class="reviews-count">({{ product.reviews_count }})</span>
      </div>

      <!-- Price -->
      <div class="price-container">
        <span v-if="product.original_price_kopecks" class="original-price">
          {{ formatCurrency(product.original_price_kopecks / 100) }}
        </span>
        <span class="current-price">{{ formatCurrency(product.price_kopecks / 100) }}</span>
        <span v-if="pricePerMonth > 0" class="price-per-month">
          от {{ formatCurrency(pricePerMonth) }}/мес
        </span>
      </div>

      <!-- Availability -->
      <div class="availability" :class="`availability-${product.availability_status}`">
        <span class="availability-dot"></span>
        <span class="availability-text">{{ getAvailabilityText() }}</span>
      </div>

      <!-- Delivery Info -->
      <div v-if="deliveryInfo" class="delivery-info">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <rect x="1" y="3" width="15" height="13" />
          <polygon points="16 8 20 8 23 11 23 16 16 16 16 8" />
          <circle cx="5.5" cy="18.5" r="2.5" />
          <circle cx="18.5" cy="18.5" r="2.5" />
        </svg>
        <span>{{ deliveryInfo }}</span>
      </div>
    </div>

    <!-- Actions -->
    <div class="card-actions">
      <button @click="addToCart" class="btn-add-cart" :disabled="!canAddToCart">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="9" cy="21" r="1" />
          <circle cx="20" cy="21" r="1" />
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
        </svg>
        <span>В корзину</span>
      </button>
      <button @click="buyNow" class="btn-buy-now" :disabled="!canBuyNow">
        Купить сейчас
      </button>
    </div>

    <!-- AI Recommendation Badge -->
    <div v-if="aiRecommendation" class="ai-badge" :title="aiRecommendation.reason">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2z" />
        <path d="M12 16v-4" />
        <path d="M12 8h.01" />
      </svg>
      <span>AI рекомендует</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Product {
  id: number
  name: string
  brand: string
  category: string
  type?: string
  price_kopecks: number
  original_price_kopecks?: number
  images: string[]
  specs: Record<string, any>
  rating: number
  reviews_count: number
  stock_quantity: number
  availability_status: string
  is_bestseller?: boolean
  is_new?: boolean
  views_count?: number
}

interface Props {
  product: Product
  hoverEffect?: boolean
  isSelected?: boolean
  aiRecommendation?: {
    score: number
    reason: string
  }
}

const props = withDefaults(defineProps<Props>(), {
  hoverEffect: true,
  isSelected: false,
})

const emit = defineEmits<{
  addToCart: [product: Product]
  buyNow: [product: Product]
  toggleWishlist: [product: Product]
  toggleCompare: [product: Product]
  quickView: [product: Product]
}>()

const currentImageIndex = ref(0)
const showThumbnails = ref(false)
const isInWishlist = ref(false)
const isInCompare = ref(false)

const images = computed(() => props.product.images || [])
const currentImage = computed(() => images.value[currentImageIndex.value] || '/placeholder-product.png')

const discountPercentage = computed(() => {
  if (!props.product.original_price_kopecks) return 0
  return Math.round(
    ((props.product.original_price_kopecks - props.product.price_kopecks) / props.product.original_price_kopecks) * 100
  )
})

const pricePerMonth = computed(() => {
  const price = props.product.price_kopecks / 100
  return price > 10000 ? Math.round(price / 12) : 0
})

const keySpecs = computed(() => {
  const specs = props.product.specs || {}
  const keySpecMap: Record<string, string[]> = {
    smartphones: ['screen_size', 'ram', 'storage', 'cpu'],
    laptops: ['cpu', 'ram', 'storage', 'screen_size'],
    tablets: ['screen_size', 'ram', 'storage'],
    headphones: ['type', 'noise_cancellation', 'battery_life'],
    tv: ['screen_size', 'resolution', 'panel_type'],
    cameras: ['sensor_size', 'megapixels', 'video_resolution'],
    smartwatches: ['screen_size', 'battery_life', 'water_resistance'],
  }

  const typeKeys = keySpecMap[props.product.type as string] || Object.keys(specs).slice(0, 3)
  return typeKeys.map(key => specs[key]).filter(Boolean)
})

const deliveryInfo = computed(() => {
  const status = props.product.availability_status
  if (status === 'in_stock') return 'Доставка завтра'
  if (status === 'low_stock') return 'Доставка 2-3 дня'
  if (status === 'pre_order') return 'Доставка через 5-7 дней'
  return null
})

const canAddToCart = computed(() => {
  return props.product.availability_status === 'in_stock' || props.product.availability_status === 'low_stock'
})

const canBuyNow = computed(() => {
  return props.product.availability_status === 'in_stock'
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    maximumFractionDigits: 0,
  }).format(amount)
}

const getStarClass = (star: number): string => {
  const rating = props.product.rating
  const fullStars = Math.floor(rating)
  const hasHalfStar = rating - fullStars >= 0.5
  
  if (star <= fullStars) return 'star-full'
  if (star === fullStars + 1 && hasHalfStar) return 'star-half'
  return 'star-empty'
}

const getAvailabilityText = (): string => {
  const status = props.product.availability_status
  const map: Record<string, string> = {
    in_stock: 'В наличии',
    low_stock: 'Мало',
    out_of_stock: 'Нет в наличии',
    pre_order: 'Предзаказ',
    discontinued: 'Снят с производства',
  }
  return map[status] || status
}

const handleImageError = (e: Event) => {
  const img = e.target as HTMLImageElement
  img.src = '/placeholder-product.png'
}

const prevImage = () => {
  currentImageIndex.value = currentImageIndex.value > 0 ? currentImageIndex.value - 1 : images.value.length - 1
}

const nextImage = () => {
  currentImageIndex.value = currentImageIndex.value < images.value.length - 1 ? currentImageIndex.value + 1 : 0
}

const toggleWishlist = () => {
  isInWishlist.value = !isInWishlist.value
  emit('toggleWishlist', props.product)
}

const toggleCompare = () => {
  isInCompare.value = !isInCompare.value
  emit('toggleCompare', props.product)
}

const showQuickView = () => {
  emit('quickView', props.product)
}

const addToCart = () => {
  emit('addToCart', props.product)
}

const buyNow = () => {
  emit('buyNow', props.product)
}

const goToProduct = () => {
  // Navigate to product detail page
  window.location.href = `/electronics/${props.product.id}`
}
</script>

<style scoped>
.electronics-product-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  position: relative;
  border: 2px solid transparent;
}

.electronics-product-card.card-hover:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.electronics-product-card.card-selected {
  border-color: #3b82f6;
}

.badges {
  position: absolute;
  top: 12px;
  left: 12px;
  z-index: 10;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.badge-new {
  background: #10b981;
  color: white;
}

.badge-bestseller {
  background: #f59e0b;
  color: white;
}

.badge-discount {
  background: #ef4444;
  color: white;
}

.badge-low-stock {
  background: #f97316;
  color: white;
}

.image-container {
  position: relative;
  aspect-ratio: 1;
  background: #f9fafb;
  overflow: hidden;
}

.main-image {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.main-image img {
  max-width: 90%;
  max-height: 90%;
  object-fit: contain;
  transition: transform 0.3s ease;
}

.image-container:hover .main-image img {
  transform: scale(1.05);
}

.image-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(255, 255, 255, 0.9);
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  z-index: 5;
}

.image-nav:hover {
  background: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.image-prev {
  left: 8px;
}

.image-next {
  right: 8px;
}

.thumbnails {
  position: absolute;
  bottom: 12px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 6px;
  background: rgba(255, 255, 255, 0.95);
  padding: 6px;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.thumbnail {
  width: 40px;
  height: 40px;
  border-radius: 4px;
  overflow: hidden;
  cursor: pointer;
  border: 2px solid transparent;
  transition: all 0.2s;
}

.thumbnail img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.thumbnail.active {
  border-color: #3b82f6;
}

.thumbnail:hover {
  transform: scale(1.1);
}

.quick-actions {
  position: absolute;
  top: 12px;
  right: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  z-index: 10;
}

.action-btn {
  width: 36px;
  height: 36px;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  color: #6b7280;
}

.action-btn:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.action-btn.active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.product-info {
  padding: 16px;
}

.brand {
  font-size: 12px;
  color: #6b7280;
  font-weight: 500;
  margin-bottom: 4px;
}

.product-name {
  font-size: 14px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 8px 0;
  line-height: 1.4;
  cursor: pointer;
  transition: color 0.2s;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-name:hover {
  color: #3b82f6;
}

.specs-preview {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-bottom: 8px;
}

.spec-item {
  font-size: 11px;
  color: #6b7280;
  background: #f3f4f6;
  padding: 2px 6px;
  border-radius: 4px;
}

.spec-more {
  font-size: 11px;
  color: #3b82f6;
  cursor: pointer;
}

.rating-container {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 8px;
}

.stars {
  display: flex;
  gap: 2px;
}

.star-full {
  fill: #f59e0b;
  stroke: #f59e0b;
}

.star-half {
  fill: url(#half-star);
  stroke: #f59e0b;
}

.star-empty {
  stroke: #d1d5db;
}

.rating-value {
  font-size: 13px;
  font-weight: 600;
  color: #1f2937;
}

.reviews-count {
  font-size: 12px;
  color: #6b7280;
}

.price-container {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-bottom: 8px;
}

.original-price {
  font-size: 13px;
  color: #9ca3af;
  text-decoration: line-through;
}

.current-price {
  font-size: 18px;
  font-weight: 700;
  color: #1f2937;
}

.price-per-month {
  font-size: 11px;
  color: #6b7280;
  background: #f3f4f6;
  padding: 2px 6px;
  border-radius: 4px;
}

.availability {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  margin-bottom: 8px;
}

.availability-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.availability-in_stock .availability-dot {
  background: #10b981;
}

.availability-low_stock .availability-dot {
  background: #f59e0b;
}

.availability-out_of_stock .availability-dot {
  background: #ef4444;
}

.availability-in_stock .availability-text {
  color: #10b981;
}

.availability-low_stock .availability-text {
  color: #f59e0b;
}

.availability-out_of_stock .availability-text {
  color: #ef4444;
}

.delivery-info {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: #6b7280;
  margin-bottom: 12px;
}

.card-actions {
  display: flex;
  gap: 8px;
  padding: 0 16px 16px 16px;
}

.btn-add-cart,
.btn-buy-now {
  flex: 1;
  padding: 10px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.btn-add-cart {
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  color: #1f2937;
}

.btn-add-cart:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-buy-now {
  background: #3b82f6;
  border: 1px solid #3b82f6;
  color: white;
}

.btn-buy-now:hover:not(:disabled) {
  background: #2563eb;
}

.btn-add-cart:disabled,
.btn-buy-now:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.ai-badge {
  position: absolute;
  bottom: 80px;
  right: 12px;
  background: linear-gradient(135deg, #8b5cf6, #6366f1);
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 4px;
  z-index: 10;
  box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
}
</style>
