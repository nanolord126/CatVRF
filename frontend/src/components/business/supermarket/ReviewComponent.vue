<template>
  <div class="review-component">
    <div class="review-header">
      <h3>Отзывы о товаре</h3>
      <div class="review-stats">
        <div class="average-rating">
          <span class="rating-value">{{ averageRating }}</span>
          <Star class="star-icon" :class="{ filled: true }" />
        </div>
        <span class="total-reviews">{{ totalReviews }} отзывов</span>
      </div>
    </div>

    <div class="review-form" v-if="canWriteReview">
      <h4>Оставить отзыв</h4>
      <div class="rating-input">
        <span>Ваша оценка:</span>
        <div class="stars">
          <Star 
            v-for="i in 5" 
            :key="i"
            class="star"
            :class="{ filled: i <= userRating }"
            @click="setRating(i)"
          />
        </div>
      </div>
      <textarea 
        v-model="comment"
        class="comment-input"
        placeholder="Опишите ваш опыт покупки..."
        rows="4"
      ></textarea>
      <div class="form-actions">
        <button @click="submitReview" class="submit-btn" :disabled="userRating === 0 || !comment">
          Отправить отзыв
        </button>
        <span class="review-hint">
          Отзыв будет привязан к вашему заказу. Платформа проверит обоснованность.
        </span>
      </div>
    </div>

    <div class="reviews-list">
      <div 
        v-for="review in reviews" 
        :key="review.id"
        class="review-item"
        :class="`type-${review.type}`"
      >
        <div class="review-header">
          <div class="customer-info">
            <span class="customer-name">{{ review.customer_name }}</span>
            <span class="review-date">{{ formatDate(review.created_at) }}</span>
          </div>
          <div class="review-rating">
            <Star 
              v-for="i in 5" 
              :key="i"
              class="star small"
              :class="{ filled: i <= review.rating }"
            />
          </div>
        </div>
        
        <p class="review-comment">{{ review.comment }}</p>
        
        <div v-if="review.type === 'paid'" class="review-badge paid">
          <Award class="badge-icon" />
          <span>Оплаченный отзыв ({{ review.payment_amount }}₽)</span>
        </div>
        
        <div v-else class="review-badge free">
          <Shield class="badge-icon" />
          <span>Проверено платформой</span>
        </div>
        
        <div v-if="review.attachments && review.attachments.length > 0" class="review-attachments">
          <ImageIcon v-for="(att, idx) in review.attachments.slice(0, 3)" :key="idx" class="attachment-icon" />
          <span v-if="review.attachments.length > 3" class="more-attachments">
            +{{ review.attachments.length - 3 }}
          </span>
        </div>
      </div>
    </div>

    <div v-if="hasMore" class="load-more">
      <button @click="loadMore" class="load-more-btn">
        Загрузить ещё
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Star, Award, Shield, ImageIcon } from 'lucide-vue-next'

interface Review {
  id: string
  customer_id: string
  customer_name: string
  order_id: string
  product_id: string
  rating: number
  comment: string
  attachments: string[]
  status: 'pending' | 'approved' | 'rejected' | 'published'
  type: 'free' | 'paid'
  payment_amount?: number
  created_at: string
}

const props = defineProps<{
  productId: string
  orderId?: string
  canWriteReview?: boolean
}>()

const emit = defineEmits<{
  reviewSubmitted: [review: Review]
}>()

const reviews = ref<Review[]>([])
const userRating = ref(0)
const comment = ref('')
const hasMore = ref(false)

const averageRating = computed(() => {
  if (reviews.value.length === 0) return 0
  const sum = reviews.value.reduce((acc, r) => acc + r.rating, 0)
  return (sum / reviews.value.length).toFixed(1)
})

const totalReviews = computed(() => reviews.value.length)

const setRating = (rating: number) => {
  userRating.value = rating
}

