<template>
  <div class="demand-analytics-dashboard">
    <div class="dashboard-header">
      <h2 class="dashboard-title">Аналитика спроса</h2>
      <div class="time-range-selector">
        <button 
          v-for="range in timeRanges" 
          :key="range.value"
          @click="selectTimeRange(range.value)"
          :class="['time-range-btn', { active: selectedTimeRange === range.value }]"
        >
          {{ range.label }}
        </button>
      </div>
    </div>

    <div class="dashboard-grid">
      <!-- Total Demand Card -->
      <div class="analytics-card total-demand">
        <div class="card-header">
          <h3>Общий спрос</h3>
          <TrendingUp class="icon" :class="getTrendClass(demandData.total_demand?.trend)" />
        </div>
        <div class="card-metrics">
          <div class="metric">
            <span class="metric-value">{{ formatNumber(demandData.total_demand?.orders || 0) }}</span>
            <span class="metric-label">Заказов</span>
          </div>
          <div class="metric">
            <span class="metric-value">{{ formatNumber(demandData.total_demand?.items || 0) }}</span>
            <span class="metric-label">Товаров</span>
          </div>
          <div class="metric">
            <span class="metric-value">{{ formatCurrency(demandData.total_demand?.revenue || 0) }}</span>
            <span class="metric-label">Выручка</span>
          </div>
        </div>
      </div>

      <!-- Top Products Card -->
      <div class="analytics-card top-products">
        <div class="card-header">
          <h3>Топ продуктов</h3>
          <Package class="icon" />
        </div>
        <div class="products-list">
          <div 
            v-for="(product, index) in demandData.top_products?.slice(0, 5)" 
            :key="product.product_id"
            class="product-item"
            :class="{ 'top-rank': index < 3 }"
          >
            <div class="product-rank" :class="`rank-${index + 1}`">{{ index + 1 }}</div>
            <div class="product-info">
              <span class="product-name">{{ product.name }}</span>
              <span class="product-category">{{ product.category }}</span>
            </div>
            <div class="product-metrics">
              <span class="product-sales">{{ formatNumber(product.sold_quantity) }}</span>
              <span class="product-trend" :class="getTrendClass(product.trend)">
                {{ product.trend_percentage > 0 ? '+' : '' }}{{ product.trend_percentage }}%
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Category Demand Card -->
      <div class="analytics-card category-demand">
        <div class="card-header">
          <h3>Спрос по категориям</h3>
          <PieChart class="icon" />
        </div>
        <div class="category-bars">
          <div 
            v-for="category in demandData.category_demand" 
            :key="category.category"
            class="category-bar"
          >
            <div class="category-info">
              <span class="category-name">{{ category.category }}</span>
              <span class="category-percentage">{{ category.percentage }}%</span>
            </div>
            <div class="category-progress">
              <div 
                class="category-fill"
                :style="{ width: category.percentage + '%' }"
                :class="getTrendClass(category.trend)"
              ></div>
            </div>
            <div class="category-revenue">{{ formatCurrency(category.revenue) }}</div>
          </div>
        </div>
      </div>

      <!-- Seasonal Trends Card -->
      <div class="analytics-card seasonal-trends">
        <div class="card-header">
          <h3>Сезонные тренды</h3>
          <Calendar class="icon" />
        </div>
        <div class="seasonal-info">
          <div class="seasonal-metric">
            <span class="seasonal-label">Текущий месяц</span>
            <span class="seasonal-value">{{ getMonthName(demandData.seasonal_trends?.current_month) }}</span>
          </div>
          <div class="seasonal-metric">
            <span class="seasonal-label">Сезонный фактор</span>
            <span class="seasonal-value" :class="getSeasonalFactorClass(demandData.seasonal_trends?.seasonal_factor)">
              {{ demandData.seasonal_trends?.seasonal_factor?.toFixed(2) }}x
            </span>
          </div>
          <div class="seasonal-metric">
            <span class="seasonal-label">Сезонность</span>
            <span class="seasonal-value" :class="getSeasonalityClass(demandData.seasonal_trends?.seasonality)">
              {{ getSeasonalityLabel(demandData.seasonal_trends?.seasonality) }}
            </span>
          </div>
          <div class="seasonal-metric">
            <span class="seasonal-label">Прогноз на следующий месяц</span>
            <span class="seasonal-value" :class="getTrendClass(demandData.seasonal_trends?.next_month_prediction)">
              {{ getTrendLabel(demandData.seasonal_trends?.next_month_prediction) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Demand Forecast Card -->
      <div class="analytics-card demand-forecast">
        <div class="card-header">
          <h3>Прогноз спроса</h3>
          <Activity class="icon" />
        </div>
        <div class="forecast-metrics">
          <div class="forecast-item">
            <span class="forecast-period">Следующие 7 дней</span>
            <span class="forecast-value">{{ formatNumber(demandData.demand_forecast?.next_7_days || 0) }}</span>
          </div>
          <div class="forecast-item">
            <span class="forecast-period">Следующие 14 дней</span>
            <span class="forecast-value">{{ formatNumber(demandData.demand_forecast?.next_14_days || 0) }}</span>
          </div>
          <div class="forecast-item">
            <span class="forecast-period">Следующие 30 дней</span>
            <span class="forecast-value">{{ formatNumber(demandData.demand_forecast?.next_30_days || 0) }}</span>
          </div>
          <div class="forecast-confidence">
            <span class="confidence-label">Уверенность прогноза</span>
            <div class="confidence-bar">
              <div 
                class="confidence-fill"
                :style="{ width: (demandData.demand_forecast?.confidence || 0) * 100 + '%' }"
              ></div>
            </div>
            <span class="confidence-value">{{ Math.round((demandData.demand_forecast?.confidence || 0) * 100) }}%</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { TrendingUp, Package, PieChart, Calendar, Activity } from 'lucide-vue-next'

interface DemandData {
  period: {
    start: string
    end: string
    days: number
  }
  total_demand: {
    orders: number
    items: number
    revenue: number
    trend?: string
  }
  top_products: Array<{
    product_id: number
    name: string
    category: string
    sold_quantity: number
    revenue: number
    avg_daily_demand: number
    trend: string
    trend_percentage: number
  }>
  category_demand: Array<{
    category: string
    sold_quantity: number
    revenue: number
    percentage: number
    trend: string
  }>
  seasonal_trends: {
    current_month: number
    seasonal_factor: number
    seasonality: string
    next_month_prediction: string
  }
  demand_forecast: {
    next_7_days: number
    next_14_days: number
    next_30_days: number
    confidence: number
  }
}

const props = defineProps<{
  demandData: DemandData
}>()

const emit = defineEmits<{
  timeRangeChange: [range: string]
}>()

const selectedTimeRange = ref('30')
const timeRanges = [
  { label: '7 дней', value: '7' },
  { label: '30 дней', value: '30' },
  { label: '90 дней', value: '90' },
]

const selectTimeRange = (range: string) => {
  selectedTimeRange.value = range
  emit('timeRangeChange', range)
}

const formatNumber = (value: number): string => {
  return new Intl.NumberFormat('ru-RU').format(value)
}

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(value)
}

const getMonthName = (month: number): string => {
  const months = [
    'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
  ]
  return months[month - 1] || ''
}

const getTrendClass = (trend?: string): string => {
  return {
    'increasing': 'text-green-500',
    'stable': 'text-gray-500',
    'decreasing': 'text-red-500'
  }[trend || 'stable'] || 'text-gray-500'
}

const getSeasonalFactorClass = (factor?: number): string => {
  if (!factor) return 'text-gray-500'
  if (factor >= 1.15) return 'text-green-500'
  if (factor >= 1.0) return 'text-blue-500'
  if (factor >= 0.85) return 'text-yellow-500'
  return 'text-red-500'
}

const getSeasonalityClass = (seasonality?: string): string => {
  return {
    'high_demand': 'text-green-500',
    'normal_demand': 'text-blue-500',
    'low_demand': 'text-yellow-500',
    'very_low_demand': 'text-red-500'
  }[seasonality || 'normal_demand'] || 'text-gray-500'
}

const getSeasonalityLabel = (seasonality?: string): string => {
  return {
    'high_demand': 'Высокий спрос',
    'normal_demand': 'Нормальный спрос',
    'low_demand': 'Низкий спрос',
    'very_low_demand': 'Очень низкий спрос'
  }[seasonality || 'normal_demand'] || 'Нормальный спрос'
}

const getTrendLabel = (trend?: string): string => {
  return {
    'increasing': 'Рост',
    'stable': 'Стабильно',
    'decreasing': 'Спад'
  }[trend || 'stable'] || 'Стабильно'
}
</script>

<style scoped>
.demand-analytics-dashboard {
  padding: 1.5rem;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.dashboard-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.time-range-selector {
  display: flex;
  gap: 0.5rem;
}

.time-range-btn {
  padding: 0.5rem 1rem;
  border: 1px solid #e5e7eb;
  background: white;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s ease;
}

.time-range-btn:hover {
  background: #f9fafb;
  border-color: #d1d5db;
}

.time-range-btn.active {
  background: #22c55e;
  border-color: #22c55e;
  color: white;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1.5rem;
}

.analytics-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.card-header h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.card-header .icon {
  width: 1.25rem;
  height: 1.25rem;
  color: #6b7280;
}

.total-demand .card-metrics {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}

.metric {
  text-align: center;
}

.metric-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
}

.metric-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.products-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.product-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border-radius: 0.5rem;
  background: #f9fafb;
  transition: all 0.2s ease;
}

