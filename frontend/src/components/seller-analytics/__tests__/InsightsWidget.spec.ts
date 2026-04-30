import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import InsightsWidget from '../InsightsWidget.vue'

describe('InsightsWidget', () => {
  it('renders insights list', () => {
    const wrapper = mount(InsightsWidget, {
      props: {
        insights: [
          {
            type: 'revenue_drop',
            title: 'Revenue Decreased',
            message: 'Revenue dropped by 15% compared to previous period',
            severity: 'warning',
            metrics: {
              'Current Revenue': 10000,
              'Previous Revenue': 11765,
            },
            recommendations: [
              'Review pricing strategy',
              'Check stock availability',
            ],
          },
        ],
      },
    })

    expect(wrapper.text()).toContain('Revenue Decreased')
    expect(wrapper.text()).toContain('Revenue dropped by 15%')
  })

  it('shows critical severity styling', () => {
    const wrapper = mount(InsightsWidget, {
      props: {
        insights: [
          {
            type: 'critical',
            title: 'Critical Issue',
            message: 'This is critical',
            severity: 'critical',
            metrics: {},
            recommendations: [],
          },
        ],
      },
    })

    expect(wrapper.find('.border-red-500').exists()).toBe(true)
  })

  it('shows warning severity styling', () => {
    const wrapper = mount(InsightsWidget, {
      props: {
        insights: [
          {
            type: 'warning',
            title: 'Warning',
            message: 'This is a warning',
            severity: 'warning',
            metrics: {},
            recommendations: [],
          },
        ],
      },
    })

    expect(wrapper.find('.border-yellow-500').exists()).toBe(true)
  })

  it('shows opportunity severity styling', () => {
    const wrapper = mount(InsightsWidget, {
      props: {
        insights: [
          {
            type: 'opportunity',
            title: 'Opportunity',
            message: 'This is an opportunity',
            severity: 'opportunity',
            metrics: {},
            recommendations: [],
          },
        ],
      },
    })

    expect(wrapper.find('.border-green-500').exists()).toBe(true)
  })

  it('displays metrics', () => {
    const wrapper = mount(InsightsWidget, {
      props: {
        insights: [
          {
            type: 'metric',
            title: 'Metric Insight',
            message: 'Insight with metrics',
            severity: 'info',
            metrics: {
              'Orders': 1000,
              'Revenue': 50000,
            },
            recommendations: [],
          },
        ],
      },
    })

    expect(wrapper.text()).toContain('Orders')
    expect(wrapper.text()).toContain('Revenue')
  })

  it('displays recommendations', () => {
    const wrapper = mount(InsightsWidget, {
      props: {
        insights: [
          {
            type: 'recommendation',
            title: 'Recommendation',
            message: 'Insight with recommendations',
            severity: 'info',
            metrics: {},
            recommendations: [
              'Do this',
              'Do that',
            ],
          },
        ],
      },
    })

    expect(wrapper.text()).toContain('Do this')
    expect(wrapper.text()).toContain('Do that')
    expect(wrapper.text()).toContain('Recommendations')
  })

  it('shows empty state when no insights', () => {
    const wrapper = mount(InsightsWidget, {
      props: {
        insights: [],
      },
    })

    expect(wrapper.text()).toContain('No insights available')
  })

  it('limits displayed insights to maxItems', () => {
    const wrapper = mount(InsightsWidget, {
      props: {
        insights: [
          { type: '1', title: 'Insight 1', message: 'Message 1', severity: 'info', metrics: {}, recommendations: [] },
          { type: '2', title: 'Insight 2', message: 'Message 2', severity: 'info', metrics: {}, recommendations: [] },
          { type: '3', title: 'Insight 3', message: 'Message 3', severity: 'info', metrics: {}, recommendations: [] },
        ],
        maxItems: 2,
      },
    })

    expect(wrapper.text()).toContain('Insight 1')
    expect(wrapper.text()).toContain('Insight 2')
    expect(wrapper.text()).not.toContain('Insight 3')
  })

  it('shows show more button when insights exceed maxItems', async () => {
    const wrapper = mount(InsightsWidget, {
      props: {
        insights: [
          { type: '1', title: 'Insight 1', message: 'Message 1', severity: 'info', metrics: {}, recommendations: [] },
          { type: '2', title: 'Insight 2', message: 'Message 2', severity: 'info', metrics: {}, recommendations: [] },
          { type: '3', title: 'Insight 3', message: 'Message 3', severity: 'info', metrics: {}, recommendations: [] },
        ],
        maxItems: 2,
      },
    })

    expect(wrapper.text()).toContain('Show all')
  })

  it('toggles show all on button click', async () => {
    const wrapper = mount(InsightsWidget, {
      props: {
        insights: [
          { type: '1', title: 'Insight 1', message: 'Message 1', severity: 'info', metrics: {}, recommendations: [] },
          { type: '2', title: 'Insight 2', message: 'Message 2', severity: 'info', metrics: {}, recommendations: [] },
          { type: '3', title: 'Insight 3', message: 'Message 3', severity: 'info', metrics: {}, recommendations: [] },
        ],
        maxItems: 2,
      },
    })

    await wrapper.find('button').trigger('click')
    expect(wrapper.text()).toContain('Insight 3')
  })

  it('shows loading state', () => {
    const wrapper = mount(InsightsWidget, {
      props: {
        insights: [],
        loading: true,
      },
    })

    expect(wrapper.find('.animate-pulse').exists()).toBe(true)
  })

  it('displays ML model name when provided', () => {
    const wrapper = mount(InsightsWidget, {
      props: {
        insights: [
          {
            type: 'ml',
            title: 'ML Insight',
            message: 'Generated by ML',
            severity: 'info',
            metrics: {},
            recommendations: [],
            ml_model: 'XGBoost-Revenue-Predictor',
          },
        ],
      },
    })

    expect(wrapper.text()).toContain('XGBoost-Revenue-Predictor')
  })
})
