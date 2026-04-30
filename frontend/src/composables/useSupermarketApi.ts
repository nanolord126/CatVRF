import { ref } from 'vue'

interface Product {
  id: string
  name: string
  description?: string
  price: number
  weight?: number
  requires_cold_chain: boolean
  shelf_life_days?: number
  sub_vertical: string
  attributes?: Record<string, any>
  is_active: boolean
  image?: string
  tenant_id?: string
  is_age_restricted?: boolean
  min_age?: number
}

interface Order {
  id: string
  uuid: string
  status: string
  sub_vertical: string
  total_amount: number
  delivery_cost?: number
  delivery_address?: string
  delivery_slot?: string
  delivery_eta?: number
  cold_chain_required: boolean
  created_at: string
  tenant_id?: string
  user_id?: number
  items?: OrderItem[]
}

interface OrderItem {
  id: string
  order_id: string
  product_id: string
  quantity: number
  price_per_unit: number
  total_price: number
  product?: Product
}

interface InventoryItem {
  id: string
  product_id: string
  quantity: number
  reserved: number
  batch_number?: string
  location?: string
  expires_at?: string
}

interface Metrics {
  total_revenue: number
  today_revenue: number
  month_revenue: number
  active_orders: number
  total_products: number
  low_stock_items: number
  revenue_trend: number
  today_trend: number
  month_trend: number
}

interface Subscription {
  id: string
  buyer_id: string
  seller_id: string
  status: string
  frequency: string
  delivery_day: number
  next_delivery_at: string
  total_amount: number
  is_b2b: boolean
  pause_until?: string
  items?: SubscriptionItem[]
}

interface SubscriptionItem {
  id: string
  subscription_id: string
  product_id: string
  variant_id?: string
  quantity: number
  price_per_unit_at_creation: number
  product?: Product
}

interface BuyerDashboard {
  user: {
    id: number
    name: string
    email: string
    bonus_balance: number
    age_verified: boolean
    age_verified_at?: string
  }
  active_subscriptions: Subscription[]
  last_order?: Order
  ai_recommendations: Product[]
  quick_access_categories: { id: string; name: string; icon: string }[]
  promotions: Array<{
    id: string
    title: string
    description: string
    discount_percent: number
    valid_until: string
  }>
}

