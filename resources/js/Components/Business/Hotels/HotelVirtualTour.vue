<template>
  <div class="hotel-virtual-tour">
    <div v-if="loading" class="loading-spinner">
      <div class="spinner"></div>
      <p>Загрузка виртуального тура...</p>
    </div>

    <div v-else-if="error" class="error-message">
      <svg class="error-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
      </svg>
      <p>{{ error }}</p>
      <button @click="retryLoad" class="retry-button">Повторить</button>
    </div>

    <div v-else class="tour-container">
      <div class="tour-header">
        <h2>{{ roomName }}</h2>
        <div class="tour-controls">
          <button
            @click="toggleMode"
            :class="['mode-toggle', { active: currentMode === '360' }]"
            title="360° обзор"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <circle cx="12" cy="12" r="9"></circle>
              <path d="M12 3a15 15 0 0 1 0 18"></path>
              <path d="M12 3a15 15 0 0 0 0 18"></path>
            </svg>
            360°
          </button>
          <button
            @click="toggleMode"
            :class="['mode-toggle', { active: currentMode === 'ar' }]"
            title="AR просмотр"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
              <line x1="8" y1="21" x2="16" y2="21"></line>
              <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
            AR
          </button>
          <button
            @click="toggleFullscreen"
            class="fullscreen-toggle"
            title="Полноэкранный режим"
          >
            <svg v-if="!isFullscreen" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
            </svg>
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3"></path>
            </svg>
          </button>
        </div>
      </div>

      <div ref="tourContainer" class="tour-viewport" :class="{ fullscreen: isFullscreen }">
        <canvas ref="panoramaCanvas" v-show="currentMode === '360'" class="panorama-canvas"></canvas>
        
        <div v-show="currentMode === 'ar'" class="ar-viewport">
          <video ref="arVideo" class="ar-video" autoplay playsinline muted></video>
          <canvas ref="arCanvas" class="ar-overlay"></canvas>
          
          <div v-if="!arSupported" class="ar-unsupported">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
              <circle cx="12" cy="13" r="4"></circle>
            </svg>
            <p>AR не поддерживается вашим устройством</p>
            <p class="ar-hint">Используйте камеру для дополненной реальности</p>
          </div>
        </div>

        <div class="hotspots-overlay">
          <div
            v-for="hotspot in visibleHotspots"
            :key="hotspot.id"
            class="hotspot"
            :style="{ left: hotspot.x + '%', top: hotspot.y + '%' }"
            @click="showHotspotInfo(hotspot)"
            @mouseenter="hoverHotspot = hotspot.id"
            @mouseleave="hoverHotspot = null"
          >
            <div class="hotspot-pulse" :class="{ active: hoverHotspot === hotspot.id }"></div>
            <div class="hotspot-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="12" cy="12" r="10"></circle>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
            </div>
            <div class="hotspot-label">{{ hotspot.label }}</div>
          </div>
        </div>

        <div class="tour-navigation">
          <button @click="prevScene" :disabled="currentSceneIndex === 0" class="nav-button prev">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
          </button>
          <div class="scene-indicator">
            {{ currentSceneIndex + 1 }} / {{ scenes.length }}
          </div>
          <button @click="nextScene" :disabled="currentSceneIndex === scenes.length - 1" class="nav-button next">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </button>
        </div>

        <div class="room-info" v-if="currentRoomInfo">
          <h3>{{ currentRoomInfo.name }}</h3>
          <div class="room-specs">
            <span class="spec">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
              {{ currentRoomInfo.capacity }} гостей
            </span>
            <span class="spec">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="3" y1="9" x2="21" y2="9"></line>
                <line x1="9" y1="21" x2="9" y2="9"></line>
              </svg>
              {{ currentRoomInfo.area }} м²
            </span>
            <span class="spec">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                <path d="M2 17l10 5 10-5"></path>
                <path d="M2 12l10 5 10-5"></path>
              </svg>
              {{ currentRoomInfo.floor }} этаж
            </span>
          </div>
          <div class="room-amenities">
            <span v-for="amenity in currentRoomInfo.amenities" :key="amenity" class="amenity-tag">
              {{ amenity }}
            </span>
          </div>
          <div class="room-price">
            <span class="price-label">от</span>
            <span class="price-value">{{ formatPrice(currentRoomInfo.price) }}</span>
            <span class="price-period">/ ночь</span>
          </div>
          <button @click="bookRoom" class="book-button">
            Забронировать
          </button>
        </div>
      </div>

      <div v-if="selectedHotspot" class="hotspot-modal" @click.self="closeHotspotInfo">
        <div class="hotspot-modal-content">
          <button @click="closeHotspotInfo" class="close-button">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
          <h3>{{ selectedHotspot.label }}</h3>
          <p>{{ selectedHotspot.description }}</p>
          <img v-if="selectedHotspot.image" :src="selectedHotspot.image" :alt="selectedHotspot.label" class="hotspot-image">
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';

