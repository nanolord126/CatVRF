<template>
  <div class="relative w-full h-screen bg-black">
    <model-viewer
      v-if="modelUrl"
      :src="modelUrl"
      :alt="title"
      auto-rotate
      camera-controls
      touch-action="pan-y"
      class="w-full h-full"
      @load="onModelLoad"
      @error="onModelError"
    />

    <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50">
      <div class="text-white text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto mb-4"></div>
        <p>Loading 3D model...</p>
      </div>
    </div>

    <div v-if="error" class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50">
      <div class="text-white text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <p>{{ error }}</p>
      </div>
    </div>

    <div class="absolute top-4 left-4 right-4 flex justify-between items-start pointer-events-none">
      <div class="bg-black bg-opacity-50 text-white p-3 rounded-lg pointer-events-auto">
        <h2 class="text-lg font-semibold">{{ title }}</h2>
        <p class="text-sm text-gray-300">{{ description }}</p>
      </div>

      <button
        @click="handleClose"
        class="bg-black bg-opacity-50 text-white p-2 rounded-lg hover:bg-opacity-70 pointer-events-auto"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div class="absolute bottom-4 left-4 right-4 flex justify-center gap-4 pointer-events-none">
      <button
        @click="toggleAR"
        class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 pointer-events-auto flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
        AR Mode
      </button>

      <button
        @click="toggleFullscreen"
        class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 pointer-events-auto flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
        </svg>
        Fullscreen
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';

const props = defineProps<{
  modelUrl: string;
  title: string;
  description: string;
}>();

const emit = defineEmits<{
  close: [];
  arToggled: [];
}>();

const loading = ref(true);
const error = ref('');

const onModelLoad = () => {
  loading.value = false;
};

const onModelError = (e: any) => {
  loading.value = false;
  error.value = 'Failed to load 3D model';
  console.error('Model load error:', e);
};

const handleClose = () => {
  emit('close');
};

const toggleAR = () => {
  emit('arToggled');
};

const toggleFullscreen = () => {
  if (!document.fullscreenElement) {
    document.documentElement.requestFullscreen();
  } else {
    document.exitFullscreen();
  }
};

onMounted(() => {
  const script = document.createElement('script');
  script.type = 'module';
  script.src = 'https://ajax.googleapis.com/ajax/libs/model-viewer/3.3.0/model-viewer.min.js';
  document.head.appendChild(script);
});
</script>

<style scoped>
model-viewer {
  --poster-color: transparent;
}
</style>
