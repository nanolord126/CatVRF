<template>
  <div class="live-metrics-dashboard bg-gray-900 text-white min-h-screen p-6">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold mb-2">Live Notification Metrics</h1>
          <p class="text-gray-400">Real-time distribution tracking and analytics</p>
        </div>
        <div class="flex items-center gap-4">
          <div class="flex items-center gap-2 bg-green-500/20 px-4 py-2 rounded-full">
            <span class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></span>
            <span class="text-green-400 font-medium">Live</span>
          </div>
          <select 
            v-model="selectedTimeRange"
            class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white"
          >
            <option value="1h">Last Hour</option>
            <option value="6h">Last 6 Hours</option>
            <option value="24h">Last 24 Hours</option>
            <option value="7d">Last 7 Days</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div 
        v-for="(metric, index) in keyMetrics"
        :key="index"
        class="bg-gray-800 rounded-2xl p-6 transform transition-all duration-300 hover:scale-105 hover:shadow-2xl"
      >
        <div class="flex items-center justify-between mb-4">
          <div 
            class="w-12 h-12 rounded-xl flex items-center justify-center"
            :class="metric.iconBg"
          >
            <component :is="metric.icon" class="w-6 h-6" :class="metric.iconColor" />
          </div>
          <span 
            class="text-sm px-3 py-1 rounded-full"
            :class="metric.trend > 0 ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'"
          >
            {{ metric.trend > 0 ? '+' : '' }}{{ metric.trend }}%
          </span>
        </div>
        <h3 class="text-3xl font-bold mb-1">
          <AnimatedNumber :value="metric.value" :duration="1500" />
        </h3>
        <p class="text-gray-400">{{ metric.label }}</p>
        <!-- Sparkline -->
        <div class="mt-4 h-16 flex items-end gap-1">
          <div 
            v-for="(point, i) in metric.sparkline"
            :key="i"
            class="flex-1 rounded-t transition-all duration-300"
            :class="metric.barColor"
            :style="{ height: point + '%' }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
      <!-- Real-time Delivery Rate -->
      <div class="bg-gray-800 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold">Delivery Rate (Real-time)</h2>
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
            <span class="text-sm text-gray-400">Updating</span>
          </div>
        </div>
        <div class="h-64">
          <canvas ref="deliveryRateChart"></canvas>
        </div>
      </div>

      <!-- Channel Performance -->
      <div class="bg-gray-800 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold">Channel Performance</h2>
          <select class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-1 text-sm">
            <option>All Time</option>
            <option>Today</option>
            <option>This Week</option>
          </select>
        </div>
        <div class="h-64">
          <canvas ref="channelChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Live Activity Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
      <div class="lg:col-span-2 bg-gray-800 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold">Live Activity Feed</h2>
          <div class="flex items-center gap-2">
            <span class="text-sm text-gray-400">{{ activityCount }} events/minute</span>
          </div>
        </div>
        <div class="space-y-3 max-h-96 overflow-y-auto custom-scrollbar">
          <div 
            v-for="(activity, index) in liveActivities"
            :key="index"
            class="flex items-center gap-4 p-3 rounded-xl bg-gray-700/50 transform transition-all duration-300 hover:bg-gray-700"
            :class="{ 'animate-slide-in': activity.isNew }"
          >
            <div 
              class="w-10 h-10 rounded-full flex items-center justify-center"
              :class="activity.iconBg"
            >
              <component :is="activity.icon" class="w-5 h-5" :class="activity.iconColor" />
            </div>
            <div class="flex-1">
              <p class="font-medium">{{ activity.message }}</p>
              <p class="text-sm text-gray-400">{{ activity.details }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-medium" :class="activity.statusColor">{{ activity.status }}</p>
              <p class="text-xs text-gray-400">{{ activity.time }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Top Performers -->
      <div class="bg-gray-800 rounded-2xl p-6">
        <h2 class="text-xl font-bold mb-6">Top Performing Campaigns</h2>
        <div class="space-y-4">
          <div 
            v-for="(campaign, index) in topCampaigns"
            :key="index"
            class="p-4 rounded-xl bg-gray-700/50 transform transition-all duration-300 hover:scale-105"
          >
            <div class="flex items-center justify-between mb-2">
              <span class="font-medium">{{ campaign.name }}</span>
              <span 
                class="text-sm px-2 py-1 rounded-full"
                :class="campaign.performanceClass"
              >
                {{ campaign.rate }}%
              </span>
            </div>
            <div class="h-2 bg-gray-600 rounded-full overflow-hidden">
              <div 
                class="h-full rounded-full transition-all duration-500"
                :class="campaign.barClass"
                :style="{ width: campaign.rate + '%' }"
              ></div>
            </div>
            <p class="text-xs text-gray-400 mt-2">{{ campaign.sent }} sent • {{ campaign.reacted }} reactions</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Geographic Distribution -->
    <div class="bg-gray-800 rounded-2xl p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold">Geographic Distribution</h2>
        <div class="flex gap-2">
          <button 
            v-for="view in ['map', 'list']"
            :key="view"
            @click="geoView = view"
            class="px-4 py-2 rounded-lg text-sm transition-colors"
            :class="geoView === view ? 'bg-indigo-600 text-white' : 'bg-gray-700 text-gray-400'"
          >
            {{ view.charAt(0).toUpperCase() + view.slice(1) }}
          </button>
        </div>
      </div>
      <div v-if="geoView === 'map'" class="h-80 bg-gray-700 rounded-xl flex items-center justify-center">
        <p class="text-gray-400">Interactive map visualization</p>
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div 
          v-for="(region, index) in geographicData"
          :key="index"
          class="p-4 rounded-xl bg-gray-700/50"
        >
          <div class="flex items-center justify-between mb-2">
            <span class="font-medium">{{ region.country }}</span>
            <span class="text-indigo-400 font-bold">{{ region.percentage }}%</span>
          </div>
          <div class="h-2 bg-gray-600 rounded-full overflow-hidden">
            <div 
              class="h-full bg-indigo-500 rounded-full transition-all duration-500"
              :style="{ width: region.percentage + '%' }"
            ></div>
          </div>
          <p class="text-xs text-gray-400 mt-2">{{ region.count }} deliveries</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { Chart } from 'chart.js/auto'
import AnimatedNumber from './AnimatedNumber.vue'

// Icons (simplified - in production use heroicons or similar)
const BellIcon = 'svg'
const CheckIcon = 'svg'
const XIcon = 'svg'
const TrendUpIcon = 'svg'

const selectedTimeRange = ref('24h')
const geoView = ref('map')
const activityCount = ref(0)

const keyMetrics = ref([
  {
    label: 'Total Sent',
    value: 125847,
    trend: 12.5,
    iconBg: 'bg-blue-500/20',
    iconColor: 'text-blue-400',
    barColor: 'bg-blue-500',
    sparkline: [30, 45, 35, 60, 50, 70, 55, 65, 75, 60, 80, 70]
  },
  {
    label: 'Delivered',
    value: 118234,
    trend: 8.3,
    iconBg: 'bg-green-500/20',
    iconColor: 'text-green-400',
    barColor: 'bg-green-500',
    sparkline: [40, 55, 45, 70, 60, 80, 65, 75, 85, 70, 90, 80]
  },
  {
    label: 'Opened',
    value: 89452,
    trend: 15.2,
    iconBg: 'bg-purple-500/20',
    iconColor: 'text-purple-400',
    barColor: 'bg-purple-500',
    sparkline: [25, 40, 30, 55, 45, 65, 50, 60, 70, 55, 75, 65]
  },
  {
    label: 'Reactions',
    value: 45678,
    trend: 22.1,
    iconBg: 'bg-amber-500/20',
    iconColor: 'text-amber-400',
    barColor: 'bg-amber-500',
    sparkline: [20, 35, 25, 50, 40, 60, 45, 55, 65, 50, 70, 60]
  }
])

const liveActivities = ref([
  {
    icon: BellIcon,
    iconBg: 'bg-blue-500/20',
    iconColor: 'text-blue-400',
    message: 'Order confirmation sent',
    details: 'To user #12345 via Telegram',
    status: 'Delivered',
    statusColor: 'text-green-400',
    time: '2s ago',
    isNew: true
  },
  {
    icon: CheckIcon,
    iconBg: 'bg-green-500/20',
    iconColor: 'text-green-400',
    message: 'Payment notification opened',
    details: 'User #67890 opened email',
    status: 'Opened',
    statusColor: 'text-indigo-400',
    time: '5s ago',
    isNew: true
  },
  {
    icon: TrendUpIcon,
    iconBg: 'bg-purple-500/20',
    iconColor: 'text-purple-400',
    message: 'Promotional campaign reacted',
    details: 'User #54321 liked notification',
    status: 'Liked',
    statusColor: 'text-purple-400',
    time: '12s ago',
    isNew: false
  },
  {
    icon: XIcon,
    iconBg: 'bg-red-500/20',
    iconColor: 'text-red-400',
    message: 'SMS delivery failed',
    details: 'User #98765 - Invalid number',
    status: 'Failed',
    statusColor: 'text-red-400',
    time: '18s ago',
    isNew: false
  }
])

const topCampaigns = ref([
  {
    name: 'Summer Sale 2026',
    rate: 94.5,
    sent: 45678,
    reacted: 12456,
    performanceClass: 'bg-green-500/20 text-green-400',
    barClass: 'bg-green-500'
  },
  {
    name: 'New Product Launch',
    rate: 87.2,
    sent: 34567,
    reacted: 8765,
    performanceClass: 'bg-blue-500/20 text-blue-400',
    barClass: 'bg-blue-500'
  },
  {
    name: 'Weekly Newsletter',
    rate: 82.8,
    sent: 23456,
    reacted: 5432,
    performanceClass: 'bg-purple-500/20 text-purple-400',
    barClass: 'bg-purple-500'
  }
])

const geographicData = ref([
  { country: 'Russia', percentage: 45, count: 56234 },
  { country: 'Ukraine', percentage: 25, count: 31245 },
  { country: 'Belarus', percentage: 15, count: 18734 },
  { country: 'Kazakhstan', percentage: 10, count: 12489 },
  { country: 'Other', percentage: 5, count: 6245 }
])

const deliveryRateChart = ref<HTMLCanvasElement>()
const channelChart = ref<HTMLCanvasElement>()

let deliveryRateInstance: Chart | null = null
let channelInstance: Chart | null = null
let ws: WebSocket | null = null

onMounted(() => {
  initCharts()
  connectWebSocket()
})

onUnmounted(() => {
  if (deliveryRateInstance) deliveryRateInstance.destroy()
  if (channelInstance) channelInstance.destroy()
  if (ws) ws.close()
})

const initCharts = () => {
  initDeliveryRateChart()
  initChannelChart()
}

const initDeliveryRateChart = () => {
  if (!deliveryRateChart.value) return

  const ctx = deliveryRateChart.value.getContext('2d')
  if (!ctx) return

  deliveryRateInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: Array.from({ length: 30 }, (_, i) => `${i}s ago`),
      datasets: [{
        label: 'Delivery Rate',
        data: Array.from({ length: 30 }, () => 85 + Math.random() * 15),
        borderColor: 'rgb(99, 102, 241)',
        backgroundColor: 'rgba(99, 102, 241, 0.1)',
        fill: true,
        tension: 0.4,
        pointRadius: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        x: {
          grid: {
            color: 'rgba(255, 255, 255, 0.1)'
          },
          ticks: {
            color: 'rgba(255, 255, 255, 0.5)'
          }
        },
        y: {
          grid: {
            color: 'rgba(255, 255, 255, 0.1)'
          },
          ticks: {
            color: 'rgba(255, 255, 255, 0.5)'
          },
          min: 0,
          max: 100
        }
      },
      animation: {
        duration: 0
      }
    }
  })
}

