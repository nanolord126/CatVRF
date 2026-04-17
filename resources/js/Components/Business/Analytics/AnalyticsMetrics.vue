<template>
  <div class="analytics-metrics">
    <div class="header">
      <h2>Metrics Dashboard</h2>
      <div class="date-range">
        <input v-model="dateFrom" type="date" class="date-input" />
        <span>to</span>
        <input v-model="dateTo" type="date" class="date-input" />
        <button @click="refreshData" class="btn-primary">Refresh</button>
      </div>
    </div>

    <div class="metrics-grid">
      <div v-for="metric in metrics" :key="metric.name" class="metric-card">
        <div class="metric-header">
          <span class="metric-name">{{ metric.name }}</span>
          <span :class="['trend-badge', metric.trend]">{{ metric.trend }}</span>
        </div>
        <div class="metric-value">{{ formatValue(metric.value) }}</div>
        <div class="metric-change" :class="metric.change >= 0 ? 'positive' : 'negative'">
          {{ metric.change >= 0 ? '+' : '' }}{{ metric.change }}%
          <span>vs last period</span>
        </div>
      </div>
    </div>

    <div class="charts-section">
      <div class="chart-card">
        <h3>Traffic Overview</h3>
        <div class="chart-placeholder">Chart visualization here</div>
      </div>
      <div class="chart-card">
        <h3>Conversion Funnel</h3>
        <div class="chart-placeholder">Chart visualization here</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Metric {
  name: string
  value: number
  trend: string
  change: number
}

const metrics = ref<Metric[]>([])
const dateFrom = ref('')
const dateTo = ref('')

const formatValue = (value: number): string => {
  if (value >= 1000000) {
    return (value / 1000000).toFixed(1) + 'M'
  } else if (value >= 1000) {
    return (value / 1000).toFixed(1) + 'K'
  }
  return value.toString()
}

const refreshData = () => {
  fetchMetrics()
}

const fetchMetrics = async () => {
  try {
    const response = await fetch(`/api/analytics/metrics?from=${dateFrom.value}&to=${dateTo.value}`)
    const data = await response.json()
    metrics.value = data
  } catch (error) {
    console.error('Failed to fetch metrics:', error)
  }
}

onMounted(() => {
  const today = new Date()
  const lastMonth = new Date(today)
  lastMonth.setMonth(lastMonth.getMonth() - 1)
  
  dateTo.value = today.toISOString().split('T')[0]
  dateFrom.value = lastMonth.toISOString().split('T')[0]
  
  fetchMetrics()
})
</script>

<style scoped>
.analytics-metrics {
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

.date-range {
  display: flex;
  align-items: center;
  gap: 8px;
}

.date-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.metric-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.metric-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}

.metric-name {
  font-size: 14px;
  color: #6b7280;
}

.trend-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.trend-badge.up {
  background: #d1fae5;
  color: #065f46;
}

.trend-badge.down {
  background: #fee2e2;
  color: #991b1b;
}

.trend-badge.stable {
  background: #e5e7eb;
  color: #374151;
}

.metric-value {
  font-size: 28px;
  font-weight: 600;
  color: #111827;
  margin-bottom: 8px;
}

.metric-change {
  font-size: 13px;
  font-weight: 500;
}

.metric-change.positive {
  color: #059669;
}

.metric-change.negative {
  color: #dc2626;
}

.metric-change span {
  color: #6b7280;
  font-weight: 400;
}

.charts-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 20px;
}

.chart-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.chart-card h3 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
}

.chart-placeholder {
  height: 250px;
  background: #f9fafb;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  font-size: 14px;
}
</style>
