import { ref, onMounted, onUnmounted } from 'vue'

interface NotificationMetrics {
  totalSent: number
  delivered: number
  opened: number
  reacted: number
  deliveryRate: number
  openRate: number
  reactionRate: number
}

interface WebSocketMessage {
  type: 'metrics' | 'activity' | 'alert'
  data: any
  timestamp: string
}

export function useNotificationWebSocket() {
  const isConnected = ref(false)
  const metrics = ref<NotificationMetrics>({
    totalSent: 0,
    delivered: 0,
    opened: 0,
    reacted: 0,
    deliveryRate: 0,
    openRate: 0,
    reactionRate: 0
  })
  const recentActivities = ref<any[]>([])
  const error = ref<string | null>(null)

  let ws: WebSocket | null = null
  let reconnectTimer: NodeJS.Timeout | null = null
  const reconnectAttempts = ref(0)
  const maxReconnectAttempts = 5

  const connect = (url: string) => {
    try {
      ws = new WebSocket(url)

      ws.onopen = () => {
        isConnected.value = true
        error.value = null
        reconnectAttempts.value = 0
        console.log('WebSocket connected')
      }

      ws.onmessage = (event) => {
        const message: WebSocketMessage = JSON.parse(event.data)
        handleMessage(message)
      }

      ws.onerror = (event) => {
        error.value = 'WebSocket error occurred'
        console.error('WebSocket error:', event)
      }

      ws.onclose = () => {
        isConnected.value = false
        console.log('WebSocket disconnected')
        attemptReconnect(url)
      }
    } catch (err) {
      error.value = 'Failed to connect to WebSocket'
      console.error('WebSocket connection error:', err)
    }
  }

  const disconnect = () => {
    if (ws) {
      ws.close()
      ws = null
    }
    if (reconnectTimer) {
      clearTimeout(reconnectTimer)
      reconnectTimer = null
    }
  }

  const attemptReconnect = (url: string) => {
    if (reconnectAttempts.value >= maxReconnectAttempts) {
      error.value = 'Max reconnection attempts reached'
      return
    }

    reconnectAttempts.value++
    const delay = Math.min(1000 * Math.pow(2, reconnectAttempts.value), 30000)

    reconnectTimer = setTimeout(() => {
      console.log(`Attempting to reconnect (${reconnectAttempts.value}/${maxReconnectAttempts})`)
      connect(url)
    }, delay)
  }

  const handleMessage = (message: WebSocketMessage) => {
    switch (message.type) {
      case 'metrics':
        metrics.value = {
          ...metrics.value,
          ...message.data
        }
        break

      case 'activity':
        recentActivities.value.unshift({
          ...message.data,
          timestamp: message.timestamp
        })
        // Keep only last 50 activities
        if (recentActivities.value.length > 50) {
          recentActivities.value = recentActivities.value.slice(0, 50)
        }
        break

      case 'alert':
        // Handle alerts (e.g., delivery rate drops, spike in failures)
        console.warn('Notification alert:', message.data)
        break
    }
  }

  const send = (data: any) => {
    if (ws && ws.readyState === WebSocket.OPEN) {
      ws.send(JSON.stringify(data))
    }
  }

  onUnmounted(() => {
    disconnect()
  })

  return {
    isConnected,
    metrics,
    recentActivities,
    error,
    connect,
    disconnect,
    send
  }
}
