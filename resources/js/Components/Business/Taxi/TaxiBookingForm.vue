<template>
  <div class="taxi-booking-form">
    <h3>Book a Ride</h3>
    
    <form @submit.prevent="submitBooking">
      <!-- Pickup Location -->
      <div class="form-group">
        <label>Pickup Location</label>
        <div class="location-input">
          <input 
            v-model="form.pickup_address"
            type="text" 
            placeholder="Enter pickup address"
            required
          />
          <button type="button" class="btn-icon" @click="useCurrentLocation">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Dropoff Location -->
      <div class="form-group">
        <label>Dropoff Location</label>
        <div class="location-input">
          <input 
            v-model="form.dropoff_address"
            type="text" 
            placeholder="Enter dropoff address"
            required
          />
          <button type="button" class="btn-icon" @click="openMap">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Favorite Locations -->
      <div v-if="favoriteLocations.length" class="form-group">
        <label>Favorite Locations</label>
        <div class="favorites">
          <button 
            v-for="fav in favoriteLocations" 
            :key="fav.uuid"
            type="button"
            class="favorite-btn"
            @click="selectFavorite(fav)"
          >
            {{ fav.name }}
          </button>
        </div>
      </div>

      <!-- Vehicle Class -->
      <div class="form-group">
        <label>Vehicle Class</label>
        <div class="vehicle-classes">
          <button 
            v-for="cls in vehicleClasses" 
            :key="cls.value"
            type="button"
            class="vehicle-class-btn"
            :class="{ active: form.vehicle_class === cls.value }"
            @click="form.vehicle_class = cls.value"
          >
            <span class="class-icon">{{ cls.icon }}</span>
            <span class="class-name">{{ cls.name }}</span>
            <span class="class-price">{{ formatPrice(cls.base_price) }}</span>
          </button>
        </div>
      </div>

      <!-- Payment Method -->
      <div class="form-group">
        <label>Payment Method</label>
        <div class="payment-methods">
          <button 
            v-for="method in paymentMethods" 
            :key="method.value"
            type="button"
            class="payment-method-btn"
            :class="{ active: form.payment_method === method.value }"
            @click="form.payment_method = method.value"
          >
            <span class="method-icon">{{ method.icon }}</span>
            <span class="method-name">{{ method.name }}</span>
          </button>
        </div>
      </div>

      <!-- Price Estimate -->
      <div v-if="priceEstimate" class="price-estimate">
        <div class="estimate-header">
          <span>Estimated Price</span>
          <span class="price">{{ formatPrice(priceEstimate.min) }} - {{ formatPrice(priceEstimate.max) }}</span>
        </div>
        <div class="estimate-details">
          <span>Distance: {{ formatDistance(priceEstimate.distance_km) }}</span>
          <span>ETA: {{ formatDuration(priceEstimate.duration_minutes) }}</span>
        </div>
        <div v-if="priceEstimate.surge_multiplier > 1" class="surge-badge">
          Surge: {{ (priceEstimate.surge_multiplier * 100).toFixed(0) }}x
        </div>
      </div>

      <!-- Submit Button -->
      <button type="submit" class="btn-submit" :disabled="loading">
        {{ loading ? 'Processing...' : 'Book Ride' }}
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface PriceEstimate {
  min: number
  max: number
  distance_km: number
  duration_minutes: number
  surge_multiplier: number
}

interface FavoriteLocation {
  uuid: string
  name: string
  address: string
  latitude: number
  longitude: number
  is_default: boolean
}

const props = defineProps<{
  userId: number
}>()

const form = ref({
  pickup_address: '',
  dropoff_address: '',
  pickup_lat: 0,
  pickup_lon: 0,
  dropoff_lat: 0,
  dropoff_lon: 0,
  vehicle_class: 'economy',
  payment_method: 'card'
})

const loading = ref(false)
const priceEstimate = ref<PriceEstimate | null>(null)
const favoriteLocations = ref<FavoriteLocation[]>([])

const vehicleClasses = [
  { value: 'economy', name: 'Economy', icon: '🚗', base_price: 150 },
  { value: 'comfort', name: 'Comfort', icon: '🚙', base_price: 250 },
  { value: 'business', name: 'Business', icon: '🚘', base_price: 400 },
  { value: 'van', name: 'Van', icon: '🚐', base_price: 500 }
]

const paymentMethods = [
  { value: 'card', name: 'Card', icon: '💳' },
  { value: 'cash', name: 'Cash', icon: '💵' },
  { value: 'wallet', name: 'Wallet', icon: '👛' }
]

const fetchFavoriteLocations = async () => {
  try {
    const response = await fetch(`/api/v1/taxi/clients/${props.userId}/dashboard`, {
      headers: { 'X-Correlation-ID': crypto.randomUUID() }
    })
    const data = await response.json()
    favoriteLocations.value = data.dashboard.favorite_locations || []
  } catch (error) {
    console.error('Failed to fetch favorites:', error)
  }
}

const useCurrentLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        form.value.pickup_lat = position.coords.latitude
        form.value.pickup_lon = position.coords.longitude
        // Reverse geocode to get address
        form.value.pickup_address = 'Current Location'
      },
      (error) => {
        console.error('Geolocation error:', error)
      }
    )
  }
}

const openMap = () => {
  // Open map picker
  console.log('Open map picker')
}

const selectFavorite = (fav: FavoriteLocation) => {
  form.value.pickup_address = fav.address
  form.value.pickup_lat = fav.latitude
  form.value.pickup_lon = fav.longitude
}

const calculatePrice = async () => {
  if (!form.value.pickup_lat || !form.value.dropoff_lat) return
  
  try {
    const response = await fetch('/api/v1/taxi/geo/route/calculate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': crypto.randomUUID()
      },
      body: JSON.stringify({
        pickup_lat: form.value.pickup_lat,
        pickup_lon: form.value.pickup_lon,
        dropoff_lat: form.value.dropoff_lat,
        dropoff_lon: form.value.dropoff_lon
      })
    })
    const data = await response.json()
    const route = data.route
    
    // Calculate price based on vehicle class
    const vehicleClass = vehicleClasses.find(c => c.value === form.value.vehicle_class)
    const basePrice = vehicleClass?.base_price || 150
    const surgeMultiplier = route.traffic_factor || 1
    
    priceEstimate.value = {
      min: Math.round(basePrice * (1 + route.distance_km * 10) * surgeMultiplier),
      max: Math.round(basePrice * (1 + route.distance_km * 15) * surgeMultiplier),
      distance_km: route.distance_km,
      duration_minutes: route.duration_minutes,
      surge_multiplier: surgeMultiplier
    }
  } catch (error) {
    console.error('Failed to calculate price:', error)
  }
}

const submitBooking = async () => {
  loading.value = true
  try {
    const response = await fetch('/api/v1/taxi/rides', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': crypto.randomUUID()
      },
      body: JSON.stringify(form.value)
    })
    
    if (response.ok) {
      const data = await response.json()
      // Navigate to ride tracking
      console.log('Ride booked:', data)
    }
  } catch (error) {
    console.error('Failed to book ride:', error)
  } finally {
    loading.value = false
  }
}

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', { 
    style: 'currency', 
    currency: 'RUB' 
  }).format(price)
}

const formatDistance = (km: number): string => {
  return `${km.toFixed(1)} km`
}

const formatDuration = (minutes: number): string => {
  if (minutes < 60) {
    return `${Math.round(minutes)} min`
  }
  return `${Math.floor(minutes / 60)}h ${Math.round(minutes % 60)}m`
}

// Watch for address changes to calculate coordinates
// In a real app, you would use a geocoding service
const updateCoordinates = () => {
  // Placeholder for geocoding
  calculatePrice()
}

onMounted(() => {
  fetchFavoriteLocations()
})
</script>

<style scoped>
.taxi-booking-form {
  padding: 20px;
}

.taxi-booking-form h3 {
  margin: 0 0 20px 0;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  font-size: 14px;
}

.location-input {
  display: flex;
  gap: 8px;
}

.location-input input {
  flex: 1;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.btn-icon {
  padding: 10px;
  background: #f0f0f0;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.favorites {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.favorite-btn {
  padding: 6px 12px;
  background: #f0f0f0;
  border: none;
  border-radius: 16px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.2s;
}

.favorite-btn:hover {
  background: #e0e0e0;
}

.vehicle-classes {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 12px;
}

.vehicle-class-btn {
  padding: 16px;
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.vehicle-class-btn:hover {
  border-color: #3b82f6;
}

.vehicle-class-btn.active {
  border-color: #3b82f6;
  background: #eff6ff;
}

.class-icon {
  font-size: 24px;
}

.class-name {
  font-weight: 600;
  font-size: 12px;
}

.class-price {
  font-size: 12px;
  color: #666;
}

.payment-methods {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 12px;
}

.payment-method-btn {
  padding: 12px;
  background: #f9fafb;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.payment-method-btn:hover {
  border-color: #3b82f6;
}

.payment-method-btn.active {
  border-color: #3b82f6;
  background: #eff6ff;
}

.method-icon {
  font-size: 20px;
}

.method-name {
  font-size: 12px;
  font-weight: 600;
}

.price-estimate {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;
}

.estimate-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
  font-weight: 600;
}

.estimate-header .price {
  font-size: 20px;
  color: #16a34a;
}

.estimate-details {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #666;
}

.surge-badge {
  display: inline-block;
  margin-top: 8px;
  padding: 4px 8px;
  background: #fef3c7;
  color: #92400e;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.btn-submit {
  width: 100%;
  padding: 14px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-submit:hover:not(:disabled) {
  background: #2563eb;
}

.btn-submit:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}
</style>
