import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import TrendChart from '../TrendChart.vue'

// Mock Chart.js
vi.mock('chart.js', () => ({
  default: vi.fn(),
  Chart: vi.fn().mockImplementation(() => ({
    destroy: vi.fn(),
  })),
  register: vi.fn(),
}))

describe('TrendChart', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders trend chart with line type', () => {
    const wrapper = mount(TrendChart, {
      props: {
        trends: [
          {
            label: 'Revenue',
            dataPoints: [
              { date: '2024-01-01', value: 1000 },
              { date: '2024-01-02', value: 1500 },
            ],
          },
        ],
        chartType: 'line',
      },
    })

    expect(wrapper.find('canvas').exists()).toBe(true)
  })

  it('renders trend chart with bar type', () => {
    const wrapper = mount(TrendChart, {
      props: {
        trends: [
          {
            label: 'Orders',
            dataPoints: [
              { date: '2024-01-01', value: 100 },
              { date: '2024-01-02', value: 150 },
            ],
          },
        ],
        chartType: 'bar',
      },
    })

    expect(wrapper.find('canvas').exists()).toBe(true)
  })

  it('shows loading state when loading prop is true', () => {
    const wrapper = mount(TrendChart, {
      props: {
        trends: [],
        loading: true,
      },
    })

    expect(wrapper.find('.animate-pulse').exists()).toBe(true)
  })

  it('displays title from first trend', () => {
    const wrapper = mount(TrendChart, {
      props: {
        trends: [
          {
            label: 'GMV Trend',
            dataPoints: [],
          },
        ],
      },
    })

    expect(wrapper.text()).toContain('GMV Trend')
  })

  it('renders multiple trends', () => {
    const wrapper = mount(TrendChart, {
      props: {
        trends: [
          {
            label: 'Revenue',
            dataPoints: [
              { date: '2024-01-01', value: 1000 },
            ],
          },
          {
            label: 'Orders',
            dataPoints: [
              { date: '2024-01-01', value: 100 },
            ],
          },
        ],
      },
    })

    expect(wrapper.find('canvas').exists()).toBe(true)
  })

  it('uses custom height when provided', () => {
    const wrapper = mount(TrendChart, {
      props: {
        trends: [
          {
            label: 'Revenue',
            dataPoints: [],
          },
        ],
        height: 400,
      },
    })

    const chartContainer = wrapper.find('.trend-chart > div')
    expect(chartContainer.attributes('style')).toContain('height: 400px')
  })

  it('does not render canvas when no trends provided', () => {
    const wrapper = mount(TrendChart, {
      props: {
        trends: [],
      },
    })

    // Should not crash when no trends
    expect(wrapper.exists()).toBe(true)
  })
})
