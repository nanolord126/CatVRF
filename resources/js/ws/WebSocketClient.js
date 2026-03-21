/**
 * WebSocket Client Optimization
 * 
 * Функции:
 * - Auto-reconnect on disconnect
 * - Exponential backoff retry
 * - Connection pooling
 * - Heartbeat monitoring
 */

import { EventEmitter } from 'events'

const DEFAULT_OPTIONS = {
  url: null,
  maxReconnectAttempts: 5,
  reconnectInterval: 1000,
  maxReconnectInterval: 30000,
  heartbeatInterval: 30000,
  debug: false,
}

export class WebSocketClient extends EventEmitter {
  private ws = null
  private reconnectAttempts = 0
  private heartbeatTimer = null
  private reconnectTimer = null
  private options = DEFAULT_OPTIONS

  constructor(options = {}) {
    super()
    this.options = { ...DEFAULT_OPTIONS, ...options }
  }

  /**
   * Connect to WebSocket server
   */
  connect() {
    try {
      this.ws = new WebSocket(this.options.url)

      this.ws.onopen = () => this._onOpen()
      this.ws.onmessage = (event) => this._onMessage(event)
      this.ws.onerror = (error) => this._onError(error)
      this.ws.onclose = () => this._onClose()

      this.emit('connecting')
    } catch (error) {
      this.emit('error', error)
      this._scheduleReconnect()
    }
  }

  /**
   * Handle connection open
   */
  _onOpen() {
    this.log('WebSocket connected')
    this.reconnectAttempts = 0
    this.emit('connected')
    this._startHeartbeat()
  }

  /**
   * Handle incoming message
   */
  _onMessage(event) {
    try {
      const data = JSON.parse(event.data)
      this.emit('message', data)
      this.emit(data.event, data)
    } catch (error) {
      this.log('Failed to parse message:', error)
    }
  }

  /**
   * Handle error
   */
  _onError(error) {
    this.log('WebSocket error:', error)
    this.emit('error', error)
  }

  /**
   * Handle connection close
   */
  _onClose() {
    this.log('WebSocket disconnected')
    this._stopHeartbeat()
    this.emit('disconnected')
    this._scheduleReconnect()
  }

  /**
   * Start heartbeat monitoring
   */
  _startHeartbeat() {
    this.heartbeatTimer = setInterval(() => {
      if (this.ws && this.ws.readyState === WebSocket.OPEN) {
        this.send({ type: 'heartbeat' })
      }
    }, this.options.heartbeatInterval)
  }

  /**
   * Stop heartbeat
   */
  _stopHeartbeat() {
    if (this.heartbeatTimer) {
      clearInterval(this.heartbeatTimer)
    }
  }

  /**
   * Schedule reconnection with exponential backoff
   */
  _scheduleReconnect() {
    if (this.reconnectAttempts >= this.options.maxReconnectAttempts) {
      this.emit('max_reconnect_attempts_reached')
      return
    }

    this.reconnectAttempts++
    const delay = Math.min(
      this.options.reconnectInterval * Math.pow(2, this.reconnectAttempts - 1),
      this.options.maxReconnectInterval
    )

    this.log(`Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts})`)

    this.reconnectTimer = setTimeout(() => {
      this.connect()
    }, delay)
  }

  /**
   * Send message
   */
  send(data) {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(data))
      return true
    }
    return false
  }

  /**
   * Subscribe to channel
   */
  subscribe(channel) {
    return this.send({
      action: 'subscribe',
      channel,
    })
  }

  /**
   * Unsubscribe from channel
   */
  unsubscribe(channel) {
    return this.send({
      action: 'unsubscribe',
      channel,
    })
  }

  /**
   * Disconnect
   */
  disconnect() {
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer)
    }
    this._stopHeartbeat()

    if (this.ws) {
      this.ws.close()
    }
  }

  /**
   * Logging helper
   */
  log(...args) {
    if (this.options.debug) {
      console.log('[WebSocket]', ...args)
    }
  }

  /**
   * Get connection status
   */
  isConnected() {
    return this.ws && this.ws.readyState === WebSocket.OPEN
  }
}

export default WebSocketClient
