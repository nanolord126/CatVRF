import axios from 'axios'

/**
 * Real-time API Client
 * 
 * Методы:
 * - trackPresence(status, location)
 * - getOnlineUsers()
 * - subscribe(channel)
 * - unsubscribe(channel)
 */

const apiClient = axios.create({
  baseURL: '/api/v2/realtime',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
})

// Add auth token to requests
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export const realtimeApi = {
  // Track user presence
  trackPresence: async (status = 'online', location = null) => {
    try {
      const response = await apiClient.post('/presence', {
        status,
        location,
      })
      return response.data
    } catch (error) {
      console.error('Failed to track presence:', error)
      throw error
    }
  },

  // Get online users
  getOnlineUsers: async () => {
    try {
      const response = await apiClient.get('/online')
      return response.data
    } catch (error) {
      console.error('Failed to get online users:', error)
      throw error
    }
  },

  // Stop tracking presence
  stopTracking: async () => {
    try {
      const response = await apiClient.delete('/presence')
      return response.data
    } catch (error) {
      console.error('Failed to stop tracking:', error)
      throw error
    }
  },

  // Subscribe to channel
  subscribe: async (channel) => {
    try {
      const response = await apiClient.post('/subscribe', { channel })
      return response.data
    } catch (error) {
      console.error('Failed to subscribe:', error)
      throw error
    }
  },

  // Unsubscribe from channel
  unsubscribe: async (channel) => {
    try {
      const response = await apiClient.delete('/unsubscribe', {
        data: { channel },
      })
      return response.data
    } catch (error) {
      console.error('Failed to unsubscribe:', error)
      throw error
    }
  },

  // Get subscribed channels
  getChannels: async () => {
    try {
      const response = await apiClient.get('/channels')
      return response.data
    } catch (error) {
      console.error('Failed to get channels:', error)
      throw error
    }
  },
}

export default realtimeApi
