<template>
  <div class="promo-campaign-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Header -->
    <div class="relative h-32 bg-gradient-to-r from-pink-500 to-rose-500">
      <div class="absolute inset-0 flex items-center justify-center">
        <h3 class="text-2xl font-bold text-white text-center px-4">{{ campaign.name }}</h3>
      </div>
      <span :class="getStatusClass(campaign.status)" class="absolute top-2 right-2 px-3 py-1 rounded-full text-xs font-bold bg-white/90">
        {{ campaign.status }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Campaign Type -->
      <div class="mb-4">
        <p class="text-sm font-medium text-gray-700 mb-1">Campaign Type</p>
        <p class="text-sm text-gray-600">{{ campaign.type }}</p>
      </div>

      <!-- Date Range -->
      <div class="mb-4">
        <p class="text-sm font-medium text-gray-700 mb-1">Duration</p>
        <p class="text-sm text-gray-600">{{ campaign.startDate }} - {{ campaign.endDate }}</p>
      </div>

      <!-- Budget -->
      <div class="bg-gray-50 rounded-lg p-3 mb-4">
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Budget:</span>
            <span class="font-medium">{{ formatPrice(campaign.budget) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Spent:</span>
            <span class="font-medium">{{ formatPrice(campaign.spent) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Remaining:</span>
            <span class="font-medium">{{ formatPrice(campaign.budget - campaign.spent) }}</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
            <div 
              class="bg-pink-600 h-2 rounded-full" 
              :style="{ width: (campaign.spent / campaign.budget * 100) + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Performance Metrics -->
      <div class="grid grid-cols-2 gap-2 mb-4">
        <div class="bg-blue-50 rounded p-2 text-center">
          <p class="text-lg font-bold text-blue-900">{{ campaign.impressions.toLocaleString() }}</p>
          <p class="text-xs text-blue-700">Impressions</p>
        </div>
        <div class="bg-green-50 rounded p-2 text-center">
          <p class="text-lg font-bold text-green-900">{{ campaign.clicks.toLocaleString() }}</p>
          <p class="text-xs text-green-700">Clicks</p>
        </div>
        <div class="bg-purple-50 rounded p-2 text-center">
          <p class="text-lg font-bold text-purple-900">{{ campaign.conversions.toLocaleString() }}</p>
          <p class="text-xs text-purple-700">Conversions</p>
        </div>
        <div class="bg-orange-50 rounded p-2 text-center">
          <p class="text-lg font-bold text-orange-900">{{ campaign.ctr }}%</p>
          <p class="text-xs text-orange-700">CTR</p>
        </div>
      </div>

      <!-- Target Audience -->
      <div class="mb-4">
        <p class="text-sm font-medium text-gray-700 mb-1">Target Audience</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="segment in campaign.targetAudience" 
            :key="segment"
            class="px-2 py-0.5 bg-pink-50 text-pink-700 rounded text-xs"
          >
            {{ segment }}
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="viewDetails"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Details
        </button>
        <button 
          @click="edit"
          class="flex-1 bg-pink-600 hover:bg-pink-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Edit
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface PromoCampaign {
  id: string
  name: string
  type: string
  status: 'Draft' | 'Active' | 'Paused' | 'Completed'
  startDate: string
  endDate: string
  budget: number
  spent: number
  impressions: number
  clicks: number
  conversions: number
  ctr: number
  targetAudience: string[]
}

const props = defineProps<{
  campaign: PromoCampaign
}>()

const emit = defineEmits<{
  viewDetails: [campaign: PromoCampaign]
  edit: [campaign: PromoCampaign]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getStatusClass = (status: string) => {
  const classes = {
    'Draft': 'text-gray-900',
    'Active': 'text-green-900',
    'Paused': 'text-yellow-900',
    'Completed': 'text-blue-900',
  }
  return classes[status as keyof typeof classes] || 'text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.campaign)
}

const edit = () => {
  emit('edit', props.campaign)
}
</script>
