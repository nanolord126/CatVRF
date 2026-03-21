<template>
  <div class="flex h-full flex-col bg-white dark:bg-gray-900">
    <!-- Header -->
    <div class="border-b border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
        {{ roomName }}
      </h2>
      <p class="text-xs text-gray-500 dark:text-gray-400">
        {{ memberCount }} members
      </p>
    </div>

    <!-- Messages -->
    <div class="flex-1 overflow-y-auto p-4 space-y-3">
      <div
        v-for="message in messages"
        :key="message.message_id"
        :class="{
          'flex justify-end': message.user_id === currentUserId,
          'flex justify-start': message.user_id !== currentUserId,
        }"
      >
        <!-- Message Bubble -->
        <div
          :class="{
            'max-w-xs rounded-lg bg-blue-500 p-3 text-white': message.user_id === currentUserId,
            'max-w-xs rounded-lg bg-gray-200 p-3 dark:bg-gray-700': message.user_id !== currentUserId,
          }"
        >
          <p
            :class="{
              'text-white': message.user_id === currentUserId,
              'text-gray-900 dark:text-white': message.user_id !== currentUserId,
            }"
            class="text-sm break-words"
          >
            {{ message.content }}
          </p>

          <div
            :class="{
              'text-blue-100': message.user_id === currentUserId,
              'text-gray-500 dark:text-gray-400': message.user_id !== currentUserId,
            }"
            class="mt-1 text-xs"
          >
            {{ formatTime(message.created_at) }}
            <span v-if="message.edited_at" class="ml-1">(edited)</span>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="messages.length === 0" class="flex h-full items-center justify-center">
        <p class="text-center text-gray-500 dark:text-gray-400">
          No messages yet. Start the conversation!
        </p>
      </div>
    </div>

    <!-- Input -->
    <div class="border-t border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
      <div class="flex gap-2">
        <textarea
          v-model="messageContent"
          placeholder="Type a message..."
          class="flex-1 resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-700 dark:text-white"
          rows="2"
          @keydown.enter.ctrl="sendMessage"
        />

        <button
          @click="sendMessage"
          :disabled="!messageContent.trim()"
          class="rounded-lg bg-blue-500 px-4 py-2 text-white hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Send
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'

interface Message {
  message_id: string
  user_id: number
  content: string
  created_at: string
  edited_at: string | null
}

const props = defineProps({
  roomId: {
    type: String,
    required: true,
  },
})

const messages = ref<Message[]>([])
const messageContent = ref('')
const roomName = ref('Chat Room')
const memberCount = ref(0)
const currentUserId = ref<number>(0)

const hasMessages = computed(() => messages.length > 0)

const loadMessages = async () => {
  try {
    const response = await fetch(`/api/v2/chat/${props.roomId}/messages?limit=50`)

    if (response.ok) {
      const data = await response.json()
      messages.value = data.messages || []
    }
  } catch (error) {
    console.error('Failed to load messages:', error)
  }
}

const sendMessage = async () => {
  if (!messageContent.value.trim()) {
    return
  }

  try {
    const response = await fetch('/api/v2/chat/send', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        room_id: props.roomId,
        content: messageContent.value,
      }),
    })

    if (response.ok) {
      const data = await response.json()
      messages.value.push(data.message)
      messageContent.value = ''

      // Scroll to bottom
      await new Promise((resolve) => setTimeout(resolve, 0))
      const container = document.querySelector('[class*="overflow-y-auto"]')
      if (container) {
        container.scrollTop = container.scrollHeight
      }
    }
  } catch (error) {
    console.error('Failed to send message:', error)
  }
}

const formatTime = (timestamp: string): string => {
  const date = new Date(timestamp)
  return date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(() => {
  loadMessages()

  // Auto-refresh every 5 seconds
  setInterval(loadMessages, 5000)
})
</script>
