<template>
  <div class="tour-search-form">
    <div class="form-header">
      <h3>Search Tours</h3>
    </div>

    <form @submit.prevent="handleSubmit" class="search-form">
      <div class="form-row">
        <div class="form-group">
          <label>Destination</label>
          <input 
            v-model="destination" 
            type="text" 
            placeholder="Where do you want to go?" 
            class="form-input"
          />
        </div>

        <div class="form-group">
          <label>Duration</label>
          <select v-model="duration" class="form-input">
            <option value="">Any Duration</option>
            <option value="1-3">1-3 days</option>
            <option value="4-7">4-7 days</option>
            <option value="8-14">8-14 days</option>
            <option value="15+">15+ days</option>
          </select>
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

        <div class="form-group">
          <label>Return Date</label>
          <input 
            v-model="returnDate" 
            type="date" 
            class="form-input"
            :min="departureDate || minDate"
          />
        </div>

        <div class="form-group">
          <label>Travelers</label>
          <select v-model="travelers" class="form-input">
            <option v-for="n in 20" :key="n" :value="n">{{ n }} {{ n === 1 ? 'Traveler' : 'Travelers' }}</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Tour Type</label>
          <select v-model="tourType" class="form-input">
            <option value="">All Types</option>
            <option value="adventure">Adventure</option>
            <option value="cultural">Cultural</option>
            <option value="beach">Beach</option>
            <option value="safari">Safari</option>
            <option value="cruise">Cruise</option>
            <option value="ski">Ski</option>
            <option value="wellness">Wellness</option>
            <option value="culinary">Culinary</option>
          </select>
        </div>

        <div class="form-group">
          <label>Budget</label>
          <select v-model="budget" class="form-input">
            <option value="">Any Budget</option>
            <option value="budget">Budget (under 50k)</option>
            <option value="mid">Mid-range (50k-150k)</option>
            <option value="premium">Premium (150k-300k)</option>
            <option value="luxury">Luxury (300k+)</option>
          </select>
        </div>
      </div>

      <div class="filters-section">
        <h4>Additional Filters</h4>
        <div class="checkbox-group">
          <label class="checkbox-label">
            <input type="checkbox" v-model="allInclusive" />
            All Inclusive
          </label>
          <label class="checkbox-label">
            <input type="checkbox" v-model="hasGuide" />
            Professional Guide
          </label>
          <label class="checkbox-label">
            <input type="checkbox" v-model="freeCancellation" />
            Free Cancellation
          </label>
          <label class="checkbox-label">
            <input type="checkbox" v-model="flexibleBooking" />
            Flexible Booking
          </label>
        </div>
      </div>

      <div class="form-actions">
        <button type="button" @click="resetForm" class="btn-reset">Reset</button>
        <button type="submit" class="btn-search" :disabled="isSearching">
          <span v-if="isSearching">Searching...</span>
          <span v-else>Search Tours</span>
        </button>
      </div>
    </form>

    <div class="quick-destinations">
      <h4>Popular Destinations</h4>
      <div class="destination-tags">
        <button 
          v-for="dest in popularDestinations" 
          :key="dest"
          @click="destination = dest"
          class="tag-btn"
        >
          {{ dest }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Emits {
  (e: 'search', params: TourSearchParams): void
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

const emit = defineEmits<Emits>()

const destination = ref('')
const duration = ref('')
const departureDate = ref('')
const returnDate = ref('')
const travelers = ref(2)
const tourType = ref('')
const budget = ref('')
const allInclusive = ref(false)
const hasGuide = ref(false)
const freeCancellation = ref(false)
const flexibleBooking = ref(false)
const isSearching = ref(false)

const popularDestinations = [
  'Moscow',
  'Saint Petersburg',
  'Sochi',
  'Istanbul',
  'Dubai',
  'Paris',
  'Rome',
  'Barcelona',
  'Tokyo',
  'Bangkok',
  'Bali',
  'Maldives',
]

const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const handleSubmit = () => {
  isSearching.value = true

  const params: TourSearchParams = {
    destination: destination.value,
    duration: duration.value,
    departure_date: departureDate.value,
    travelers: travelers.value,
    tour_type: tourType.value,
    budget: budget.value,
    all_inclusive: allInclusive.value,
    has_guide: hasGuide.value,
    free_cancellation: freeCancellation.value,
    flexible_booking: flexibleBooking.value,
  }

  if (returnDate.value) {
    params.return_date = returnDate.value
  }

  emit('search', params)
  
  setTimeout(() => {
    isSearching.value = false
  }, 1000)
}

const resetForm = () => {
  destination.value = ''
  duration.value = ''
  departureDate.value = ''
  returnDate.value = ''
  travelers.value = 2
  tourType.value = ''
  budget.value = ''
  allInclusive.value = false
  hasGuide.value = false
  freeCancellation.value = false
  flexibleBooking.value = false
}
</script>

<style scoped>
.tour-search-form {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-header {
  margin-bottom: 24px;
}

.form-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.search-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-row {
  display: flex;
  gap: 12px;
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

.filters-section {
  padding: 16px;
  background: #f9fafb;
  border-radius: 6px;
}

.filters-section h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.checkbox-group {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #374151;
  cursor: pointer;
}

.form-actions {
  display: flex;
  gap: 12px;
  padding-top: 8px;
}

.btn-reset {
  padding: 12px 24px;
  background: #f3f4f6;
  color: #374151;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 600;
  flex: 1;
}

.btn-reset:hover {
  background: #e5e7eb;
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
  flex: 2;
}

.btn-search:hover:not(:disabled) {
  background: #2563eb;
}

.btn-search:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.quick-destinations {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.quick-destinations h4 {
  margin: 0 0 12px 0;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.destination-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.tag-btn {
  padding: 6px 12px;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  border-radius: 20px;
  cursor: pointer;
  font-size: 13px;
  color: #6b7280;
  transition: all 0.2s;
}

.tag-btn:hover {
  background: #e5e7eb;
  border-color: #d1d5db;
  color: #374151;
}

@media (max-width: 640px) {
  .form-row {
    flex-direction: column;
  }
  
  .checkbox-group {
    grid-template-columns: 1fr;
  }
}
</style>
