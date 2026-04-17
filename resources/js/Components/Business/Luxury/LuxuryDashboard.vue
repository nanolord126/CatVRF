<template>
  <div class="luxury-dashboard">
    <div class="header">
      <h1>Luxury Vertical Dashboard</h1>
      <button @click="refresh" class="btn-primary">Refresh</button>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <h3>VIP Clients</h3>
        <p class="stat-value">{{ stats.vipClients }}</p>
      </div>
      <div class="stat-card">
        <h3>Active Bookings</h3>
        <p class="stat-value">{{ stats.activeBookings }}</p>
      </div>
      <div class="stat-card">
        <h3>Revenue This Month</h3>
        <p class="stat-value">{{ formatCurrency(stats.revenueMonth) }}</p>
      </div>
      <div class="stat-card">
        <h3>Average Order Value</h3>
        <p class="stat-value">{{ formatCurrency(stats.avgOrderValue) }}</p>
      </div>
    </div>

    <div class="content-grid">
      <div class="card">
        <h2>Recent Bookings</h2>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Booking ID</th>
                <th>Client</th>
                <th>Service</th>
                <th>Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="booking in recentBookings" :key="booking.id">
                <td>{{ booking.id }}</td>
                <td>{{ booking.client }}</td>
                <td>{{ booking.service }}</td>
                <td>{{ formatCurrency(booking.amount) }}</td>
                <td>
                  <span :class="['status-badge', booking.status]">{{ booking.status }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <h2>Popular Services</h2>
        <div class="service-list">
          <div v-for="service in popularServices" :key="service.name" class="service-item">
            <div class="service-info">
              <h4>{{ service.name }}</h4>
              <p>{{ service.bookings }} bookings</p>
            </div>
            <div class="service-revenue">
              {{ formatCurrency(service.revenue) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Stats {
  vipClients: number
  activeBookings: number
  revenueMonth: number
  avgOrderValue: number
}

interface Booking {
  id: number
  client: string
  service: string
  amount: number
  status: string
}

interface Service {
  name: string
  bookings: number
  revenue: number
}

const stats = ref<Stats>({
  vipClients: 0,
  activeBookings: 0,
  revenueMonth: 0,
  avgOrderValue: 0
})

const recentBookings = ref<Booking[]>([])
const popularServices = ref<Service[]>([])

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const refresh = async () => {
  await fetchData()
}

const fetchData = async () => {
  try {
    const response = await fetch('/api/luxury/dashboard')
    const data = await response.json()
    stats.value = data.stats
    recentBookings.value = data.recentBookings
    popularServices.value = data.popularServices
  } catch (error) {
    console.error('Failed to fetch luxury dashboard data:', error)
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.luxury-dashboard {
  padding: 24px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header h1 {
  margin: 0;
  font-size: 24px;
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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-card h3 {
  margin: 0 0 8px 0;
  font-size: 14px;
  color: #6b7280;
  font-weight: 500;
}

.stat-value {
  margin: 0;
  font-size: 28px;
  font-weight: 600;
  color: #1f2937;
}

.content-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}

.card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card h2 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
}

.table-container {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

th {
  font-weight: 600;
  color: #6b7280;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge.confirmed {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.completed {
  background: #dbeafe;
  color: #1e40af;
}

.service-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.service-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
}

.service-info h4 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.service-info p {
  margin: 0;
  font-size: 12px;
  color: #6b7280;
}

.service-revenue {
  font-size: 14px;
  font-weight: 600;
  color: #059669;
}
</style>
