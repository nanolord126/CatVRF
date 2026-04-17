<template>
  <div class="flight-search-results">
    <div class="results-header">
      <h3>Flight Results</h3>
      <div class="results-count" v-if="results">
        {{ results.count }} flights found
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Searching for the best flights...</p>
    </div>

    <div v-else-if="error" class="error-state">
      <p>{{ error }}</p>
      <button @click="$emit('retry')" class="btn-retry">Retry</button>
    </div>

    <div v-else-if="!results || results.count === 0" class="empty-state">
      <p>No flights found for your search criteria.</p>
      <p>Try different dates or airports.</p>
    </div>

    <div v-else class="results-list">
      <div 
        v-for="flight in results.flights" 
        :key="flight.id" 
        class="flight-card"
        @click="$emit('selectFlight', flight)"
      >
        <div class="flight-header">
          <div class="airline-info">
            <span class="airline-name">{{ flight.airline }}</span>
            <span class="flight-number">{{ flight.flight_number }}</span>
          </div>
          <span class="flight-class">{{ formatClass(flight.class) }}</span>
        </div>

        <div class="flight-route">
          <div class="route-point">
            <div class="airport-code">{{ flight.origin }}</div>
            <div class="departure-time">{{ formatTime(flight.departure_time) }}</div>
            <div class="date">{{ formatDate(flight.departure_time) }}</div>
          </div>

          <div class="route-connection">
            <div class="duration">{{ formatDuration(flight.duration) }}</div>
            <div class="stops">
              {{ flight.stops === 0 ? 'Direct' : `${flight.stops} stop${flight.stops > 1 ? 's' : ''}` }}
            </div>
            <div class="route-line">
              <div class="line-dot start"></div>
              <div class="line"></div>
              <div class="line-dot end"></div>
            </div>
          </div>

          <div class="route-point">
            <div class="airport-code">{{ flight.destination }}</div>
            <div class="arrival-time">{{ formatTime(flight.arrival_time) }}</div>
            <div class="date">{{ formatDate(flight.arrival_time) }}</div>
          </div>
        </div>

        <div class="flight-footer">
          <div class="price-info">
            <span class="price">{{ formatCurrency(flight.price) }}</span>
            <span class="provider-badge">{{ flight.provider }}</span>
          </div>
          <button class="btn-select">Select</button>
        </div>
      </div>
    </div>

    <div class="results-footer" v-if="results && results.count > 0">
      <p>Prices include taxes and fees. Subject to availability.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

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

interface FlightResults {
  flights: Flight[]
  count: number
  currency: string
  error?: string
}

interface Props {
  results: FlightResults | null
  loading: boolean
  error: string | null
}

const props = withDefaults(defineProps<Props>(), {
  results: null,
  loading: false,
  error: null,
})

const emit = defineEmits<{
  (e: 'selectFlight', flight: Flight): void
  (e: 'retry'): void
}>()

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: props.results?.currency || 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

const formatTime = (dateTime: string): string => {
  const date = new Date(dateTime)
  return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
}

const formatDate = (dateTime: string): string => {
  const date = new Date(dateTime)
  return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
}

const formatDuration = (duration: string): string => {
  // Duration is typically in format like "PT2H30M"
  const hoursMatch = duration.match(/(\d+)H/)
  const minutesMatch = duration.match(/(\d+)M/)
  
  const hours = hoursMatch ? parseInt(hoursMatch[1]) : 0
  const minutes = minutesMatch ? parseInt(minutesMatch[1]) : 0
  
  if (hours === 0) return `${minutes}m`
  if (minutes === 0) return `${hours}h`
  return `${hours}h ${minutes}m`
}

const formatClass = (flightClass: string): string => {
  const classes: Record<string, string> = {
    'economy': 'Economy',
    'business': 'Business',
    'first': 'First Class',
  }
  return classes[flightClass] || flightClass
}
</script>

<style scoped>
.flight-search-results {
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

.results-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.flight-card {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 20px;
  cursor: pointer;
  transition: all 0.2s;
}

.flight-card:hover {
  border-color: #3b82f6;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.flight-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.airline-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.airline-name {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.flight-number {
  font-size: 12px;
  color: #6b7280;
}

.flight-class {
  padding: 4px 12px;
  background: #f3f4f6;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  color: #6b7280;
}

.flight-route {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.route-point {
  text-align: center;
}

.airport-code {
  font-size: 20px;
  font-weight: 700;
  color: #374151;
}

.departure-time,
.arrival-time {
  font-size: 16px;
  font-weight: 600;
  color: #374151;
  margin-top: 4px;
}

.date {
  font-size: 12px;
  color: #6b7280;
  margin-top: 2px;
}

.route-connection {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 0 20px;
}

.duration {
  font-size: 13px;
  color: #6b7280;
}

.stops {
  font-size: 12px;
  color: #6b7280;
}

.route-line {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
}

.line {
  flex: 1;
  height: 2px;
  background: linear-gradient(to right, #3b82f6, #f59e0b);
  margin: 0 8px;
}

.line-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #3b82f6;
}

.line-dot.end {
  background: #f59e0b;
}

.flight-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.price-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.price {
  font-size: 24px;
  font-weight: 700;
  color: #059669;
}

.provider-badge {
  font-size: 12px;
  color: #6b7280;
  text-transform: capitalize;
}

.btn-select {
  padding: 10px 24px;
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

.results-footer {
  margin-top: 20px;
  text-align: center;
  font-size: 12px;
  color: #9ca3af;
}
</style>
