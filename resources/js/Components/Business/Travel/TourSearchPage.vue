<template>
  <div class="tour-search-page">
    <div class="page-header">
      <h1>Tour Search</h1>
      <p>Discover amazing tours and experiences</p>
    </div>

    <div class="page-content">
      <div class="sidebar">
        <TourSearchForm @search="handleSearch" />
        
        <div class="search-tips">
          <h4>Search Tips</h4>
          <ul>
            <li>Book in advance for better prices</li>
            <li>Check flexible dates for savings</li>
            <li>Consider off-peak seasons</li>
            <li>Look for all-inclusive packages</li>
          </ul>
        </div>
      </div>

      <div class="main-content">
        <TourSearchResults 
          :results="searchResults"
          :loading="isSearching"
          :error="searchError"
          @selectTour="handleSelectTour"
          @toggleWishlist="handleToggleWishlist"
          @retry="handleRetry"
        />

        <div class="tour-details" v-if="selectedTour">
          <h3>Selected Tour</h3>
          <div class="selected-tour-card">
            <div class="tour-header">
              <h4>{{ selectedTour.title }}</h4>
              <div class="rating">
                <span>⭐ {{ selectedTour.rating }}</span>
                <span class="reviews">({{ selectedTour.reviews }} reviews)</span>
              </div>
            </div>
            <p class="tour-location">{{ selectedTour.destination }}</p>
            <div class="tour-meta">
              <span>{{ selectedTour.duration }} days</span>
              <span>•</span>
              <span>{{ selectedTour.group_size }} people</span>
              <span>•</span>
              <span v-if="selectedTour.has_guide">Guide included</span>
            </div>
            <div class="tour-features">
              <span v-if="selectedTour.all_inclusive" class="feature-tag">All Inclusive</span>
              <span v-if="selectedTour.free_cancellation" class="feature-tag">Free Cancellation</span>
              <span v-if="selectedTour.flexible_booking" class="feature-tag">Flexible Booking</span>
            </div>
            <div class="price-section">
              <span v-if="selectedTour.discount" class="original-price">{{ formatCurrency(selectedTour.original_price) }}</span>
              <span class="price">{{ formatCurrency(selectedTour.price) }}</span>
              <span class="per-person">/ person</span>
            </div>
            <button @click="proceedToBooking" class="btn-book">
              Book Now - {{ formatCurrency(selectedTour.price * lastSearchParams?.travelers || 1) }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showBookingModal" class="modal-overlay" @click="closeBookingModal">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>Complete Your Tour Booking</h3>
          <button @click="closeBookingModal" class="btn-close">&times;</button>
        </div>
        <div class="modal-body">
          <TourBookingForm 
            :tour="selectedTour"
            :search-params="lastSearchParams"
            @submit="handleBookingSubmit"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import TourSearchForm from './TourSearchForm.vue'
import TourSearchResults from './TourSearchResults.vue'
import TourBookingForm from './TourBookingForm.vue'

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

interface TourSearchParams {
  destination: string
  duration: string
  departure_date: string
  return_date?: string
  travelers: number
  tour_type: string
  budget: string
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

const searchResults = ref<TourResults | null>(null)
const isSearching = ref(false)
const searchError = ref<string | null>(null)
const selectedTour = ref<Tour | null>(null)
const lastSearchParams = ref<TourSearchParams | null>(null)
const showBookingModal = ref(false)

const handleSearch = async (params: TourSearchParams) => {
  isSearching.value = true
  searchError.value = null
  selectedTour.value = null
  lastSearchParams.value = params

  try {
    const response = await fetch('/api/travel/tours/search?' + new URLSearchParams({
      destination: params.destination,
      duration: params.duration,
      departure_date: params.departure_date,
      ...(params.return_date && { return_date: params.return_date }),
      travelers: params.travelers.toString(),
      tour_type: params.tour_type,
      budget: params.budget,
      all_inclusive: params.all_inclusive.toString(),
      has_guide: params.has_guide.toString(),
      free_cancellation: params.free_cancellation.toString(),
      flexible_booking: params.flexible_booking.toString(),
    }))

    const data = await response.json()

    if (data.success) {
      searchResults.value = data.data
    } else {
      searchError.value = data.message || 'Search failed'
    }
  } catch (error) {
    searchError.value = 'Failed to search tours. Please try again.'
  } finally {
    isSearching.value = false
  }
}

const handleSelectTour = (tour: Tour) => {
  selectedTour.value = tour
}

const handleToggleWishlist = (tourId: number) => {
  // Toggle wishlist status
  console.log('Toggle wishlist for tour:', tourId)
}

const handleRetry = () => {
  if (lastSearchParams.value) {
    handleSearch(lastSearchParams.value)
  }
}

const proceedToBooking = () => {
  showBookingModal.value = true
}

const closeBookingModal = () => {
  showBookingModal.value = false
}

const handleBookingSubmit = (bookingData: any) => {
  console.log('Booking submitted:', bookingData)
  closeBookingModal()
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}
</script>

<style scoped>
.tour-search-page {
  min-height: 100vh;
  background: #f9fafb;
}

.page-header {
  background: white;
  padding: 32px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.page-header h1 {
  margin: 0 0 8px 0;
  font-size: 28px;
  font-weight: 700;
  color: #111827;
}

.page-header p {
  margin: 0;
  font-size: 16px;
  color: #6b7280;
}

.page-content {
  display: grid;
  grid-template-columns: 400px 1fr;
  gap: 24px;
  padding: 24px;
  max-width: 1600px;
  margin: 0 auto;
}

.sidebar {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.search-tips {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.search-tips h4 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
}

.search-tips ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.search-tips li {
  padding: 8px 0;
  padding-left: 20px;
  position: relative;
  font-size: 14px;
  color: #6b7280;
}

.search-tips li::before {
  content: "✓";
  position: absolute;
  left: 0;
  color: #10b981;
  font-weight: bold;
}

.main-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.tour-details {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tour-details h3 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
}

.selected-tour-card {
  border: 2px solid #3b82f6;
  border-radius: 8px;
  padding: 20px;
}

.tour-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.tour-header h4 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
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
  font-size: 14px;
  color: #6b7280;
  margin-bottom: 12px;
}

.tour-meta {
  display: flex;
  gap: 8px;
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 12px;
}

.tour-features {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.feature-tag {
  padding: 4px 8px;
  background: #f3f4f6;
  border-radius: 4px;
  font-size: 11px;
  color: #6b7280;
}

.price-section {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-bottom: 16px;
}

.original-price {
  text-decoration: line-through;
  color: #9ca3af;
  font-size: 16px;
}

.price {
  font-size: 24px;
  font-weight: 700;
  color: #059669;
}

.per-person {
  font-size: 12px;
  color: #6b7280;
}

.btn-book {
  width: 100%;
  padding: 14px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}

.btn-book:hover {
  background: #2563eb;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 8px;
  max-width: 900px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.btn-close {
  background: none;
  border: none;
  font-size: 28px;
  cursor: pointer;
  color: #6b7280;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-close:hover {
  color: #111827;
}

.modal-body {
  padding: 20px;
}

@media (max-width: 1024px) {
  .page-content {
    grid-template-columns: 1fr;
  }
}
</style>
