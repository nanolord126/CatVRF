<template>
  <div class="video-room-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Preview Image -->
    <div class="relative h-40 bg-gradient-to-r from-violet-500 to-purple-500">
      <img 
        :src="room.thumbnailUrl" 
        :alt="room.name"
        class="w-full h-full object-cover opacity-90"
      />
      <span :class="getStatusClass(room.status)" class="absolute top-2 left-2 px-3 py-1 rounded-full text-xs font-bold bg-white/90">
        {{ room.status }}
      </span>
      <div class="absolute bottom-2 right-2 flex items-center gap-1 bg-black/70 text-white px-2 py-1 rounded text-xs">
        <span>👥</span>
        <span>{{ room.participants }}/{{ room.maxParticipants }}</span>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Room Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2">{{ room.name }}</h3>
      
      <!-- Host Info -->
      <div class="flex items-center gap-2 mb-4">
        <div class="w-8 h-8 bg-violet-100 rounded-full flex items-center justify-center">
          <span class="text-violet-600 font-bold text-xs">{{ host.initials }}</span>
        </div>
        <div>
          <p class="text-sm font-medium text-gray-900">{{ host.name }}</p>
          <p class="text-xs text-gray-500">{{ host.role }}</p>
        </div>
      </div>

      <!-- Room Details -->
      <div class="bg-gray-50 rounded-lg p-3 mb-4">
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Type:</span>
            <span class="font-medium">{{ room.type }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Duration:</span>
            <span class="font-medium">{{ room.duration }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Started:</span>
            <span class="font-medium">{{ room.startTime }}</span>
          </div>
        </div>
      </div>

      <!-- Features -->
      <div class="mb-4">
        <p class="text-xs font-medium text-gray-700 mb-1">Features</p>
        <div class="flex flex-wrap gap-1">
          <span 
            v-for="feature in room.features" 
            :key="feature"
            class="px-2 py-0.5 bg-violet-50 text-violet-700 rounded text-xs"
          >
            {{ feature }}
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="joinRoom"
          class="flex-1 bg-violet-600 hover:bg-violet-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Join Room
        </button>
        <button 
          @click="shareRoom"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Share
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Host {
  name: string
  initials: string
  role: string
}

interface VideoRoom {
  id: string
  name: string
  status: 'Live' | 'Scheduled' | 'Ended'
  type: string
  thumbnailUrl: string
  participants: number
  maxParticipants: number
  duration: string
  startTime: string
  features: string[]
}

const props = defineProps<{
  room: VideoRoom
  host: Host
}>()

const emit = defineEmits<{
  joinRoom: [room: VideoRoom]
  shareRoom: [room: VideoRoom]
}>()

const getStatusClass = (status: string) => {
  const classes = {
    'Live': 'text-red-900',
    'Scheduled': 'text-blue-900',
    'Ended': 'text-gray-900',
  }
  return classes[status as keyof typeof classes] || 'text-gray-900'
}

const joinRoom = () => {
  emit('joinRoom', props.room)
}

const shareRoom = () => {
  emit('shareRoom', props.room)
}
</script>