interface Scene {
  id: string;
  name: string;
  panoramaUrl: string;
  hotspots: Hotspot[];
}

interface Hotspot {
  id: string;
  label: string;
  description: string;
  x: number;
  y: number;
  image?: string;
}

interface RoomInfo {
  id: number;
  name: string;
  capacity: number;
  area: number;
  floor: number;
  price: number;
  amenities: string[];
}

const props = defineProps<{
  roomId: number;
  hotelId: number;
}>();

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const error = ref<string | null>(null);
const currentMode = ref<'360' | 'ar'>('360');
const isFullscreen = ref(false);
const currentSceneIndex = ref(0);
const hoverHotspot = ref<string | null>(null);
const selectedHotspot = ref<Hotspot | null>(null);

const tourContainer = ref<HTMLDivElement | null>(null);
const panoramaCanvas = ref<HTMLCanvasElement | null>(null);
const arVideo = ref<HTMLVideoElement | null>(null);
const arCanvas = ref<HTMLCanvasElement | null>(null);

const scenes = ref<Scene[]>([]);
const roomInfo = ref<RoomInfo | null>(null);
const arSupported = ref(false);

let panoramaViewer: any = null;
let arSession: any = null;

const roomName = computed(() => roomInfo.value?.name || `Номер #${props.roomId}`);

const currentRoomInfo = computed(() => roomInfo.value);

const visibleHotspots = computed(() => {
  if (currentSceneIndex.value >= scenes.value.length) return [];
  return scenes.value[currentSceneIndex.value]?.hotspots || [];
});

const loadTourData = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await fetch(`/api/v1/hotels/${props.hotelId}/rooms/${props.roomId}/virtual-tour`);
    if (!response.ok) {
      throw new Error('Не удалось загрузить данные виртуального тура');
    }

    const data = await response.json();
    scenes.value = data.scenes || [];
    roomInfo.value = data.roomInfo || null;

    if (scenes.value.length === 0) {
      throw new Error('Нет доступных сцен для виртуального тура');
    }

    await initializePanorama();
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Произошла ошибка';
  } finally {
    loading.value = false;
  }
};

const initializePanorama = async () => {
  if (!panoramaCanvas.value || scenes.value.length === 0) return;

  const canvas = panoramaCanvas.value;
  const ctx = canvas.getContext('2d');
  if (!ctx) return;

  canvas.width = tourContainer.value?.clientWidth || 800;
  canvas.height = tourContainer.value?.clientHeight || 600;

  const currentScene = scenes.value[currentSceneIndex.value];
  const image = new Image();
  image.crossOrigin = 'anonymous';
  
  image.onload = () => {
    drawPanorama(ctx, image, canvas.width, canvas.height);
  };
  
  image.onerror = () => {
    error.value = 'Не удалось загрузить панораму';
  };
  
  image.src = currentScene.panoramaUrl;
};

const drawPanorama = (ctx: CanvasRenderingContext2D, image: HTMLImageElement, width: number, height: number) => {
  ctx.clearRect(0, 0, width, height);
  
  const aspectRatio = image.width / image.height;
  const canvasAspectRatio = width / height;
  
  let drawWidth, drawHeight, drawX, drawY;
  
  if (canvasAspectRatio > aspectRatio) {
    drawWidth = width;
    drawHeight = width / aspectRatio;
    drawX = 0;
    drawY = (height - drawHeight) / 2;
  } else {
    drawHeight = height;
    drawWidth = height * aspectRatio;
    drawX = (width - drawWidth) / 2;
    drawY = 0;
  }
  
  ctx.drawImage(image, drawX, drawY, drawWidth, drawHeight);
};