export function useSupermarketApi() {
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Products
  const products = ref<Product[]>([])
  const currentProduct = ref<Product | null>(null)

  // Orders
  const orders = ref<Order[]>([])
  const currentOrder = ref<Order | null>(null)

  // Inventory
  const inventoryItems = ref<InventoryItem[]>([])

  // Analytics
  const metrics = ref<Metrics>({
    total_revenue: 0,
    today_revenue: 0,
    month_revenue: 0,
    active_orders: 0,
    total_products: 0,
    low_stock_items: 0,
    revenue_trend: 0,
    today_trend: 0,
    month_trend: 0
  })

  // Product API
  const fetchProducts = async (params?: Record<string, any>) => {
    loading.value = true
    error.value = null
    try {
      const queryString = new URLSearchParams(params as any).toString()
      const response = await fetch(`/api/supermarket/products${queryString ? '?' + queryString : ''}`)
      if (!response.ok) throw new Error('Failed to fetch products')
      const data = await response.json()
      products.value = data.data || data
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const fetchProduct = async (id: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/supermarket/products/${id}`)
      if (!response.ok) throw new Error('Failed to fetch product')
      const data = await response.json()
      currentProduct.value = data
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const createProduct = async (productData: Partial<Product>) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch('/api/supermarket/products', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(productData)
      })
      if (!response.ok) throw new Error('Failed to create product')
      const data = await response.json()
      products.value.push(data)
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const updateProduct = async (id: string, productData: Partial<Product>) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/supermarket/products/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(productData)
      })
      if (!response.ok) throw new Error('Failed to update product')
      const data = await response.json()
      const index = products.value.findIndex(p => p.id === id)
      if (index !== -1) {
        products.value[index] = data
      }
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const deleteProduct = async (id: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/supermarket/products/${id}`, {
        method: 'DELETE'
      })
      if (!response.ok) throw new Error('Failed to delete product')
      products.value = products.value.filter(p => p.id !== id)
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  // Order API
  const fetchOrders = async (params?: Record<string, any>) => {
    loading.value = true
    error.value = null
    try {
      const queryString = new URLSearchParams(params as any).toString()
      const response = await fetch(`/api/supermarket/orders${queryString ? '?' + queryString : ''}`)
      if (!response.ok) throw new Error('Failed to fetch orders')
      const data = await response.json()
      orders.value = data.data || data
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const fetchOrder = async (id: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/supermarket/orders/${id}`)
      if (!response.ok) throw new Error('Failed to fetch order')
      const data = await response.json()
      currentOrder.value = data
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const acceptOrder = async (id: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/supermarket/orders/${id}/accept`, {
        method: 'POST'
      })
      if (!response.ok) throw new Error('Failed to accept order')
      const data = await response.json()
      const index = orders.value.findIndex(o => o.id === id)
      if (index !== -1) {
        orders.value[index] = data
      }
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const rejectOrder = async (id: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/supermarket/orders/${id}/reject`, {
        method: 'POST'
      })
      if (!response.ok) throw new Error('Failed to reject order')
      const data = await response.json()
      const index = orders.value.findIndex(o => o.id === id)
      if (index !== -1) {
        orders.value[index] = data
      }
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const readyForDelivery = async (id: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/supermarket/orders/${id}/ready-for-delivery`, {
        method: 'POST'
      })
      if (!response.ok) throw new Error('Failed to mark order as ready for delivery')
      const data = await response.json()
      const index = orders.value.findIndex(o => o.id === id)
      if (index !== -1) {
        orders.value[index] = data
      }
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  // Inventory API
  const fetchInventory = async (params?: Record<string, any>) => {
    loading.value = true
    error.value = null
    try {
      const queryString = new URLSearchParams(params as any).toString()
      const response = await fetch(`/api/supermarket/inventory${queryString ? '?' + queryString : ''}`)
      if (!response.ok) throw new Error('Failed to fetch inventory')
      const data = await response.json()
      inventoryItems.value = data.data || data
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const updateInventoryQuantity = async (id: string, quantityChange: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/supermarket/inventory/${id}/quantity`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ quantity_change })
      })
      if (!response.ok) throw new Error('Failed to update inventory quantity')
      const data = await response.json()
      const index = inventoryItems.value.findIndex(i => i.id === id)
      if (index !== -1) {
        inventoryItems.value[index] = data
      }
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const bulkUpdateInventoryQuantity = async (ids: string[], quantityChange: number) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch('/api/supermarket/inventory/bulk-update-quantity', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids, quantity_change })
      })
      if (!response.ok) throw new Error('Failed to bulk update inventory quantity')
      const data = await response.json()
      await fetchInventory()
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const resetInventoryReservations = async (ids: string[]) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch('/api/supermarket/inventory/reset-reservations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids })
      })
      if (!response.ok) throw new Error('Failed to reset inventory reservations')
      const data = await response.json()
      await fetchInventory()
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  // Analytics API
  const fetchMetrics = async (timeRange?: string) => {
    loading.value = true
    error.value = null
    try {
      const params = timeRange ? { time_range: timeRange } : {}
      const queryString = new URLSearchParams(params as any).toString()
      const response = await fetch(`/api/supermarket/analytics/metrics${queryString ? '?' + queryString : ''}`)
      if (!response.ok) throw new Error('Failed to fetch metrics')
      const data = await response.json()
      metrics.value = data
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const fetchRecentOrders = async (limit = 10) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/supermarket/analytics/recent-orders?limit=${limit}`)
      if (!response.ok) throw new Error('Failed to fetch recent orders')
      const data = await response.json()
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const fetchLowStockProducts = async (limit = 10) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/supermarket/analytics/low-stock?limit=${limit}`)
      if (!response.ok) throw new Error('Failed to fetch low stock products')
      const data = await response.json()
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const fetchTopProducts = async (limit = 5) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/supermarket/analytics/top-products?limit=${limit}`)
      if (!response.ok) throw new Error('Failed to fetch top products')
      const data = await response.json()
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const fetchRevenueBySubVertical = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch('/api/supermarket/analytics/revenue-by-sub-vertical')
      if (!response.ok) throw new Error('Failed to fetch revenue by sub-vertical')
      const data = await response.json()
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  // Buyer Dashboard API
  const fetchBuyerDashboard = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch('/api/buyer/dashboard')
      if (!response.ok) throw new Error('Failed to fetch buyer dashboard')
      const data = await response.json()
      buyerDashboard.value = data
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const fetchBuyerOrders = async (status?: string) => {
    loading.value = true
    error.value = null
    try {
      const params = status ? { status } : {}
      const queryString = new URLSearchParams(params as any).toString()
      const response = await fetch(`/api/buyer/orders${queryString ? '?' + queryString : ''}`)
      if (!response.ok) throw new Error('Failed to fetch buyer orders')
      const data = await response.json()
      buyerOrders.value = data.data || data
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const fetchBuyerSubscriptions = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch('/api/buyer/subscriptions')
      if (!response.ok) throw new Error('Failed to fetch buyer subscriptions')
      const data = await response.json()
      buyerSubscriptions.value = data.data || data
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const pauseSubscription = async (id: string, duration?: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/buyer/subscriptions/${id}/pause`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ duration })
      })
      if (!response.ok) throw new Error('Failed to pause subscription')
      const data = await response.json()
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const cancelSubscription = async (id: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/buyer/subscriptions/${id}`, {
        method: 'DELETE'
      })
      if (!response.ok) throw new Error('Failed to cancel subscription')
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const repeatOrder = async (orderId: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/buyer/orders/${orderId}/repeat`, {
        method: 'POST'
      })
      if (!response.ok) throw new Error('Failed to repeat order')
      const data = await response.json()
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const fetchOrderTracking = async (orderUuid: string) => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch(`/api/supermarket/tracking/${orderUuid}`)
      if (!response.ok) throw new Error('Failed to fetch order tracking')
      const data = await response.json()
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  const fetchAIRecommendations = async () => {
    loading.value = true
    error.value = null
    try {
      const response = await fetch('/api/supermarket/recommendations/ai')
      if (!response.ok) throw new Error('Failed to fetch AI recommendations')
      const data = await response.json()
      return data
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      throw e
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    loading,
    error,
    products,
    currentProduct,
    orders,
    currentOrder,
    inventoryItems,
    metrics,
    buyerDashboard,
    buyerSubscriptions,
    buyerOrders,

    // Product methods
    fetchProducts,
    fetchProduct,
    createProduct,
    updateProduct,
    deleteProduct,

    // Order methods
    fetchOrders,
    fetchOrder,
    acceptOrder,
    rejectOrder,
    readyForDelivery,

    // Inventory methods
    fetchInventory,
    updateInventoryQuantity,
    bulkUpdateInventoryQuantity,
    resetInventoryReservations,

    // Analytics methods
    fetchMetrics,
    fetchRecentOrders,
    fetchLowStockProducts,
    fetchTopProducts,
    fetchRevenueBySubVertical,

    // Buyer Dashboard methods
    fetchBuyerDashboard,
    fetchBuyerOrders,
    fetchBuyerSubscriptions,
    pauseSubscription,
    cancelSubscription,
    repeatOrder,

    // Tracking methods
    fetchOrderTracking,

    // AI Recommendations methods
    fetchAIRecommendations
  }
}
