<template>
  <div class="taxi-client-dashboard">
    <div class="dashboard-header">
      <h2>Taxi</h2>
      <button class="btn btn-primary" @click="openBookingModal">Book a Ride</button>
    </div>

    <div class="dashboard-grid">
      <!-- Active Ride -->
      <div v-if="dashboard.active_ride" class="card active-ride">
        <h3>Active Ride</h3>
        <div class="ride-info">
          <div class="location">
            <span class="label">Pickup:</span>
            <span class="address">{{ dashboard.active_ride.pickup_address }}</span>
          </div>
          <div class="location">
            <span class="label">Dropoff:</span>
            <span class="address">{{ dashboard.active_ride.dropoff_address }}</span>
          </div>
          <div class="driver-info" v-if="dashboard.active_ride.driver">
            <div class="driver">
              <span class="label">Driver:</span>
              <span class="name">{{ dashboard.active_ride.driver.name }}</span>
              <span class="rating">⭐ {{ dashboard.active_ride.driver.rating }}</span>
            </div>
            <div class="vehicle" v-if="dashboard.active_ride.driver.vehicle">
              <span class="label">Vehicle:</span>
              <span>{{ dashboard.active_ride.driver.vehicle.brand }} {{ dashboard.active_ride.driver.vehicle.model }}</span>
              <span class="plate">{{ dashboard.active_ride.driver.vehicle.license_plate }}</span>
            </div>
          </div>
          <div class="status">
            <span class="status-badge" :class="dashboard.active_ride.status">
              {{ dashboard.active_ride.status }}
            </span>
          </div>
        </div>
        <div class="ride-actions">
          <button class="btn btn-danger" @click="cancelRide" v-if="canCancelRide">
            Cancel Ride
          </button>
        </div>
      </div>

      <!-- Recent Rides -->
      <div class="card recent-rides">
        <h3>Recent Rides</h3>
        <div v-if="dashboard.recent_rides?.length" class="rides-list">
          <div v-for="ride in dashboard.recent_rides" :key="ride.uuid" class="ride-item">
            <div class="ride-route">
              <span class="pickup">{{ ride.pickup_address }}</span>
              <span class="arrow">→</span>
              <span class="dropoff">{{ ride.dropoff_address }}</span>
            </div>
            <div class="ride-details">
              <span class="price">{{ formatCurrency(ride.price_rubles) }}</span>
              <span class="date">{{ formatDate(ride.created_at) }}</span>
            </div>
          </div>
        </div>
        <div v-else class="empty-state">
          <p>No recent rides</p>
        </div>
        <button class="btn btn-link" @click="viewAllRides">View All Rides</button>
      </div>

      <!-- Favorite Locations -->
      <div v-if="dashboard.favorite_locations?.length" class="card favorites">
        <h3>Favorite Locations</h3>
        <div class="favorites-list">
          <div v-for="fav in dashboard.favorite_locations" :key="fav.uuid" class="favorite-item">
            <span class="name">{{ fav.name }}</span>
            <span class="address">{{ fav.address }}</span>
            <span v-if="fav.is_default" class="default-badge">Default</span>
          </div>
        </div>
        <button class="btn btn-link" @click="manageFavorites">Manage Favorites</button>
      </div>

      <!-- Favorite Drivers -->
      <div v-if="dashboard.favorite_drivers?.length" class="card favorite-drivers">
        <h3>Favorite Drivers</h3>
        <div class="drivers-list">
          <div v-for="fav in dashboard.favorite_drivers" :key="fav.uuid" class="driver-item">
            <span class="name">{{ fav.driver_name }}</span>
            <span class="rating">⭐ {{ fav.driver_rating }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'

interface Dashboard {
  active_ride?: {
    uuid: string
    status: string
    pickup_address: string
    dropoff_address: string
    driver?: {
      name: string
      rating: number
      vehicle?: {
        brand: string
        model: string
        license_plate: string
      }
    }
  }
  recent_rides?: Array<{
    uuid: string
    pickup_address: string
    dropoff_address: string
    price_rubles: number
    created_at: string
  }>
  favorite_locations?: Array<{
    uuid: string
    name: string
    address: string
    is_default: boolean
  }>
  favorite_drivers?: Array<{
    uuid: string
    driver_name: string
    driver_rating: number
  }>
}

const props = defineProps<{
  userId: number
}>()

const dashboard = ref<Dashboard>({})
const loading = ref(false)

const canCancelRide = computed(() => {
  return dashboard.value.active_ride?.status === 'pending' || 
         dashboard.value.active_ride?.status === 'accepted'
})

const fetchDashboard = async () => {
  loading.value = true
  try {
    const response = await fetch(`/api/v1/taxi/clients/${props.userId}/dashboard`, {
      headers: {
        'X-Correlation-ID': crypto.randomUUID()
      }
    })
    const data = await response.json()
    dashboard.value = data.dashboard
  } catch (error) {
    console.error('Failed to fetch dashboard:', error)
  } finally {
    loading.value = false
  }
}

const openBookingModal = () => {
  // Open booking modal
  console.log('Open booking modal')
}

const cancelRide = () => {
  // Cancel ride logic
  console.log('Cancel ride')
}

const viewAllRides = () => {
  // Navigate to rides history
  console.log('View all rides')
}

const manageFavorites = () => {
  // Navigate to favorites management
  console.log('Manage favorites')
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', { 
    style: 'currency', 
    currency: 'RUB' 
  }).format(amount)
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  fetchDashboard()
  // Refresh dashboard every 30 seconds
  setInterval(fetchDashboard, 30000)
})
</script>

<style scoped>
.taxi-client-dashboard {
  padding: 20px;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.dashboard-header h2 {
  margin: 0;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.card h3 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
}

.active-ride .ride-info {
  margin-bottom: 16px;
}

.location {
  margin-bottom: 12px;
}

.location .label {
  font-weight: 600;
  margin-right: 8px;
}

.driver-info {
  margin-top: 12px;
  padding-top: 12px;
  border-top: 1px solid #eee;
}

.driver {
  margin-bottom: 8px;
}

.driver .name {
  font-weight: 600;
  margin-right: 8px;
}

.driver .rating {
  color: #f59e0b;
}

.vehicle .plate {
  background: #fef3c7;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.status {
  margin-top: 12px;
}

.status-badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.accepted {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.started {
  background: #d1fae5;
  color: #065f46;
}

.ride-actions {
  display: flex;
  gap: 8px;
}

.rides-list {
  max-height: 300px;
  overflow-y: auto;
}

.ride-item {
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
}

.ride-item:last-child {
  border-bottom: none;
}

.ride-route {
  margin-bottom: 8px;
}

.ride-route .pickup,
.ride-route .dropoff {
  font-size: 14px;
}

.ride-route .arrow {
  margin: 0 8px;
  color: #999;
}

.ride-details {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  color: #666;
}

.ride-details .price {
  font-weight: 600;
  color: #1a1a1a;
}

.empty-state {
  text-align: center;
  padding: 40px 0;
  color: #999;
}

.favorites-list {
  margin-bottom: 12px;
}

.favorite-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.favorite-item .name {
  font-weight: 600;
}

.favorite-item .address {
  font-size: 12px;
  color: #666;
}

.default-badge {
  background: #d1fae5;
  color: #065f46;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 10px;
  font-weight: 600;
}

.drivers-list .driver-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.driver-item .rating {
  color: #f59e0b;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-link {
  background: none;
  color: #3b82f6;
  text-decoration: none;
  padding: 0;
}
</style>