const initializeAR = async () => {
  if (!('xr' in navigator)) {
    arSupported.value = false;
    return;
  }

  try {
    const isARSupported = await (navigator as any).xr.isSessionSupported('immersive-ar');
    arSupported.value = isARSupported;

    if (isARSupported && arVideo.value) {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment' }
      });
      arVideo.value.srcObject = stream;
    }
  } catch (err) {
    arSupported.value = false;
    console.error('AR initialization failed:', err);
  }
};

const toggleMode = () => {
  currentMode.value = currentMode.value === '360' ? 'ar' : '360';
  
  if (currentMode.value === 'ar') {
    initializeAR();
  }
};

const toggleFullscreen = () => {
  if (!tourContainer.value) return;

  if (!document.fullscreenElement) {
    tourContainer.value.requestFullscreen().catch((err) => {
      console.error('Fullscreen error:', err);
    });
    isFullscreen.value = true;
  } else {
    document.exitFullscreen();
    isFullscreen.value = false;
  }
};

const prevScene = () => {
  if (currentSceneIndex.value > 0) {
    currentSceneIndex.value--;
    initializePanorama();
  }
};

const nextScene = () => {
  if (currentSceneIndex.value < scenes.value.length - 1) {
    currentSceneIndex.value++;
    initializePanorama();
  }
};

const showHotspotInfo = (hotspot: Hotspot) => {
  selectedHotspot.value = hotspot;
};

const closeHotspotInfo = () => {
  selectedHotspot.value = null;
};

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(price);
};

const bookRoom = () => {
  router.push({
    name: 'hotel-booking',
    params: {
      hotelId: props.hotelId,
      roomId: props.roomId,
    },
  });
};

const retryLoad = () => {
  loadTourData();
};

const handleResize = () => {
  if (panoramaCanvas.value && tourContainer.value) {
    panoramaCanvas.value.width = tourContainer.value.clientWidth;
    panoramaCanvas.value.height = tourContainer.value.clientHeight;
    initializePanorama();
  }
};

onMounted(() => {
  loadTourData();
  window.addEventListener('resize', handleResize);
  document.addEventListener('fullscreenchange', () => {
    isFullscreen.value = !!document.fullscreenElement;
  });
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
  document.removeEventListener('fullscreenchange', () => {
    isFullscreen.value = !!document.fullscreenElement;
  });
  
  if (arVideo.value?.srcObject) {
    const tracks = (arVideo.value.srcObject as MediaStream).getTracks();
    tracks.forEach(track => track.stop());
  }
});
</script>

<style scoped>
.hotel-virtual-tour {
  width: 100%;
  height: 100%;
  min-height: 600px;
  background: #000;
  border-radius: 12px;
  overflow: hidden;
  position: relative;
}

.loading-spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #fff;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 3px solid rgba(255, 255, 255, 0.3);
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #fff;
  padding: 20px;
  text-align: center;
}

.error-icon {
  width: 48px;
  height: 48px;
  margin-bottom: 16px;
  color: #ef4444;
}

.retry-button {
  margin-top: 16px;
  padding: 10px 20px;
  background: #3b82f6;
  color: #fff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.2s;
}

.retry-button:hover {
  background: #2563eb;
}

.tour-container {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.tour-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: rgba(0, 0, 0, 0.8);
  color: #fff;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.tour-header h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.tour-controls {
  display: flex;
  gap: 8px;
}

.mode-toggle,
.fullscreen-toggle {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.1);
  color: #fff;
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s;
}

.mode-toggle:hover,
.fullscreen-toggle:hover {
  background: rgba(255, 255, 255, 0.2);
}

.mode-toggle.active {
  background: #3b82f6;
  border-color: #3b82f6;
}

.mode-toggle svg,
.fullscreen-toggle svg {
  width: 18px;
  height: 18px;
}

