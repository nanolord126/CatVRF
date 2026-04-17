<script setup lang="ts">
import { ref } from 'vue';
import { uploadFile } from '@/services/api';
import AIDiagnosticsService from '@/services/AIDiagnosticsService';

const vin = ref('');
const photo = ref<File | null>(null);
const photoPreview = ref<string | null>(null);
const latitude = ref<number | null>(null);
const longitude = ref<number | null>(null);
const isAnalyzing = ref(false);
const diagnosisResult = ref<any>(null);
const errorMessage = ref('');

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  
  if (file) {
    photo.value = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      photoPreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
};

const getLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        latitude.value = position.coords.latitude;
        longitude.value = position.coords.longitude;
      },
      (error) => {
        console.error('Error getting location:', error);
      }
    );
  }
};

const submitDiagnostics = async () => {
  if (!vin.value || !photo.value) {
    errorMessage.value = 'VIN и фото обязательны';
    return;
  }

  isAnalyzing.value = true;
  errorMessage.value = '';

  try {
    const formData = new FormData();
    formData.append('vin', vin.value);
    formData.append('photo', photo.value);
    if (latitude.value) formData.append('latitude', latitude.value.toString());
    if (longitude.value) formData.append('longitude', longitude.value.toString());

    const result = await AIDiagnosticsService.analyzePhotoAndVIN(formData);
    diagnosisResult.value = result;
  } catch (error: any) {
    errorMessage.value = error.message || 'Ошибка при анализе';
  } finally {
    isAnalyzing.value = false;
  }
};

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(price);
};
</script>

<template>
  <div class="ai-diagnostics-form max-w-4xl mx-auto p-6 bg-white rounded-xl shadow-lg">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">AI Диагностика автомобиля</h2>
    
    <div v-if="!diagnosisResult" class="space-y-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">VIN код</label>
        <input 
          v-model="vin"
          type="text" 
          maxlength="17"
          placeholder="X7X12345678901234"
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase"
          @input="vin = vin.toUpperCase()"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Фото автомобиля</label>
        <div 
          class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors cursor-pointer"
          @click="$refs.fileInput.click()"
        >
          <input 
            ref="fileInput"
            type="file" 
            accept="image/*"
            class="hidden"
            @change="handleFileChange"
          />
          
          <div v-if="!photoPreview">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="mt-2 text-sm text-gray-600">Нажмите для загрузки фото</p>
          </div>
          
          <img v-else :src="photoPreview" alt="Preview" class="max-h-64 mx-auto rounded-lg" />
        </div>
      </div>

      <div class="flex gap-4">
        <button 
          @click="getLocation"
          class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
        >
          📍 Определить геолокацию
        </button>
      </div>

      <div v-if="errorMessage" class="p-4 bg-red-50 text-red-700 rounded-lg">
        {{ errorMessage }}
      </div>

      <button 
        @click="submitDiagnostics"
        :disabled="isAnalyzing || !vin || !photo"
        class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
      >
        {{ isAnalyzing ? 'Анализирую...' : 'Запустить AI диагностику' }}
      </button>
    </div>

    <div v-else class="space-y-6">
      <div class="p-4 bg-green-50 text-green-700 rounded-lg">
        ✅ Диагностика завершена успешно
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="p-4 bg-gray-50 rounded-lg">
          <h3 class="font-semibold text-gray-900 mb-3">Информация об автомобиле</h3>
          <div class="space-y-2 text-sm">
            <p><span class="text-gray-600">VIN:</span> {{ diagnosisResult.vehicle.vin }}</p>
            <p><span class="text-gray-600">Марка:</span> {{ diagnosisResult.vehicle.make }}</p>
            <p><span class="text-gray-600">Модель:</span> {{ diagnosisResult.vehicle.model }}</p>
            <p><span class="text-gray-600">Год:</span> {{ diagnosisResult.vehicle.year }}</p>
            <p><span class="text-gray-600">Двигатель:</span> {{ diagnosisResult.vehicle.engine }}</p>
          </div>
        </div>

        <div class="p-4 bg-gray-50 rounded-lg">
          <h3 class="font-semibold text-gray-900 mb-3">Обнаруженные повреждения</h3>
          <div class="space-y-2">
            <div v-for="(damage, index) in diagnosisResult.damage_detection.damages" :key="index" 
                 class="p-2 rounded"
                 :class="{
                   'bg-red-100 text-red-800': damage.severity === 'high',
                   'bg-yellow-100 text-yellow-800': damage.severity === 'medium',
                   'bg-green-100 text-green-800': damage.severity === 'low'
                 }">
              <p class="font-medium">{{ damage.location }} - {{ damage.type }}</p>
              <p class="text-sm">{{ damage.description }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="p-4 bg-gray-50 rounded-lg">
        <h3 class="font-semibold text-gray-900 mb-3">Список работ</h3>
        <div class="space-y-2">
          <div v-for="(work, index) in diagnosisResult.work_list" :key="index" 
               class="flex justify-between items-center p-2 bg-white rounded">
            <div>
              <p class="font-medium">{{ work.type }}</p>
              <p class="text-sm text-gray-600">{{ work.description }}</p>
              <p class="text-xs text-gray-500">{{ work.estimated_hours }} ч • {{ work.priority === 'urgent' ? 'Срочно' : 'Обычный' }}</p>
            </div>
            <p class="font-semibold">{{ formatPrice(work.price) }}</p>
          </div>
        </div>
      </div>

      <div class="p-4 bg-blue-50 rounded-lg">
        <h3 class="font-semibold text-gray-900 mb-3">Итоговая оценка</h3>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Работы:</span>
            <span>{{ formatPrice(diagnosisResult.price_estimate.labor_total) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Запчасти:</span>
            <span>{{ formatPrice(diagnosisResult.price_estimate.parts_total) }}</span>
          </div>
          <div class="flex justify-between font-bold text-lg border-t pt-2">
            <span>Итого:</span>
            <span>{{ formatPrice(diagnosisResult.price_estimate.total) }}</span>
          </div>
        </div>
      </div>

      <div class="flex gap-4">
        <button 
          @click="diagnosisResult = null"
          class="flex-1 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-300 transition-colors"
        >
          Новый анализ
        </button>
        <button 
          class="flex-1 px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
        >
          Записаться на ремонт
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ai-diagnostics-form {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}
</style>
