<template>
  <div class="ad-campaign-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ campaign.name }}</h3>
          <p class="text-sm text-gray-500">{{ campaign.type }}</p>
        </div>
        <span :class="getStatusClass(campaign.status)" class="px-3 py-1 rounded-full text-xs font-medium">
          {{ campaign.status }}
        </span>
      </div>
    </div>

    <!-- Campaign Details -->
    <div class="space-y-3 mb-4">
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Budget:</span>
        <span class="font-medium">{{ formatPrice(campaign.budget) }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Spent:</span>
        <span class="font-medium">{{ formatPrice(campaign.spent) }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Impressions:</span>
        <span class="font-medium">{{ formatNumber(campaign.impressions) }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Clicks:</span>
        <span class="font-medium">{{ formatNumber(campaign.clicks) }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">CTR:</span>
        <span class="font-medium">{{ campaign.ctr }}%</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Conversions:</span>
        <span class="font-medium">{{ formatNumber(campaign.conversions) }}</span>
      </div>
    </div>

    <!-- Progress Bar -->
    <div class="mb-4">
      <div class="flex justify-between text-xs text-gray-500 mb-1">
        <span>Budget Used</span>
        <span>{{ Math.round((campaign.spent / campaign.budget) * 100) }}%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div 
          class="bg-blue-600 h-2 rounded-full transition-all" 
          :style="{ width: Math.min((campaign.spent / campaign.budget) * 100, 100) + '%' }"
        ></div>
      </div>
    </div>

    <!-- Date Range -->
    <div class="text-sm text-gray-500 mb-4">
      <p>{{ formatDate(campaign.startDate) }} - {{ formatDate(campaign.endDate) }}</p>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="edit"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Edit
      </button>
      <button 
        @click="pause"
        v-if="campaign.status === 'Active'"
        class="flex-1 bg-yellow-100 hover:bg-yellow-200 text-yellow-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Pause
      </button>
      <button 
        @click="resume"
        v-if="campaign.status === 'Paused'"
        class="flex-1 bg-green-100 hover:bg-green-200 text-green-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Resume
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Campaign {
  id: string
  name: string
  type: string
  status: 'Active' | 'Paused' | 'Completed' | 'Draft'
  budget: number
  spent: number
  impressions: number
  clicks: number
  ctr: number
  conversions: number
  startDate: string
  endDate: string
}

const props = defineProps<{
  campaign: Campaign
}>()

const emit = defineEmits<{
  edit: [campaign: Campaign]
  pause: [campaign: Campaign]
  resume: [campaign: Campaign]
}>()

const getStatusClass = (status: string) => {
  const classes = {
    'Active': 'bg-green-100 text-green-900',
    'Paused': 'bg-yellow-100 text-yellow-900',
    'Completed': 'bg-blue-100 text-blue-900',
    'Draft': 'bg-gray-100 text-gray-900',
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const formatNumber = (num: number) => {
  return new Intl.NumberFormat('ru-RU').format(num)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
}

const edit = () => {
  emit('edit', props.campaign)
}

const pause = () => {
  emit('pause', props.campaign)
}

const resume = () => {
  emit('resume', props.campaign)
}
</script>
