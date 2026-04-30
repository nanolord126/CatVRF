<template>
  <div class="notification-distribution-tracker">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Notification Distributions</h1>
          <p class="text-gray-600">Track and manage your notification campaigns</p>
        </div>
        <div class="flex items-center gap-4">
          <button 
            @click="showCreateModal = true"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New Distribution
          </button>
          <button 
            @click="showLiveMetrics = true"
            class="bg-gray-900 hover:bg-gray-800 text-white px-6 py-2 rounded-lg font-medium transition-colors flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Live Metrics
          </button>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white border-b border-gray-200 px-6 py-4">
      <div class="flex items-center gap-4 flex-wrap">
        <div class="flex-1 min-w-64">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search distributions..."
            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
          />
        </div>
        <select v-model="filterStatus" class="border border-gray-300 rounded-lg px-4 py-2">
          <option value="all">All Statuses</option>
          <option value="scheduled">Scheduled</option>
          <option value="sending">Sending</option>
          <option value="completed">Completed</option>
          <option value="paused">Paused</option>
          <option value="failed">Failed</option>
        </select>
        <select v-model="filterChannel" class="border border-gray-300 rounded-lg px-4 py-2">
          <option value="all">All Channels</option>
          <option value="email">Email</option>
          <option value="sms">SMS</option>
          <option value="push">Push</option>
          <option value="telegram">Telegram</option>
          <option value="whatsapp">WhatsApp</option>
        </select>
        <input
          v-model="dateFrom"
          type="date"
          class="border border-gray-300 rounded-lg px-4 py-2"
        />
        <input
          v-model="dateTo"
          type="date"
          class="border border-gray-300 rounded-lg px-4 py-2"
        />
      </div>
    </div>

    <!-- Distributions Grid -->
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <NotificationDistributionCard
          v-for="distribution in filteredDistributions"
          :key="distribution.id"
          :distribution="distribution"
          :metrics="distribution.metrics"
          :channels="distribution.channels"
          :recentActivities="distribution.recentActivities"
          :is-active="selectedDistributionId === distribution.id"
          @view-details="viewDistribution(distribution)"
          @start-distribution="startDistribution(distribution.id)"
          @pause-distribution="pauseDistribution(distribution.id)"
          @clone-distribution="cloneDistribution(distribution.id)"
        />
      </div>

      <!-- Empty State -->
      <div v-if="filteredDistributions.length === 0" class="text-center py-16">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No distributions found</h3>
        <p class="text-gray-600 mb-4">Create your first notification distribution to get started</p>
        <button 
          @click="showCreateModal = true"
          class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-lg font-medium transition-colors"
        >
          Create Distribution
        </button>
      </div>
    </div>

    <!-- Create Distribution Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold">Create New Distribution</h2>
            <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        <div class="p-6 space-y-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Distribution Name</label>
            <input
              v-model="newDistribution.name"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
              placeholder="e.g., Summer Sale Campaign"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Notification Type</label>
            <select v-model="newDistribution.type" class="w-full border border-gray-300 rounded-lg px-4 py-2">
              <option value="promotional">Promotional</option>
              <option value="transactional">Transactional</option>
              <option value="informational">Informational</option>
              <option value="reminder">Reminder</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Channels</label>
            <div class="grid grid-cols-2 gap-3">
              <label v-for="channel in availableChannels" :key="channel.value" class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                <input
                  v-model="newDistribution.channels"
                  type="checkbox"
                  :value="channel.value"
                  class="rounded text-indigo-600"
                />
                <span>{{ channel.label }}</span>
              </label>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Schedule</label>
            <div class="flex gap-4">
              <label class="flex items-center gap-2">
                <input v-model="newDistribution.scheduleType" type="radio" value="immediate" class="text-indigo-600" />
                <span>Send Immediately</span>
              </label>
              <label class="flex items-center gap-2">
                <input v-model="newDistribution.scheduleType" type="radio" value="scheduled" class="text-indigo-600" />
                <span>Schedule for Later</span>
              </label>
            </div>
            <div v-if="newDistribution.scheduleType === 'scheduled'" class="mt-3">
              <input
                v-model="newDistribution.scheduledAt"
                type="datetime-local"
                class="w-full border border-gray-300 rounded-lg px-4 py-2"
              />
            </div>
          </div>
        </div>
        <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
          <button 
            @click="showCreateModal = false"
            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
          >
            Cancel
          </button>
          <button 
            @click="createDistribution"
            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors"
          >
            Create Distribution
          </button>
        </div>
      </div>
    </div>

    <!-- Live Metrics Modal -->
    <div v-if="showLiveMetrics" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div class="bg-gray-900 rounded-2xl shadow-2xl w-full max-w-6xl mx-4 max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b border-gray-700 flex items-center justify-between">
          <h2 class="text-xl font-bold text-white">Live Metrics Dashboard</h2>
          <button @click="showLiveMetrics = false" class="text-gray-400 hover:text-gray-200">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-80px)]">
          <LiveMetricsDashboard />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import NotificationDistributionCard from './NotificationDistributionCard.vue'
