<template>
  <div class="gadget-vision-analyzer">
    <div class="upload-section">
      <h2 class="text-2xl font-bold mb-4">AI-подбор гаджета по фото</h2>
      <p class="text-gray-600 mb-6">Загрузите фото вашего устройства или комнаты для персонализированных рекомендаций</p>
      
      <div
        class="upload-zone"
        :class="{ 'drag-over': isDragOver }"
        @dragover.prevent="isDragOver = true"
        @dragleave.prevent="isDragOver = false"
        @drop.prevent="handleDrop"
        @click="triggerFileInput"
      >
        <input
          ref="fileInput"
          type="file"
          accept="image/*"
          class="hidden"
          @change="handleFileSelect"
        />
        
        <div v-if="!previewImage" class="upload-placeholder">
          <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <p class="text-gray-500">Перетащите фото сюда или кликните для выбора</p>
          <p class="text-gray-400 text-sm mt-2">Максимум 10MB</p>
        </div>
        
        <div v-else class="preview-container">
          <img :src="previewImage" alt="Preview" class="preview-image" />
          <button @click.stop="removeImage" class="remove-button">×</button>
        </div>
      </div>
    </div>

    <div class="options-section mt-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Бюджет (₽)</label>
          <input
            v-model.number="budget"
            type="number"
            min="0"
            step="1000"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Максимальный бюджет"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Тип анализа</label>
          <select v-model="analysisType" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            <option value="gadget_recommendation">Подбор гаджета</option>
            <option value="room_analysis">Анализ помещения</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Предпочитаемые бренды</label>
          <input
            v-model="brandsInput"
            type="text"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Apple, Samsung, Xiaomi (через запятую)"
          />
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Сценарии использования</label>
          <input
            v-model="useCasesInput"
            type="text"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Гейминг, Работа, Фото (через запятую)"
          />
        </div>
      </div>
      
      <button
        @click="analyzeImage"
        :disabled="!previewImage || isLoading"
        class="mt-6 w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
      >
        {{ isLoading ? 'Анализируем...' : 'Получить рекомендации' }}
      </button>
    </div>

    <div v-if="error" class="error-message mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
      <p class="text-red-600">{{ error }}</p>
    </div>

    <div v-if="result" class="results-section mt-8">
      <div class="vision-analysis mb-6 p-4 bg-gray-50 rounded-lg">
        <h3 class="text-lg font-semibold mb-2">AI-анализ изображения</h3>
        <div class="analysis-content">
          <p v-if="result.vision_analysis.detected_device" class="mb-2">
            <strong>Обнаружено:</strong> {{ result.vision_analysis.detected_device }}
          </p>
          <p v-if="result.vision_analysis.estimated_price_range" class="mb-2">
            <strong>Диапазон цен:</strong> {{ result.vision_analysis.estimated_price_range }}
          </p>
          <div v-if="result.vision_analysis.features?.length" class="mb-2">
            <strong>Ключевые особенности:</strong>
            <ul class="list-disc list-inside ml-2">
              <li v-for="feature in result.vision_analysis.features" :key="feature">{{ feature }}</li>
            </ul>
          </div>
        </div>
      </div>

      <div v-if="result.pricing_info" class="pricing-info mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
        <h3 class="text-lg font-semibold mb-2">Динамическое ценообразование</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p class="text-sm text-gray-600">Базовая сумма</p>
            <p class="text-xl font-bold">{{ formatPrice(result.pricing_info.base_total_kopecks) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Скидка</p>
            <p class="text-xl font-bold text-green-600">{{ result.pricing_info.discount_percentage }}%</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Итоговая сумма</p>
            <p class="text-xl font-bold text-blue-600">{{ formatPrice(result.pricing_info.final_total_kopecks) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-600">Экономия</p>
            <p class="text-xl font-bold text-green-700">{{ formatPrice(result.pricing_info.savings_kopecks) }}</p>
          </div>
        </div>
        <div v-if="result.pricing_info.dynamic_factors?.length" class="mt-3">
          <p class="text-sm text-gray-600">Факторы скидки:</p>
          <div class="flex flex-wrap gap-2 mt-1">
            <span v-for="factor in result.pricing_info.dynamic_factors" :key="factor" class="px-2 py-1 bg-white rounded text-sm">
              {{ formatFactor(factor) }}
            </span>
          </div>
        </div>
      </div>

      <div v-if="result.flash_sale_offer" class="flash-sale mb-6 p-4 bg-orange-50 border border-orange-200 rounded-lg">
        <h3 class="text-lg font-semibold mb-2">🔥 Flash Sale!</h3>
        <p class="text-orange-700">Специальное предложение на выбранные товары!</p>
      </div>

      <h3 class="text-xl font-bold mb-4">Рекомендованные гаджеты</h3>
      <div class="products-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="product in result.recommended_products"
          :key="product.id"
          class="product-card border rounded-lg overflow-hidden hover:shadow-lg transition"
        >
          <div class="product-image">
            <img v-if="product.images?.[0]" :src="product.images[0]" :alt="product.name" class="w-full h-48 object-cover" />
            <div v-else class="w-full h-48 bg-gray-200 flex items-center justify-center">
              <span class="text-gray-400">Нет изображения</span>
            </div>
          </div>
          
          <div class="product-content p-4">
            <div class="flex justify-between items-start mb-2">
              <h4 class="font-semibold text-lg">{{ product.name }}</h4>
              <span class="text-sm bg-gray-100 px-2 py-1 rounded">{{ product.brand }}</span>
            </div>
            
            <div class="flex items-center mb-2">
              <div class="flex text-yellow-400">
                <span v-for="n in 5" :key="n">{{ n <= Math.round(product.rating) ? '★' : '☆' }}</span>
              </div>
              <span class="text-sm text-gray-500 ml-2">({{ product.reviews_count }})</span>
            </div>
            
            <div class="price-section mb-3">
              <p class="text-2xl font-bold text-blue-600">{{ formatPrice(product.price_kopecks) }}</p>
              <p v-if="product.original_price_kopecks > product.price_kopecks" class="text-sm text-gray-400 line-through">
                {{ formatPrice(product.original_price_kopecks) }}
              </p>
            </div>
            
            <div class="scores mb-3">
              <div class="flex items-center text-sm mb-1">
                <span class="w-24 text-gray-600">Совместимость:</span>
                <div class="flex-1 bg-gray-200 rounded-full h-2">
                  <div class="bg-green-500 h-2 rounded-full" :style="{ width: (product.compatibility_score * 100) + '%' }"></div>
                </div>
                <span class="ml-2 text-green-600">{{ Math.round(product.compatibility_score * 100) }}%</span>
              </div>
              <div class="flex items-center text-sm">
                <span class="w-24 text-gray-600">Персонализация:</span>
                <div class="flex-1 bg-gray-200 rounded-full h-2">
                  <div class="bg-blue-500 h-2 rounded-full" :style="{ width: (product.personalization_score * 100) + '%' }"></div>
                </div>
                <span class="ml-2 text-blue-600">{{ Math.round(product.personalization_score * 100) }}%</span>
              </div>
            </div>
            
            <div class="actions flex gap-2">
              <button
                @click="openARPreview(product.id)"
                class="flex-1 bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition text-sm"
              >
                3D/AR Preview
              </button>
              <button
                @click="addToCart(product)"
                class="flex-1 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition text-sm"
              >
                В корзину
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="result.video_call_available" class="video-call-section mt-8 p-6 bg-blue-50 rounded-lg">
        <h3 class="text-xl font-bold mb-4">📹 Консультация с экспертом</h3>
        <p class="text-gray-600 mb-4">Получите профессиональную консультацию перед покупкой дорогого гаджета</p>
        <button
          @click="initiateVideoCall"
          class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition"
        >
          Начать видеозвонок
        </button>
      </div>
    </div>

    <ARPreviewModal
      v-if="showARModal"
      :product-id="selectedProductId"
      @close="showARModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';

interface Product {
  id: number;
  sku: string;
  name: string;
  brand: string;
  category: string;
  price_kopecks: number;
  original_price_kopecks: number;
  specs: Record<string, unknown>;
  images: string[];
  rating: number;
  reviews_count: number;
  stock_quantity: number;
  compatibility_score: number;
  personalization_score: number;
}

interface AnalysisResult {
  success: boolean;
  correlation_id: string;
  vision_analysis: {
    detected_device?: string;
    features?: string[];
    estimated_price_range?: string;
  };
  recommended_products: Product[];
  ar_preview_urls: Record<number, unknown>;
  pricing_info: {
    base_total_kopecks: number;
    discount_percentage: number;
    final_total_kopecks: number;
    savings_kopecks: number;
    dynamic_factors: string[];
  };
  video_call_available: boolean;
  video_call_token?: string;
  flash_sale_offer?: string;
}

const isDragOver = ref(false);
const previewImage = ref<string | null>(null);
const selectedFile = ref<File | null>(null);
const budget = ref(100000);
const analysisType = ref('gadget_recommendation');
const brandsInput = ref('');
const useCasesInput = ref('');
const isLoading = ref(false);
const error = ref<string | null>(null);
const result = ref<AnalysisResult | null>(null);
const showARModal = ref(false);
const selectedProductId = ref<number | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

const preferredBrands = computed(() => {
  return brandsInput.value
    .split(',')
    .map(b => b.trim())
    .filter(b => b.length > 0);
});

const useCases = computed(() => {
  return useCasesInput.value
    .split(',')
    .map(u => u.trim())
    .filter(u => u.length > 0);
});

function triggerFileInput(): void {
  fileInput.value?.click();
}

function handleFileSelect(event: Event): void {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    processFile(file);
  }
}

function handleDrop(event: DragEvent): void {
  isDragOver.value = false;
  const file = event.dataTransfer?.files[0];
  if (file && file.type.startsWith('image/')) {
    processFile(file);
  }
}

function processFile(file: File): void {
  if (file.size > 10 * 1024 * 1024) {
    error.value = 'Файл слишком большой. Максимум 10MB.';
    return;
  }
  
  selectedFile.value = file;
  const reader = new FileReader();
  reader.onload = (e) => {
    previewImage.value = e.target?.result as string;
  };
  reader.readAsDataURL(file);
}

function removeImage(): void {
  selectedFile.value = null;
  previewImage.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
}

async function analyzeImage(): Promise<void> {
  if (!selectedFile.value) {
    error.value = 'Пожалуйста, выберите изображение';
    return;
  }

  isLoading.value = true;
  error.value = null;
  result.value = null;

  try {
    const formData = new FormData();
    formData.append('image', selectedFile.value);
    formData.append('budget_max_kopecks', (budget.value * 100).toString());
    formData.append('analysis_type', analysisType.value);
    
    preferredBrands.value.forEach(brand => {
      formData.append('preferred_brands[]', brand);
    });
    
    useCases.value.forEach(useCase => {
      formData.append('use_cases[]', useCase);
    });

    const idempotencyKey = `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    formData.append('idempotency_key', idempotencyKey);

    const response = await axios.post('/api/v1/electronics/vision/analyze', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    result.value = response.data;
  } catch (err: unknown) {
    if (axios.isAxiosError(err)) {
      error.value = err.response?.data?.error || err.message || 'Ошибка при анализе';
    } else {
      error.value = 'Неизвестная ошибка';
    }
  } finally {
    isLoading.value = false;
  }
}

function formatPrice(kopecks: number): string {
  const rubles = kopecks / 100;
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(rubles);
}

function formatFactor(factor: string): string {
  const factorMap: Record<string, string> = {
    loyalty_discount: 'Лояльность',
    first_purchase_bonus: 'Первый заказ',
    evening_hours: 'Вечерняя скидка',
  };
  return factorMap[factor] || factor;
}

function openARPreview(productId: number): void {
  selectedProductId.value = productId;
  showARModal.value = true;
}

function addToCart(product: Product): void {
  console.log('Adding to cart:', product);
}

async function initiateVideoCall(): Promise<void> {
  if (!result.value?.video_call_token) {
    error.value = 'Токен для видеозвонка недоступен';
    return;
  }

  try {
    const response = await axios.post('/api/v1/electronics/vision/video-call', {
      token: result.value.video_call_token,
      product_ids: result.value.recommended_products.map(p => p.id),
    });

    const { room_name, participant_token, webrtc_config } = response.data;
    
    console.log('Video call initiated:', { room_name, participant_token, webrtc_config });
  } catch (err: unknown) {
    if (axios.isAxiosError(err)) {
      error.value = err.response?.data?.error || err.message || 'Ошибка при инициализации видеозвонка';
    } else {
      error.value = 'Неизвестная ошибка';
    }
  }
}
</script>

<style scoped>
.gadget-vision-analyzer {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.upload-zone {
  border: 2px dashed #d1d5db;
  border-radius: 12px;
  padding: 40px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s;
}

.upload-zone:hover,
.upload-zone.drag-over {
  border-color: #3b82f6;
  background-color: #eff6ff;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.preview-container {
  position: relative;
  display: inline-block;
}

.preview-image {
  max-width: 100%;
  max-height: 400px;
  border-radius: 8px;
}

.remove-button {
  position: absolute;
  top: -10px;
  right: -10px;
  width: 30px;
  height: 30px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 50%;
  font-size: 20px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.product-card {
  transition: transform 0.2s, box-shadow 0.2s;
}

.product-card:hover {
  transform: translateY(-4px);
}

.error-message {
  animation: shake 0.5s;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10px); }
  75% { transform: translateX(10px); }
}
</style>