const submitReview = () => {
  // TODO: API call
  const newReview: Review = {
    id: Date.now().toString(),
    customer_id: '1',
    customer_name: 'Вы',
    order_id: props.orderId || '',
    product_id: props.productId,
    rating: userRating.value,
    comment: comment.value,
    attachments: [],
    status: 'pending',
    type: 'free',
    created_at: new Date().toISOString(),
  }
  
  reviews.value.unshift(newReview)
  emit('reviewSubmitted', newReview)
  
  // Reset form
  userRating.value = 0
  comment.value = ''
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  
  if (diffDays === 0) return 'Сегодня'
  if (diffDays === 1) return 'Вчера'
  if (diffDays < 7) return `${diffDays} дн. назад`
  return date.toLocaleDateString('ru-RU')
}

const loadMore = () => {
  hasMore.value = false
}

onMounted(() => {
  // TODO: Load reviews from API
  reviews.value = [
    {
      id: '1',
      customer_id: '1',
      customer_name: 'Алексей',
      order_id: '123',
      product_id: props.productId,
      rating: 5,
      comment: 'Отличный товар, свежий, быстро доставили',
      attachments: [],
      status: 'published',
      type: 'free',
      created_at: new Date(Date.now() - 86400000).toISOString(),
    },
    {
      id: '2',
      customer_id: '2',
      customer_name: 'Мария',
      order_id: '124',
      product_id: props.productId,
      rating: 4,
      comment: 'Хороший продукт, но упаковка немного помялась',
      attachments: [],
      status: 'published',
      type: 'paid',
      payment_amount: 100,
      created_at: new Date(Date.now() - 172800000).toISOString(),
    },
  ]
})
</script>

<style scoped>
.review-component {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.review-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.review-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #111827;
}

.review-stats {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.average-rating {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.rating-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
}

.star-icon {
  width: 1.5rem;
  height: 1.5rem;
  color: #d1d5db;
}

.star-icon.filled {
  color: #fbbf24;
}

.total-reviews {
  font-size: 0.875rem;
  color: #6b7280;
}

.review-form {
  background: #f9fafb;
  border-radius: 0.75rem;
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.review-form h4 {
  margin: 0 0 1rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
}

.rating-input {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.stars {
  display: flex;
  gap: 0.25rem;
}

.star {
  width: 1.5rem;
  height: 1.5rem;
  color: #d1d5db;
  cursor: pointer;
  transition: color 0.2s ease;
}

.star:hover {
  color: #fbbf24;
}

.star.filled {
  color: #fbbf24;
}

.star.small {
  width: 1rem;
  height: 1rem;
}

.comment-input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-family: inherit;
  resize: vertical;
  margin-bottom: 1rem;
}

.comment-input:focus {
  outline: none;
  border-color: #22c55e;
  box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.1);
}

.form-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.submit-btn {
  padding: 0.5rem 1.5rem;
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.submit-btn:hover:not(:disabled) {
  background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(34, 197, 94, 0.3);
}

.submit-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.review-hint {
  font-size: 0.75rem;
  color: #6b7280;
}

.reviews-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.review-item {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.75rem;
  border-left: 3px solid transparent;
}

.review-item.type-paid {
  border-left-color: #22c55e;
}

.review-item.type-free {
  border-left-color: #3b82f6;
}

.review-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.75rem;
}

.customer-info {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.customer-name {
  font-weight: 600;
  color: #111827;
}

.review-date {
  font-size: 0.75rem;
  color: #6b7280;
}

.review-rating {
  display: flex;
  gap: 0.125rem;
}

.review-comment {
  margin: 0 0 0.75rem 0;
  color: #374151;
  line-height: 1.5;
}

.review-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
}

.review-badge.paid {
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
  color: #166534;
}

.review-badge.free {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  color: #1e40af;
}

.badge-icon {
  width: 0.875rem;
  height: 0.875rem;
}

.review-attachments {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.attachment-icon {
  width: 1.25rem;
  height: 1.25rem;
  color: #6b7280;
}

.more-attachments {
  font-size: 0.75rem;
  color: #6b7280;
}

.load-more {
  margin-top: 1rem;
}

.load-more-btn {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  background: white;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s ease;
}

.load-more-btn:hover {
  background: #f9fafb;
  border-color: #d1d5db;
}
</style>
