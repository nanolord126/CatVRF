<template>
  <div class="media-file-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Thumbnail -->
    <div class="relative h-40 bg-gray-100">
      <img 
        :src="media.thumbnailUrl" 
        :alt="media.name"
        class="w-full h-full object-cover"
      />
      <span :class="getTypeClass(media.type)" class="absolute top-2 left-2 px-2 py-1 rounded text-xs font-bold bg-white/90">
        {{ media.type }}
      </span>
      <div class="absolute bottom-2 right-2 bg-black/70 text-white px-2 py-1 rounded text-xs">
        {{ media.duration }}
      </div>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Name -->
      <h3 class="text-lg font-bold text-gray-900 mb-2 truncate">{{ media.name }}</h3>
      
      <!-- File Info -->
      <div class="space-y-1 mb-4 text-sm text-gray-600">
        <div class="flex justify-between">
          <span>Size:</span>
          <span>{{ media.size }}</span>
        </div>
        <div class="flex justify-between">
          <span>Resolution:</span>
          <span>{{ media.resolution }}</span>
        </div>
        <div class="flex justify-between">
          <span>Format:</span>
          <span>{{ media.format }}</span>
        </div>
      </div>

      <!-- Status -->
      <div class="mb-4">
        <div class="flex items-center gap-2">
          <span :class="getStatusDotClass(media.status)" class="w-2 h-2 rounded-full"></span>
          <span :class="getStatusTextClass(media.status)" class="text-sm font-medium">
            {{ media.status }}
          </span>
        </div>
        <div v-if="media.status === 'Processing'" class="w-full bg-gray-200 rounded-full h-2 mt-2">
          <div 
            class="bg-blue-600 h-2 rounded-full" 
            :style="{ width: media.progress + '%' }"
          ></div>
        </div>
      </div>

      <!-- Upload Date -->
      <div class="mb-4">
        <p class="text-xs text-gray-500">Uploaded: {{ media.uploadDate }}</p>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="preview"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Preview
        </button>
        <button 
          @click="download"
          class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Download
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface MediaFile {
  id: string
  name: string
  type: 'Video' | 'Audio' | 'Image'
  thumbnailUrl: string
  duration: string
  size: string
  resolution: string
  format: string
  status: 'Ready' | 'Processing' | 'Upload Failed'
  progress?: number
  uploadDate: string
}

const props = defineProps<{
  media: MediaFile
}>()

const emit = defineEmits<{
  preview: [media: MediaFile]
  download: [media: MediaFile]
}>()

const getTypeClass = (type: string) => {
  const classes = {
    'Video': 'text-purple-900',
    'Audio': 'text-blue-900',
    'Image': 'text-green-900',
  }
  return classes[type as keyof typeof classes] || 'text-gray-900'
}

const getStatusDotClass = (status: string) => {
  const classes = {
    'Ready': 'bg-green-500',
    'Processing': 'bg-blue-500',
    'Upload Failed': 'bg-red-500',
  }
  return classes[status as keyof typeof classes] || 'bg-gray-500'
}

const getStatusTextClass = (status: string) => {
  const classes = {
    'Ready': 'text-green-600',
    'Processing': 'text-blue-600',
    'Upload Failed': 'text-red-600',
  }
  return classes[status as keyof typeof classes] || 'text-gray-600'
}

const preview = () => {
  emit('preview', props.media)
}

const download = () => {
  emit('download', props.media)
}
</script>
