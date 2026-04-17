<template>
  <div class="serial-validation">
    <h2 class="text-2xl font-bold mb-6">Валидация серийного номера</h2>

    <form @submit.prevent="validateSerial" class="validation-form">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Продукт</label>
          <select
            v-model="formData.productId"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            required
          >
            <option value="">Выберите продукт</option>
            <option v-for="product in products" :key="product.id" :value="product.id">
              {{ product.name }} ({{ product.brand }})
            </option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Серийный номер</label>
          <input
            v-model="formData.serialNumber"
            type="text"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="Например: AP15PRO123456"
            required
            maxlength="100"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Заказ (опционально)</label>
          <input
            v-model="formData.orderId"
            type="number"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="ID заказа"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Дата покупки (опционально)</label>
          <input
            v-model="formData.purchaseDate"
            type="date"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          />
        </div>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Ссылка на чек (опционально)</label>
        <input
          v-model="formData.proofOfPurchaseUrl"
          type="url"
          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
          placeholder="https://..."
          maxlength="500"
        />
      </div>

      <button
        type="submit"
        :disabled="isLoading"
        class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
      >
        {{ isLoading ? 'Проверяем...' : 'Валидировать' }}
      </button>
    </form>

    <div v-if="error" class="error-message mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
      <p class="text-red-600">{{ error }}</p>
    </div>

    <div v-if="result" class="result-section mt-6">
      <div :class="['result-header', result.riskLevel]">
        <h3 class="text-xl font-bold">
          {{ result.is_fraudulent ? '⚠️ Обнаружен подлог' : '✅ Валидация пройдена' }}
        </h3>
        <div class="risk-badge">{{ getRiskLabel(result.risk_level) }}</div>
      </div>

      <div class="result-metrics grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
        <div class="metric-card">
          <p class="text-sm text-gray-600">Вероятность подлога</p>
          <p class="text-2xl font-bold" :class="getProbabilityColor(result.fraud_probability)">
            {{ (result.fraud_probability * 100).toFixed(1) }}%
          </p>
        </div>
        <div class="metric-card">
          <p class="text-sm text-gray-600">Риск</p>
          <p class="text-2xl font-bold">{{ getRiskLabel(result.risk_level) }}</p>
        </div>
        <div class="metric-card">
          <p class="text-sm text-gray-600">Рекомендация</p>
          <p class="text-lg font-semibold">{{ getActionLabel(result.recommended_action) }}</p>
        </div>
        <div v-if="result.hold_duration_minutes" class="metric-card">
          <p class="text-sm text-gray-600">Hold время</p>
          <p class="text-2xl font-bold">{{ formatHoldTime(result.hold_duration_minutes) }}</p>
        </div>
      </div>

      <div v-if="result.risk_factors?.length" class="risk-factors mt-6">
        <h4 class="text-lg font-semibold mb-3">Факторы риска</h4>
        <div class="space-y-2">
          <div
            v-for="(factor, index) in result.risk_factors"
            :key="index"
            :class="['factor-item', factor.severity]"
          >
            <div class="factor-header">
              <span class="factor-name">{{ formatFactorName(factor.factor) }}</span>
              <span class="factor-severity">{{ factor.severity }}</span>
            </div>
            <p class="factor-description">{{ factor.description }}</p>
            <p class="factor-value">Значение: {{ factor.value }}</p>
          </div>
        </div>
      </div>

      <div v-if="result.ml_features" class="ml-features mt-6">
        <h4 class="text-lg font-semibold mb-3">ML-признаки</h4>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
          <div v-for="(value, key) in result.ml_features" :key="key" class="feature-item">
            <span class="feature-key">{{ formatFeatureKey(key) }}:</span>
            <span class="feature-value">{{ formatFeatureValue(value) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface Product {
  id: number;
  name: string;
  brand: string;
}

interface FraudDetectionResult {
  is_fraudulent: boolean;
  fraud_probability: number;
  risk_level: string;
  risk_factors: Array<{
    factor: string;
    severity: string;
    description: string;
    value: string | number;
  }>;
  ml_features: Record<string, unknown>;
  recommended_action: string;
  hold_duration_minutes: number | null;
}

const formData = ref({
  productId: '',
  serialNumber: '',
  orderId: '',
  purchaseDate: '',
  proofOfPurchaseUrl: '',
});

const products = ref<Product[]>([]);
const isLoading = ref(false);
const error = ref<string | null>(null);
const result = ref<FraudDetectionResult | null>(null);

async function loadProducts(): Promise<void> {
  try {
    const response = await axios.get('/api/v1/electronics/v1');
    products.value = response.data.data || [];
  } catch (err: unknown) {
    console.error('Failed to load products:', err);
  }
}

async function validateSerial(): Promise<void> {
  isLoading.value = true;
  error.value = null;
  result.value = null;

  try {
    const payload = {
      product_id: formData.value.productId,
      serial_number: formData.value.serialNumber,
      order_id: formData.value.orderId || null,
      purchase_date: formData.value.purchaseDate || null,
      proof_of_purchase_url: formData.value.proofOfPurchaseUrl || null,
      idempotency_key: `serial_${Date.now()}`,
    };

    const response = await axios.post('/api/v1/electronics/v1/fraud/serial/validate', payload);
    result.value = response.data;
  } catch (err: unknown) {
    if (axios.isAxiosError(err)) {
      error.value = err.response?.data?.error || err.message || 'Ошибка валидации';
    } else {
      error.value = 'Неизвестная ошибка';
    }
  } finally {
    isLoading.value = false;
  }
}

function getRiskLabel(level: string): string {
  const labels: Record<string, string> = {
    critical: 'Критический',
    high: 'Высокий',
    medium: 'Средний',
    low: 'Низкий',
    minimal: 'Минимальный',
  };
  return labels[level] || level;
}

function getProbabilityColor(probability: number): string {
  if (probability >= 0.7) return 'text-red-600';
  if (probability >= 0.4) return 'text-orange-600';
  return 'text-green-600';
}

function getActionLabel(action: string): string {
  const labels: Record<string, string> = {
    block_and_investigate: 'Блокировать и расследовать',
    manual_review_required: 'Требуется ручная проверка',
    additional_verification: 'Дополнительная верификация',
    flag_for_monitoring: 'Пометить для мониторинга',
    proceed_with_caution: 'Продолжить с осторожностью',
    approve: 'Одобрить',
  };
  return labels[action] || action;
}

function formatHoldTime(minutes: number): string {
  if (minutes >= 1440) {
    return `${Math.floor(minutes / 1440)} дн`;
  }
  if (minutes >= 60) {
    return `${Math.floor(minutes / 60)} ч`;
  }
  return `${minutes} мин`;
}

function formatFactorName(factor: string): string {
  const names: Record<string, string> = {
    high_serial_usage: 'Многократное использование',
    high_return_rate: 'Высокий процент возвратов',
    unusual_serial_pattern: 'Необычный паттерн',
    high_risk_category: 'Категория с высоким риском',
    no_proof_of_purchase: 'Нет чека',
    expired_warranty: 'Истекла гарантия',
  };
  return names[factor] || factor;
}

function formatFeatureKey(key: string): string {
  return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function formatFeatureValue(value: unknown): string {
  if (typeof value === 'number') {
    return value % 1 === 0 ? value.toString() : value.toFixed(2);
  }
  if (typeof value === 'boolean') {
    return value ? 'Да' : 'Нет';
  }
  return String(value);
}

onMounted(() => {
  loadProducts();
});
</script>

<style scoped>
.validation-form {
  background: #f9fafb;
  padding: 24px;
  border-radius: 8px;
}

.result-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-radius: 8px;
}

.result-header.critical {
  background: #fef2f2;
  border: 2px solid #ef4444;
}

.result-header.high {
  background: #fff7ed;
  border: 2px solid #f97316;
}

.result-header.medium {
  background: #fefce8;
  border: 2px solid #eab308;
}

.result-header.low,
.result-header.minimal {
  background: #f0fdf4;
  border: 2px solid #22c55e;
}

.risk-badge {
  padding: 6px 16px;
  border-radius: 20px;
  font-weight: 600;
  font-size: 14px;
}

.metric-card {
  background: white;
  padding: 16px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.factor-item {
  padding: 12px;
  border-radius: 6px;
  border-left: 4px solid;
}

.factor-item.critical {
  background: #fef2f2;
  border-left-color: #ef4444;
}

.factor-item.high {
  background: #fff7ed;
  border-left-color: #f97316;
}

.factor-item.medium {
  background: #fefce8;
  border-left-color: #eab308;
}

.factor-item.low {
  background: #f0fdf4;
  border-left-color: #22c55e;
}

.factor-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 4px;
}

.factor-name {
  font-weight: 600;
}

.factor-severity {
  text-transform: uppercase;
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 4px;
}

.factor-description {
  color: #6b7280;
  margin-bottom: 4px;
}

.factor-value {
  font-size: 14px;
  color: #374151;
}

.feature-item {
  display: flex;
  justify-content: space-between;
  padding: 8px;
  background: #f9fafb;
  border-radius: 4px;
}

.feature-key {
  color: #6b7280;
}

.feature-value {
  font-weight: 500;
}
</style>
