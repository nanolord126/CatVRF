import { ref, onMounted, onUnmounted } from 'vue'

interface SupermarketWebSocketMessage {
  type: 'order_status' | 'batch_arrival' | 'stock_alert' | 'demand_alert' | 'courier_location'
  channel: string
  data: any
  timestamp: string
}

interface CourierLocationUpdate {
  order_id: string
  courier_id: string
  latitude: number
  longitude: number
  eta_minutes?: number
}

interface OrderStatusUpdate {
  order_id: string
  order_number: string
  old_status: string
  new_status: string
  timestamp: string
}

interface BatchArrivalUpdate {
  batch_id: string
  warehouse_id: string
  product_count: number
  total_quantity: number
  estimated_value: number
}

export function useSupermarketWebSocket() {
  const isConnected = ref(false)
  const error = ref<string | null>(null)
  const reconnectAttempts = ref(0)
  const maxReconnectAttempts = 5

  let ws: WebSocket | null = null
  let reconnectTimer: NodeJS.Timeout | null = null
  let heartbeatInterval: NodeJS.Timeout | null = null

  const connect = (channel: string = 'supermarket') => {
    const wsUrl = (import.meta as any).env?.VITE_SUPERMARKET_WS_URL || 
                  `${window.location.protocol === 'https:' ? 'wss:' : 'ws:'}//${window.location.host}/ws/supermarket`
    
    try {
      ws = new WebSocket(wsUrl)

      ws.onopen = () => {
        isConnected.value = true
        error.value = null
        reconnectAttempts.value = 0
        
        // Subscribe to channel
        send({
          action: 'subscribe',
          channel: channel
        })
        
        // Start heartbeat
        startHeartbeat()
        
        console.log(`Supermarket WebSocket connected to channel: ${channel}`)
      }

      ws.onmessage = (event) => {
        try {
          const message: SupermarketWebSocketMessage = JSON.parse(event.data)
          handleMessage(message)
        } catch (err) {
          console.error('Failed to parse WebSocket message:', err)
        }
      }

      ws.onerror = (event) => {
        error.value = 'WebSocket error occurred'
        console.error('Supermarket WebSocket error:', event)
      }

      ws.onclose = () => {
        isConnected.value = false
        stopHeartbeat()
        console.log('Supermarket WebSocket disconnected')
        attemptReconnect(channel)
      }
    } catch (err) {
      error.value = 'Failed to connect to WebSocket'
      console.error('Supermarket WebSocket connection error:', err)
    }
  }

  const disconnect = () => {
    stopHeartbeat()
    if (ws) {
      ws.close()
      ws = null
    }
    if (reconnectTimer) {
      clearTimeout(reconnectTimer)
      reconnectTimer = null
    }
  }

  const attemptReconnect = (channel: string) => {
    if (reconnectAttempts.value >= maxReconnectAttempts) {
      error.value = 'Max reconnection attempts reached'
      return
    }

    reconnectAttempts.value++
    const delay = Math.min(1000 * Math.pow(2, reconnectAttempts.value), 30000)

    reconnectTimer = setTimeout(() => {
      console.log(`Attempting to reconnect to Supermarket WebSocket (${reconnectAttempts.value}/${maxReconnectAttempts})`)
      connect(channel)
    }, delay)
  }

  const startHeartbeat = () => {
    heartbeatInterval = setInterval(() => {
      send({ action: 'ping' })
    }, 30000) // Send ping every 30 seconds
  }

  const stopHeartbeat = () => {
    if (heartbeatInterval) {
      clearInterval(heartbeatInterval)
      heartbeatInterval = null
    }
  }

  const handleMessage = (message: SupermarketWebSocketMessage) => {
    // Emit custom events for different message types
    const event = new CustomEvent(`supermarket:${message.type}`, {
      detail: message.data
    })
    window.dispatchEvent(event)
  }

  const send = (data: any) => {
    if (ws && ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify(data))
    }
  }

  // Subscribe to specific order updates
  const subscribeToOrder = (orderId: string) => {
    send({
      action: 'subscribe_order',
      order_id: orderId
    })
  }

  // Unsubscribe from specific order updates
  const unsubscribeFromOrder = (orderId: string) => {
    send({
      action: 'unsubscribe_order',
      order_id: orderId
    })
  }

  // Subscribe to batch arrivals
  const subscribeToBatches = (warehouseId?: string) => {
    send({
      action: 'subscribe_batches',
      warehouse_id: warehouseId
    })
  }

  // Subscribe to stock alerts
  const subscribeToStockAlerts = (storeId?: string) => {
    send({
      action: 'subscribe_stock_alerts',
      store_id: storeId
    })
  }

  onUnmounted(() => {
    disconnect()
  })

  return {
    isConnected,
    error,
    reconnectAttempts,
    connect,
    disconnect,
    send,
    subscribeToOrder,
    unsubscribeFromOrder,
    subscribeToBatches,
    subscribeToStockAlerts
  }
}

// Helper composable for listening to Supermarket WebSocket events
export function useSupermarketEventListeners() {
  const onOrderStatusUpdate = (callback: (data: OrderStatusUpdate) => void) => {
    const handler = (event: Event) => {
      const customEvent = event as CustomEvent<OrderStatusUpdate>
      callback(customEvent.detail)
    }
    window.addEventListener('supermarket:order_status', handler)
    return () => window.removeEventListener('supermarket:order_status', handler)
  }

  const onBatchArrival = (callback: (data: BatchArrivalUpdate) => void) => {
    const handler = (event: Event) => {
      const customEvent = event as CustomEvent<BatchArrivalUpdate>
      callback(customEvent.detail)
    }
    window.addEventListener('supermarket:batch_arrival', handler)
    return () => window.removeEventListener('supermarket:batch_arrival', handler)
  }

  const onCourierLocationUpdate = (callback: (data: CourierLocationUpdate) => void) => {
    const handler = (event: Event) => {
      const customEvent = event as CustomEvent<CourierLocationUpdate>
      callback(customEvent.detail)
    }
    window.addEventListener('supermarket:courier_location', handler)
    return () => window.removeEventListener('supermarket:courier_location', handler)
  }

  const onStockAlert = (callback: (data: any) => void) => {
    const handler = (event: Event) => {
      const customEvent = event as CustomEvent
      callback(customEvent.detail)
    }
    window.addEventListener('supermarket:stock_alert', handler)
    return () => window.removeEventListener('supermarket:stock_alert', handler)
  }

  const onDemandAlert = (callback: (data: any) => void) => {
    const handler = (event: Event) => {
      const customEvent = event as CustomEvent
      callback(customEvent.detail)
    }
    window.addEventListener('supermarket:demand_alert', handler)
    return () => window.removeEventListener('supermarket:demand_alert', handler)
  }

  return {
    onOrderStatusUpdate,
    onBatchArrival,
    onCourierLocationUpdate,
    onStockAlert,
    onDemandAlert
  }
}
