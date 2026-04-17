<template>
  <div class="flight-search-form">
    <div class="form-header">
      <h3>Search Flights</h3>
      <div class="trip-type-toggle">
        <button 
          :class="['trip-btn', { active: tripType === 'roundtrip' }]"
          @click="tripType = 'roundtrip'"
        >
          Round Trip
        </button>
        <button 
          :class="['trip-btn', { active: tripType === 'oneway' }]"
          @click="tripType = 'oneway'"
        >
          One Way
        </button>
      </div>
    </div>

    <form @submit.prevent="handleSubmit" class="search-form">
      <div class="form-row">
        <div class="form-group">
          <label>From</label>
          <div class="input-with-suggestions">
            <input 
              v-model="origin" 
              type="text" 
              placeholder="Airport or city" 
              class="form-input"
              @input="debounceOriginSearch"
              @focus="showOriginSuggestions = true"
              ref="originInput"
            />
            <ul v-if="showOriginSuggestions && originSuggestions.length" class="suggestions">
              <li 
                v-for="airport in originSuggestions" 
                :key="airport.code"
                @click="selectOrigin(airport)"
              >
                {{ airport.code }} - {{ airport.city }}, {{ airport.country }}
              </li>
            </ul>
          </div>
        </div>

        <div class="swap-btn" @click="swapAirports">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
          </svg>
        </div>

        <div class="form-group">
          <label>To</label>
          <div class="input-with-suggestions">
            <input 
              v-model="destination" 
              type="text" 
              placeholder="Airport or city" 
              class="form-input"
              @input="debounceDestinationSearch"
              @focus="showDestinationSuggestions = true"
              ref="destinationInput"
            />
            <ul v-if="showDestinationSuggestions && destinationSuggestions.length" class="suggestions">
              <li 
                v-for="airport in destinationSuggestions" 
                :key="airport.code"
                @click="selectDestination(airport)"
              >
                {{ airport.code }} - {{ airport.city }}, {{ airport.country }}
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Departure Date</label>
          <input 
            v-model="departureDate" 
            type="date" 
            class="form-input"
            :min="minDate"
          />
        </div>

        <div class="form-group" v-if="tripType === 'roundtrip'">
          <label>Return Date</label>
          <input 
            v-model="returnDate" 
            type="date" 
            class="form-input"
            :min="departureDate || minDate"
          />
        </div>

        <div class="form-group">
          <label>Passengers</label>
          <select v-model="passengers" class="form-input">
            <option v-for="n in 9" :key="n" :value="n">{{ n }} {{ n === 1 ? 'Adult' : 'Adults' }}</option>
          </select>
        </div>

        <div class="form-group">
          <label>Class</label>
          <select v-model="travelClass" class="form-input">
            <option value="economy">Economy</option>
            <option value="business">Business</option>
            <option value="first">First Class</option>
          </select>
        </div>
      </div>

      <div class="form-actions">
        <label class="checkbox-label">
          <input type="checkbox" v-model="directOnly" />
          Direct flights only
        </label>
        <button type="submit" class="btn-search" :disabled="isSearching">
          <span v-if="isSearching">Searching...</span>
          <span v-else>Search Flights</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

interface Airport {
  code: string
  name: string
  city: string
  country: string
}

