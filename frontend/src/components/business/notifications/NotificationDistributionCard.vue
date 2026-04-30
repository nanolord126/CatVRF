<template>
  <div 
    class="notification-distribution-card bg-white rounded-2xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl"
    :class="{ 'ring-2 ring-indigo-500': isActive }"
  >
    <!-- Header with Animation -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div 
            class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center animate-pulse"
            v-if="isSending"
          >
            <svg class="w-5 h-5 text-white animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
          <div 
            v-else
            class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center"
          >
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
          </div>
          <div>
            <h3 class="text-white font-bold text-lg">{{ distribution.name }}</h3>
            <p class="text-white/80 text-sm">{{ distribution.type }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span 
            class="px-3 py-1 rounded-full text-xs font-semibold"
            :class="statusClasses"
          >
            {{ distribution.status }}
          </span>
        </div>
      </div>
    </div>

    <!-- Progress Section -->
    <div class="px-6 py-4">
      <div class="mb-4">
        <div class="flex justify-between text-sm mb-2">
          <span class="text-gray-600">Progress</span>
          <span class="font-semibold text-gray-900">{{ distribution.progress }}%</span>
        </div>
        <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
          <div 
            class="h-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full transition-all duration-500 ease-out"
            :style="{ width: distribution.progress + '%' }"
          >
            <div class="h-full w-full animate-shimmer"></div>
          </div>
        </div>
      </div>

      <!-- Metrics Grid -->
      <div class="grid grid-cols-3 gap-3 mb-4">
        <div 
          v-for="(metric, index) in metrics"
          :key="index"
          class="bg-gray-50 rounded-xl p-3 text-center transform transition-all duration-300 hover:scale-105"
          :class="metric.bgClass"
        >
          <p class="text-xs text-gray-600 mb-1">{{ metric.label }}</p>
          <p 
            class="text-2xl font-bold transition-all duration-300"
            :class="metric.valueClass"
          >
            <AnimatedNumber :value="metric.value" :duration="1000" />
          </p>
          <p 
            class="text-xs mt-1"
            :class="metric.trend > 0 ? 'text-green-600' : 'text-red-600'"
          >
            <svg 
              class="inline w-3 h-3" 
              :class="metric.trend > 0 ? 'transform rotate-0' : 'transform rotate-180'"
              fill="none" 
              viewBox="0 0 24 24" 
              stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
            </svg>
            {{ Math.abs(metric.trend) }}%
          </p>
        </div>
      </div>

      <!-- Channel Distribution -->
      <div class="mb-4">
        <p class="text-sm font-medium text-gray-700 mb-2">Channel Distribution</p>
        <div class="flex gap-2">
          <div 
            v-for="(channel, index) in channels"
            :key="index"
            class="flex-1 h-2 rounded-full transition-all duration-500"
            :class="channel.colorClass"
            :style="{ width: channel.percentage + '%' }"
            :title="channel.name + ': ' + channel.percentage + '%'"
          ></div>
        </div>
        <div class="flex gap-2 mt-2">
          <span 
            v-for="(channel, index) in channels"
            :key="index"
            class="text-xs text-gray-600"
          >
            {{ channel.name }}: {{ channel.percentage }}%
          </span>
        </div>
      </div>

      <!-- Real-time Activity -->
      <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm font-medium text-gray-700">Real-time Activity</p>
          <span class="flex items-center gap-1 text-xs text-green-600">
            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
            Live
          </span>
        </div>
        <div class="space-y-2">
          <div 
            v-for="(activity, index) in recentActivities"
            :key="index"
            class="flex items-center justify-between text-sm"
          >
            <span class="text-gray-600">{{ activity.message }}</span>
            <span class="text-gray-400 text-xs">{{ activity.time }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
      <div class="flex gap-2">
        <button 
          @click="viewDetails"
          class="flex-1 bg-white hover:bg-gray-100 text-gray-900 py-2 px-4 rounded-xl text-sm font-medium transition-all duration-300 hover:scale-105 border border-gray-300"
        >
          View Details
        </button>
        <button 
          v-if="distribution.status === 'scheduled'"
          @click="startDistribution"
          class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-xl text-sm font-medium transition-all duration-300 hover:scale-105"
        >
          Start Now
        </button>
        <button 
          v-else-if="distribution.status === 'sending'"
          @click="pauseDistribution"
          class="flex-1 bg-amber-500 hover:bg-amber-600 text-white py-2 px-4 rounded-xl text-sm font-medium transition-all duration-300 hover:scale-105"
        >
          Pause
        </button>
        <button 
          v-else
          @click="cloneDistribution"
          class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2 px-4 rounded-xl text-sm font-medium transition-all duration-300 hover:scale-105"
        >
          Clone
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import AnimatedNumber from './AnimatedNumber.vue'

interface Distribution {
  name: string
  type: string
  status: 'scheduled' | 'sending' | 'completed' | 'paused' | 'failed'
  progress: number
}

interface Metric {
  label: string
  value: number
  trend: number
  bgClass: string
  valueClass: string
}

interface Channel {
  name: string
  percentage: number
  colorClass: string
}

interface Activity {
  message: string
  time: string
}

const props = defineProps<{
  distribution: Distribution
  metrics: Metric[]
  channels: Channel[]
  recentActivities: Activity[]
  isActive?: boolean
}>()

const emit = defineEmits<{
  viewDetails: []
  startDistribution: []
  pauseDistribution: []
  cloneDistribution: []
}>()

const isSending = computed(() => props.distribution.status === 'sending')

const statusClasses = computed(() => {
  const classes = {
    scheduled: 'bg-blue-100 text-blue-800',
    sending: 'bg-amber-100 text-amber-800',
    completed: 'bg-green-100 text-green-800',
    paused: 'bg-gray-100 text-gray-800',
    failed: 'bg-red-100 text-red-800'
  }
  return classes[props.distribution.status]
})

const viewDetails = () => emit('viewDetails')
const startDistribution = () => emit('startDistribution')
const pauseDistribution = () => emit('pauseDistribution')
const cloneDistribution = () => emit('cloneDistribution')
</script>

<style scoped>
@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.animate-shimmer {
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
  background-size: 200% 100%;
  animation: shimmer 2s infinite;
}
</style>
