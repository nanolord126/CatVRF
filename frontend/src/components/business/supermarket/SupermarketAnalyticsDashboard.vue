<template>
  <div class="supermarket-analytics-dashboard">
    <div class="dashboard-header">
      <h2 class="dashboard-title">Аналитика супермаркета</h2>
      <div class="header-controls">
        <select v-model="selectedPeriod" @change="loadData" class="period-select">
          <option value="7">7 дней</option>
          <option value="30">30 дней</option>
          <option value="90">90 дней</option>
          <option value="365">Год</option>
        </select>
        <button @click="exportReport" class="export-btn">
          <Download class="btn-icon" />
          Экспорт
        </button>
      </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon revenue">
          <TrendingUp class="icon" />
        </div>
        <div class="kpi-content">
          <span class="kpi-label">Выручка</span>
          <span class="kpi-value">{{ formatCurrency(metrics.revenue) }}</span>
          <span class="kpi-trend" :class="getTrendClass(metrics.revenueTrend)">
            {{ metrics.revenueTrend > 0 ? '+' : '' }}{{ metrics.revenueTrend }}%
          </span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon orders">
          <ShoppingCart class="icon" />
        </div>
        <div class="kpi-content">
          <span class="kpi-label">Заказы</span>
          <span class="kpi-value">{{ formatNumber(metrics.orders) }}</span>
          <span class="kpi-trend" :class="getTrendClass(metrics.ordersTrend)">
            {{ metrics.ordersTrend > 0 ? '+' : '' }}{{ metrics.ordersTrend }}%
          </span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon customers">
          <Users class="icon" />
        </div>
        <div class="kpi-content">
          <span class="kpi-label">Клиенты</span>
          <span class="kpi-value">{{ formatNumber(metrics.customers) }}</span>
          <span class="kpi-trend" :class="getTrendClass(metrics.customersTrend)">
            {{ metrics.customersTrend > 0 ? '+' : '' }}{{ metrics.customersTrend }}%
          </span>
        </div>
      </div>

      <div class="kpi-card">
        <div class="kpi-icon aov">
          <DollarSign class="icon" />
        </div>
        <div class="kpi-content">
          <span class="kpi-label">Средний чек</span>
          <span class="kpi-value">{{ formatCurrency(metrics.aov) }}</span>
          <span class="kpi-trend" :class="getTrendClass(metrics.aovTrend)">
            {{ metrics.aovTrend > 0 ? '+' : '' }}{{ metrics.aovTrend }}%
          </span>
        </div>
      </div>
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid">
      <!-- Revenue Chart -->
      <div class="chart-card">
        <div class="chart-header">
          <h3>Выручка по дням</h3>
          <div class="chart-legend">
            <span class="legend-item">
              <span class="legend-color gross"></span>
              Брутто
            </span>
            <span class="legend-item">
              <span class="legend-color net"></span>
              Нетто
            </span>
          </div>
        </div>
        <div class="chart-container">
          <RevenueChart :data="revenueData" />
        </div>
      </div>

      <!-- Orders by Status -->
      <div class="chart-card">
        <div class="chart-header">
          <h3>Заказы по статусу</h3>
        </div>
        <div class="chart-container">
          <OrdersStatusChart :data="ordersByStatus" />
        </div>
      </div>

      <!-- Top Products -->
      <div class="chart-card">
        <div class="chart-header">
          <h3>Топ продуктов</h3>
        </div>
        <div class="chart-container">
          <TopProductsChart :data="topProducts" />
        </div>
      </div>

      <!-- Categories Distribution -->
      <div class="chart-card">
        <div class="chart-header">
          <h3>Распределение по категориям</h3>
        </div>
        <div class="chart-container">
          <CategoriesChart :data="categoriesData" />
        </div>
      </div>
    </div>

    <!-- RFM Segments -->
    <div class="section-card">
      <div class="section-header">
        <h3>Сегментация клиентов (RFM)</h3>
      </div>
      <div class="rfm-grid">
        <div 
          v-for="segment in rfmSegments" 
          :key="segment.name"
          class="rfm-segment"
          :class="segment.slug"
        >
          <div class="segment-header">
            <span class="segment-name">{{ segment.name }}</span>
            <span class="segment-count">{{ segment.count }}</span>
          </div>
          <div class="segment-bar">
            <div 
              class="segment-fill"
              :style="{ width: segment.percentage + '%' }"
            ></div>
          </div>
          <div class="segment-metrics">
            <span class="segment-metric">LTV: {{ formatCurrency(segment.ltv) }}</span>
            <span class="segment-metric">Частота: {{ segment.frequency }}/мес</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Dynamic Pricing Stats -->
    <div class="section-card">
      <div class="section-header">
        <h3>Статистика динамического ценообразования</h3>
      </div>
      <div class="pricing-stats-grid">
        <div class="pricing-stat">
          <span class="stat-label">Продуктов с динамикой</span>
          <span class="stat-value">{{ pricingStats.activeProducts }}</span>
        </div>
        <div class="pricing-stat">
          <span class="stat-label">Средний рост цены</span>
          <span class="stat-value positive">+{{ pricingStats.avgIncrease }}%</span>
        </div>
        <div class="pricing-stat">
          <span class="stat-label">Маржа платформы</span>
          <span class="stat-value">{{ formatCurrency(pricingStats.platformMargin) }}</span>
        </div>
        <div class="pricing-stat">
          <span class="stat-label">Дополнительная выручка продавцов</span>
          <span class="stat-value">{{ formatCurrency(pricingStats.sellerRevenue) }}</span>
        </div>
      </div>
    </div>

    <!-- Reviews Stats -->
    <div class="section-card">
      <div class="section-header">
        <h3>Статистика отзывов</h3>
      </div>
      <div class="reviews-stats-grid">
        <div class="review-stat">
          <span class="stat-label">Всего отзывов</span>
          <span class="stat-value">{{ reviewStats.total }}</span>
        </div>
        <div class="review-stat">
          <span class="stat-label">Платные</span>
          <span class="stat-value">{{ reviewStats.paid }}</span>
        </div>
        <div class="review-stat">
          <span class="stat-label">Бесплатные</span>
          <span class="stat-value">{{ reviewStats.free }}</span>
        </div>
        <div class="review-stat">
          <span class="stat-label">Средняя оценка</span>
          <span class="stat-value">{{ reviewStats.avgRating }}</span>
        </div>
        <div class="review-stat">
          <span class="stat-label">Выплачено магазинами</span>
          <span class="stat-value">{{ formatCurrency(reviewStats.totalPaidOut) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { 
  TrendingUp, 
  ShoppingCart, 
  Users, 
  DollarSign, 
  Download 
} from 'lucide-vue-next'
import RevenueChart from './charts/RevenueChart.vue'
import OrdersStatusChart from './charts/OrdersStatusChart.vue'
import TopProductsChart from './charts/TopProductsChart.vue'
import CategoriesChart from './charts/CategoriesChart.vue'

const selectedPeriod = ref('30')

const metrics = ref({
  revenue: 1500000,
  revenueTrend: 15.5,
  orders: 1200,
  ordersTrend: 8.3,
  customers: 450,
  customersTrend: 12.1,
  aov: 1250,
  aovTrend: 6.7,
})

const revenueData = ref([])
const ordersByStatus = ref([])
const topProducts = ref([])
const categoriesData = ref([])

const rfmSegments = ref([
  { name: 'Чемпионы', slug: 'champions', count: 50, percentage: 11, ltv: 45000, frequency: 8 },
  { name: 'Лояльные', slug: 'loyal', count: 120, percentage: 27, ltv: 25000, frequency: 5 },
  { name: 'Потенциальные', slug: 'potential', count: 80, percentage: 18, ltv: 15000, frequency: 3 },
  { name: 'Новые', slug: 'new', count: 100, percentage: 22, ltv: 5000, frequency: 1 },
  { name: 'В зоне риска', slug: 'at_risk', count: 60, percentage: 13, ltv: 18000, frequency: 4 },
  { name: 'Спящие', slug: 'hibernating', count: 30, percentage: 7, ltv: 12000, frequency: 2 },
  { name: 'Потерянные', slug: 'lost', count: 10, percentage: 2, ltv: 8000, frequency: 1 },
])

const pricingStats = ref({
  activeProducts: 150,
  avgIncrease: 12.5,
  platformMargin: 225000,
  sellerRevenue: 525000,
})

const reviewStats = ref({
  total: 350,
  paid: 150,
  free: 200,
  avgRating: 4.2,
  totalPaidOut: 24000,
})

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
  }).format(value)
}

