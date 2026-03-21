<template>
  <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
    <!-- Header -->
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
        Team Chat
      </h3>
      <button
        @click="toggleExpanded"
        class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300"
      >
        {{ isExpanded ? '−' : '+' }}
      </button>
    </div>

    <!-- Chat Rooms List -->
    <div v-if="isExpanded" class="space-y-2">
      <div
        v-for="room in chatRooms"
        :key="room.room_id"
        :class="{
          'bg-blue-50 dark:bg-blue-900': activeRoomId === room.room_id,
          'hover:bg-gray-50 dark:hover:bg-gray-700': activeRoomId !== room.room_id,
        }"
        class="cursor-pointer rounded-lg p-2 transition"
        @click="selectRoom(room.room_id)"
      >
        <p class="text-sm font-medium text-gray-900 dark:text-white">
          {{ room.name }}
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ room.members.length }} members
        </p>
      </div>

      <!-- Create Room Button -->
      <button
        @click="showCreateForm = true"
        class="w-full rounded-lg bg-blue-500 px-3 py-2 text-sm font-medium text-white hover:bg-blue-600"
      >
        + New Room
      </button>
    </div>

    <!-- Create Room Form -->
    <div v-if="showCreateForm" class="space-y-2 border-t border-gray-200 pt-4 dark:border-gray-700">
      <input
        v-model="newRoomName"
        type="text"
        placeholder="Room name..."
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
      />

      <div class="flex gap-2">
        <button
          @click="createRoom"
          class="flex-1 rounded-lg bg-green-500 px-2 py-2 text-sm font-medium text-white hover:bg-green-600"
        >
          Create
        </button>
        <button
          @click="showCreateForm = false"
          class="flex-1 rounded-lg bg-gray-300 px-2 py-2 text-sm font-medium text-gray-900 hover:bg-gray-400"
        >
          Cancel
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div v-if="!isExpanded" class="text-center">
      <p class="text-xs text-gray-500 dark:text-gray-400">
        {{ chatRooms.length }} rooms
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface ChatRoom {
  room_id: string
  name: string
  members: number[]
  created_at: string
}

const chatRooms = ref<ChatRoom[]>([])
const activeRoomId = ref<string | null>(null)
const isExpanded = ref(true)
const showCreateForm = ref(false)
const newRoomName = ref('')

const loadChatRooms = async () => {
  try {
    // Placeholder: в реальности нужно загружать данные с сервера
    chatRooms.value = []
  } catch (error) {
    console.error('Failed to load chat rooms:', error)
  }
}

const selectRoom = (roomId: string) => {
  activeRoomId.value = roomId
}

const createRoom = async () => {
  if (!newRoomName.value.trim()) {
    return
  }

  try {
    const response = await fetch('/api/v2/chat/rooms', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        name: newRoomName.value,
      }),
    })

    if (response.ok) {
      const data = await response.json()
      chatRooms.value.push(data.room)
      newRoomName.value = ''
      showCreateForm.value = false
      selectRoom(data.room.room_id)
    }
  } catch (error) {
    console.error('Failed to create room:', error)
  }
}

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
}

onMounted(() => {
  loadChatRooms()
})
</script>
