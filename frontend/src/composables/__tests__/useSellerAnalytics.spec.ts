import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { ref } from 'vue'
import { useSellerAnalytics } from '../useSellerAnalytics'

// Mock global fetch
global.fetch = vi.fn()

describe('useSellerAnalytics', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  describe('fetchDashboard', () => {
    it('fetches dashboard data successfully', async () => {
      const mockResponse = {
        period: { type: 'last_30_days', from: '2024-01-01', to: '2024-01-30' },
        seller_id: 1,
        tenant_id: 1,
        kpi_cards: [
          {
            label: 'GMV',
            value: 10000,
            previous_value: 8000,
            growth_rate: 25,
            format: 'currency',
            trend: 'up',
          },
        ],
        trends: [
          {
            label: 'GMV Trend',
            dataPoints: [
              { date: '2024-01-01', value: 1000 },
              { date: '2024-01-02', value: 1200 },
            ],
          },
        ],
        top_products: {
          items: [
            {
              id: 1,
              name: 'Product A',
              value: 5000,
              metadata: { orders: 100, conversion_rate: 5.5 },
            },
          ],
          total: 1,
        },
        insights: [],
        generated_at: '2024-01-30T00:00:00Z',
      }

      vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      } as Response)

      const { fetchDashboard, dashboardData, loading, error } = useSellerAnalytics()

      await fetchDashboard('last_30_days')

      expect(dashboardData.value).toEqual(mockResponse)
      expect(loading.value).toBe(false)
      expect(error.value).toBe(null)
    })

    it('handles fetch error', async () => {
      vi.mocked(fetch).mockRejectedValueOnce(new Error('Network error'))

      const { fetchDashboard, dashboardData, loading, error } = useSellerAnalytics()

      await fetchDashboard('last_30_days')

      expect(dashboardData.value).toBe(null)
      expect(loading.value).toBe(false)
      expect(error.value).toBe('Failed to fetch dashboard data')
    })

    it('sets loading state during fetch', async () => {
      let resolveFetch: (value: Response) => void
      const fetchPromise = new Promise<Response>((resolve) => {
        resolveFetch = resolve
      })

      vi.mocked(fetch).mockReturnValueOnce(fetchPromise)

      const { fetchDashboard, loading } = useSellerAnalytics()

      const fetchCall = fetchDashboard('last_30_days')

      expect(loading.value).toBe(true)

      resolveFetch!({
        ok: true,
        json: async () => ({}),
      } as Response)

      await fetchCall

      expect(loading.value).toBe(false)
    })
  })

  describe('fetchProductAnalytics', () => {
    it('fetches product analytics successfully', async () => {
      const mockResponse = {
        data: [
          {
            id: 1,
            name: 'Product A',
            views: 1000,
            add_to_cart: 500,
            purchases: 100,
            revenue: 5000,
            conversion_rate: 10,
          },
        ],
        pagination: {
          total: 1,
          per_page: 50,
          current_page: 1,
          last_page: 1,
        },
      }

      vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse,
      } as Response)

      const { fetchProductAnalytics, productAnalytics } = useSellerAnalytics()

      await fetchProductAnalytics('last_30_days', 1, 50)

      expect(productAnalytics.value).toEqual(mockResponse)
    })

    it('passes pagination parameters', async () => {
      vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: [], pagination: {} }),
      } as Response)

      const { fetchProductAnalytics } = useSellerAnalytics()

      await fetchProductAnalytics('last_30_days', 2, 25)

      expect(fetch).toHaveBeenCalledWith(
        expect.stringContaining('page=2'),
        expect.any(Object)
      )
      expect(fetch).toHaveBeenCalledWith(
        expect.stringContaining('per_page=25'),
        expect.any(Object)
      )
    })
  })

  describe('fetchInsights', () => {
    it('fetches insights successfully', async () => {
      const mockInsights = [
        {
          type: 'revenue_drop',
          title: 'Revenue Decreased',
          message: 'Revenue dropped by 15%',
          severity: 'warning',
          metrics: { drop_rate: 15 },
          recommendations: ['Review pricing'],
        },
      ]

      vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        json: async () => mockInsights,
      } as Response)

      const { fetchInsights, insights } = useSellerAnalytics()

      await fetchInsights('last_30_days')

      expect(insights.value).toEqual(mockInsights)
    })
  })

  describe('formatValue', () => {
    it('formats currency values', () => {
      const { formatValue } = useSellerAnalytics()

      expect(formatValue(12500.50, 'currency')).toContain('$')
      expect(formatValue(12500.50, 'currency')).toContain('12,500.50')
    })

    it('formats percentage values', () => {
      const { formatValue } = useSellerAnalytics()

      expect(formatValue(5.55, 'percentage')).toBe('5.6%')
    })

    it('formats number values', () => {
      const { formatValue } = useSellerAnalytics()

      expect(formatValue(1500, 'number')).toBe('1,500')
    })

    it('returns string for unknown format', () => {
      const { formatValue } = useSellerAnalytics()

      expect(formatValue(100, 'unknown')).toBe('100')
    })
  })

  describe('getTrendIcon', () => {
    it('returns up arrow for up trend', () => {
      const { getTrendIcon } = useSellerAnalytics()

      expect(getTrendIcon('up')).toBe('↑')
    })

    it('returns down arrow for down trend', () => {
      const { getTrendIcon } = useSellerAnalytics()

      expect(getTrendIcon('down')).toBe('↓')
    })

    it('returns right arrow for neutral trend', () => {
      const { getTrendIcon } = useSellerAnalytics()

      expect(getTrendIcon('neutral')).toBe('→')
    })
  })

  describe('getTrendColor', () => {
    it('returns green for up trend', () => {
      const { getTrendColor } = useSellerAnalytics()

      expect(getTrendColor('up')).toBe('text-green-600')
    })

    it('returns red for down trend', () => {
      const { getTrendColor } = useSellerAnalytics()

      expect(getTrendColor('down')).toBe('text-red-600')
    })

    it('returns gray for neutral trend', () => {
      const { getTrendColor } = useSellerAnalytics()

      expect(getTrendColor('neutral')).toBe('text-gray-600')
    })
  })

  describe('getSeverityColor', () => {
    it('returns red styling for critical severity', () => {
      const { getSeverityColor } = useSellerAnalytics()

      expect(getSeverityColor('critical')).toBe('border-red-500 bg-red-50')
    })

    it('returns yellow styling for warning severity', () => {
      const { getSeverityColor } = useSellerAnalytics()

      expect(getSeverityColor('warning')).toBe('border-yellow-500 bg-yellow-50')
    })

    it('returns green styling for opportunity severity', () => {
      const { getSeverityColor } = useSellerAnalytics()

      expect(getSeverityColor('opportunity')).toBe('border-green-500 bg-green-50')
    })

    it('returns gray styling for info severity', () => {
      const { getSeverityColor } = useSellerAnalytics()

      expect(getSeverityColor('info')).toBe('border-gray-200 bg-white')
    })
  })

  describe('getSeverityTextColor', () => {
    it('returns red text for critical severity', () => {
      const { getSeverityTextColor } = useSellerAnalytics()

      expect(getSeverityTextColor('critical')).toBe('text-red-800')
    })

    it('returns yellow text for warning severity', () => {
      const { getSeverityTextColor } = useSellerAnalytics()

      expect(getSeverityTextColor('warning')).toBe('text-yellow-800')
    })

    it('returns green text for opportunity severity', () => {
      const { getSeverityTextColor } = useSellerAnalytics()

      expect(getSeverityTextColor('opportunity')).toBe('text-green-800')
    })
  })

  describe('computed properties', () => {
    it('kpiCards returns empty array when dashboardData is null', () => {
      const { kpiCards } = useSellerAnalytics()

      expect(kpiCards.value).toEqual([])
    })

    it('kpiCards returns kpi_cards from dashboardData', () => {
      const { kpiCards, dashboardData } = useSellerAnalytics()

      dashboardData.value = {
        kpi_cards: [{ label: 'Test', value: 100, format: 'currency', trend: 'up' }],
      } as any

      expect(kpiCards.value).toHaveLength(1)
    })

    it('trends returns empty array when dashboardData is null', () => {
      const { trends } = useSellerAnalytics()

      expect(trends.value).toEqual([])
    })

    it('topProducts returns empty array when dashboardData is null', () => {
      const { topProducts } = useSellerAnalytics()

      expect(topProducts.value).toEqual([])
    })
  })

  describe('exportProductAnalytics', () => {
    it('downloads Excel file on successful export', async () => {
      const mockBlob = new Blob(['test'], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' })
      vi.mocked(fetch).mockResolvedValueOnce({
        ok: true,
        blob: async () => mockBlob,
      } as Response)

      // Mock URL.createObjectURL and revokeObjectURL
      const mockUrl = 'blob:test-url'
      global.URL.createObjectURL = vi.fn(() => mockUrl)
      global.URL.revokeObjectURL = vi.fn()

      // Mock document.createElement and related methods
      const mockAnchor = {
        href: '',
        download: '',
        click: vi.fn(),
      }
      document.createElement = vi.fn(() => mockAnchor as any)
      document.body.appendChild = vi.fn()
      document.body.removeChild = vi.fn()

      const { exportProductAnalytics } = useSellerAnalytics()

      await exportProductAnalytics('last_30_days')

      expect(fetch).toHaveBeenCalledWith(
        expect.stringContaining('/export/products'),
        expect.any(Object)
      )
      expect(document.createElement).toHaveBeenCalledWith('a')
      expect(mockAnchor.click).toHaveBeenCalled()
    })

    it('handles export error', async () => {
      vi.mocked(fetch).mockRejectedValueOnce(new Error('Export failed'))

      const { exportProductAnalytics, error } = useSellerAnalytics()

      await exportProductAnalytics('last_30_days')

      expect(error.value).toBe('Failed to export data')
    })
  })
})