const initChannelChart = () => {
  if (!channelChart.value) return

  const ctx = channelChart.value.getContext('2d')
  if (!ctx) return

  channelInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Telegram', 'Email', 'SMS', 'Push', 'WhatsApp'],
      datasets: [{
        data: [35, 25, 20, 15, 5],
        backgroundColor: [
          'rgb(59, 130, 246)',
          'rgb(34, 197, 94)',
          'rgb(234, 179, 8)',
          'rgb(168, 85, 247)',
          'rgb(6, 182, 212)'
        ],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',
          labels: {
            color: 'rgba(255, 255, 255, 0.8)'
          }
        }
      }
    }
  })
}

const connectWebSocket = () => {
  // WebSocket connection for real-time updates
  ws = new WebSocket('wss://your-domain.com/ws/notifications/metrics')
  
  ws.onmessage = (event) => {
    const data = JSON.parse(event.data)
    updateMetrics(data)
  }

  ws.onerror = (error) => {
    console.error('WebSocket error:', error)
  }

  // Simulate real-time updates for demo
  setInterval(() => {
    simulateUpdate()
  }, 2000)
}

const updateMetrics = (data: any) => {
  // Update metrics with real-time data
  keyMetrics.value.forEach((metric, index) => {
    if (data[index] !== undefined) {
      metric.value = data[index].value
      metric.trend = data[index].trend
    }
  })
}

