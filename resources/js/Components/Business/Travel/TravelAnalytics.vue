<template>
  <div class="travel-analytics">
    <div class="analytics-header">
      <h2>Travel Analytics Dashboard</h2>
      <div class="date-range-selector">
        <select v-model="dateRange" @change="fetchAnalytics" class="date-select">
          <option value="7">Last 7 days</option>
          <option value="30">Last 30 days</option>
          <option value="90">Last 90 days</option>
          <option value="365">Last year</option>
        </select>
        <button @click="fetchAnalytics" class="btn-refresh">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading analytics...</p>
    </div>

    <div v-else class="analytics-content">
      <!-- Key Metrics -->
      <div class="metrics-grid">
        <div class="metric-card">
          <div class="metric-icon metric-icon-blue">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
          </div>
          <div class="metric-content">
            <div class="metric-label">Total Bookings</div>
            <div class="metric-value">{{ analytics?.totalBookings || 0 }}</div>
            <div class="metric-change" :class="getChangeClass(analytics?.bookingsChange)">
              {{ formatChange(analytics?.bookingsChange) }}
            </div>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon metric-icon-green">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="metric-content">
            <div class="metric-label">Revenue</div>
            <div class="metric-value">{{ formatCurrency(analytics?.revenue || 0) }}</div>
            <div class="metric-change" :class="getChangeClass(analytics?.revenueChange)">
              {{ formatChange(analytics?.revenueChange) }}
            </div>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon metric-icon-purple">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </div>
          <div class="metric-content">
            <div class="metric-label">Average Order Value</div>
            <div class="metric-value">{{ formatCurrency(analytics?.avgOrderValue || 0) }}</div>
            <div class="metric-change" :class="getChangeClass(analytics?.avgOrderValueChange)">
              {{ formatChange(analytics?.avgOrderValueChange) }}
            </div>
          </div>
        </div>

        <div class="metric-card">
          <div class="metric-icon metric-icon-orange">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
          </div>
          <div class="metric-content">
            <div class="metric-label">Customer Satisfaction</div>
            <div class="metric-value">{{ analytics?.satisfaction || 0 }}%</div>
            <div class="metric-change" :class="getChangeClass(analytics?.satisfactionChange)">
              {{ formatChange(analytics?.satisfactionChange) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="charts-grid">
        <div class="chart-card">
          <h4>Bookings Over Time</h4>
          <div class="chart-container">
            <div class="chart-placeholder">
              <svg width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#d1d5db">
                <rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"/>
                <path d="M3 9h18M3 15h18M9 3v18M15 3v18" stroke-width="2"/>
              </svg>
              <p>Chart placeholder - integrate Chart.js or similar</p>
            </div>
          </div>
        </div>

        <div class="chart-card">
          <h4>Revenue by Destination</h4>
          <div class="chart-container">
            <div class="destination-list">
              <div v-for="(dest, index) in topDestinations" :key="index" class="destination-item">
                <span class="destination-name">{{ dest.name }}</span>
                <div class="destination-bar">
                  <div class="bar-fill" :style="{ width: dest.percentage + '%' }"></div>
                </div>
                <span class="destination-value">{{ formatCurrency(dest.value) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tables Section -->
      <div class="tables-grid">
        <div class="table-card">
          <h4>Top Tours</h4>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Tour</th>
                  <th>Bookings</th>
                  <th>Revenue</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(tour, index) in topTours" :key="index">
                  <td>{{ tour.name }}</td>
                  <td>{{ tour.bookings }}</td>
                  <td>{{ formatCurrency(tour.revenue) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="table-card">
          <h4>Recent Activity</h4>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Activity</th>
                  <th>Time</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(activity, index) in recentActivity" :key="index">
                  <td>{{ activity.type }}</td>
                  <td>{{ formatTime(activity.time) }}</td>
                  <td>
                    <span :class="['status-badge', activity.status]">{{ activity.status }}</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Analytics {
  totalBookings: number
  bookingsChange: number
  revenue: number
  revenueChange: number
  avgOrderValue: number
  avgOrderValueChange: number
  satisfaction: number
  satisfactionChange: number
}

const dateRange = ref('30')
const loading = ref(false)
const analytics = ref<Analytics | null>(null)

const topDestinations = ref([
  { name: 'Moscow', value: 1500000, percentage: 85 },
  { name: 'Saint Petersburg', value: 1200000, percentage: 68 },
  { name: 'Sochi', value: 900000, percentage: 51 },
  { name: 'Istanbul', value: 750000, percentage: 42 },
  { name: 'Dubai', value: 600000, percentage: 34 },
])

const topTours = ref([
  { name: 'Golden Ring Tour', bookings: 245, revenue: 12500000 },
  { name: 'Moscow City Tour', bookings: 189, revenue: 8900000 },
  { name: 'Hermitage Visit', bookings: 156, revenue: 7200000 },
  { name: 'Red Square Tour', bookings: 134, revenue: 5600000 },
  { name: 'Kremlin Tour', bookings: 112, revenue: 4800000 },
])

const recentActivity = ref([
  { type: 'New Booking', time: new Date(Date.now() - 300000), status: 'completed' },
  { type: 'Payment Received', time: new Date(Date.now() - 900000), status: 'completed' },
  { type: 'Tour Cancellation', time: new Date(Date.now() - 1800000), status: 'cancelled' },
  { type: 'New Booking', time: new Date(Date.now() - 3600000), status: 'pending' },
  { type: 'Review Posted', time: new Date(Date.now() - 7200000), status: 'completed' },
])

const fetchAnalytics = async () => {
  loading.value = true
  try {
    const response = await fetch(`/api/travel/analytics?days=${dateRange.value}`)
    const data = await response.json()
    if (data.success) {
      analytics.value = data.data
    }
  } catch (error) {
    console.error('Failed to fetch analytics:', error)
    // Set mock data for demo
    analytics.value = {
      totalBookings: 847,
      bookingsChange: 12.5,
      revenue: 34500000,
      revenueChange: 8.3,
      avgOrderValue: 40732,
      avgOrderValueChange: -2.1,
      satisfaction: 94,
      satisfactionChange: 3.2,
    }
  } finally {
    loading.value = false
  }
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

const formatChange = (change: number | undefined): string => {
  if (!change) return 'No change'
  const sign = change > 0 ? '+' : ''
  return `${sign}${change}%`
}

const getChangeClass = (change: number | undefined): string => {
  if (!change) return 'neutral'
  return change > 0 ? 'positive' : 'negative'
}

const formatTime = (date: Date): string => {
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  
  if (minutes < 1) return 'Just now'
  if (minutes < 60) return `${minutes}m ago`
  if (hours < 24) return `${hours}h ago`
  return date.toLocaleDateString()
}

onMounted(() => {
  fetchAnalytics()
})
</script>

<style scoped>
.travel-analytics {
  padding: 24px;
  background: #f9fafb;
  min-height: 100vh;
}

.analytics-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.analytics-header h2 {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  color: #111827;
}

.date-range-selector {
  display: flex;
  gap: 8px;
}

.date-select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  background: white;
}

.btn-refresh {
  padding: 8px 12px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-refresh:hover {
  background: #f3f4f6;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  color: #6b7280;
}

.spinner {
  width: 40px;
  height: 40px;
  margin-bottom: 16px;
  border: 3px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.analytics-content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

.metric-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.metric-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
}

.metric-icon-blue {
  background: #3b82f6;
}

.metric-icon-green {
  background: #10b981;
}

.metric-icon-purple {
  background: #8b5cf6;
}

.metric-icon-orange {
  background: #f59e0b;
}

.metric-content {
  flex: 1;
}

.metric-label {
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 4px;
}

.metric-value {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 4px;
}

.metric-change {
  font-size: 12px;
  font-weight: 500;
}

.metric-change.positive {
  color: #10b981;
}

.metric-change.negative {
  color: #ef4444;
}

.metric-change.neutral {
  color: #6b7280;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 20px;
}

.chart-card,
.table-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.chart-card h4,
.table-card h4 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
}

.chart-container {
  min-height: 300px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chart-placeholder {
  text-align: center;
  color: #9ca3af;
}

.chart-placeholder p {
  margin-top: 12px;
  font-size: 14px;
}

.destination-list {
  width: 100%;
}

.destination-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid #f3f4f6;
}

.destination-item:last-child {
  border-bottom: none;
}

.destination-name {
  width: 120px;
  font-size: 14px;
  color: #374151;
}

.destination-bar {
  flex: 1;
  height: 8px;
  background: #f3f4f6;
  border-radius: 4px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  background: linear-gradient(to right, #3b82f6, #8b5cf6);
  border-radius: 4px;
  transition: width 0.3s ease;
}

.destination-value {
  width: 100px;
  text-align: right;
  font-size: 14px;
  font-weight: 600;
  color: #111827;
}

.tables-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 20px;
}

.table-container {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th {
  text-align: left;
  padding: 12px;
  font-size: 13px;
  font-weight: 600;
  color: #6b7280;
  border-bottom: 2px solid #e5e7eb;
}

.data-table td {
  padding: 12px;
  font-size: 14px;
  color: #374151;
  border-bottom: 1px solid #f3f4f6;
}

.data-table tr:last-child td {
  border-bottom: none;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge.completed {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.cancelled {
  background: #fee2e2;
  color: #991b1b;
}

@media (max-width: 768px) {
  .metrics-grid {
    grid-template-columns: 1fr;
  }
  
  .charts-grid,
  .tables-grid {
    grid-template-columns: 1fr;
  }
  
  .analytics-header {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }
}
</style>
