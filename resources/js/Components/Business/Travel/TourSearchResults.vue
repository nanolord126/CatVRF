<template>
  <div class="tour-search-results">
    <div class="results-header">
      <h3>Tour Results</h3>
      <div class="results-count" v-if="results">
        {{ results.count }} tours found
      </div>
    </div>

    <div class="results-filters">
      <select v-model="sortBy" class="sort-select" @change="sortTours">
        <option value="recommended">Recommended</option>
        <option value="price_low">Price: Low to High</option>
        <option value="price_high">Price: High to Low</option>
        <option value="duration">Duration</option>
        <option value="rating">Rating</option>
      </select>
      <div class="view-toggle">
        <button 
          :class="['view-btn', { active: viewMode === 'grid' }]"
          @click="viewMode = 'grid'"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <rect x="3" y="3" width="7" height="7" stroke-width="2"/>
            <rect x="14" y="3" width="7" height="7" stroke-width="2"/>
            <rect x="14" y="14" width="7" height="7" stroke-width="2"/>
            <rect x="3" y="14" width="7" height="7" stroke-width="2"/>
          </svg>
        </button>
        <button 
          :class="['view-btn', { active: viewMode === 'list' }]"
          @click="viewMode = 'list'"
        >
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <line x1="8" y1="6" x2="21" y2="6" stroke-width="2"/>
            <line x1="8" y1="12" x2="21" y2="12" stroke-width="2"/>
            <line x1="8" y1="18" x2="21" y2="18" stroke-width="2"/>
            <line x1="3" y1="6" x2="3.01" y2="6" stroke-width="2"/>
            <line x1="3" y1="12" x2="3.01" y2="12" stroke-width="2"/>
            <line x1="3" y1="18" x2="3.01" y2="18" stroke-width="2"/>
          </svg>
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Searching for the best tours...</p>
    </div>

    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <button @click="$emit('retry')" class="btn-retry">Retry</button>
    </div>

    <div v-else-if="!results || results.count === 0" class="empty-state">
      <p>No tours found for your search criteria.</p>
      <p>Try different dates or destinations.</p>
    </div>

    <div v-else :class="['results-container', viewMode]">
      <div 
        v-for="tour in sortedTours" 
        :key="tour.id" 
        :class="['tour-card', viewMode]"
        @click="$emit('selectTour', tour)"
      >
        <div class="tour-image">
          <img :src="tour.image" :alt="tour.title" />
          <div class="tour-badges">
            <span v-if="tour.is_popular" class="badge badge-popular">Popular</span>
            <span v-if="tour.discount" class="badge badge-discount">-{{ tour.discount }}%</span>
          </div>
          <button class="btn-wishlist" @click.stop="$emit('toggleWishlist', tour.id)">
            <svg width="20" height="20" viewBox="0 0 24 24" :fill="tour.is_wishlist ? 'currentColor' : 'none'" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
          </button>
        </div>

        <div class="tour-content">
          <div class="tour-header">
            <h4>{{ tour.title }}</h4>
            <div class="rating">
              <span>⭐ {{ tour.rating }}</span>
              <span class="reviews">({{ tour.reviews }})</span>
            </div>
          </div>

          <p class="tour-location">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            {{ tour.destination }}
          </p>

          <div class="tour-details">
            <span class="detail-item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="12" cy="12" r="10" stroke-width="2"/>
                <polyline points="12 6 12 12 16 14" stroke-width="2"/>
              </svg>
              {{ tour.duration }} days
            </span>
            <span class="detail-item">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
              </svg>
              {{ tour.group_size }} people
            </span>
            <span class="detail-item" v-if="tour.has_guide">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              Guide included
            </span>
          </div>

          <div class="tour-features">
            <span v-if="tour.all_inclusive" class="feature-tag">All Inclusive</span>
            <span v-if="tour.free_cancellation" class="feature-tag">Free Cancellation</span>
            <span v-if="tour.flexible_booking" class="feature-tag">Flexible</span>
          </div>

          <div class="tour-footer">
            <div class="price-info">
              <span v-if="tour.discount" class="original-price">{{ formatCurrency(tour.original_price) }}</span>
              <span class="price">{{ formatCurrency(tour.price) }}</span>
              <span class="per-person">/ person</span>
            </div>
            <button class="btn-select">View Details</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Tour {
  id: number
  title: string
  destination: string
  image: string
  duration: number
  price: number
  original_price: number
  discount: number
  rating: number
  reviews: number
  group_size: number
  is_popular: boolean
  is_wishlist: boolean
  all_inclusive: boolean
  has_guide: boolean
  free_cancellation: boolean
  flexible_booking: boolean
}

