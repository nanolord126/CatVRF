<template>
  <div class="travel-destinations">
    <div class="header">
      <h2>Popular Destinations</h2>
      <button @click="addDestination" class="btn-primary">Add Destination</button>
    </div>

    <div class="filters">
      <input 
        v-model="searchQuery" 
        type="text" 
        placeholder="Search destinations..." 
        class="search-input"
      />
      <select v-model="countryFilter">
        <option value="">All Countries</option>
        <option v-for="country in uniqueCountries" :key="country" :value="country">
          {{ country }}
        </option>
      </select>
      <div class="price-range">
        <input 
          v-model="minPrice" 
          type="number" 
          placeholder="Min Price" 
          class="price-input"
        />
        <span>-</span>
        <input 
          v-model="maxPrice" 
          type="number" 
          placeholder="Max Price" 
          class="price-input"
        />
      </div>
      <label class="checkbox-filter">
        <input type="checkbox" v-model="popularOnly" />
        Popular Only
      </label>
      <label class="checkbox-filter">
        <input type="checkbox" v-model="highRatedOnly" />
        High Rated (4.5+)
      </label>
      <button @click="resetFilters" class="btn-reset">Reset</button>
    </div>

    <div class="destinations-grid">
      <div v-for="destination in filteredDestinations" :key="destination.id" class="destination-card">
        <div class="destination-image">
          <img :src="destination.image" :alt="destination.name" />
          <span class="badge-popular" v-if="destination.isPopular">POPULAR</span>
        </div>
        <div class="destination-details">
          <h3>{{ destination.name }}</h3>
          <p class="country">{{ destination.country }}</p>
          <p class="description">{{ destination.description }}</p>
          <div class="stats">
            <span class="rating">⭐ {{ destination.rating }}</span>
            <span class="bookings">{{ destination.bookings }} bookings</span>
          </div>
          <div class="price">{{ formatCurrency(destination.min_price) }}</div>
        </div>
        <div class="destination-actions">
          <button @click="viewDestination(destination)" class="btn-sm">View</button>
          <button @click="editDestination(destination)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Destination {
  id: number
  name: string
  country: string
  description: string
  rating: number
  bookings: number
  min_price: number
  isPopular: boolean
  image: string
}

const destinations = ref<Destination[]>([])
const searchQuery = ref('')
const countryFilter = ref('')
const minPrice = ref<number | null>(null)
const maxPrice = ref<number | null>(null)
const popularOnly = ref(false)
const highRatedOnly = ref(false)

const uniqueCountries = computed(() => {
  const countries = destinations.value.map(d => d.country)
  return [...new Set(countries)].sort()
})

const filteredDestinations = computed(() => {
  return destinations.value.filter(destination => {
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase()
      if (!destination.name.toLowerCase().includes(query) && 
          !destination.country.toLowerCase().includes(query) &&
          !destination.description.toLowerCase().includes(query)) {
        return false
      }
    }
    
    if (countryFilter.value && destination.country !== countryFilter.value) return false
    
    if (minPrice.value !== null && destination.min_price < minPrice.value) return false
    if (maxPrice.value !== null && destination.min_price > maxPrice.value) return false
    
    if (popularOnly.value && !destination.isPopular) return false
    if (highRatedOnly.value && destination.rating < 4.5) return false
    
    return true
  })
})

const resetFilters = () => {
  searchQuery.value = ''
  countryFilter.value = ''
  minPrice.value = null
  maxPrice.value = null
  popularOnly.value = false
  highRatedOnly.value = false
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const addDestination = () => {
  // Open modal to add new destination
}

const viewDestination = (destination: Destination) => {
  // Open destination details
}

const editDestination = (destination: Destination) => {
  // Open edit modal
}

const fetchDestinations = async () => {
  try {
    const response = await fetch('/api/travel/destinations')
    const data = await response.json()
    destinations.value = data
  } catch (error) {
    console.error('Failed to fetch destinations:', error)
  }
}

onMounted(() => {
  fetchDestinations()
})
</script>

<style scoped>
.travel-destinations {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  flex-wrap: wrap;
  align-items: center;
}

.search-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  min-width: 250px;
}

.filters select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.price-range {
  display: flex;
  align-items: center;
  gap: 8px;
}

.price-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  width: 100px;
}

.checkbox-filter {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  cursor: pointer;
}

.btn-reset {
  padding: 8px 16px;
  background: #6b7280;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
}

.btn-reset:hover {
  background: #4b5563;
}

.destinations-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.destination-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.destination-image {
  position: relative;
  width: 100%;
  height: 200px;
}

.destination-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.badge-popular {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #f59e0b;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
}

.destination-details {
  padding: 16px;
}

.destination-details h3 {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
}

.country {
  margin: 0 0 8px 0;
  font-size: 12px;
  color: #6b7280;
}

.description {
  margin: 0 0 12px 0;
  font-size: 13px;
  color: #4b5563;
  line-height: 1.5;
}

.stats {
  display: flex;
  justify-content: space-between;
  margin-bottom: 12px;
  font-size: 13px;
}

.rating {
  color: #f59e0b;
  font-weight: 500;
}

.bookings {
  color: #6b7280;
}

.price {
  font-size: 18px;
  font-weight: 600;
  color: #059669;
}

.destination-actions {
  padding: 12px 16px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 8px;
}

.btn-sm {
  flex: 1;
  padding: 8px;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}
</style>
