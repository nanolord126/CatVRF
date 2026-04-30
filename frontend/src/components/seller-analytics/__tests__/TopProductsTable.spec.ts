import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import TopProductsTable from '../TopProductsTable.vue'

describe('TopProductsTable', () => {
  it('renders products table', () => {
    const wrapper = mount(TopProductsTable, {
      props: {
        products: [
          {
            id: 1,
            name: 'Product A',
            category: 'Electronics',
            value: 5000,
            metadata: {
              orders: 100,
              conversion_rate: 5.5,
            },
          },
        ],
      },
    })

    expect(wrapper.text()).toContain('Product A')
    expect(wrapper.text()).toContain('Electronics')
  })

  it('displays revenue in currency format', () => {
    const wrapper = mount(TopProductsTable, {
      props: {
        products: [
          {
            id: 1,
            name: 'Product A',
            value: 12500.50,
            metadata: {
              orders: 100,
              conversion_rate: 5.5,
            },
          },
        ],
      },
    })

    expect(wrapper.text()).toContain('$')
  })

  it('displays orders count', () => {
    const wrapper = mount(TopProductsTable, {
      props: {
        products: [
          {
            id: 1,
            name: 'Product A',
            value: 5000,
            metadata: {
              orders: 1500,
              conversion_rate: 5.5,
            },
          },
        ],
      },
    })

    expect(wrapper.text()).toContain('1,500')
  })

  it('displays conversion rate', () => {
    const wrapper = mount(TopProductsTable, {
      props: {
        products: [
          {
            id: 1,
            name: 'Product A',
            value: 5000,
            metadata: {
              orders: 100,
              conversion_rate: 5.5,
            },
          },
        ],
      },
    })

    expect(wrapper.text()).toContain('5.5%')
  })

  it('shows ranking badges for top 3 products', () => {
    const wrapper = mount(TopProductsTable, {
      props: {
        products: [
          {
            id: 1,
            name: 'Product 1',
            value: 5000,
            metadata: { orders: 100, conversion_rate: 5.5 },
          },
          {
            id: 2,
            name: 'Product 2',
            value: 4000,
            metadata: { orders: 80, conversion_rate: 4.5 },
          },
          {
            id: 3,
            name: 'Product 3',
            value: 3000,
            metadata: { orders: 60, conversion_rate: 3.5 },
          },
        ],
      },
    })

    const badges = wrapper.findAll('.bg-indigo-100')
    expect(badges.length).toBe(3)
  })

  it('shows gray badge for products beyond top 3', () => {
    const wrapper = mount(TopProductsTable, {
      props: {
        products: [
          {
            id: 1,
            name: 'Product 1',
            value: 5000,
            metadata: { orders: 100, conversion_rate: 5.5 },
          },
          {
            id: 2,
            name: 'Product 2',
            value: 4000,
            metadata: { orders: 80, conversion_rate: 4.5 },
          },
          {
            id: 3,
            name: 'Product 3',
            value: 3000,
            metadata: { orders: 60, conversion_rate: 3.5 },
          },
          {
            id: 4,
            name: 'Product 4',
            value: 2000,
            metadata: { orders: 40, conversion_rate: 2.5 },
          },
        ],
      },
    })

    const grayBadge = wrapper.find('.bg-gray-100')
    expect(grayBadge.exists()).toBe(true)
  })

  it('shows green color for high conversion rate', () => {
    const wrapper = mount(TopProductsTable, {
      props: {
        products: [
          {
            id: 1,
            name: 'Product A',
            value: 5000,
            metadata: {
              orders: 100,
              conversion_rate: 6.0,
            },
          },
        ],
      },
    })

    expect(wrapper.find('.text-green-600').exists()).toBe(true)
  })

  it('shows red color for low conversion rate', () => {
    const wrapper = mount(TopProductsTable, {
      props: {
        products: [
          {
            id: 1,
            name: 'Product A',
            value: 5000,
            metadata: {
              orders: 100,
              conversion_rate: 2.0,
            },
          },
        ],
      },
    })

    expect(wrapper.find('.text-red-600').exists()).toBe(true)
  })

  it('shows N/A for missing category', () => {
    const wrapper = mount(TopProductsTable, {
      props: {
        products: [
          {
            id: 1,
            name: 'Product A',
            value: 5000,
            metadata: {
              orders: 100,
              conversion_rate: 5.5,
            },
          },
        ],
      },
    })

    expect(wrapper.text()).toContain('N/A')
  })

  it('shows empty state when no products', () => {
    const wrapper = mount(TopProductsTable, {
      props: {
        products: [],
      },
    })

    expect(wrapper.text()).toContain('No products data available')
  })

  it('limits displayed products to maxItems', () => {
    const wrapper = mount(TopProductsTable, {
      props: {
        products: [
          { id: 1, name: 'Product 1', value: 5000, metadata: { orders: 100, conversion_rate: 5.5 } },
          { id: 2, name: 'Product 2', value: 4000, metadata: { orders: 80, conversion_rate: 4.5 } },
          { id: 3, name: 'Product 3', value: 3000, metadata: { orders: 60, conversion_rate: 3.5 } },
        ],
        maxItems: 2,
      },
    })

    expect(wrapper.text()).toContain('Product 1')
    expect(wrapper.text()).toContain('Product 2')
    expect(wrapper.text()).not.toContain('Product 3')
  })

  it('shows show more button when products exceed maxItems', () => {
    const wrapper = mount(TopProductsTable, {
      props: {
        products: [
          { id: 1, name: 'Product 1', value: 5000, metadata: { orders: 100, conversion_rate: 5.5 } },
          { id: 2, name: 'Product 2', value: 4000, metadata: { orders: 80, conversion_rate: 4.5 } },
          { id: 3, name: 'Product 3', value: 3000, metadata: { orders: 60, conversion_rate: 3.5 } },
        ],
        maxItems: 2,
      },
    })

    expect(wrapper.text()).toContain('View all')
  })

  it('shows loading state', () => {
    const wrapper = mount(TopProductsTable, {
      props: {
        products: [],
        loading: true,
      },
    })

    expect(wrapper.find('.animate-pulse').exists()).toBe(true)
  })
})
