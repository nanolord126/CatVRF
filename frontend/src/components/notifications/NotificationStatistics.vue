<template>
  <div class="notification-statistics">
    <div class="header">
      <h2>Notification Statistics</h2>
      <div class="filters">
        <select v-model="filters.channel" @change="loadStatistics" class="filter-select">
          <option value="all">All Channels</option>
          <option v-for="channel in channels" :key="channel" :value="channel">
            {{ formatChannelName(channel) }}
          </option>
        </select>
        <input
          v-model="filters.from"
          type="date"
          class="filter-input"
          @change="loadStatistics"
        />
        <input
          v-model="filters.to"
          type="date"
          class="filter-input"
          @change="loadStatistics"
        />
        <button @click="exportStatistics" class="btn btn-secondary">
          Export
        </button>
      </div>
    </div>

    <!-- Overview Stats -->
    <div class="stats-overview">
      <div class="stat-card">
        <div class="stat-icon total">
          <i class="fas fa-bell"></i>
        </div>
        <div class="stat-info">
          <h3>{{ statistics.total }}</h3>
          <p>Total Sent</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon delivered">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
          <h3>{{ statistics.delivered }}</h3>
          <p>Delivered ({{ statistics.delivery_rate }}%)</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon failed">
          <i class="fas fa-times-circle"></i>
        </div>
        <div class="stat-info">
          <h3>{{ statistics.failed }}</h3>
          <p>Failed ({{ statistics.failure_rate }}%)</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon success">
          <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-info">
          <h3>{{ statistics.success_rate }}%</h3>
          <p>Success Rate</p>
        </div>
      </div>
    </div>

    <!-- Channel Performance -->
    <div class="section">
      <h3>Channel Performance</h3>
      <div class="chart-container">
        <canvas ref="channelChart"></canvas>
      </div>
    </div>

    <!-- Delivery Trend -->
    <div class="section">
      <h3>Delivery Trend (30 Days)</h3>
      <div class="chart-container">
        <canvas ref="trendChart"></canvas>
      </div>
    </div>

    <!-- Event Type Distribution -->
    <div class="section">
      <h3>Event Type Distribution</h3>
      <div class="chart-container">
        <canvas ref="eventChart"></canvas>
      </div>
    </div>

    <!-- Detailed Stats Table -->
    <div class="section">
      <h3>Detailed Statistics by Channel</h3>
      <table class="stats-table">
        <thead>
          <tr>
            <th>Channel</th>
            <th>Total</th>
            <th>Delivered</th>
            <th>Failed</th>
            <th>Delivery Rate</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(stats, channel) in statistics.by_channel" :key="channel">
            <td>{{ formatChannelName(channel) }}</td>
            <td>{{ stats.total }}</td>
            <td>{{ stats.delivered }}</td>
            <td>{{ stats.failed }}</td>
            <td>{{ stats.delivery_rate }}%</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import axios from 'axios'
import Chart from 'chart.js/auto'

interface Statistics {
  total: number
  sent: number
  delivered: number
  failed: number
  pending: number
  success_rate: number
  delivery_rate: number
  failure_rate: number
  by_channel: Record<string, any>
  by_event: any[]
}

const statistics = ref<Statistics>({
  total: 0,
  sent: 0,
  delivered: 0,
  failed: 0,
  pending: 0,
  success_rate: 0,
  delivery_rate: 0,
  failure_rate: 0,
  by_channel: {},
  by_event: []
})

const filters = ref({
  channel: 'all',
  from: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
  to: new Date().toISOString().split('T')[0]
})

const channels = ref<string[]>(['telegram', 'whatsapp', 'viber', 'kakaotalk', 'signal', 'wechat', 'vk', 'odnoklassniki', 'email', 'sms'])

const channelChart = ref<HTMLCanvasElement>()
const trendChart = ref<HTMLCanvasElement>()
const eventChart = ref<HTMLCanvasElement>()

let channelChartInstance: Chart | null = null
let trendChartInstance: Chart | null = null
let eventChartInstance: Chart | null = null

onMounted(async () => {
  await loadStatistics()
  await loadTrends()
})

const loadStatistics = async () => {
  try {
    const response = await axios.get('/api/notifications/statistics', {
      params: filters.value
    })
    statistics.value = response.data.statistics
    await nextTick()
    renderCharts()
  } catch (error) {
    console.error('Failed to load statistics:', error)
  }
}

const loadTrends = async () => {
  try {
    const response = await axios.get('/api/notifications/statistics/trends')
    const trends = response.data.trends
    await nextTick()
    renderTrendChart(trends)
  } catch (error) {
    console.error('Failed to load trends:', error)
  }
}

const renderCharts = () => {
  renderChannelChart()
  renderEventChart()
}

