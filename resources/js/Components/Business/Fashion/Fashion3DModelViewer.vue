<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';

interface ModelViewerConfig {
  src: string;
  alt: string;
  auto_rotate: boolean;
  camera_controls: boolean;
  shadow_intensity: number;
  exposure: number;
  background_color: string;
  style_profile: {
    color_type: string;
    preferred_palette: string[];
  };
}

const route = useRoute();
const loading = ref<boolean>(true);
const arModelUrl = ref<string>('');
const textureUrl = ref<string>('');
const config = ref<ModelViewerConfig | null>(null);
const error = ref<string>('');
const autoRotate = ref<boolean>(true);
const shadowIntensity = ref<number>(0.5);
const exposure = ref<number>(1.0);

const loadARPreview = async () => {
  const designId = route.params.designId as string;
  const productId = route.params.productId as string;

  if (!designId || !productId) {
    error.value = 'Missing required parameters';
    loading.value = false;
    return;
  }

  try {
    const response = await fetch(`/api/fashion/ai/ar-preview/${designId}/${productId}`);
    const data = await response.json();

    if (data.success) {
      arModelUrl.value = data.data.ar_model_url;
      textureUrl.value = data.data.texture_url;
      config.value = data.data.model_viewer_config;
    }
  } catch (err: any) {
    error.value = 'Failed to load AR preview';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadARPreview();
});

const toggleAutoRotate = () => {
  autoRotate.value = !autoRotate.value;
};

const resetView = () => {
  shadowIntensity.value = 0.5;
  exposure.value = 1.0;
};
</script>

<template>
  <div class="fashion-3d-viewer">
    <div class="viewer-header">
      <h2>3D просмотр товара</h2>
      <p>Интерактивная 3D-модель с настройками</p>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Загрузка 3D модели...</p>
    </div>

    <div v-else-if="error" class="error-message">
      {{ error }}
    </div>

    <div v-else class="viewer-content">
      <div class="model-container">
        <model-viewer
          v-if="arModelUrl"
          :src="arModelUrl"
          :alt="config?.alt || '3D Model'"
          :auto-rotate="autoRotate"
          :camera-controls="true"
          :shadow-intensity="shadowIntensity"
          :exposure="exposure"
          :background-color="config?.background_color || '#f5f5f5'"
          class="model-viewer"
        />
        <div v-else class="no-model">
          <p>3D модель недоступна</p>
          <p class="hint">Модель генерируется в фоновом режиме</p>
        </div>
      </div>

      <div class="controls">
        <div class="control-group">
          <h3>Настройки просмотра</h3>
          
          <div class="control-item">
            <label>
              <input type="checkbox" v-model="autoRotate" @change="toggleAutoRotate" />
              Авто-вращение
            </label>
          </div>

          <div class="control-item">
            <label>
              Интенсивность тени
              <input
                type="range"
                v-model="shadowIntensity"
                min="0"
                max="2"
                step="0.1"
              />
              <span>{{ shadowIntensity.toFixed(1) }}</span>
            </label>
          </div>

          <div class="control-item">
            <label>
              Экспозиция
              <input
                type="range"
                v-model="exposure"
                min="0"
                max="3"
                step="0.1"
              />
              <span>{{ exposure.toFixed(1) }}</span>
            </label>
          </div>

          <button class="reset-btn" @click="resetView">
            Сбросить настройки
          </button>
        </div>

        <div v-if="config?.style_profile" class="style-info">
          <h3>Информация о стиле</h3>
          <div class="info-item">
            <span class="label">Цветотип:</span>
            <span class="value">{{ config.style_profile.color_type }}</span>
          </div>
          <div class="info-item">
            <span class="label">Палитра:</span>
            <div class="palette">
              <div
                v-for="color in config.style_profile.preferred_palette"
                :key="color"
                class="color-swatch"
                :style="{ backgroundColor: color }"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fashion-3d-viewer {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

.viewer-header {
  text-align: center;
  margin-bottom: 2rem;
}

.viewer-header h2 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.loading {
  text-align: center;
  padding: 4rem;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-message {
  padding: 2rem;
  background: #fee2e2;
  color: #991b1b;
  border-radius: 8px;
  text-align: center;
}

.viewer-content {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 2rem;
}

.model-container {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  min-height: 600px;
}

.model-viewer {
  width: 100%;
  height: 600px;
  border-radius: 8px;
}

.no-model {
  height: 600px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #6b7280;
}

.hint {
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.controls {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.control-group {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.control-group h3 {
  margin: 0 0 1rem 0;
  font-size: 1.125rem;
}

.control-item {
  margin-bottom: 1rem;
}

.control-item label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.control-item input[type="range"] {
  flex: 1;
  margin: 0 0.5rem;
}

.control-item span {
  min-width: 30px;
  text-align: right;
  font-weight: 600;
}

.reset-btn {
  width: 100%;
  padding: 0.75rem;
  background: #6b7280;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: background 0.3s;
}

.reset-btn:hover {
  background: #4b5563;
}

.style-info {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.style-info h3 {
  margin: 0 0 1rem 0;
  font-size: 1.125rem;
}

.info-item {
  margin-bottom: 1rem;
}

.label {
  display: block;
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.value {
  font-weight: 600;
  color: #1f2937;
}

.palette {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.color-swatch {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2px solid #e5e7eb;
}

@media (max-width: 1024px) {
  .viewer-content {
    grid-template-columns: 1fr;
  }

  .model-container {
    min-height: 400px;
  }

  .model-viewer {
    height: 400px;
  }
}
</style>
