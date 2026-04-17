<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

interface Location {
  latitude: number
  longitude: number
  address: string
}

interface Driver {
  driver_id: number
  name: string
  rating: number
  vehicle: any
  distance_meters: number
  eta_minutes: number
  score: number
}

const pickup = ref<Location>({ latitude: 0, longitude: 0, address: '' })
const dropoff = ref<Location>({ latitude: 0, longitude: 0, address: '' })
const vehicleClass = ref<'economy' | 'comfort' | 'business'>('economy')
const drivers = ref<Driver[]>([])
const loading = ref(false)
const selectedDriver = ref<Driver | null>(null)
const surgeMultiplier = ref(1.0)
const estimatedPrice = ref(0)
const booking = ref(false)

const fetchSurgeMultiplier = async () => {
  try {
    const response = await axios.get('/api/v1/taxi/surge/calculate', {
      params: {
        latitude: pickup.value.latitude,
        longitude: pickup.value.longitude,
      },
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'X-Correlation-ID': crypto.randomUUID()
      }
    })
    surgeMultiplier.value = response.data.multiplier
    calculateEstimatedPrice()
  } catch (err) {
    console.error('Failed to fetch surge multiplier:', err)
  }
}

const findDrivers = async () => {
  if (!pickup.value.latitude || !dropoff.value.latitude) {
    alert('Please enter pickup and dropoff locations')
    return
  }

  try {
    loading.value = true
    const response = await axios.get('/api/v1/taxi/drivers/find', {
      params: {
        pickup_latitude: pickup.value.latitude,
        pickup_longitude: pickup.value.longitude,
        vehicle_class: vehicleClass.value,
      },
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'X-Correlation-ID': crypto.randomUUID()
      }
    })
    drivers.value = response.data
  } catch (err) {
    console.error('Failed to find drivers:', err)
  } finally {
    loading.value = false
  }
}

const selectDriver = (driver: Driver) => {
  selectedDriver.value = driver
}

const calculateEstimatedPrice = () => {
  const basePrice = 150
  const distancePrice = (calculateDistance() / 1000) * 15
  const timePrice = 5 * 3
  estimatedPrice.value = Math.round((basePrice + distancePrice + timePrice) * surgeMultiplier.value)
}

const calculateDistance = (): number => {
  const R = 6371000
  const dLat = toRad(dropoff.value.latitude - pickup.value.latitude)
  const dLon = toRad(dropoff.value.longitude - pickup.value.longitude)
  const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
           Math.cos(toRad(pickup.value.latitude)) * Math.cos(toRad(dropoff.value.latitude)) *
           Math.sin(dLon / 2) * Math.sin(dLon / 2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
  return R * c
}

const toRad = (deg: number): number => deg * (Math.PI / 180)

const bookRide = async () => {
  if (!selectedDriver.value) {
    alert('Please select a driver')
    return
  }

  try {
    booking.value = true
    const response = await axios.post('/api/v1/taxi/rides', {
      passenger_id: localStorage.getItem('userId'),
      pickup_latitude: pickup.value.latitude,
      pickup_longitude: pickup.value.longitude,
      dropoff_latitude: dropoff.value.latitude,
      dropoff_longitude: dropoff.value.longitude,
      vehicle_class: vehicleClass.value,
      driver_id: selectedDriver.value.driver_id,
    }, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'X-Correlation-ID': crypto.randomUUID()
      }
    })
    alert('Ride booked successfully!')
    selectedDriver.value = null
    drivers.value = []
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to book ride')
  } finally {
    booking.value = false
  }
}

const getCurrentLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition((position) => {
      pickup.value.latitude = position.coords.latitude
      pickup.value.longitude = position.coords.longitude
      pickup.value.address = 'Current Location'
      fetchSurgeMultiplier()
    })
  }
}

onMounted(() => {
  getCurrentLocation()
})
</script>

