/**
 * Seller Analytics API Types
 * TypeScript types for the Seller Analytics API responses
 */

export interface KPICard {
  label: string
  value: number
  previous_value?: number
  growth_rate?: number
  format: 'currency' | 'percentage' | 'number'
  trend: 'up' | 'down' | 'neutral'
  icon?: string
}

export interface TrendDataPoint {
  date: string
  value: number
}

export interface Trend {
  label: string
  dataPoints: TrendDataPoint[]
}

export interface TopProduct {
  id: number
  name: string
  category?: string
  value: number
  metadata: {
    orders: number
    conversion_rate: number
  }
}

export interface TopProductsResponse {
  items: TopProduct[]
  total: number
}

export interface SellerInsight {
  type: string
  title: string
  message: string
  severity: 'critical' | 'warning' | 'opportunity' | 'info'
  metrics: Record<string, number>
  recommendations: string[]
  ml_model?: string
}

export interface Period {
  type: string
  from: string
  to: string
}

export interface SellerDashboardResponse {
  period: Period
  seller_id: number
  tenant_id: number
  kpi_cards: KPICard[]
  trends: Trend[]
  top_products: TopProductsResponse
  insights: SellerInsight[]
  generated_at: string
}

export interface ProductAnalytics {
  id: number
  name: string
  category?: string
  price: number
  views: number
  add_to_cart: number
  purchases: number
  revenue: number
  conversion_rate: number
  cart_conversion_rate: number
  refunds: number
  refund_rate: number
  avg_rating: number
}

export interface ProductAnalyticsResponse {
  data: ProductAnalytics[]
  pagination: {
    total: number
    per_page: number
    current_page: number
    last_page: number
  }
}

export type PeriodType = 'today' | 'last_7_days' | 'last_30_days' | 'last_90_days' | 'custom'

export interface ChartData {
  labels: string[]
  datasets: {
    label: string
    data: number[]
    borderColor?: string
    backgroundColor?: string
    fill?: boolean
    tension?: number
  }[]
}

export interface ChartConfig {
  type: 'line' | 'bar' | 'doughnut' | 'pie'
  data: ChartData
  options?: any
}

export interface ExportOptions {
  format: 'xlsx' | 'csv'
  includeHeaders?: boolean
  filename?: string
}

export interface AnalyticsFilter {
  period: PeriodType
  category?: string
  dateRange?: {
    from: string
    to: string
  }
}

export interface KPICardProps {
  kpi: KPICard
  loading?: boolean
}

export interface TrendChartProps {
  trends: Trend[]
  height?: number
  loading?: boolean
}

export interface InsightsWidgetProps {
  insights: SellerInsight[]
  loading?: boolean
  maxItems?: number
}

export interface TopProductsTableProps {
  products: TopProduct[]
  loading?: boolean
  maxItems?: number
}
