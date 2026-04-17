<template>
  <div v-if="isOpen" class="ar-modal-overlay" @click.self="close">
    <div class="ar-modal-content">
      <div class="ar-modal-header">
        <h3 class="text-xl font-bold">3D/AR Preview</h3>
        <button @click="close" class="close-button">×</button>
      </div>
      
      <div class="ar-modal-body">
        <div v-if="isLoading" class="loading-container">
          <div class="spinner"></div>
          <p class="mt-4 text-gray-600">Загрузка 3D модели...</p>
        </div>
        
        <div v-else-if="error" class="error-container">
          <p class="text-red-600">{{ error }}</p>
          <img v-if="fallbackImage" :src="fallbackImage" alt="Fallback" class="fallback-image mt-4" />
        </div>
        
        <div v-else class="model-viewer-container">
          <model-viewer
            v-if="modelUrl"
            :src="modelUrl"
            :auto-rotate="true"
            :camera-controls="true"
            :shadow-intensity="0.5"
            :interaction-prompt-threshold="0"
            class="model-viewer"
            @load="onModelLoad"
            @error="onModelError"
          />
          <div v-else class="no-model">
            <p class="text-gray-500">3D модель недоступна для этого товара</p>
            <img v-if="fallbackImage" :src="fallbackImage" alt="Product" class="fallback-image mt-4" />
          </div>
        </div>
        
        <div v-if="arData" class="ar-info mt-4 p-4 bg-gray-50 rounded-lg">
          <h4 class="font-semibold mb-2">AR-информация</h4>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-gray-600">Тип AR:</p>
              <p class="font-medium">{{ arData.ar_type }}</p>
            </div>
            <div>
              <p class="text-gray-600">Поддерживаемые форматы:</p>
              <p class="font-medium">{{ arData.supported_formats?.join(', ') }}</p>
            </div>
          </div>
          
          <div v-if="arData.qr_code_url" class="mt-4">
            <p class="text-gray-600 mb-2">QR-код для мобильного AR:</p>
            <img :src="arData.qr_code_url" alt="QR Code" class="qr-code" />
            <a :href="arData.qr_code_url + '&download=1'" download class="download-link mt-2 inline-block">
              Скачать QR-код
            </a>
          </div>
        </div>
      </div>
      
      <div class="ar-modal-footer">
        <button @click="close" class="close-modal-button">
          Закрыть
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import '@google/model-viewer';

interface Props {
  productId: number;
  isOpen: boolean;
}

interface Emits {
  (e: 'close'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const isLoading = ref(true);
const error = ref<string | null>(null);
const modelUrl = ref<string | null>(null);
const fallbackImage = ref<string | null>(null);
const arData = ref<Record<string, unknown> | null>(null);

async function loadARData(): Promise<void> {
  isLoading.value = true;
  error.value = null;
  modelUrl.value = null;
  arData.value = null;

  try {
    const response = await axios.get(`/api/v1/electronics/products/${props.productId}/ar-model`);
    modelUrl.value = response.data.model_url;
    fallbackImage.value = response.data.fallback_image;
    arData.value = response.data;
  } catch (err: unknown) {
    if (axios.isAxiosError(err)) {
      error.value = err.response?.data?.error || 'Ошибка загрузки AR данных';
    } else {
      error.value = 'Неизвестная ошибка';
    }
  } finally {
    isLoading.value = false;
  }
}

function onModelLoad(): void {
  console.log('3D model loaded successfully');
}

function onModelError(event: Event): void {
  console.error('Model viewer error:', event);
  error.value = 'Ошибка загрузки 3D модели';
}

function close(): void {
  emit('close');
}

watch(() => props.isOpen, (newValue) => {
  if (newValue && props.productId) {
    loadARData();
  }
});

onMounted(() => {
  if (props.isOpen && props.productId) {
    loadARData();
  }
});
</script>

<style scoped>
.ar-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.ar-modal-content {
  background: white;
  border-radius: 12px;
  max-width: 800px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.ar-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.close-button {
  width: 32px;
  height: 32px;
  background: #f3f4f6;
  border: none;
  border-radius: 50%;
  font-size: 24px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.close-button:hover {
  background: #e5e7eb;
}

.ar-modal-body {
  padding: 20px;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 300px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-container {
  text-align: center;
  padding: 40px;
}

.fallback-image {
  max-width: 100%;
  max-height: 300px;
  border-radius: 8px;
}

.model-viewer-container {
  min-height: 400px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.model-viewer {
  width: 100%;
  height: 400px;
  border-radius: 8px;
}

.no-model {
  text-align: center;
  padding: 40px;
}

.ar-info {
  margin-top: 20px;
}

.qr-code {
  width: 150px;
  height: 150px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.download-link {
  color: #3b82f6;
  text-decoration: underline;
  cursor: pointer;
}

.download-link:hover {
  color: #2563eb;
}

.ar-modal-footer {
  padding: 20px;
  border-top: 1px solid #e5e7eb;
  text-align: right;
}

.close-modal-button {
  padding: 10px 24px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.close-modal-button:hover {
  background: #2563eb;
}
</style>
