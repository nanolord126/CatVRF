<template>
  <div class="relative space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Team Presence
      </h3>
      <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
        {{ onlineCount }}/{{ totalCount }} online
      </span>
    </div>

    <!-- Team Members Grid -->
    <div v-if="members.length > 0" class="grid grid-cols-2 gap-3 md:grid-cols-3">
      <div
        v-for="member in members"
        :key="member.user_id"
        class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800"
      >
        <div class="flex items-center gap-2">
          <!-- Avatar -->
          <div class="relative">
            <div
              v-if="member.avatar_url"
              class="h-10 w-10 flex-shrink-0 rounded-full"
              :style="{ backgroundImage: `url(${member.avatar_url})`, backgroundSize: 'cover' }"
            />
            <div v-else class="h-10 w-10 flex-shrink-0 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600" />

            <!-- Status Indicator -->
            <span
              :class="[
                'absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-white',
                member.status === 'online'
                  ? 'bg-green-500'
                  : member.status === 'idle'
                    ? 'bg-yellow-500'
                    : 'bg-gray-500',
              ]"
            />
          </div>

          <!-- User Info -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
              {{ member.name }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
              {{ formatStatus(member.status) }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="rounded-lg bg-gray-50 p-6 text-center dark:bg-gray-800">
      <p class="text-sm text-gray-500 dark:text-gray-400">
        No team members currently viewing this document
      </p>
    </div>

    <!-- Activity Status -->
    <div v-if="lastActivityTime" class="rounded-lg bg-blue-50 p-3 dark:bg-blue-900">
      <p class="text-xs text-blue-700 dark:text-blue-100">
        Last activity: {{ lastActivityTime }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

interface TeamMember {
  user_id: number
  name: string
  avatar_url: string | null
  status: 'online' | 'idle' | 'away'
  last_active: string
}

const members = ref<TeamMember[]>([])
const onlineCount = ref(0)
const totalCount = ref(0)
const lastActivityTime = ref<string | null>(null)
let refreshInterval: ReturnType<typeof setInterval> | null = null

const loadPresence = async () => {
  try {
    // Placeholder: в реальности нужно передавать documentType и documentId
    const response = await fetch('/api/v2/collaboration/team-presence', {
      method: 'GET',
    })

    if (response.ok) {
      const data = await response.json()
      members.value = data.presence || []
      totalCount.value = data.count || 0
      onlineCount.value = members.value.filter((m) => m.status === 'online').length
      lastActivityTime.value = new Date().toLocaleTimeString()
    }
  } catch (error) {
    console.error('Failed to load team presence:', error)
  }
}

const formatStatus = (status: string): string => {
  const statuses: Record<string, string> = {
    online: '🟢 Online',
    idle: '🟡 Idle',
    away: '⚫ Away',
  }

  return statuses[status] || status
}

onMounted(() => {
  loadPresence()
  refreshInterval = setInterval(loadPresence, 10000) // Refresh every 10 seconds
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>