.tour-viewport {
  flex: 1;
  position: relative;
  overflow: hidden;
}

.tour-viewport.fullscreen {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  z-index: 9999;
}

.panorama-canvas {
  width: 100%;
  height: 100%;
  cursor: grab;
}

.panorama-canvas:active {
  cursor: grabbing;
}

.ar-viewport {
  width: 100%;
  height: 100%;
  position: relative;
  background: #000;
}

.ar-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.ar-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

.ar-unsupported {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #fff;
  padding: 20px;
  text-align: center;
}

.ar-unsupported svg {
  width: 64px;
  height: 64px;
  margin-bottom: 16px;
  opacity: 0.5;
}

.ar-hint {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  margin-top: 8px;
}

.hotspots-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

.hotspot {
  position: absolute;
  transform: translate(-50%, -50%);
  pointer-events: auto;
  cursor: pointer;
}

.hotspot-pulse {
  position: absolute;
  width: 40px;
  height: 40px;
  background: rgba(59, 130, 246, 0.3);
  border-radius: 50%;
  animation: pulse 2s infinite;
}

.hotspot-pulse.active {
  background: rgba(59, 130, 246, 0.6);
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.5);
    opacity: 0.5;
  }
}

.hotspot-icon {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 24px;
  height: 24px;
  background: #3b82f6;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
}

.hotspot-icon svg {
  width: 14px;
  height: 14px;
}

.hotspot-label {
  position: absolute;
  top: 30px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(0, 0, 0, 0.8);
  color: #fff;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  white-space: nowrap;
  opacity: 0;
  transition: opacity 0.2s;
}

.hotspot:hover .hotspot-label {
  opacity: 1;
}

.tour-navigation {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  gap: 16px;
  background: rgba(0, 0, 0, 0.8);
  padding: 12px 20px;
  border-radius: 30px;
  color: #fff;
}

.nav-button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  color: #fff;
  cursor: pointer;
  transition: all 0.2s;
}

.nav-button:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
}

.nav-button:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.nav-button svg {
  width: 18px;
  height: 18px;
}

.scene-indicator {
  font-size: 14px;
  font-weight: 500;
}

.room-info {
  position: absolute;
  top: 20px;
  right: 20px;
  background: rgba(0, 0, 0, 0.9);
  color: #fff;
  padding: 20px;
  border-radius: 12px;
  max-width: 300px;
  backdrop-filter: blur(10px);
}

.room-info h3 {
  margin: 0 0 12px 0;
  font-size: 18px;
  font-weight: 600;
}

.room-specs {
  display: flex;
  gap: 16px;
  margin-bottom: 12px;
}

.spec {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.8);
}

.spec svg {
  width: 16px;
  height: 16px;
}

.room-amenities {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 16px;
}

.amenity-tag {
  background: rgba(59, 130, 246, 0.2);
  color: #60a5fa;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
}

.room-price {
  display: flex;
  align-items: baseline;
  gap: 4px;
  margin-bottom: 16px;
}

.price-label {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.7);
}

.price-value {
  font-size: 24px;
  font-weight: 700;
  color: #3b82f6;
}

.price-period {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.7);
}

.book-button {
  width: 100%;
  padding: 12px;
  background: #3b82f6;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.book-button:hover {
  background: #2563eb;
}

.hotspot-modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  padding: 20px;
}

.hotspot-modal-content {
  background: #1f2937;
  color: #fff;
  padding: 24px;
  border-radius: 12px;
  max-width: 500px;
  width: 100%;
  position: relative;
}

.close-button {
  position: absolute;
  top: 16px;
  right: 16px;
  background: none;
  border: none;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  padding: 4px;
  transition: color 0.2s;
}

.close-button:hover {
  color: #fff;
}

.close-button svg {
  width: 20px;
  height: 20px;
}

.hotspot-modal-content h3 {
  margin: 0 0 12px 0;
  font-size: 20px;
  font-weight: 600;
}

.hotspot-modal-content p {
  margin: 0 0 16px 0;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.8);
}

.hotspot-image {
  width: 100%;
  border-radius: 8px;
  margin-top: 16px;
}
</style>
