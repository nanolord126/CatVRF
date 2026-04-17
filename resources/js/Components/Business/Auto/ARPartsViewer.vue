<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

interface Part {
  id: number;
  name: string;
  model_url: string;
  price: number;
  is_oem: boolean;
  warranty_months: number;
}

const props = defineProps<{
  part: Part;
  vehicleVin: string;
}>();

const emit = defineEmits<{
  addToCart: [part: Part];
  close: [];
}>();

const viewerLoaded = ref(false);
const loadingError = ref(false);
const arSupported = ref(false);
const modelViewerRef = ref<HTMLElement | null>(null);

const checkARSupport = async () => {
  if ('xr' in navigator) {
    const isARSupported = await (navigator as any).xr.isSessionSupported('immersive-ar');
    arSupported.value = isARSupported;
  }
};

const addToCart = () => {
  emit('addToCart', props.part);
};

const closeViewer = () => {
  emit('close');
};

const handleModelError = () => {
  loadingError.value = true;
};

const handleModelLoad = () => {
  viewerLoaded.value = true;
};

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(price);
};

onMounted(() => {
  checkARSupport();
});
</script>

<template>
  <div class="ar-parts-viewer fixed inset-0 z-50 bg-black flex flex-col">
    <div class="absolute top-0 left-0 right-0 z-10 p-4 bg-gradient-to-b from-black/80 to-transparent">
      <div class="flex justify-between items-start">
        <button 
          @click="closeViewer"
          class="p-2 bg-white/20 backdrop-blur-sm rounded-full hover:bg-white/30 transition-colors"
        >
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
        
        <div class="text-white text-right">
          <h3 class="font-bold text-lg">{{ part.name }}</h3>
          <p class="text-sm opacity-80">VIN: {{ vehicleVin }}</p>
        </div>
      </div>
    </div>

    <div class="flex-1 relative">
      <model-viewer
        ref="modelViewerRef"
        :src="part.model_url"
        :ar="arSupported"
        :ar-modes="'scene-viewer webxr quick-look'"
        auto-rotate
        camera-controls
        touch-action="pan-y"
        :interaction-prompt="'when-focused'"
        :interaction-prompt-style="'wiggle'"
        :camera-orbit="'45deg 55deg 2.5m'"
        :min-camera-orbit="'auto auto 2m'"
        :max-camera-orbit="'auto auto 5m'"
        loading="eager"
        reveal="auto"
        @error="handleModelError"
        @load="handleModelLoad"
        class="w-full h-full"
      >
        <div slot="poster" class="flex items-center justify-center h-full">
          <div class="text-center text-white">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto mb-4"></div>
            <p>Загрузка модели...</p>
          </div>
        </div>
      </model-viewer>

      <div v-if="loadingError" class="absolute inset-0 flex items-center justify-center bg-black/80">
        <div class="text-center text-white p-6">
          <svg class="w-16 h-16 mx-auto mb-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <h3 class="text-xl font-bold mb-2">Ошибка загрузки модели</h3>
          <p class="text-sm opacity-80">Пожалуйста, попробуйте позже</p>
        </div>
      </div>

      <div v-if="!arSupported && viewerLoaded" class="absolute bottom-24 left-4 right-4 bg-yellow-500/90 backdrop-blur-sm rounded-lg p-3 text-sm text-yellow-900">
        <p>⚠️ AR не поддерживается вашим устройством. Используйте режим просмотра.</p>
      </div>
    </div>

    <div class="absolute bottom-0 left-0 right-0 z-10 p-4 bg-gradient-to-t from-black/80 via-black/50 to-transparent">
      <div class="bg-white/10 backdrop-blur-md rounded-xl p-4 border border-white/20">
        <div class="flex justify-between items-center mb-3">
          <div>
            <p class="text-white font-semibold">{{ part.name }}</p>
            <div class="flex gap-2 mt-1">
              <span v-if="part.is_oem" class="px-2 py-0.5 bg-blue-500/80 text-white text-xs rounded-full">
                OEM
              </span>
              <span class="px-2 py-0.5 bg-green-500/80 text-white text-xs rounded-full">
                Гарантия {{ part.warranty_months }} мес
              </span>
            </div>
          </div>
          <p class="text-white font-bold text-xl">{{ formatPrice(part.price) }}</p>
        </div>

        <div class="flex gap-3">
          <button
            v-if="arSupported"
            class="flex-1 px-4 py-3 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 transition-colors flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            AR Режим
          </button>
          <button
            @click="addToCart"
            class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
          >
            В корзину
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import url('@google/model-viewer');

ar-parts-viewer {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

model-viewer {
  width: 100%;
  height: 100%;
  --poster-color: transparent;
}
</style>
