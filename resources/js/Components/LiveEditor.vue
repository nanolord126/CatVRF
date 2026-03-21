<template>
  <div class="relative space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Live Editing
      </h3>
      <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
        {{ editorCount }} editing
      </span>
    </div>

    <!-- Active Editors List -->
    <div v-if="editors.length > 0" class="space-y-2">
      <div
        v-for="editor in editors"
        :key="editor.session_id"
        class="flex items-center gap-3 rounded-lg bg-gray-50 p-3 dark:bg-gray-800"
      >
        <div
          v-if="editor.user_avatar"
          class="h-8 w-8 flex-shrink-0 rounded-full"
          :style="{ backgroundImage: `url(${editor.user_avatar})`, backgroundSize: 'cover' }"
        />
        <div v-else class="h-8 w-8 flex-shrink-0 rounded-full bg-gradient-to-br from-blue-400 to-blue-600" />

        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
            {{ editor.user_name }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ formatTime(editor.started_at) }}
          </p>
        </div>

        <!-- Cursor Position Indicator -->
        <div
          v-if="editor.cursor_position"
          class="h-3 w-3 rounded-full flex-shrink-0"
          :style="{ backgroundColor: editor.cursor_position.color || '#10b981' }"
        />
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="rounded-lg bg-gray-50 p-6 text-center dark:bg-gray-800">
      <p class="text-sm text-gray-500 dark:text-gray-400">
        No active editors at this moment
      </p>
    </div>

    <!-- Auto Refresh -->
    <div class="flex gap-2">
      <button
        @click="refresh"
        class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100 dark:bg-blue-900 dark:text-blue-100"
      >
        ↻ Refresh
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

interface Editor {
  session_id: string
  user_id: number
  user_name: string
  user_avatar: string | null
  started_at: string
  editing_element: string | null
  cursor_position: { color: string } | null
}

const editors = ref<Editor[]>([])
const editorCount = ref(0)
let refreshInterval: ReturnType<typeof setInterval> | null = null

const loadEditors = async () => {
  try {
    // Placeholder: в реальности нужно передавать documentType и documentId
    const response = await fetch('/api/v2/collaboration/active-editors', {
      method: 'GET',
    })

    if (response.ok) {
      const data = await response.json()
      editors.value = data.editors || []
      editorCount.value = data.count || 0
    }
  } catch (error) {
    console.error('Failed to load active editors:', error)
  }
}

const refresh = () => {
  loadEditors()
}

const formatTime = (timestamp: string): string => {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const minutes = Math.floor(diff / 60000)

  if (minutes === 0) return 'Just now'
  if (minutes < 60) return `${minutes}m ago`

  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`

  return date.toLocaleDateString()
}

onMounted(() => {
  loadEditors()
  refreshInterval = setInterval(loadEditors, 5000) // Refresh every 5 seconds
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>
