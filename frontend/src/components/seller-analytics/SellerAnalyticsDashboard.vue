<template>
  <div class="seller-analytics-dashboard min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Seller Analytics</h1>
            <p class="text-sm text-gray-600 mt-1">
              <span v-if="dashboardData">
                {{ formatPeriod(dashboardData.period) }}
              </span>
              <span v-else>Select a period to view analytics</span>
            </p>
          </div>

          <div class="flex items-center gap-3">
            <!-- Period Selector -->
            <select
              v-model="selectedPeriod"
              @change="handlePeriodChange"
              :disabled="loading"
              class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:opacity-50"
            >
              <option value="today">Today</option>
              <option value="last_7_days">Last 7 Days</option>
              <option value="last_30_days">Last 30 Days</option>
              <option value="last_90_days">Last 90 Days</option>
              <option value="custom">Custom Range</option>
            </select>

            <!-- Category Filter -->
            <select
              v-model="selectedCategory"
              @change="handleCategoryChange"
              :disabled="loading"
              class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 disabled:opacity-50"
            >
              <option value="">All Categories</option>
              <option v-for="category in categories" :key="category" :value="category">
                {{ category }}
              </option>
            </select>

            <!-- Comparison Toggle -->
            <button
              @click="toggleComparison"
              :class="[
                'inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors',
                showComparison
                  ? 'bg-indigo-100 text-indigo-700 border border-indigo-300'
                  : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'
              ]"
              :disabled="loading"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
              Compare
            </button>

            <!-- Custom Date Range Picker -->
            <div v-if="selectedPeriod === 'custom'" class="flex items-center gap-2">
              <input
                v-model="customDateFrom"
                type="date"
                :max="customDateTo"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              />
              <span class="text-gray-500">to</span>
              <input
                v-model="customDateTo"
                type="date"
                :min="customDateFrom"
                :max="todayDate"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
              />
              <button
                @click="applyCustomRange"
                :disabled="!customDateFrom || !customDateTo || loading"
                class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
              >
                Apply
              </button>
            </div>

            <!-- Export Button -->
            <button
              @click="handleExport"
              :disabled="loading || !dashboardData"
              class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Export
            </button>

            <!-- Refresh Button -->
            <button
              @click="handleRefresh"
              :disabled="loading"
              class="inline-flex items-center gap-2 bg-white border border-gray-300 hover:bg-gray-50 disabled:bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            >
              <svg
                :class="{ 'animate-spin': loading }"
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Refresh
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-center gap-3">
          <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
          <div>
            <h3 class="text-sm font-medium text-red-800">Error loading analytics</h3>
            <p class="text-sm text-red-700 mt-1">{{ error }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Dashboard Content -->
    <div v-else class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- KPI Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <KPICard
          v-for="kpi in kpiCards"
          :key="kpi.label"
          :kpi="kpi"
          :loading="loading"
        />
      </div>

      <!-- Comparison View -->
      <div v-if="showComparison && previousPeriodData" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Period Comparison</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h4 class="text-sm font-medium text-gray-500 mb-3">Current Period</h4>
            <div class="space-y-2">
              <div v-for="kpi in kpiCards" :key="`current-${kpi.label}`" class="flex justify-between items-center">
                <span class="text-sm text-gray-700">{{ kpi.label }}</span>
                <span class="text-sm font-medium text-gray-900">{{ formatValue(kpi.value, kpi.format) }}</span>
              </div>
            </div>
          </div>
          <div>
            <h4 class="text-sm font-medium text-gray-500 mb-3">Previous Period</h4>
            <div class="space-y-2">
              <div v-for="kpi in previousPeriodData.kpi_cards" :key="`previous-${kpi.label}`" class="flex justify-between items-center">
                <span class="text-sm text-gray-700">{{ kpi.label }}</span>
                <span class="text-sm font-medium text-gray-900">{{ formatValue(kpi.value, kpi.format) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts and Tables Row -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Trend Chart -->
        <div class="lg:col-span-2">
          <TrendChart
            :trends="trends"
            :loading="loading"
            :height="350"
          />
        </div>

        <!-- Insights Widget -->
        <div>
          <InsightsWidget
            :insights="insights"
            :loading="loading"
            :max-items="5"
          />
        </div>
      </div>

      <!-- Top Products Table -->
      <div>
        <TopProductsTable
          :products="topProducts"
          :loading="loading"
          :max-items="10"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useSellerAnalytics } from '@/composables/useSellerAnalytics'
import KPICard from './KPICard.vue'
import TrendChart from './TrendChart.vue'
import InsightsWidget from './InsightsWidget.vue'
import TopProductsTable from './TopProductsTable.vue'
import type { PeriodType } from '@/types/analytics'
import * as XLSX from 'xlsx'

const {
  loading,
  error,
  dashboardData,
  productAnalytics,
  insights,
  kpiCards,
  trends,
  topProducts,
  fetchDashboard,
  fetchInsights,
  exportProductAnalytics,
} = useSellerAnalytics()

const selectedPeriod = ref<PeriodType>('last_30_days')
const customDateFrom = ref('')
const customDateTo = ref('')
const todayDate = ref(new Date().toISOString().split('T')[0])
const selectedCategory = ref('')
const showComparison = ref(false)
const previousPeriodData = ref<SellerDashboardResponse | null>(null)
const categories = ref<string[]>([
  'Electronics',
  'Fashion',
  'Home & Garden',
  'Sports',
  'Beauty',
  'Books',
  'Toys',
  'Food',
  'Automotive',
  'Health',
])

const loadData = async () => {
  if (selectedPeriod.value === 'custom' && (!customDateFrom.value || !customDateTo.value)) {
    return
  }

  const dateFrom = selectedPeriod.value === 'custom' ? customDateFrom.value : undefined
  const dateTo = selectedPeriod.value === 'custom' ? customDateTo.value : undefined

  await Promise.all([
    fetchDashboard(selectedPeriod.value, dateFrom, dateTo, selectedCategory.value),
    fetchInsights(selectedPeriod.value, dateFrom, dateTo, selectedCategory.value),
  ])
}

const handlePeriodChange = () => {
  if (selectedPeriod.value !== 'custom') {
    loadData()
  }
}

const handleCategoryChange = () => {
  loadData()
}

const toggleComparison = async () => {
  showComparison.value = !showComparison.value

  if (showComparison.value) {
    // Load previous period data for comparison
    await loadPreviousPeriodData()
  }
}

const loadPreviousPeriodData = async () => {
  const previousPeriod = getPreviousPeriod(selectedPeriod.value)
  const dateFrom = selectedPeriod.value === 'custom' ? customDateFrom.value : undefined
  const dateTo = selectedPeriod.value === 'custom' ? customDateTo.value : undefined

  if (previousPeriod) {
    const response = await fetch(`/api/analytics/seller/dashboard?period=${previousPeriod}${dateFrom && dateTo ? `&from=${dateFrom}&to=${dateTo}` : ''}${selectedCategory.value ? `&category=${encodeURIComponent(selectedCategory.value)}` : ''}`)
    if (response.ok) {
      previousPeriodData.value = await response.json()
    }
  }
}

const getPreviousPeriod = (currentPeriod: PeriodType): string | null => {
  switch (currentPeriod) {
    case 'today':
      return 'yesterday'
    case 'last_7_days':
      return 'last_7_days' // Previous 7 days
    case 'last_30_days':
      return 'last_30_days' // Previous 30 days
    case 'last_90_days':
      return 'last_90_days' // Previous 90 days
    case 'custom':
      return 'custom' // Will calculate from custom dates
    default:
      return null
  }
}

const applyCustomRange = () => {
  if (customDateFrom.value && customDateTo.value) {
    // Validate date range
    const fromDate = new Date(customDateFrom.value)
    const toDate = new Date(customDateTo.value)

    if (fromDate > toDate) {
      alert('Start date must be before end date')
      return
    }

    // Calculate days difference
    const daysDiff = Math.ceil((toDate.getTime() - fromDate.getTime()) / (1000 * 60 * 60 * 24))

    if (daysDiff > 365) {
      alert('Date range cannot exceed 1 year')
      return
    }

    loadData()
  }
}

const handleRefresh = () => {
  loadData()
}

const handleExport = async () => {
  try {
    // Create workbook with multiple sheets
    const workbook = XLSX.utils.book_new()

    // KPI Cards Sheet
    if (kpiCards.value.length > 0) {
      const kpiData = kpiCards.value.map(kpi => ({
        Label: kpi.label,
        Value: kpi.value,
        Previous: kpi.previous_value || 0,
        GrowthRate: kpi.growth_rate || 0,
        Trend: kpi.trend,
        Format: kpi.format,
      }))
      const kpiSheet = XLSX.utils.json_to_sheet(kpiData)
      XLSX.utils.book_append_sheet(workbook, kpiSheet, 'KPI Cards')
    }

    // Trends Sheet
    if (trends.value.length > 0) {
      const trendData = trends.value.flatMap(trend =>
        trend.dataPoints.map(dp => ({
          Trend: trend.label,
          Date: dp.date,
          Value: dp.value,
        }))
      )
      const trendSheet = XLSX.utils.json_to_sheet(trendData)
      XLSX.utils.book_append_sheet(workbook, trendSheet, 'Trends')
    }

    // Top Products Sheet
    if (topProducts.value.length > 0) {
      const productData = topProducts.value.map(product => ({
        ID: product.id,
        Name: product.name,
        Category: product.category || 'N/A',
        Revenue: product.value,
        Orders: product.metadata.orders,
        ConversionRate: product.metadata.conversion_rate,
      }))
      const productSheet = XLSX.utils.json_to_sheet(productData)
      XLSX.utils.book_append_sheet(workbook, productSheet, 'Top Products')
    }

    // Insights Sheet
    if (insights.value.length > 0) {
      const insightData = insights.value.map(insight => ({
        Type: insight.type,
        Title: insight.title,
        Message: insight.message,
        Severity: insight.severity,
        Metrics: JSON.stringify(insight.metrics),
        Recommendations: insight.recommendations.join('; '),
        MLModel: insight.ml_model || 'N/A',
      }))
      const insightSheet = XLSX.utils.json_to_sheet(insightData)
      XLSX.utils.book_append_sheet(workbook, insightSheet, 'Insights')
    }

    // Generate filename with period and date
    const date = new Date().toISOString().split('T')[0]
    const filename = `seller-analytics-${selectedPeriod.value}-${date}.xlsx`

    // Download the file
    XLSX.writeFile(workbook, filename)
  } catch (err) {
    console.error('Error exporting data:', err)
    alert('Failed to export data. Please try again.')
  }
}

const formatPeriod = (period: any): string => {
  const periodMap: Record<string, string> = {
    'today': 'Today',
    'last_7_days': 'Last 7 Days',
    'last_30_days': 'Last 30 Days',
    'last_90_days': 'Last 90 Days',
  }
  return periodMap[period.type] || period.type
}

const formatValue = (value: number, format: string): string => {
  switch (format) {
    case 'currency':
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
      }).format(value)
    case 'percentage':
      return `${value.toFixed(1)}%`
    case 'number':
      return new Intl.NumberFormat('en-US').format(value)
    default:
      return value.toString()
  }
}

onMounted(() => {
  loadData()
})
</script>
