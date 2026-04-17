<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

interface RideStatus {
  id: number
  status: string
  driver: any
  vehicle: any
  current_latitude: number
  current_longitude: number
  eta_minutes: number
  distance_remaining_meters: number
}

const props = defineProps<{
  rideId: number
}>()

const rideStatus = ref<RideStatus | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const refreshInterval = ref<number | null>(null)

const fetchRideStatus = async () => {
  try {
    const response = await axios.get(`/api/v1/taxi/rides/${props.rideId}/status`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'X-Correlation-ID': crypto.randomUUID()
      }
    })
    rideStatus.value = response.data
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to fetch ride status'
  } finally {
    loading.value = false
  }
}

const cancelRide = async () => {
  if (!confirm('Are you sure you want to cancel this ride?')) return

  try {
    await axios.post(`/api/v1/taxi/rides/${props.rideId}/cancel`, {}, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'X-Correlation-ID': crypto.randomUUID()
      }
    })
    alert('Ride cancelled successfully')
    if (refreshInterval.value) {
      clearInterval(refreshInterval.value)
    }
    await fetchRideStatus()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to cancel ride')
  }
}

const callDriver = () => {
  alert('Video call feature coming soon via WebRTC')
}

const getStatusColor = (status: string): string => {
  return match (status) {
    'searching' => '#f59e0b',
    'driver_assigned' => '#3b82f6',
    'arriving' => '#8b5cf6',
    'in_progress' => '#10b981',
    'completed' => '#6b7280',
    'cancelled' => '#ef4444',
    default => '#6b7280',
  }
}

const getStatusText = (status: string): string => {
  return match (status) {
    'searching' => 'Searching for driver...',
    'driver_assigned' => 'Driver assigned',
    'arriving' => 'Driver arriving',
    'in_progress' => 'In progress',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    default => status,
  }
}

onMounted(() => {
  fetchRideStatus()
  refreshInterval.value = window.setInterval(fetchRideStatus, 10000)
})

onUnmounted(() => {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value)
  }
})
</script>

<template>
  <div class="ride-tracking">
    <h2>Ride Tracking</h2>
    
    <div v-if="loading" class="loading">Loading ride status...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    
    <div v-else-if="rideStatus" class="tracking-content">
      <div class="status-header">
        <div 
          class="status-badge" 
          :style="{ backgroundColor: getStatusColor(rideStatus.status) }"
        >
          {{ getStatusText(rideStatus.status) }}
        </div>
        <div class="eta-info" v-if="rideStatus.eta_minutes">
          ETA: {{ rideStatus.eta_minutes }} min
        </div>
      </div>

      <div class="driver-info" v-if="rideStatus.driver">
        <div class="driver-avatar">
          {{ rideStatus.driver.name.charAt(0) }}
        </div>
        <div class="driver-details">
          <h3>{{ rideStatus.driver.name }}</h3>
          <p>⭐ {{ rideStatus.driver.rating.toFixed(1) }}</p>
        </div>
        <button @click="callDriver" class="btn-call">📞 Call</button>
      </div>

      <div class="vehicle-info" v-if="rideStatus.vehicle">
        <div class="vehicle-details">
          <span class="vehicle-class">{{ rideStatus.vehicle.vehicle_class }}</span>
          <span class="vehicle-model">{{ rideStatus.vehicle.model }}</span>
          <span class="vehicle-plate">{{ rideStatus.vehicle.license_plate }}</span>
        </div>
        <span class="vehicle-color" :style="{ backgroundColor: rideStatus.vehicle.color }"></span>
      </div>

      <div class="progress-info">
        <div class="distance-remaining">
          Distance remaining: {{ (rideStatus.distance_remaining_meters / 1000).toFixed(1) }} km
        </div>
        <div class="progress-bar">
          <div 
            class="progress-fill" 
            :style="{ width: `${(1 - rideStatus.distance_remaining_meters / 5000) * 100}%` }"
          ></div>
        </div>
      </div>

      <div class="map-placeholder">
        <p>🗺️ Map view with real-time driver tracking</p>
      </div>

      <div class="action-buttons" v-if="['searching', 'driver_assigned', 'arriving'].includes(rideStatus.status)">
        <button @click="cancelRide" class="btn-cancel">Cancel Ride</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ride-tracking {
  max-width: 600px;
  margin: 0 auto;
  padding: 2rem;
}

.loading, .error {
  text-align: center;
  padding: 2rem;
}

.error {
  color: #ef4444;
}

.tracking-content {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
}

.status-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  color: white;
  font-weight: 600;
}

.eta-info {
  font-size: 1.125rem;
  font-weight: 600;
}

.driver-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.driver-avatar {
  width: 48px;
  height: 48px;
  background: #3b82f6;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  font-weight: bold;
}

.driver-details h3 {
  margin: 0 0 0.25rem 0;
}

.driver-details p {
  margin: 0;
  color: #6b7280;
}

.btn-call {
  margin-left: auto;
  padding: 0.5rem 1rem;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.vehicle-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}

.vehicle-details {
  display: flex;
  gap: 1rem;
}

.vehicle-class {
  padding: 0.125rem 0.5rem;
  background: #dbeafe;
  color: #1e40af;
  border-radius: 9999px;
  font-weight: 600;
}

.vehicle-color {
  width: 32px;
  height: 32px;
  border-radius: 4px;
  border: 1px solid #e5e7eb;
}

.progress-info {
  margin-bottom: 1.5rem;
}

.distance-remaining {
  text-align: center;
  margin-bottom: 0.5rem;
  color: #6b7280;
}

.progress-bar {
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #10b981);
  transition: width 0.5s ease;
}

.map-placeholder {
  height: 300px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.125rem;
  margin-bottom: 1.5rem;
}

.action-buttons {
  display: flex;
  gap: 1rem;
}

.btn-cancel {
  flex: 1;
  padding: 0.75rem;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}
</style>
