<template>
  <div class="notification-card bg-white rounded-xl shadow-md p-4">
    <!-- Header -->
    <div class="flex items-start gap-3">
      <!-- Icon -->
      <div :class="getIconClass(notification.type)" class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0">
        <span class="text-xl">{{ getIcon(notification.type) }}</span>
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex justify-between items-start">
          <h4 class="text-sm font-bold text-gray-900 truncate">{{ notification.title }}</h4>
          <span class="text-xs text-gray-400 flex-shrink-0 ml-2">{{ notification.time }}</span>
        </div>
        <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ notification.message }}</p>
      </div>

      <!-- Unread Indicator -->
      <div v-if="!notification.read" class="w-2 h-2 bg-blue-600 rounded-full flex-shrink-0"></div>
    </div>

    <!-- Action Button (if applicable) -->
    <div v-if="notification.action" class="mt-3">
      <button 
        @click="handleAction"
        class="w-full text-sm text-blue-600 hover:text-blue-700 font-medium text-left"
      >
        {{ notification.action.label }} →
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface NotificationAction {
  label: string
  value: string
}

interface Notification {
  id: string
  type: 'info' | 'success' | 'warning' | 'error'
  title: string
  message: string
  time: string
  read: boolean
  action?: NotificationAction
}

const props = defineProps<{
  notification: Notification
}>()

const emit = defineEmits<{
  action: [notification: Notification]
}>()

const getIcon = (type: string) => {
  const icons = {
    'info': 'ℹ️',
    'success': '✅',
    'warning': '⚠️',
    'error': '❌',
  }
  return icons[type as keyof typeof icons] || 'ℹ️'
}

const getIconClass = (type: string) => {
  const classes = {
    'info': 'bg-blue-100 text-blue-600',
    'success': 'bg-green-100 text-green-600',
    'warning': 'bg-yellow-100 text-yellow-600',
    'error': 'bg-red-100 text-red-600',
  }
  return classes[type as keyof typeof classes] || 'bg-gray-100 text-gray-600'
}

const handleAction = () => {
  emit('action', props.notification)
}
</script>
