import { ref, computed } from 'vue'
import type {
  SellerDashboardResponse,
  ProductAnalyticsResponse,
  SellerInsight[],
  PeriodType,
} from '@/types/analytics'

export function useSellerAnalytics() {
  const loading = ref(false)
  const error = ref<string | null>(null)
  const dashboardData = ref<SellerDashboardResponse | null>(null)
  const productAnalytics = ref<ProductAnalyticsResponse | null>(null)
  const insights = ref<SellerInsight[]>([])

  const apiBase = '/api/seller/analytics'

  /**
   * Fetch dashboard data
   */
  const fetchDashboard = async (period: PeriodType = 'last_30_days', dateFrom?: string, dateTo?: string, category?: string) => {
    loading.value = true
    error.value = null

    let url = `${apiBase}/dashboard?period=${period}`
    if (period === 'custom' && dateFrom && dateTo) {
      url += `&from=${dateFrom}&to=${dateTo}`
    }
    if (category) {
      url += `&category=${encodeURIComponent(category)}`
    }

    try {
      const response = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data: SellerDashboardResponse = await response.json()
      dashboardData.value = data
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch dashboard data'
      console.error('Error fetching dashboard:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch product analytics
   */
  const fetchProductAnalytics = async (
    period: PeriodType = 'last_30_days',
    page: number = 1,
    perPage: number = 50,
    dateFrom?: string,
    dateTo?: string
  ) => {
    loading.value = true
    error.value = null

    let url = `${apiBase}/products?period=${period}&page=${page}&per_page=${perPage}`
    if (period === 'custom' && dateFrom && dateTo) {
      url += `&from=${dateFrom}&to=${dateTo}`
    }

    try {
      const response = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data: ProductAnalyticsResponse = await response.json()
      productAnalytics.value = data
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch product analytics'
      console.error('Error fetching product analytics:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Fetch AI insights
   */
  const fetchInsights = async (period: PeriodType = 'last_30_days', dateFrom?: string, dateTo?: string, category?: string) => {
    loading.value = true
    error.value = null

    let url = `${apiBase}/insights?period=${period}`
    if (period === 'custom' && dateFrom && dateTo) {
      url += `&from=${dateFrom}&to=${dateTo}`
    }
    if (category) {
      url += `&category=${encodeURIComponent(category)}`
    }

    try {
      const response = await fetch(url, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data: SellerInsight[] = await response.json()
      insights.value = data
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to fetch insights'
      console.error('Error fetching insights:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Export product analytics to Excel
   */
  const exportProductAnalytics = async (period: PeriodType = 'last_30_days') => {
    try {
      const response = await fetch(`${apiBase}/export/products?period=${period}`)
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const blob = await response.blob()
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `product-metrics-${period}-${new Date().toISOString().split('T')[0]}.xlsx`
      document.body.appendChild(a)
      a.click()
      window.URL.revokeObjectURL(url)
      document.body.removeChild(a)
    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Failed to export data'
      console.error('Error exporting data:', err)
    }
  }

  /**
   * Format value based on format type
   */
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

  /**
   * Get trend icon
   */
  const getTrendIcon = (trend: string): string => {
    switch (trend) {
      case 'up':
        return '↑'
      case 'down':
        return '↓'
      default:
        return '→'
    }
  }

  /**
   * Get trend color class
   */
  const getTrendColor = (trend: string): string => {
    switch (trend) {
      case 'up':
        return 'text-green-600'
      case 'down':
        return 'text-red-600'
      default:
        return 'text-gray-600'
    }
  }

  /**
   * Get severity color class
   */
  const getSeverityColor = (severity: string): string => {
    switch (severity) {
      case 'critical':
        return 'border-red-500 bg-red-50'
      case 'warning':
        return 'border-yellow-500 bg-yellow-50'
      case 'opportunity':
        return 'border-green-500 bg-green-50'
      default:
        return 'border-gray-200 bg-white'
    }
  }

  /**
   * Get severity text color
   */
  const getSeverityTextColor = (severity: string): string => {
    switch (severity) {
      case 'critical':
        return 'text-red-800'
      case 'warning':
        return 'text-yellow-800'
      case 'opportunity':
        return 'text-green-800'
      default:
        return 'text-gray-800'
    }
  }

  const kpiCards = computed(() => dashboardData.value?.kpi_cards ?? [])
  const trends = computed(() => dashboardData.value?.trends ?? [])
  const topProducts = computed(() => dashboardData.value?.top_products?.items ?? [])

  return {
    loading,
    error,
    dashboardData,
    productAnalytics,
    insights,
    kpiCards,
    trends,
    topProducts,
    fetchDashboard,
    fetchProductAnalytics,
    fetchInsights,
    exportProductAnalytics,
    formatValue,
    getTrendIcon,
    getTrendColor,
    getSeverityColor,
    getSeverityTextColor,
  }
}
