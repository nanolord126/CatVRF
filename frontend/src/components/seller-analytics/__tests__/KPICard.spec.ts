import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import KPICard from '../KPICard.vue'

describe('KPICard', () => {
  it('renders KPI card with currency format', () => {
    const wrapper = mount(KPICard, {
      props: {
        kpi: {
          label: 'Revenue',
          value: 12500.50,
          previous_value: 10000,
          growth_rate: 25,
          format: 'currency',
          trend: 'up',
          icon: '💰',
        },
      },
    })

    expect(wrapper.text()).toContain('Revenue')
    expect(wrapper.text()).toContain('$')
  })

  it('renders KPI card with percentage format', () => {
    const wrapper = mount(KPICard, {
      props: {
        kpi: {
          label: 'Conversion Rate',
          value: 5.5,
          format: 'percentage',
          trend: 'neutral',
        },
      },
    })

    expect(wrapper.text()).toContain('Conversion Rate')
    expect(wrapper.text()).toContain('5.5%')
  })

  it('renders KPI card with number format', () => {
    const wrapper = mount(KPICard, {
      props: {
        kpi: {
          label: 'Orders',
          value: 1500,
          format: 'number',
          trend: 'down',
        },
      },
    })

    expect(wrapper.text()).toContain('Orders')
    expect(wrapper.text()).toContain('1,500')
  })

  it('shows correct trend icon for up trend', () => {
    const wrapper = mount(KPICard, {
      props: {
        kpi: {
          label: 'Revenue',
          value: 1000,
          format: 'currency',
          trend: 'up',
        },
      },
    })

    expect(wrapper.text()).toContain('↑')
  })

  it('shows correct trend icon for down trend', () => {
    const wrapper = mount(KPICard, {
      props: {
        kpi: {
          label: 'Revenue',
          value: 1000,
          format: 'currency',
          trend: 'down',
        },
      },
    })

    expect(wrapper.text()).toContain('↓')
  })

  it('shows loading state when loading prop is true', () => {
    const wrapper = mount(KPICard, {
      props: {
        kpi: {
          label: 'Revenue',
          value: 1000,
          format: 'currency',
          trend: 'up',
        },
        loading: true,
      },
    })

    expect(wrapper.find('.animate-pulse').exists()).toBe(true)
  })

  it('shows previous value when provided', () => {
    const wrapper = mount(KPICard, {
      props: {
        kpi: {
          label: 'Revenue',
          value: 15000,
          previous_value: 10000,
          format: 'currency',
          trend: 'up',
        },
      },
    })

    expect(wrapper.text()).toContain('$10,000')
  })
})