interface Emits {
  (e: 'search', params: FlightSearchParams): void
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

const emit = defineEmits<Emits>()

const tripType = ref<'roundtrip' | 'oneway'>('roundtrip')
const origin = ref('')
const destination = ref('')
const departureDate = ref('')
const returnDate = ref('')
const passengers = ref(1)
const travelClass = ref('economy')
const directOnly = ref(false)
const isSearching = ref(false)

const originSuggestions = ref<Airport[]>([])
const destinationSuggestions = ref<Airport[]>([])
const showOriginSuggestions = ref(false)
const showDestinationSuggestions = ref(false)

const originInput = ref<HTMLInputElement | null>(null)
const destinationInput = ref<HTMLInputElement | null>(null)

const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

let originDebounceTimer: number | null = null
let destinationDebounceTimer: number | null = null

const debounceOriginSearch = () => {
  if (originDebounceTimer) clearTimeout(originDebounceTimer)
  originDebounceTimer = window.setTimeout(() => searchAirports(origin.value, 'origin'), 300)
}

const debounceDestinationSearch = () => {
  if (destinationDebounceTimer) clearTimeout(destinationDebounceTimer)
  destinationDebounceTimer = window.setTimeout(() => searchAirports(destination.value, 'destination'), 300)
}

const searchAirports = async (query: string, type: 'origin' | 'destination') => {
  if (query.length < 2) {
    if (type === 'origin') {
      originSuggestions.value = []
      showOriginSuggestions.value = false
    } else {
      destinationSuggestions.value = []
      showDestinationSuggestions.value = false
    }
    return
  }

  try {
    const response = await fetch(`/api/travel/flights/search/airports?q=${query}`)
    const data = await response.json()
    
    if (data.success) {
      if (type === 'origin') {
        originSuggestions.value = data.data
        showOriginSuggestions.value = true
      } else {
        destinationSuggestions.value = data.data
        showDestinationSuggestions.value = true
      }
    }
  } catch (error) {
    console.error('Airport search failed:', error)
  }
}

const selectOrigin = (airport: Airport) => {
  origin.value = airport.code
  originSuggestions.value = []
  showOriginSuggestions.value = false
}

const selectDestination = (airport: Airport) => {
  destination.value = airport.code
  destinationSuggestions.value = []
  showDestinationSuggestions.value = false
}

const swapAirports = () => {
  const temp = origin.value
  origin.value = destination.value
  destination.value = temp
}

const handleSubmit = () => {
  if (!origin.value || !destination.value || !departureDate.value) {
    alert('Please fill in all required fields')
    return
  }

  if (tripType.value === 'roundtrip' && !returnDate.value) {
    alert('Please select a return date')
    return
  }

  isSearching.value = true

  const params: FlightSearchParams = {
    origin: origin.value,
    destination: destination.value,
    date: departureDate.value,
    passengers: passengers.value,
    class: travelClass.value,
    direct_only: directOnly.value,
  }

  if (tripType.value === 'roundtrip' && returnDate.value) {
    params.return_date = returnDate.value
  }

  emit('search', params)
  
  // Reset searching state after a delay
  setTimeout(() => {
    isSearching.value = false
  }, 1000)
}

const handleClickOutside = (event: MouseEvent) => {
  if (originInput.value && !originInput.value.contains(event.target as Node)) {
    showOriginSuggestions.value = false
  }
  if (destinationInput.value && !destinationInput.value.contains(event.target as Node)) {
    showDestinationSuggestions.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  
  // Set default departure date to tomorrow
  const tomorrow = new Date()
  tomorrow.setDate(tomorrow.getDate() + 1)
  departureDate.value = tomorrow.toISOString().split('T')[0]
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  if (originDebounceTimer) clearTimeout(originDebounceTimer)
  if (destinationDebounceTimer) clearTimeout(destinationDebounceTimer)
})
</script>

<style scoped>
.flight-search-form {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.form-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.trip-type-toggle {
  display: flex;
  gap: 8px;
}

.trip-btn {
  padding: 8px 16px;
  background: #f3f4f6;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #6b7280;
}

.trip-btn.active {
  background: #3b82f6;
  color: white;
}

.search-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-row {
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.form-group {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 500;
  color: #374151;
}

.form-input {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  width: 100%;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.input-with-suggestions {
  position: relative;
}

.suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  max-height: 200px;
  overflow-y: auto;
  z-index: 100;
  margin-top: 4px;
}

.suggestions li {
  padding: 10px 12px;
  cursor: pointer;
  font-size: 13px;
}

.suggestions li:hover {
  background: #f3f4f6;
}

.swap-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  margin-top: 24px;
  background: #f3f4f6;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.2s;
}

.swap-btn:hover {
  background: #e5e7eb;
  color: #374151;
  transform: rotate(180deg);
}

.form-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 8px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #374151;
  cursor: pointer;
}

.btn-search {
  padding: 12px 24px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  min-width: 160px;
}

.btn-search:hover:not(:disabled) {
  background: #2563eb;
}

.btn-search:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}
</style>