interface TourResults {
  tours: Tour[]
  count: number
  currency: string
  error?: string
}

interface Props {
  results: TourResults | null
  loading: boolean
  error: string | null
}

const props = withDefaults(defineProps<Props>(), {
  results: null,
  loading: false,
  error: null,
})

const emit = defineEmits<{
  (e: 'selectTour', tour: Tour): void
  (e: 'toggleWishlist', tourId: number): void
  (e: 'retry'): void
}>()

const viewMode = ref<'grid' | 'list'>('grid')
const sortBy = ref('recommended')

const sortedTours = computed(() => {
  if (!props.results?.tours) return []
  
  const tours = [...props.results.tours]
  
  switch (sortBy.value) {
    case 'price_low':
      return tours.sort((a, b) => a.price - b.price)
    case 'price_high':
      return tours.sort((a, b) => b.price - a.price)
    case 'duration':
      return tours.sort((a, b) => a.duration - b.duration)
    case 'rating':
      return tours.sort((a, b) => b.rating - a.rating)
    default:
      return tours
  }
})

const sortTours = () => {
  // Triggered by sortBy change, computed property handles the sorting
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: props.results?.currency || 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}
</script>

<style scoped>
.tour-search-results {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.results-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.results-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.results-count {
  font-size: 14px;
  color: #6b7280;
}

.results-filters {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.sort-select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.view-toggle {
  display: flex;
  gap: 8px;
}

.view-btn {
  padding: 8px;
  background: #f3f4f6;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  color: #6b7280;
}

.view-btn.active {
  background: #3b82f6;
  color: white;
}

.loading-state,
.error-state,
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #6b7280;
}

.spinner {
  width: 40px;
  height: 40px;
  margin: 0 auto 20px;
  border: 3px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.btn-retry {
  margin-top: 16px;
  padding: 10px 20px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
}

.results-container.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.results-container.list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.tour-card {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
}

.tour-card:hover {
  border-color: #3b82f6;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.tour-card.list {
  display: flex;
  flex-direction: row;
}

.tour-card.list .tour-image {
  width: 300px;
}

.tour-image {
  position: relative;
  width: 100%;
  height: 200px;
}

.tour-card.list .tour-image {
  height: 200px;
}

.tour-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.tour-badges {
  position: absolute;
  top: 10px;
  left: 10px;
  display: flex;
  gap: 8px;
}

.badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  color: white;
}

.badge-popular {
  background: #f59e0b;
}

.badge-discount {
  background: #ef4444;
}

.btn-wishlist {
  position: absolute;
  top: 10px;
  right: 10px;
  padding: 8px;
  background: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.btn-wishlist:hover {
  color: #ef4444;
}

.tour-content {
  padding: 16px;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.tour-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;
}

.tour-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  flex: 1;
}

.rating {
  display: flex;
  gap: 4px;
  font-size: 13px;
}

.reviews {
  color: #6b7280;
}

.tour-location {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 12px;
}

.tour-details {
  display: flex;
  gap: 16px;
  margin-bottom: 12px;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #6b7280;
}

.tour-features {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 12px;
}

.feature-tag {
  padding: 4px 8px;
  background: #f3f4f6;
  border-radius: 4px;
  font-size: 11px;
  color: #6b7280;
}

.tour-footer {
  margin-top: auto;
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  padding-top: 12px;
  border-top: 1px solid #e5e7eb;
}

.price-info {
  display: flex;
  align-items: baseline;
  gap: 4px;
}

.original-price {
  text-decoration: line-through;
  color: #9ca3af;
  font-size: 14px;
}

.price {
  font-size: 20px;
  font-weight: 700;
  color: #059669;
}

.per-person {
  font-size: 12px;
  color: #6b7280;
}

.btn-select {
  padding: 8px 16px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
}

.btn-select:hover {
  background: #2563eb;
}

@media (max-width: 768px) {
  .tour-card.list {
    flex-direction: column;
  }
  
  .tour-card.list .tour-image {
    width: 100%;
  }
  
  .results-filters {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }
}
</style>
