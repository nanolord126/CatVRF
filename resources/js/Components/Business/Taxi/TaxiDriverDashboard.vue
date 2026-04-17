<template>
  <div class="taxi-driver-dashboard">
    <div class="dashboard-header">
      <h2>Driver Dashboard</h2>
      <div class="availability-toggle">
        <label>Online</label>
        <Toggle 
          :model-value="isAvailable" 
          @update:model-value="toggleAvailability"
        />
      </div>
    </div>

    <div class="dashboard-grid">
      <!-- Today's Stats -->
      <div class="card today-stats">
        <h3>Today</h3>
        <div class="stats-grid">
          <div class="stat-item">
            <span class="stat-value">{{ dashboard.today?.rides || 0 }}</span>
            <span class="stat-label">Rides</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">{{ formatCurrency(dashboard.today?.earnings_rubles || 0) }}</span>
            <span class="stat-label">Earnings</span>
          </div>
          <div class="stat-item">
            <span class="stat-value">{{ dashboard.today?.completion_rate?.toFixed(1) || 0 }}%</span>
            <span class="stat-label">Completion</span>
          </div>
        </div>
      </div>

      <!-- Wallet -->
      <div class="card wallet">
        <h3>Wallet</h3>
        <div class="balance">
          <span class="balance-amount">{{ formatCurrency(dashboard.wallet?.balance_rubles || 0) }}</span>
          <span class="balance-label">Available Balance</span>
        </div>
        <div class="pending">
          <span class="pending-amount">{{ formatCurrency(dashboard.wallet?.pending_withdrawals_rubles || 0) }}</span>
          <span class="pending-label">Pending Withdrawals</span>
        </div>
        <button class="btn btn-primary" @click="requestWithdrawal">Withdraw</button>
      </div>

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
          <div class="driver-info">
            <span class="label">Status:</span>
            <span class="status">{{ dashboard.active_ride.status }}</span>
          </div>
        </div>
        <div class="ride-actions">
          <button class="btn btn-success" @click="startRide" v-if="dashboard.active_ride.status === 'accepted'">
            Start Ride
          </button>
          <button class="btn btn-warning" @click="completeRide" v-if="dashboard.active_ride.status === 'started'">
            Complete Ride
          </button>
        </div>
      </div>

      <!-- Schedule -->
      <div v-if="dashboard.schedule" class="card schedule">
        <h3>Today's Schedule</h3>
        <div class="schedule-info">
          <div class="time">
            <span class="label">Start:</span>
            <span>{{ formatTime(dashboard.schedule.start_time) }}</span>
          </div>
          <div class="time">
            <span class="label">End:</span>
            <span>{{ formatTime(dashboard.schedule.end_time) }}</span>
          </div>
          <div class="targets">
            <div class="target">
              <span class="label">Target Rides:</span>
              <span>{{ dashboard.schedule.target_rides }}</span>
            </div>
            <div class="target">
              <span class="label">Target Earnings:</span>
              <span>{{ formatCurrency(dashboard.schedule.target_earnings_rubles) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Toggle from '@/Components/UI/Toggle.vue'

interface Dashboard {
  today?: {
    rides: number
    earnings_rubles: number
    completion_rate: number
  }
  wallet?: {
    balance_rubles: number
    pending_withdrawals_rubles: number
  }
  active_ride?: {
    uuid: string
    status: string
    pickup_address: string
    dropoff_address: string
  }
  schedule?: {
    start_time: string
    end_time: string
    target_rides: number
    target_earnings_rubles: number
  }
}

const props = defineProps<{
  driverId: number
}>()

const dashboard = ref<Dashboard>({})
const isAvailable = ref(false)
const loading = ref(false)

const fetchDashboard = async () => {
  loading.value = true
  try {
    const response = await fetch(`/api/v1/taxi/drivers/${props.driverId}/dashboard`, {
      headers: {
        'X-Correlation-ID': crypto.randomUUID()
      }
    })
    const data = await response.json()
    dashboard.value = data.dashboard
    isAvailable.value = data.dashboard.driver?.is_available || false
  } catch (error) {
    console.error('Failed to fetch dashboard:', error)
  } finally {
    loading.value = false
  }
}

const toggleAvailability = async (available: boolean) => {
  try {
    await fetch(`/api/v1/taxi/drivers/${props.driverId}/availability`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': crypto.randomUUID()
      },
      body: JSON.stringify({ available })
    })
    isAvailable.value = available
  } catch (error) {
    console.error('Failed to toggle availability:', error)
  }
}

const requestWithdrawal = () => {
  // Open withdrawal modal
  console.log('Request withdrawal')
}

const startRide = () => {
  // Start ride logic
  console.log('Start ride')
}

const completeRide = () => {
  // Complete ride logic
  console.log('Complete ride')
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', { 
    style: 'currency', 
    currency: 'RUB' 
  }).format(amount)
}

const formatTime = (dateString: string): string => {
  return new Date(dateString).toLocaleTimeString('ru-RU', {
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
.taxi-driver-dashboard {
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

.availability-toggle {
  display: flex;
  align-items: center;
  gap: 12px;
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

.today-stats .stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.stat-item {
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
}

.stat-label {
  font-size: 12px;
  color: #666;
}

.wallet .balance,
.wallet .pending {
  margin-bottom: 16px;
}

.balance-amount,
.pending-amount {
  display: block;
  font-size: 28px;
  font-weight: 700;
  color: #10b981;
}

.pending-amount {
  color: #f59e0b;
  font-size: 20px;
}

.balance-label,
.pending-label {
  font-size: 12px;
  color: #666;
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
}

.driver-info .status {
  text-transform: capitalize;
  font-weight: 600;
}

.ride-actions {
  display: flex;
  gap: 8px;
}

.schedule .schedule-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.time,
.target {
  display: flex;
  justify-content: space-between;
}

.time .label,
.target .label {
  color: #666;
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

.btn-success {
  background: #10b981;
  color: white;
}

.btn-warning {
  background: #f59e0b;
  color: white;
}
</style>
