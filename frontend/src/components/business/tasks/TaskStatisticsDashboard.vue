<template>
  <div class="task-statistics-dashboard bg-gray-900 text-white p-6 rounded-lg">
    <h2 class="text-2xl font-bold mb-6">Task Statistics</h2>

    <!-- Key Metrics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-gray-800 rounded-lg p-4">
        <div class="text-3xl font-bold text-blue-400">{{ statistics.total || 0 }}</div>
        <div class="text-sm text-gray-400 mt-1">Total Tasks</div>
      </div>
      <div class="bg-gray-800 rounded-lg p-4">
        <div class="text-3xl font-bold text-yellow-400">{{ statistics.pending || 0 }}</div>
        <div class="text-sm text-gray-400 mt-1">Pending</div>
      </div>
      <div class="bg-gray-800 rounded-lg p-4">
        <div class="text-3xl font-bold text-purple-400">{{ statistics.in_progress || 0 }}</div>
        <div class="text-sm text-gray-400 mt-1">In Progress</div>
      </div>
      <div class="bg-gray-800 rounded-lg p-4">
        <div class="text-3xl font-bold text-green-400">{{ statistics.completed || 0 }}</div>
        <div class="text-sm text-gray-400 mt-1">Completed</div>
      </div>
    </div>

    <!-- Priority Distribution -->
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
      <h3 class="text-lg font-semibold mb-4">By Priority</h3>
      <div class="space-y-3">
        <div v-for="(count, priority) in statistics.by_priority" :key="priority" class="flex items-center">
          <span class="w-20 text-sm text-gray-400 capitalize">{{ priority }}</span>
          <div class="flex-1 mx-4 bg-gray-700 rounded-full h-3">
            <div 
              class="h-3 rounded-full"
              :class="priorityBarColor(priority)"
              :style="{ width: `${getPercentage(count, statistics.total)}%` }"
            />
          </div>
          <span class="w-12 text-right text-sm">{{ count }}</span>
        </div>
      </div>
    </div>

    <!-- Type Distribution -->
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
      <h3 class="text-lg font-semibold mb-4">By Type</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div 
          v-for="(count, type) in statistics.by_type" 
          :key="type"
          class="text-center p-4 bg-gray-700 rounded-lg"
        >
          <div class="text-2xl font-bold">{{ count }}</div>
          <div class="text-sm text-gray-400 mt-1 capitalize">{{ type }}</div>
        </div>
      </div>
    </div>

    <!-- Overdue & Due Soon -->
    <div class="grid grid-cols-2 gap-4">
      <div class="bg-red-900/30 border border-red-700 rounded-lg p-4">
        <div class="flex items-center gap-3">
          <svg class="w-8 h-8 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <div>
            <div class="text-2xl font-bold text-red-400">{{ statistics.overdue || 0 }}</div>
            <div class="text-sm text-gray-400">Overdue</div>
          </div>
        </div>
      </div>
      <div class="bg-yellow-900/30 border border-yellow-700 rounded-lg p-4">
        <div class="flex items-center gap-3">
          <svg class="w-8 h-8 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div>
            <div class="text-2xl font-bold text-yellow-400">{{ statistics.due_soon || 0 }}</div>
            <div class="text-sm text-gray-400">Due Soon (3 days)</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Statistics {
  total: number
  pending: number
  in_progress: number
  review: number
  completed: number
  overdue: number
  due_soon: number
  by_priority: Record<string, number>
  by_type: Record<string, number>
}

const statistics = ref<Statistics>({
  total: 0,
  pending: 0,
  in_progress: 0,
  review: 0,
  completed: 0,
  overdue: 0,
  due_soon: 0,
  by_priority: {},
  by_type: {},
})

const loadStatistics = async () => {
  const tenantId = 1 // Get from auth
  const response = await fetch(`/api/notifications/internal-tasks/statistics?tenant_id=${tenantId}`)
  const data = await response.json()
  statistics.value = data.data
}

const getPercentage = (count: number, total: number): number => {
  return total > 0 ? (count / total) * 100 : 0
}

const priorityBarColor = (priority: string): string => {
  return {
    'red': 'bg-red-500',
    'orange': 'bg-orange-500',
    'medium': 'bg-yellow-500',
    'low': 'bg-green-500',
  }[priority] || 'bg-gray-500'
}

onMounted(() => {
  loadStatistics()
  
  // Refresh every 30 seconds
  setInterval(loadStatistics, 30000)
})
</script>

<style scoped>
.task-statistics-dashboard {
  min-height: 400px;
}
</style>