<template>
  <div class="taxi-booking">
    <h2>Book a Taxi</h2>
    
    <div class="booking-form">
      <div class="location-inputs">
        <div class="input-group">
          <label>Pickup Location</label>
          <input v-model="pickup.address" type="text" placeholder="Enter pickup address" />
          <button @click="getCurrentLocation" class="btn-location">📍 Use Current Location</button>
        </div>
        
        <div class="input-group">
          <label>Dropoff Location</label>
          <input v-model="dropoff.address" type="text" placeholder="Enter dropoff address" />
        </div>
      </div>

      <div class="vehicle-selector">
        <label>Vehicle Class</label>
        <div class="vehicle-options">
          <button 
            v-for="cls in ['economy', 'comfort', 'business']" 
            :key="cls"
            @click="vehicleClass = cls as any"
            class="vehicle-option"
            :class="{ active: vehicleClass === cls }"
          >
            {{ cls.charAt(0).toUpperCase() + cls.slice(1) }}
          </button>
        </div>
      </div>

      <div class="pricing-info" v-if="surgeMultiplier > 1.0">
        <div class="surge-warning">
          ⚠️ Surge pricing active: {{ (surgeMultiplier * 100).toFixed(0) }}%
        </div>
        <div class="price-display">
          Estimated: {{ estimatedPrice }} ₽
        </div>
      </div>

      <button @click="findDrivers" :disabled="loading" class="btn-find">
        {{ loading ? 'Finding drivers...' : 'Find Drivers' }}
      </button>
    </div>

    <div v-if="drivers.length > 0" class="drivers-list">
      <h3>Available Drivers</h3>
      <div class="driver-cards">
        <div 
          v-for="driver in drivers" 
          :key="driver.driver_id"
          class="driver-card"
          :class="{ selected: selectedDriver?.driver_id === driver.driver_id }"
          @click="selectDriver(driver)"
        >
          <div class="driver-info">
            <div class="driver-name">{{ driver.name }}</div>
            <div class="driver-rating">⭐ {{ driver.rating.toFixed(1) }}</div>
          </div>
          <div class="driver-stats">
            <span class="stat">{{ driver.distance_meters }}m away</span>
            <span class="stat">{{ driver.eta_minutes }} min ETA</span>
          </div>
          <div class="vehicle-info" v-if="driver.vehicle">
            <span class="vehicle-class">{{ driver.vehicle.vehicle_class }}</span>
            <span class="vehicle-model">{{ driver.vehicle.model }}</span>
          </div>
        </div>
      </div>
    </div>

    <div v-if="selectedDriver" class="booking-actions">
      <button @click="bookRide" :disabled="booking" class="btn-book">
        {{ booking ? 'Booking...' : `Book Ride - ${estimatedPrice} ₽` }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.taxi-booking {
  max-width: 600px;
  margin: 0 auto;
  padding: 2rem;
}

.booking-form {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.location-inputs {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.input-group label {
  font-weight: 600;
}

.input-group input {
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
}

.btn-location {
  padding: 0.5rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 0.5rem;
}

.vehicle-selector {
  margin-bottom: 1.5rem;
}

.vehicle-selector label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.vehicle-options {
  display: flex;
  gap: 1rem;
}

.vehicle-option {
  flex: 1;
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  background: white;
}

.vehicle-option.active {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.pricing-info {
  background: #fef3c7;
  padding: 1rem;
  border-radius: 6px;
  margin-bottom: 1.5rem;
}

.surge-warning {
  color: #92400e;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.price-display {
  font-size: 1.25rem;
  font-weight: 700;
}

.btn-find, .btn-book {
  width: 100%;
  padding: 0.75rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.btn-find:disabled, .btn-book:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.drivers-list {
  margin-bottom: 2rem;
}

.driver-cards {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.driver-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1rem;
  cursor: pointer;
  transition: all 0.2s;
}

.driver-card:hover {
  border-color: #3b82f6;
}

.driver-card.selected {
  border-color: #10b981;
  background: #f0fdf4;
}

.driver-info {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.driver-name {
  font-weight: 600;
}

.driver-rating {
  color: #f59e0b;
}

.driver-stats {
  display: flex;
  gap: 1rem;
  color: #6b7280;
  font-size: 0.875rem;
}

.vehicle-info {
  margin-top: 0.5rem;
  display: flex;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.vehicle-class {
  padding: 0.125rem 0.5rem;
  background: #dbeafe;
  color: #1e40af;
  border-radius: 9999px;
  font-weight: 600;
}
</style>