const formatNumber = (value: number): string => {
  return new Intl.NumberFormat('ru-RU').format(value)
}

const getTrendClass = (trend: number): string => {
  return trend > 0 ? 'positive' : (trend < 0 ? 'negative' : 'neutral')
}

const loadData = () => {
  // TODO: Load data from API
}

const exportReport = () => {
  // TODO: Export report
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.supermarket-analytics-dashboard {
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

.header-controls {
  display: flex;
  gap: 1rem;
}

.period-select {
  padding: 0.5rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  background: white;
  cursor: pointer;
}

.export-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.export-btn:hover {
  background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(34, 197, 94, 0.3);
}

.btn-icon {
  width: 1rem;
  height: 1rem;
}

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.kpi-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  display: flex;
  gap: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.kpi-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.kpi-icon.revenue {
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
}

.kpi-icon.revenue .icon {
  color: #16a34a;
}

.kpi-icon.orders {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}

.kpi-icon.orders .icon {
  color: #2563eb;
}

.kpi-icon.customers {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
}

.kpi-icon.customers .icon {
  color: #d97706;
}

.kpi-icon.aov {
  background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
}

.kpi-icon.aov .icon {
  color: #4f46e5;
}

.kpi-icon .icon {
  width: 1.5rem;
  height: 1.5rem;
}

.kpi-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.kpi-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.kpi-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #111827;
}

.kpi-trend {
  font-size: 0.875rem;
  font-weight: 500;
}

.kpi-trend.positive {
  color: #16a34a;
}

.kpi-trend.negative {
  color: #dc2626;
}

.kpi-trend.neutral {
  color: #6b7280;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.chart-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.chart-header h3 {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.chart-legend {
  display: flex;
  gap: 1rem;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.75rem;
  color: #6b7280;
}

.legend-color {
  width: 0.75rem;
  height: 0.75rem;
  border-radius: 50%;
}

.legend-color.gross {
  background: #22c55e;
}

.legend-color.net {
  background: #3b82f6;
}

.chart-container {
  height: 250px;
}

.section-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section-header {
  margin-bottom: 1.5rem;
}

.section-header h3 {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.rfm-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.rfm-segment {
  padding: 1rem;
  border-radius: 0.75rem;
  border: 2px solid #e5e7eb;
}

.rfm-segment.champions {
  border-color: #22c55e;
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
}

.rfm-segment.loyal {
  border-color: #3b82f6;
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}

.rfm-segment.potential {
  border-color: #f59e0b;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
}

.rfm-segment.new {
  border-color: #8b5cf6;
  background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
}

.rfm-segment.at_risk {
  border-color: #ef4444;
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
}

.segment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.segment-name {
  font-weight: 600;
  color: #111827;
}

.segment-count {
  font-size: 0.875rem;
  color: #6b7280;
}

.segment-bar {
  height: 0.5rem;
  background: rgba(255, 255, 255, 0.5);
  border-radius: 9999px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.segment-fill {
  height: 100%;
  background: currentColor;
  border-radius: 9999px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.segment-metrics {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #4b5563;
}

.pricing-stats-grid,
.reviews-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.pricing-stat,
.review-stat {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.stat-label {
  font-size: 0.75rem;
  color: #6b7280;
}

.stat-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #111827;
}

.stat-value.positive {
  color: #16a34a;
}
</style>