const simulateUpdate = () => {
  // Simulate real-time activity
  activityCount.value = Math.floor(Math.random() * 100) + 50
  
  // Update charts
  if (deliveryRateInstance) {
    const newData = deliveryRateInstance.data.datasets[0].data
    newData.shift()
    newData.push(85 + Math.random() * 15)
    deliveryRateInstance.update('none')
  }

  // Add new activity occasionally
  if (Math.random() > 0.7) {
    const activities = [
      {
        icon: BellIcon,
        iconBg: 'bg-blue-500/20',
        iconColor: 'text-blue-400',
        message: 'New notification sent',
        details: `To user #${Math.floor(Math.random() * 100000)}`,
        status: 'Sent',
        statusColor: 'text-green-400',
        time: 'Just now',
        isNew: true
      },
      {
        icon: CheckIcon,
        iconBg: 'bg-green-500/20',
        iconColor: 'text-green-400',
        message: 'Notification delivered',
        details: `User #${Math.floor(Math.random() * 100000)}`,
        status: 'Delivered',
        statusColor: 'text-green-400',
        time: 'Just now',
        isNew: true
      }
    ]
    
    const newActivity = activities[Math.floor(Math.random() * activities.length)]
    liveActivities.value.unshift(newActivity)
    if (liveActivities.value.length > 20) {
      liveActivities.value.pop()
    }
  }
}
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.3);
}

@keyframes slide-in {
  from {
    opacity: 0;
    transform: translateX(-20px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.animate-slide-in {
  animation: slide-in 0.3s ease-out;
}
</style>