.product-item:hover {
  background: #f3f4f6;
  transform: translateX(4px);
}

.product-item.top-rank {
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}

.product-rank {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.875rem;
  background: #e5e7eb;
  color: #6b7280;
}

.product-rank.rank-1 {
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  color: white;
}

.product-rank.rank-2 {
  background: linear-gradient(135deg, #9ca3af 0%, #6b7280 100%);
  color: white;
}

.product-rank.rank-3 {
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
  color: white;
}

.product-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.product-name {
  font-weight: 600;
  color: #111827;
}

.product-category {
  font-size: 0.75rem;
  color: #6b7280;
}

.product-metrics {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}

.product-sales {
  font-weight: 600;
  color: #111827;
}

.product-trend {
  font-size: 0.75rem;
  font-weight: 500;
}

.category-bars {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.category-bar {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.category-info {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
}

.category-name {
  font-weight: 500;
  color: #111827;
}

.category-percentage {
  color: #6b7280;
}

.category-progress {
  height: 0.5rem;
  background: #e5e7eb;
  border-radius: 9999px;
  overflow: hidden;
}

.category-fill {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.category-fill.increasing {
  background: linear-gradient(90deg, #22c55e, #16a34a);
}

.category-fill.stable {
  background: linear-gradient(90deg, #6b7280, #4b5563);
}

.category-fill.decreasing {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}

.category-revenue {
  font-size: 0.75rem;
  color: #6b7280;
  text-align: right;
}

.seasonal-info {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.seasonal-metric {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.seasonal-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.seasonal-value {
  font-weight: 600;
  font-size: 1rem;
  color: #111827;
}

.forecast-metrics {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.forecast-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.forecast-period {
  font-size: 0.875rem;
  color: #6b7280;
}

.forecast-value {
  font-weight: 700;
  font-size: 1.125rem;
  color: #111827;
}

.forecast-confidence {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.confidence-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.confidence-bar {
  height: 0.5rem;
  background: #e5e7eb;
  border-radius: 9999px;
  overflow: hidden;
}

.confidence-fill {
  height: 100%;
  background: linear-gradient(90deg, #22c55e, #16a34a);
  border-radius: 9999px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.confidence-value {
  font-weight: 600;
  text-align: right;
  color: #111827;
}
</style>