const renderChannelChart = () => {
  if (!channelChart.value) return

  if (channelChartInstance) {
    channelChartInstance.destroy()
  }

  const ctx = channelChart.value.getContext('2d')
  if (!ctx) return

  const labels = Object.keys(statistics.value.by_channel)
  const deliveredData = labels.map(ch => statistics.value.by_channel[ch]?.delivered || 0)
  const failedData = labels.map(ch => statistics.value.by_channel[ch]?.failed || 0)

  channelChartInstance = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels.map(formatChannelName),
      datasets: [
        {
          label: 'Delivered',
          data: deliveredData,
          backgroundColor: 'rgba(34, 197, 94, 0.5)',
          borderColor: 'rgb(34, 197, 94)',
          borderWidth: 1
        },
        {
          label: 'Failed',
          data: failedData,
          backgroundColor: 'rgba(239, 68, 68, 0.5)',
          borderColor: 'rgb(239, 68, 68)',
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  })
}

const renderTrendChart = (trends: any[]) => {
  if (!trendChart.value) return

  if (trendChartInstance) {
    trendChartInstance.destroy()
  }

  const ctx = trendChart.value.getContext('2d')
  if (!ctx) return

  trendChartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: trends.map(t => t.date),
      datasets: [
        {
          label: 'Total',
          data: trends.map(t => t.total),
          borderColor: 'rgb(59, 130, 246)',
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          fill: true,
          tension: 0.4
        },
        {
          label: 'Delivered',
          data: trends.map(t => t.delivered),
          borderColor: 'rgb(34, 197, 94)',
          backgroundColor: 'rgba(34, 197, 94, 0.1)',
          fill: true,
          tension: 0.4
        },
        {
          label: 'Failed',
          data: trends.map(t => t.failed),
          borderColor: 'rgb(239, 68, 68)',
          backgroundColor: 'rgba(239, 68, 68, 0.1)',
          fill: true,
          tension: 0.4
        }
      ]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  })
}

const renderEventChart = () => {
  if (!eventChart.value) return

  if (eventChartInstance) {
    eventChartInstance.destroy()
  }

  const ctx = eventChart.value.getContext('2d')
  if (!ctx) return

  const eventStats = statistics.value.by_event
  const labels = eventStats.map(e => formatEventName(e.event_type))
  const data = eventStats.map(e => e.count)

  eventChartInstance = new Chart(ctx, {
    type: 'pie',
    data: {
      labels,
      datasets: [{
        data,
        backgroundColor: [
          'rgb(59, 130, 246)',
          'rgb(34, 197, 94)',
          'rgb(234, 179, 8)',
          'rgb(239, 68, 68)',
          'rgb(168, 85, 247)',
          'rgb(6, 182, 212)'
        ]
      }]
    },
    options: {
      responsive: true
    }
  })
}

const exportStatistics = async () => {
  try {
    const response = await axios.get('/api/notifications/statistics/export', {
      params: filters.value
    })
    
    // For now, just show the data. In production, download file
    const blob = new Blob([JSON.stringify(response.data.statistics, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `notification-statistics-${filters.value.from}-${filters.value.to}.json`
    a.click()
    URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export statistics:', error)
    alert('Failed to export statistics')
  }
}

const formatChannelName = (channel: string) => {
  const names: Record<string, string> = {
    telegram: 'Telegram',
    whatsapp: 'WhatsApp',
    viber: 'Viber',
    kakaotalk: 'KakaoTalk',
    signal: 'Signal',
    wechat: 'WeChat',
    vk: 'VK',
    odnoklassniki: 'Odnoklassniki',
    email: 'Email',
    sms: 'SMS',
  }
  return names[channel] || channel
}

const formatEventName = (event: string) => {
  const names: Record<string, string> = {
    created: 'Created',
    confirmed: 'Confirmed',
    ready_for_delivery: 'Ready for Delivery',
    in_delivery: 'In Delivery',
    delivered: 'Delivered',
    cancelled: 'Cancelled',
  }
  return names[event] || event
}
</script>

<style scoped>
.notification-statistics {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30px;
  flex-wrap: wrap;
  gap: 15px;
}

.filters {
  display: flex;
  gap: 10px;
  align-items: center;
}

.filter-select,
.filter-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 14px;
}

.stats-overview {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.stat-icon.total {
  background-color: #dbeafe;
  color: #1e40af;
}

.stat-icon.delivered {
  background-color: #dcfce7;
  color: #166534;
}

.stat-icon.failed {
  background-color: #fee2e2;
  color: #991b1b;
}

.stat-icon.success {
  background-color: #fef3c7;
  color: #92400e;
}

.stat-info h3 {
  margin: 0 0 5px 0;
  font-size: 24px;
  font-weight: 700;
}

.stat-info p {
  margin: 0;
  font-size: 14px;
  color: #6b7280;
}

.section {
  background: white;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 30px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section h3 {
  margin: 0 0 20px 0;
  font-size: 18px;
}

.chart-container {
  height: 300px;
}

.stats-table {
  width: 100%;
  border-collapse: collapse;
}

.stats-table th,
.stats-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

.stats-table th {
  background-color: #f9fafb;
  font-weight: 600;
}

.btn {
  padding: 8px 16px;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  font-size: 14px;
}

.btn-secondary {
  background-color: #6b7280;
  color: white;
}
</style>
