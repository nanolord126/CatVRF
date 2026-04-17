<template>
  <div class="real-estate-analytics">
    <div class="header">
      <h2>Real Estate Analytics</h2>
      <select v-model="period" @change="fetchAnalytics">
        <option value="7">Last 7 days</option>
        <option value="30">Last 30 days</option>
        <option value="90">Last 90 days</option>
        <option value="365">Last year</option>
      </select>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <h3>Total Listings</h3>
        <p class="stat-value">{{ analytics.totalListings }}</p>
        <p class="stat-change" :class="analytics.listingsChange >= 0 ? 'positive' : 'negative'">
          {{ analytics.listingsChange >= 0 ? '+' : '' }}{{ analytics.listingsChange }}%
        </p>
      </div>
      <div class="stat-card">
        <h3>Sold Properties</h3>
        <p class="stat-value">{{ analytics.soldProperties }}</p>
        <p class="stat-change" :class="analytics.soldChange >= 0 ? 'positive' : 'negative'">
          {{ analytics.soldChange >= 0 ? '+' : '' }}{{ analytics.soldChange }}%
        </p>
      </div>
      <div class="stat-card">
        <h3>Average Price</h3>
        <p class="stat-value">{{ formatCurrency(analytics.avgPrice) }}</p>
        <p class="stat-change" :class="analytics.priceChange >= 0 ? 'positive' : 'negative'">
          {{ analytics.priceChange >= 0 ? '+' : '' }}{{ analytics.priceChange }}%
        </p>
      </div>
      <div class="stat-card">
        <h3>Total Revenue</h3>
        <p class="stat-value">{{ formatCurrency(analytics.revenue) }}</p>
        <p class="stat-change" :class="analytics.revenueChange >= 0 ? 'positive' : 'negative'">
          {{ analytics.revenueChange >= 0 ? '+' : '' }}{{ analytics.revenueChange }}%
        </p>
      </div>
    </div>

    <div class="charts-grid">
      <div class="chart-card">
        <h3>Sales by Property Type</h3>
        <div class="chart-placeholder">
          <div v-for="(value, type) in analytics.salesByType" :key="type" class="bar-item">
            <span class="bar-label">{{ type }}</span>
            <div class="bar">
              <div class="bar-fill" :style="{ width: (value / maxSales * 100) + '%' }"></div>
            </div>
            <span class="bar-value">{{ value }}</span>
          </div>
        </div>
      </div>

      <div class="chart-card">
        <h3>Price Distribution</h3>
        <div class="chart-placeholder">
          <div v-for="(count, range) in analytics.priceDistribution" :key="range" class="bar-item">
            <span class="bar-label">{{ range }}</span>
            <div class="bar">
              <div class="bar-fill" :style="{ width: (count / maxPriceDist * 100) + '%' }"></div>
            </div>
            <span class="bar-value">{{ count }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Analytics {
  totalListings: number
  listingsChange: number
  soldProperties: number
  soldChange: number
  avgPrice: number
  priceChange: number
  revenue: number
  revenueChange: number
  salesByType: Record<string, number>
  priceDistribution: Record<string, number>
}

const period = ref('30')
const analytics = ref<Analytics>({
  totalListings: 0,
  listingsChange: 0,
  soldProperties: 0,
  soldChange: 0,
  avgPrice: 0,
  priceChange: 0,
  revenue: 0,
  revenueChange: 0,
  salesByType: {},
  priceDistribution: {}
})

const maxSales = computed(() => Math.max(...Object.values(analytics.value.salesByType), 1))
const maxPriceDist = computed(() => Math.max(...Object.values(analytics.value.priceDistribution), 1))

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    maximumFractionDigits: 0
  }).format(amount)
}

const fetchAnalytics = async () => {
  try {
    const response = await fetch(`/api/real-estate/analytics?period=${period.value}`)
    const data = await response.json()
    analytics.value = data
  } catch (error) {
    console.error('Failed to fetch analytics:', error)
  }
}

onMounted(() => {
  fetchAnalytics()
})
</script>

<style scoped>
.real-estate-analytics {
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

.header select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
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
  margin: 0 0 4px 0;
  font-size: 28px;
  font-weight: 600;
  color: #1f2937;
}

.stat-change {
  margin: 0;
  font-size: 14px;
  font-weight: 500;
}

.stat-change.positive {
  color: #10b981;
}

.stat-change.negative {
  color: #ef4444;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 20px;
}

.chart-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.chart-card h3 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
}

.chart-placeholder {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.bar-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.bar-label {
  width: 100px;
  font-size: 13px;
  color: #6b7280;
}

.bar {
  flex: 1;
  height: 24px;
  background: #f3f4f6;
  border-radius: 4px;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  background: #3b82f6;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.bar-value {
  width: 40px;
  font-size: 13px;
  font-weight: 600;
  color: #374151;
}
</style>
