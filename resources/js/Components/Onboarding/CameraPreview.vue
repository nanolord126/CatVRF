<template>
  <div class="camera-preview">
    <div class="relative">
      <video
        ref="videoRef"
        autoplay
        playsinline
        class="w-full rounded-lg"
        :class="{ 'hidden': !isStreaming }"
      />
      <canvas
        ref="canvasRef"
        class="hidden"
      />
      
      <!-- Placeholder when camera is off -->
      <div
        v-if="!isStreaming"
        class="bg-gray-100 rounded-lg p-8 flex flex-col items-center justify-center min-h-[300px]"
      >
        <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <p class="text-gray-600">{{ placeholderText }}</p>
      </div>

      <!-- Face guide overlay -->
      <div
        v-if="isStreaming && showFaceGuide"
        class="absolute inset-0 flex items-center justify-center pointer-events-none"
      >
        <div class="border-4 border-blue-500 border-opacity-50 rounded-full w-48 h-64 flex items-center justify-center">
          <div class="text-blue-500 text-xs">Расположите лицо в центре</div>
        </div>
      </div>

      <!-- Error message -->
      <div v-if="error" class="absolute bottom-0 left-0 right-0 bg-red-500 text-white p-2 text-sm rounded-b-lg">
        {{ error }}
      </div>
    </div>

    <!-- Controls -->
    <div class="flex justify-center space-x-4 mt-4">
      <button
        @click="toggleCamera"
        :disabled="isLoading"
        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
      >
        {{ isStreaming ? 'Выключить камеру' : 'Включить камеру' }}
      </button>
      
      <button
        v-if="isStreaming"
        @click="capturePhoto"
        :disabled="isLoading"
        class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
      >
        Сделать фото
      </button>

      <button
        v-if="capturedImage"
        @click="retakePhoto"
        class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700"
      >
        Переснять
      </button>
    </div>

    <!-- Captured image preview -->
    <div v-if="capturedImage" class="mt-4">
      <img :src="capturedImage" alt="Captured photo" class="w-full rounded-lg" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

interface Props {
  placeholderText?: string;
  showFaceGuide?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  placeholderText: 'Нажмите "Включить камеру" для начала',
  showFaceGuide: true,
});

const emit = defineEmits<{
  (e: 'photo-captured', data: string): void;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);
const canvasRef = ref<HTMLCanvasElement | null>(null);
const isStreaming = ref(false);
const isLoading = ref(false);
const capturedImage = ref<string | null>(null);
const error = ref<string>('');
let stream: MediaStream | null = null;

const toggleCamera = async () => {
  if (isStreaming.value) {
    stopCamera();
  } else {
    await startCamera();
  }
};

const startCamera = async () => {
  isLoading.value = true;
  error.value = '';

  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: 'user',
        width: { ideal: 1280 },
        height: { ideal: 720 },
      },
    });

    if (videoRef.value) {
      videoRef.value.srcObject = stream;
      isStreaming.value = true;
    }
  } catch (err: any) {
    error.value = err.message || 'Не удалось получить доступ к камере';
  } finally {
    isLoading.value = false;
  }
};

const stopCamera = () => {
  if (stream) {
    stream.getTracks().forEach(track => track.stop());
    stream = null;
  }
  isStreaming.value = false;
};

const capturePhoto = () => {
  if (!videoRef.value || !canvasRef.value) return;

  const video = videoRef.value;
  const canvas = canvasRef.value;

  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;

  const ctx = canvas.getContext('2d');
  if (ctx) {
    ctx.drawImage(video, 0, 0);
    const dataUrl = canvas.toDataURL('image/jpeg', 0.95);
    capturedImage.value = dataUrl;
    emit('photo-captured', dataUrl);
    stopCamera();
  }
};

const retakePhoto = () => {
  capturedImage.value = null;
  startCamera();
};

onUnmounted(() => {
  stopCamera();
});
</script>

<style scoped>
.camera-preview {
  @apply w-full;
}
</style>
