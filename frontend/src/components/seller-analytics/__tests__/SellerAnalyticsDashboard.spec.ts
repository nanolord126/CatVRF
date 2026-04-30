import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import SellerAnalyticsDashboard from '../SellerAnalyticsDashboard.vue'
import * as XLSX from 'xlsx'

// Mock useSellerAnalytics composable
vi.mock('@/composables/useSellerAnalytics', () => ({
  useSellerAnalytics: () => ({
    loading: ref(false),
    error: ref(null),
    dashboardData: ref({
      period: { type: 'last_30_days', from: '2024-01-01', to: '2024-01-30' },
      seller_id: 1,
      tenant_id: 1,
      kpi_cards: [
        { label: 'Revenue', value: 10000, format: 'currency', trend: 'up' },
      ],
      trends: [
        { label: 'GMV', dataPoints: [{ date: '2024-01-01', value: 1000 }] },
      ],
      top_products: { items: [], total: 0 },
      insights: [],
      generated_at: '2024-01-30T00:00:00Z',
    }),
    productAnalytics: ref(null),
    insights: ref([]),
    kpiCards: computed(() => []),
    trends: computed(() => []),
    topProducts: computed(() => []),
    fetchDashboard: vi.fn(),
    fetchInsights: vi.fn(),
    exportProductAnalytics: vi.fn(),
  }),
}))

// Mock XLSX
vi.mock('xlsx', () => ({
  utils: {
    book_new: vi.fn(() => ({})),
    json_to_sheet: vi.fn(() => ({})),
    book_append_sheet: vi.fn(),
  },
  writeFile: vi.fn(),
}))

describe('SellerAnalyticsDashboard', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders dashboard header', () => {
    const wrapper = mount(SellerAnalyticsDashboard)

    expect(wrapper.text()).toContain('Seller Analytics')
  })

  it('renders period selector', () => {
    const wrapper = mount(SellerAnalyticsDashboard)

    expect(wrapper.find('select').exists()).toBe(true)
  })

  it('renders export button', () => {
    const wrapper = mount(SellerAnalyticsDashboard)

    const exportButton = wrapper.findAll('button').find(btn => btn.text().includes('Export'))
    expect(exportButton).toBeDefined()
  })

  it('renders refresh button', () => {
    const wrapper = mount(SellerAnalyticsDashboard)

    const refreshButton = wrapper.findAll('button').find(btn => btn.text().includes('Refresh'))
    expect(refreshButton).toBeDefined()
  })

  it('displays error state when error exists', async () => {
    const { useSellerAnalytics } = await import('@/composables/useSellerAnalytics')
    vi.mocked(useSellerAnalytics).mockReturnValue({
      loading: ref(false),
      error: ref('Failed to load data'),
      dashboardData: ref(null),
      productAnalytics: ref(null),
      insights: ref([]),
      kpiCards: computed(() => []),
      trends: computed(() => []),
      topProducts: computed(() => []),
      fetchDashboard: vi.fn(),
      fetchInsights: vi.fn(),
      exportProductAnalytics: vi.fn(),
    } as any)

    const wrapper = mount(SellerAnalyticsDashboard)

    expect(wrapper.text()).toContain('Error loading analytics')
    expect(wrapper.text()).toContain('Failed to load data')
  })

  it('calls fetchDashboard on mount', async () => {
    const { useSellerAnalytics } = await import('@/composables/useSellerAnalytics')
    const mockFetch = vi.fn()
    vi.mocked(useSellerAnalytics).mockReturnValue({
      loading: ref(false),
      error: ref(null),
      dashboardData: ref(null),
      productAnalytics: ref(null),
      insights: ref([]),
      kpiCards: computed(() => []),
      trends: computed(() => []),
      topProducts: computed(() => []),
      fetchDashboard: mockFetch,
      fetchInsights: vi.fn(),
      exportProductAnalytics: vi.fn(),
    } as any)

    mount(SellerAnalyticsDashboard)

    expect(mockFetch).toHaveBeenCalled()
  })

  it('disables buttons when loading', async () => {
    const { useSellerAnalytics } = await import('@/composables/useSellerAnalytics')
    vi.mocked(useSellerAnalytics).mockReturnValue({
      loading: ref(true),
      error: ref(null),
      dashboardData: ref(null),
      productAnalytics: ref(null),
      insights: ref([]),
      kpiCards: computed(() => []),
      trends: computed(() => []),
      topProducts: computed(() => []),
      fetchDashboard: vi.fn(),
      fetchInsights: vi.fn(),
      exportProductAnalytics: vi.fn(),
    } as any)

    const wrapper = mount(SellerAnalyticsDashboard)

    const buttons = wrapper.findAll('button')
    buttons.forEach(btn => {
      expect((btn.element as HTMLButtonElement).disabled).toBe(true)
    })
  })
})
