<template>
  <div class="travel-map-search">
    <div class="map-header">
      <h3>Search on Map</h3>
      <button @click="toggleMap" class="btn-toggle">
        {{ showMap ? 'Hide Map' : 'Show Map' }}
      </button>
    </div>

    <div v-if="showMap" class="map-container">
      <div class="map-filters">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Search destinations on map..." 
          class="search-input"
          @input="searchOnMap"
        />
        <select v-model="countryFilter" class="filter-select" @change="filterMapMarkers">
          <option value="">All Countries</option>
          <option v-for="country in uniqueCountries" :key="country" :value="country">
            {{ country }}
          </option>
        </select>
        <select v-model="priceRange" class="filter-select" @change="filterMapMarkers">
          <option value="">All Prices</option>
          <option value="budget">Budget (&lt;50k)</option>
          <option value="mid">Mid (50k-100k)</option>
          <option value="premium">Premium (100k+)</option>
        </select>
      </div>

      <div class="map-wrapper" ref="mapWrapper">
        <div v-if="loading" class="map-loading">Loading map...</div>
        <div v-else-if="mapError" class="map-error">{{ mapError }}</div>
        <div v-else id="travel-map" class="travel-map"></div>
      </div>

      <div class="map-legend">
        <div class="legend-item">
          <span class="legend-marker marker-budget"></span>
          <span>Budget (&lt;50k)</span>
        </div>
        <div class="legend-item">
          <span class="legend-marker marker-mid"></span>
          <span>Mid (50k-100k)</span>
        </div>
        <div class="legend-item">
          <span class="legend-marker marker-premium"></span>
          <span>Premium (100k+)</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

interface Destination {
  id: number
  name: string
  country: string
  latitude: number
  longitude: number
  min_price: number
  rating: number
  image: string
}

interface Props {
  destinations: Destination[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  (e: 'destinationSelected', destination: Destination): void
}>()

const showMap = ref(false)
const searchQuery = ref('')
const countryFilter = ref('')
const priceRange = ref('')
const loading = ref(false)
const mapError = ref('')
const mapWrapper = ref<HTMLElement | null>(null)
let map: any = null
let markers: any[] = []

const uniqueCountries = computed(() => {
  const countries = props.destinations.map(d => d.country)
  return [...new Set(countries)].sort()
})

const toggleMap = () => {
  showMap.value = !showMap.value
  if (showMap.value) {
    initMap()
  }
}

const initMap = async () => {
  loading.value = true
  mapError.value = ''
  
  try {
    // Load Leaflet library dynamically
    if (!window.L) {
      await loadLeaflet()
    }
    
    await nextTick()
    
    if (mapWrapper.value) {
      map = window.L.map('travel-map').setView([20, 0], 2)
      
      window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
      }).addTo(map)
      
      addMarkers()
    }
  } catch (error) {
    mapError.value = 'Failed to load map. Please try again.'
    console.error('Map initialization error:', error)
  } finally {
    loading.value = false
  }
}

const loadLeaflet = (): Promise<void> => {
  return new Promise((resolve, reject) => {
    const link = document.createElement('link')
    link.rel = 'stylesheet'
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
    document.head.appendChild(link)
    
    const script = document.createElement('script')
    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
    script.onload = () => resolve()
    script.onerror = reject
    document.head.appendChild(script)
  })
}

const addMarkers = () => {
  if (!map) return
  
  // Clear existing markers
  markers.forEach(marker => map.removeLayer(marker))
  markers = []
  
  const filteredDestinations = getFilteredDestinations()
  
  filteredDestinations.forEach(destination => {
    const priceCategory = getPriceCategory(destination.min_price)
    const markerColor = getMarkerColor(priceCategory)
    
    const markerIcon = window.L.divIcon({
      className: 'custom-marker',
      html: `<div style="background: ${markerColor}; width: 24px; height: 24px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>`,
      iconSize: [24, 24],
      iconAnchor: [12, 12]
    })
    
    const marker = window.L.marker([destination.latitude, destination.longitude], { icon: markerIcon })
      .addTo(map)
      .bindPopup(`
        <div class="map-popup">
          <img src="${destination.image}" style="width: 200px; height: 120px; object-fit: cover; border-radius: 4px;" />
          <h4>${destination.name}</h4>
          <p>${destination.country}</p>
          <p><strong>From:</strong> ${formatCurrency(destination.min_price)}</p>
          <p><strong>Rating:</strong> ⭐ ${destination.rating}</p>
          <button onclick="selectDestination(${destination.id})" class="btn-view">View Details</button>
        </div>
      `)
    
    markers.push(marker)
  })
  
  // Fit bounds to show all markers
  if (markers.length > 0) {
    const group = window.L.featureGroup(markers)
    map.fitBounds(group.getBounds().pad(0.1))
  }
}

const getFilteredDestinations = () => {
  return props.destinations.filter(destination => {
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase()
      if (!destination.name.toLowerCase().includes(query) && 
          !destination.country.toLowerCase().includes(query)) {
        return false
      }
    }
    
    if (countryFilter.value && destination.country !== countryFilter.value) return false
    
    if (priceRange.value) {
      const category = getPriceCategory(destination.min_price)
      if (category !== priceRange.value) return false
    }
    
    return true
  })
}

const getPriceCategory = (price: number): string => {
  if (price < 50000) return 'budget'
  if (price < 100000) return 'mid'
  return 'premium'
}

const getMarkerColor = (category: string): string => {
  const colors = {
    budget: '#10b981',
    mid: '#3b82f6',
    premium: '#f59e0b'
  }
  return colors[category as keyof typeof colors] || '#6b7280'
}

const searchOnMap = () => {
  filterMapMarkers()
}

const filterMapMarkers = () => {
  addMarkers()
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

// Expose function for popup button
;(window as any).selectDestination = (id: number) => {
  const destination = props.destinations.find(d => d.id === id)
  if (destination) {
    emit('destinationSelected', destination)
  }
}

onBeforeUnmount(() => {
  if (map) {
    map.remove()
    map = null
  }
})
</script>

<style scoped>
.travel-map-search {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.map-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.map-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.btn-toggle {
  padding: 8px 16px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
}

.btn-toggle:hover {
  background: #2563eb;
}

.map-container {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.map-filters {
  display: flex;
  gap: 12px;
  padding: 12px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
  flex-wrap: wrap;
}

.search-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  min-width: 250px;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  min-width: 150px;
}

.map-wrapper {
  position: relative;
  height: 500px;
}

.map-loading,
.map-error {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  font-size: 16px;
  color: #6b7280;
}

.map-error {
  color: #ef4444;
}

.travel-map {
  width: 100%;
  height: 100%;
}

.map-legend {
  display: flex;
  gap: 24px;
  padding: 12px;
  background: #f9fafb;
  border-top: 1px solid #e5e7eb;
  justify-content: center;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.legend-marker {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.marker-budget {
  background: #10b981;
}

.marker-mid {
  background: #3b82f6;
}

.marker-premium {
  background: #f59e0b;
}

:deep(.map-popup) {
  min-width: 220px;
}

:deep(.map-popup h4) {
  margin: 8px 0 4px 0;
  font-size: 16px;
}

:deep(.map-popup p) {
  margin: 4px 0;
  font-size: 14px;
}

.btn-view {
  width: 100%;
  padding: 8px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  margin-top: 8px;
}

.btn-view:hover {
  background: #2563eb;
}
</style>
