<template>
  <div class="bonus-reward-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Image -->
    <div class="relative h-32 bg-gradient-to-r from-amber-400 to-yellow-500">
      <div class="absolute inset-0 flex items-center justify-center">
        <span class="text-5xl">{{ reward.icon }}</span>
      </div>
      <span 
        v-if="reward.isExclusive"
        class="absolute top-2 left-2 bg-black/70 text-white px-2 py-1 rounded text-xs font-bold"
      >
        EXCLUSIVE
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ reward.name }}</h3>
      
      <!-- Description -->
      <p class="text-sm text-gray-600 mb-3">{{ reward.description }}</p>

      <!-- Points Required -->
      <div class="flex items-center gap-2 mb-4">
        <span class="text-2xl font-bold text-amber-600">{{ reward.pointsRequired.toLocaleString() }}</span>
        <span class="text-xs text-gray-400">points</span>
      </div>

      <!-- Progress -->
      <div v-if="userPoints < reward.pointsRequired" class="mb-4">
        <div class="flex justify-between text-sm mb-1">
          <span class="text-gray-600">Your points</span>
          <span class="font-medium">{{ userPoints.toLocaleString() }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div 
            class="bg-amber-500 h-2 rounded-full" 
            :style="{ width: progress + '%' }"
          ></div>
        </div>
        <p class="text-xs text-gray-500 mt-1">{{ pointsNeeded.toLocaleString() }} more needed</p>
      </div>

      <!-- Validity -->
      <div class="mb-4">
        <p class="text-xs text-gray-500">Valid until: {{ reward.validUntil }}</p>
      </div>

      <!-- Category -->
      <div class="mb-4">
        <span class="px-2 py-1 bg-amber-50 text-amber-700 rounded text-xs">
          {{ reward.category }}
        </span>
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
          @click="redeem"
          :disabled="userPoints < reward.pointsRequired"
          class="flex-1 bg-amber-600 hover:bg-amber-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          {{ userPoints >= reward.pointsRequired ? 'Redeem' : 'Locked' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface BonusReward {
  id: string
  name: string
  description: string
  icon: string
  pointsRequired: number
  category: string
  validUntil: string
  isExclusive: boolean
}

const props = defineProps<{
  reward: BonusReward
  userPoints: number
}>()

const emit = defineEmits<{
  viewDetails: [reward: BonusReward]
  redeem: [reward: BonusReward]
}>()

const progress = Math.min(Math.round((props.userPoints / props.reward.pointsRequired) * 100), 100)
const pointsNeeded = Math.max(props.reward.pointsRequired - props.userPoints, 0)

const viewDetails = () => {
  emit('viewDetails', props.reward)
}

const redeem = () => {
  emit('redeem', props.reward)
}
</script>
