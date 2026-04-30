<template>
  <div class="loyalty-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ program.name }}</h3>
          <p class="text-sm text-gray-500">{{ program.tier }}</p>
        </div>
        <div class="text-right">
          <p class="text-2xl font-bold text-indigo-600">{{ points.toLocaleString() }}</p>
          <p class="text-xs text-gray-400">points</p>
        </div>
      </div>
    </div>

    <!-- Progress to Next Tier -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <div class="flex justify-between text-sm mb-2">
        <span class="text-gray-600">Progress to {{ nextTier.name }}</span>
        <span class="font-medium">{{ progress }}%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div 
          class="bg-indigo-600 h-2 rounded-full" 
          :style="{ width: progress + '%' }"
        ></div>
      </div>
      <p class="text-xs text-gray-500 mt-1">{{ pointsNeeded.toLocaleString() }} more points</p>
    </div>

    <!-- Benefits -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Current Benefits</p>
      <div class="space-y-2">
        <div 
          v-for="benefit in program.benefits" 
          :key="benefit"
          class="flex items-center gap-2 text-sm text-gray-600"
        >
          <span class="text-green-500">✓</span>
          <span>{{ benefit }}</span>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Recent Activity</p>
      <div class="space-y-2">
        <div 
          v-for="activity in recentActivities.slice(0, 3)" 
          :key="activity.id"
          class="flex justify-between items-center text-sm"
        >
          <div>
            <p class="text-gray-900">{{ activity.description }}</p>
            <p class="text-xs text-gray-500">{{ activity.date }}</p>
          </div>
          <span 
            :class="activity.points > 0 ? 'text-green-600' : 'text-red-600'"
            class="font-medium"
          >
            {{ activity.points > 0 ? '+' : '' }}{{ activity.points }}
          </span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="viewRewards"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        View Rewards
      </button>
      <button 
        @click="redeemPoints"
        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Redeem
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface LoyaltyProgram {
  name: string
  tier: string
  benefits: string[]
}

interface NextTier {
  name: string
  pointsRequired: number
}

interface Activity {
  id: string
  description: string
  points: number
  date: string
}

const props = defineProps<{
  program: LoyaltyProgram
  points: number
  nextTier: NextTier
  recentActivities: Activity[]
}>()

const emit = defineEmits<{
  viewRewards: []
  redeemPoints: []
}>()

const progress = Math.min(Math.round((props.points / props.nextTier.pointsRequired) * 100), 100)
const pointsNeeded = Math.max(props.nextTier.pointsRequired - props.points, 0)

const viewRewards = () => {
  emit('viewRewards')
}

const redeemPoints = () => {
  emit('redeemPoints')
}
</script>