import LiveMetricsDashboard from './LiveMetricsDashboard.vue'
import { useNotificationWebSocket } from '@/composables/useNotificationWebSocket'

interface Distribution {
  id: number
  name: string
  type: string
  status: 'scheduled' | 'sending' | 'completed' | 'paused' | 'failed'
  progress: number
  metrics: any[]
  channels: any[]
  recentActivities: any[]
}

const searchQuery = ref('')
const filterStatus = ref('all')
const filterChannel = ref('all')
const dateFrom = ref('')
const dateTo = ref('')
const selectedDistributionId = ref<number | null>(null)
const showCreateModal = ref(false)
const showLiveMetrics = ref(false)

const newDistribution = ref({
  name: '',
  type: 'promotional',
  channels: [] as string[],
  scheduleType: 'immediate',
  scheduledAt: ''
})

const availableChannels = [
  { value: 'email', label: 'Email' },
  { value: 'sms', label: 'SMS' },
  { value: 'push', label: 'Push' },
  { value: 'telegram', label: 'Telegram' },
  { value: 'whatsapp', label: 'WhatsApp' }
]

const distributions = ref<Distribution[]>([
  {
    id: 1,
    name: 'Summer Sale 2026',
    type: 'promotional',
    status: 'sending',
    progress: 67,
    metrics: [
      { label: 'Sent', value: 45678, trend: 12.5, bgClass: 'bg-blue-50', valueClass: 'text-blue-900' },
      { label: 'Delivered', value: 43234, trend: 8.3, bgClass: 'bg-green-50', valueClass: 'text-green-900' },
      { label: 'Opened', value: 34567, trend: 15.2, bgClass: 'bg-purple-50', valueClass: 'text-purple-900' }
    ],
    channels: [
      { name: 'Email', percentage: 45, colorClass: 'bg-blue-500' },
      { name: 'Push', percentage: 30, colorClass: 'bg-purple-500' },
      { name: 'SMS', percentage: 25, colorClass: 'bg-green-500' }
    ],
    recentActivities: [
      { message: 'Batch 45 completed', time: '2m ago' },
      { message: 'Delivery rate: 94.5%', time: '5m ago' },
      { message: 'Started batch 46', time: '8m ago' }
    ]
  },
  {
    id: 2,
    name: 'Weekly Newsletter',
    type: 'informational',
    status: 'scheduled',
    progress: 0,
    metrics: [
      { label: 'Recipients', value: 23456, trend: 5.2, bgClass: 'bg-blue-50', valueClass: 'text-blue-900' },
      { label: 'Estimated', value: 23456, trend: 0, bgClass: 'bg-gray-50', valueClass: 'text-gray-900' },
      { label: 'Channels', value: 3, trend: 0, bgClass: 'bg-purple-50', valueClass: 'text-purple-900' }
    ],
    channels: [
      { name: 'Email', percentage: 60, colorClass: 'bg-blue-500' },
      { name: 'Telegram', percentage: 40, colorClass: 'bg-indigo-500' }
    ],
    recentActivities: []
  },
  {
    id: 3,
    name: 'Order Confirmation',
    type: 'transactional',
    status: 'completed',
    progress: 100,
    metrics: [
      { label: 'Sent', value: 12345, trend: 8.7, bgClass: 'bg-blue-50', valueClass: 'text-blue-900' },
      { label: 'Delivered', value: 12123, trend: 7.2, bgClass: 'bg-green-50', valueClass: 'text-green-900' },
      { label: 'Opened', value: 9876, trend: 12.1, bgClass: 'bg-purple-50', valueClass: 'text-purple-900' }
    ],
    channels: [
      { name: 'Email', percentage: 50, colorClass: 'bg-blue-500' },
      { name: 'SMS', percentage: 30, colorClass: 'bg-green-500' },
      { name: 'Push', percentage: 20, colorClass: 'bg-purple-500' }
    ],
    recentActivities: [
      { message: 'Distribution completed', time: '1h ago' },
      { message: 'Final delivery rate: 98.2%', time: '1h ago' }
    ]
  }
])

