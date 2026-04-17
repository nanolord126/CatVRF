<template>
  <div class="flight-search-page">
    <div class="page-header">
      <h1>Flight Search</h1>
      <p>Find the best flights for your journey</p>
    </div>

    <div class="page-content">
      <div class="sidebar">
        <FlightSearchForm @search="handleSearch" />
        
        <div class="search-history" v-if="searchHistory.length">
          <h4>Recent Searches</h4>
          <ul>
            <li v-for="(search, index) in searchHistory" :key="index" @click="restoreSearch(search)">
              {{ search.origin }} → {{ search.destination }}
              <span class="search-date">{{ formatDate(search.date) }}</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="main-content">
        <FlightSearchResults 
          :results="searchResults"
          :loading="isSearching"
          :error="searchError"
          @selectFlight="handleSelectFlight"
          @retry="handleRetry"
        />

        <div class="booking-summary" v-if="selectedFlight">
          <h3>Selected Flight</h3>
          <div class="selected-flight-card">
            <div class="flight-info">
              <span class="airline">{{ selectedFlight.airline }}</span>
              <span class="flight-number">{{ selectedFlight.flight_number }}</span>
            </div>
            <div class="route">
              <span>{{ selectedFlight.origin }}</span>
              <span>→</span>
              <span>{{ selectedFlight.destination }}</span>
            </div>
            <div class="price">{{ formatCurrency(selectedFlight.price) }}</div>
            <button @click="proceedToBooking" class="btn-book">
              Book Now
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showBookingModal" class="modal-overlay" @click="closeBookingModal">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>Complete Your Booking</h3>
          <button @click="closeBookingModal" class="btn-close">&times;</button>
        </div>
        <div class="modal-body">
          <TravelBookingForm 
            :flight="selectedFlight"
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
import FlightSearchForm from './FlightSearchForm.vue'
import FlightSearchResults from './FlightSearchResults.vue'
import TravelBookingForm from './TravelBookingForm.vue'

interface Flight {
  id: string
  provider: string
  airline: string
  flight_number: string
  origin: string
  destination: string
  departure_time: string
  arrival_time: string
  duration: string
  price: number
  currency: string
  stops: number
  class: string
}

interface FlightSearchParams {
  origin: string
  destination: string
  date: string
  return_date?: string
  passengers: number
  class: string
  direct_only: boolean
}

interface SearchHistoryItem extends FlightSearchParams {
  date: string
}

const searchResults = ref<any>(null)
const isSearching = ref(false)
const searchError = ref<string | null>(null)
const selectedFlight = ref<Flight | null>(null)
const lastSearchParams = ref<FlightSearchParams | null>(null)
const searchHistory = ref<SearchHistoryItem[]>([])
const showBookingModal = ref(false)

const handleSearch = async (params: FlightSearchParams) => {
  isSearching.value = true
  searchError.value = null
  selectedFlight.value = null
  lastSearchParams.value = params

  try {
    const response = await fetch('/api/travel/flights/search?' + new URLSearchParams({
      origin: params.origin,
      destination: params.destination,
      date: params.date,
      ...(params.return_date && { return_date: params.return_date }),
      passengers: params.passengers.toString(),
      class: params.class,
      direct_only: params.direct_only.toString(),
    }))

    const data = await response.json()

    if (data.success) {
      searchResults.value = data.data
      
      // Add to search history
      const historyItem: SearchHistoryItem = {
        ...params,
        date: new Date().toISOString(),
      }
      searchHistory.value.unshift(historyItem)
      if (searchHistory.value.length > 5) {
        searchHistory.value.pop()
      }
    } else {
      searchError.value = data.message || 'Search failed'
    }
  } catch (error) {
    searchError.value = 'Failed to search flights. Please try again.'
  } finally {
    isSearching.value = false
  }
}

const handleSelectFlight = (flight: Flight) => {
  selectedFlight.value = flight
}

const handleRetry = () => {
  if (lastSearchParams.value) {
    handleSearch(lastSearchParams.value)
  }
}

const restoreSearch = (search: SearchHistoryItem) => {
  handleSearch(search)
}

const proceedToBooking = () => {
  showBookingModal.value = true
}

const closeBookingModal = () => {
  showBookingModal.value = false
}

const handleBookingSubmit = (bookingData: any) => {
  // Handle booking submission
  console.log('Booking submitted:', bookingData)
  closeBookingModal()
  // Navigate to booking confirmation or success page
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
  })
}
</script>

<style scoped>
.flight-search-page {
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

.search-history {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.search-history h4 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
  color: #111827;
}

.search-history ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.search-history li {
  padding: 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.2s;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.search-history li:hover {
  background: #f3f4f6;
}

.search-date {
  font-size: 12px;
  color: #9ca3af;
}

.main-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.booking-summary {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.booking-summary h3 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
}

.selected-flight-card {
  border: 2px solid #3b82f6;
  border-radius: 8px;
  padding: 20px;
}

.flight-info {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
}

.airline {
  font-weight: 600;
  color: #111827;
}

.flight-number {
  color: #6b7280;
}

.route {
  display: flex;
  gap: 8px;
  align-items: center;
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 16px;
}

.price {
  font-size: 28px;
  font-weight: 700;
  color: #059669;
  margin-bottom: 16px;
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
  max-width: 800px;
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
