<template>
  <div 
    class="task-card bg-white rounded-lg shadow hover:shadow-lg transition-shadow cursor-pointer border-l-4"
    :class="priorityBorderClass"
    @click="$emit('view-details')"
  >
    <!-- Header -->
    <div class="p-4 border-b border-gray-100">
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <h3 class="font-semibold text-gray-900 mb-1">{{ task.title }}</h3>
          <p v-if="task.description" class="text-sm text-gray-600 line-clamp-2">{{ task.description }}</p>
        </div>
        <div class="ml-2">
          <span 
            class="inline-flex items-center px-2 py-1 rounded text-xs font-medium"
            :class="priorityClass"
          >
            {{ task.priority }}
          </span>
        </div>
      </div>
    </div>

    <!-- Body -->
    <div class="p-4 space-y-3">
      <!-- Status Badge -->
      <div class="flex items-center justify-between">
        <span 
          class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
          :class="statusClass"
        >
          {{ formatStatus(task.status) }}
        </span>
        <span v-if="task.is_overdue" class="text-xs text-red-600 font-medium flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          Overdue
        </span>
      </div>

      <!-- Progress -->
      <div>
        <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
          <span>Progress</span>
          <span>{{ Math.round(task.progress) }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div 
            class="h-2 rounded-full transition-all duration-300"
            :class="progressClass"
            :style="{ width: `${task.progress}%` }"
          />
        </div>
      </div>

      <!-- Due Date -->
      <div v-if="task.due_date" class="flex items-center text-sm text-gray-600">
        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        {{ formatDate(task.due_date) }}
      </div>

      <!-- Assignees -->
      <div class="flex items-center justify-between text-sm">
        <div v-if="task.assignee" class="flex items-center text-gray-600">
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          {{ task.assignee.name }}
        </div>
        <div v-if="task.controller" class="flex items-center text-gray-500 text-xs">
          <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
          Controller: {{ task.controller.name }}
        </div>
      </div>
    </div>

    <!-- Footer Actions -->
    <div class="p-4 bg-gray-50 rounded-b-lg border-t border-gray-100">
      <div class="flex items-center justify-between">
        <span class="text-xs text-gray-500">{{ task.type }}</span>
        <div class="flex items-center gap-2">
          <button 
            v-if="task.status !== 'completed'"
            @click.stop="$emit('mark-complete')"
            class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
            title="Mark Complete"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </button>
          <button 
            @click.stop="$emit('update-status', 'in_progress')"
            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
            title="Start"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Task {
  id: number
  title: string
  description: string | null
  status: string
  priority: string
  type: string
  due_date: string | null
  progress: number
  is_overdue: boolean
  assignee: { name: string } | null
  controller: { name: string } | null
}

defineProps<{
  task: Task
}>()

defineEmits<{
  'view-details': []
  'update-status': [status: string]
  'mark-complete': []
}>()

const priorityBorderClass = computed(() => {
  return {
    'border-red-500': props.task.priority === 'urgent',
    'border-orange-500': props.task.priority === 'high',
    'border-yellow-500': props.task.priority === 'medium',
    'border-green-500': props.task.priority === 'low',
  }
})

const priorityClass = computed(() => {
  return {
    'bg-red-100 text-red-800': props.task.priority === 'urgent',
    'bg-orange-100 text-orange-800': props.task.priority === 'high',
    'bg-yellow-100 text-yellow-800': props.task.priority === 'medium',
    'bg-green-100 text-green-800': props.task.priority === 'low',
  }
})

const statusClass = computed(() => {
  return {
    'bg-gray-100 text-gray-800': props.task.status === 'draft',
    'bg-blue-100 text-blue-800': props.task.status === 'pending',
    'bg-yellow-100 text-yellow-800': props.task.status === 'in_progress',
    'bg-purple-100 text-purple-800': props.task.status === 'review',
    'bg-green-100 text-green-800': props.task.status === 'completed',
    'bg-red-100 text-red-800': props.task.status === 'cancelled',
  }
})

const progressClass = computed(() => {
  if (props.task.progress === 100) return 'bg-green-500'
  if (props.task.progress >= 50) return 'bg-blue-500'
  return 'bg-yellow-500'
})

const formatStatus = (status: string): string => {
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatDate = (dateStr: string): string => {
  return new Date(dateStr).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<style scoped>
.task-card {
  transition: all 0.2s ease;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