const filteredDistributions = computed(() => {
  return distributions.value.filter(d => {
    const matchesSearch = d.name.toLowerCase().includes(searchQuery.value.toLowerCase())
    const matchesStatus = filterStatus.value === 'all' || d.status === filterStatus.value
    return matchesSearch && matchesStatus
  })
})

const viewDistribution = (distribution: Distribution) => {
  selectedDistributionId.value = distribution.id
  // Navigate to distribution details
}

const startDistribution = (id: number) => {
  const distribution = distributions.value.find(d => d.id === id)
  if (distribution) {
    distribution.status = 'sending'
    // API call to start distribution
  }
}

const pauseDistribution = (id: number) => {
  const distribution = distributions.value.find(d => d.id === id)
  if (distribution) {
    distribution.status = 'paused'
    // API call to pause distribution
  }
}

const cloneDistribution = (id: number) => {
  const distribution = distributions.value.find(d => d.id === id)
  if (distribution) {
    newDistribution.value.name = `${distribution.name} (Copy)`
    newDistribution.value.type = distribution.type
    showCreateModal.value = true
  }
}

const createDistribution = () => {
  // API call to create distribution
  const newDist: Distribution = {
    id: Date.now(),
    name: newDistribution.value.name,
    type: newDistribution.value.type,
    status: newDistribution.value.scheduleType === 'immediate' ? 'sending' : 'scheduled',
    progress: 0,
    metrics: [
      { label: 'Recipients', value: 0, trend: 0, bgClass: 'bg-blue-50', valueClass: 'text-blue-900' },
      { label: 'Sent', value: 0, trend: 0, bgClass: 'bg-green-50', valueClass: 'text-green-900' },
      { label: 'Channels', value: newDistribution.value.channels.length, trend: 0, bgClass: 'bg-purple-50', valueClass: 'text-purple-900' }
    ],
    channels: newDistribution.value.channels.map(ch => ({
      name: ch.charAt(0).toUpperCase() + ch.slice(1),
      percentage: 100 / newDistribution.value.channels.length,
      colorClass: 'bg-indigo-500'
    })),
    recentActivities: []
  }
  distributions.value.unshift(newDist)
  showCreateModal.value = false
  newDistribution.value = {
    name: '',
    type: 'promotional',
    channels: [],
    scheduleType: 'immediate',
    scheduledAt: ''
  }
}

// WebSocket integration for real-time updates
const { isConnected, metrics, connect, disconnect } = useNotificationWebSocket()

// Connect to WebSocket for live updates (uncomment in production)
// connect('wss://your-domain.com/ws/notifications')
</script>

<style scoped>
.notification-distribution-tracker {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
}
</style>
