<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { io, Socket } from 'socket.io-client';

interface ImportStatus {
  id: number;
  vin: string;
  status: string;
  progress: number;
  current_stage: string;
  estimated_completion: string;
}

const importId = ref<number | null>(null);
const importStatus = ref<ImportStatus | null>(null);
const isLoading = ref(false);
const socket = ref<Socket | null>(null);

const stages = [
  { key: 'pending_payment', label: 'Ожидание оплаты', icon: '💳' },
  { key: 'customs_processing', label: 'Таможенное оформление', icon: '📋' },
  { key: 'document_verification', label: 'Проверка документов', icon: '📄' },
  { key: 'transportation', label: 'Транспортировка', icon: '🚚' },
  { key: 'customs_clearance', label: 'Таможенное оформление РФ', icon: '🏛️' },
  { key: 'delivery', label: 'Доставка клиенту', icon: '🚗' },
  { key: 'completed', label: 'Завершено', icon: '✅' },
];

const loadImportStatus = async (id: number) => {
  isLoading.value = true;
  try {
    const response = await fetch(`/api/v1/auto/import/${id}/status`);
    const data = await response.json();
    if (data.success) {
      importStatus.value = data.import;
    }
  } catch (error) {
    console.error('Error loading import status:', error);
  } finally {
    isLoading.value = false;
  }
};

const initWebSocket = () => {
  socket.value = io(config('services.websocket.url'), {
    transports: ['websocket'],
  });

  socket.value.on('import_status_update', (data: ImportStatus) => {
    if (importId.value === data.id) {
      importStatus.value = data;
    }
  });
};

onMounted(() => {
  if (importId.value) {
    loadImportStatus(importId.value);
    initWebSocket();
  }
});

onUnmounted(() => {
  if (socket.value) {
    socket.value.disconnect();
  }
});

const getStageIndex = (status: string): number => {
  return stages.findIndex(s => s.key === status);
};

const getProgress = (status: string): number => {
  const index = getStageIndex(status);
  return Math.round((index / (stages.length - 1)) * 100);
};
</script>

<template>
  <div class="car-import-tracker max-w-4xl mx-auto p-6 bg-white rounded-xl shadow-lg">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Отслеживание импорта</h2>

    <div v-if="!importId" class="text-center py-8">
      <input 
        v-model.number="importId"
        type="number" 
        placeholder="Введите ID импорта"
        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
      />
      <button 
        @click="loadImportStatus(importId!)"
        class="ml-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        Отследить
      </button>
    </div>

    <div v-else-if="isLoading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Загрузка статуса...</p>
    </div>

    <div v-else-if="importStatus" class="space-y-6">
      <div class="p-4 bg-gray-50 rounded-lg">
        <div class="flex justify-between items-center">
          <div>
            <p class="text-sm text-gray-600">VIN</p>
            <p class="font-bold text-lg">{{ importStatus.vin }}</p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-600">Статус</p>
            <p class="font-bold text-lg text-blue-600">{{ importStatus.status }}</p>
          </div>
        </div>
      </div>

      <div class="relative">
        <div class="absolute top-1/2 left-0 right-0 h-1 bg-gray-200 -translate-y-1/2"></div>
        <div 
          class="absolute top-1/2 left-0 h-1 bg-blue-600 -translate-y-1/2 transition-all duration-500"
          :style="{ width: getProgress(importStatus.status) + '%' }"
        ></div>

        <div class="relative flex justify-between">
          <div 
            v-for="(stage, index) in stages" 
            :key="stage.key"
            class="flex flex-col items-center"
            :class="{ 'opacity-50': index > getStageIndex(importStatus.status) }"
          >
            <div 
              class="w-10 h-10 rounded-full flex items-center justify-center text-lg mb-2 transition-all duration-300"
              :class="{
                'bg-blue-600 text-white': index <= getStageIndex(importStatus.status),
                'bg-gray-200 text-gray-600': index > getStageIndex(importStatus.status)
              }"
            >
              {{ stage.icon }}
            </div>
            <p class="text-xs text-center font-medium max-w-20">{{ stage.label }}</p>
          </div>
        </div>
      </div>

      <div class="p-4 bg-blue-50 rounded-lg">
        <div class="flex justify-between items-center">
          <span class="text-gray-700">Прогресс</span>
          <span class="font-bold text-blue-600">{{ getProgress(importStatus.status) }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
          <div 
            class="bg-blue-600 h-2 rounded-full transition-all duration-500"
            :style="{ width: getProgress(importStatus.status) + '%' }"
          ></div>
        </div>
      </div>

      <div v-if="importStatus.estimated_completion" class="p-4 bg-yellow-50 text-yellow-800 rounded-lg">
        <p class="font-medium">⏱️ Ориентировочное завершение:</p>
        <p class="text-lg">{{ new Date(importStatus.estimated_completion).toLocaleString('ru-RU') }}</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.car-import-tracker {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>
