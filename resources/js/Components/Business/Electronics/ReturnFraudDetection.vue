<template>
  <div class="return-fraud-detection">
    <h2 class="text-2xl font-bold mb-6">Детекция мошенничества при возврате</h2>

    <form @submit.prevent="detectFraud" class="detection-form">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Заказ</label>
          <input
            v-model="formData.orderId"
            type="number"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            placeholder="ID заказа"
            required
          />
        </div>

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
            placeholder="Серийный номер устройства"
            required
            maxlength="100"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Причина возврата</label>
          <select
            v-model="formData.returnReason"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            required
          >
            <option value="">Выберите причину</option>
            <option value="defective">Брак/дефект</option>
            <option value="not_as_described">Не соответствует описанию</option>
            <option value="wrong_item">Неправильный товар</option>
            <option value="damaged">Повреждён при доставке</option>
            <option value="changed_mind">Передумал</option>
            <option value="found_better_price">Нашёл дешевле</option>
            <option value="no_longer_needed">Больше не нужен</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Состояние</label>
          <select
            v-model="formData.condition"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
            required
          >
            <option value="">Выберите состояние</option>
            <option value="new">Новый</option>
            <option value="like_new">Как новый</option>
            <option value="good">Хорошее</option>
            <option value="fair">Удовлетворительное</option>
            <option value="poor">Плохое</option>
            <option value="damaged">Повреждён</option>
          </select>
        </div>
      </div>

      <div class="mb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Метаданные устройства</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-xs text-gray-600 mb-1">IMEI</label>
            <input
              v-model="formData.deviceMetadata.imei"
              type="text"
              class="w-full px-3 py-2 border rounded-lg text-sm"
              placeholder="IMEI"
              maxlength="50"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Здоровье батареи (%)</label>
            <input
              v-model.number="formData.deviceMetadata.batteryHealth"
              type="number"
              min="0"
              max="100"
              class="w-full px-3 py-2 border rounded-lg text-sm"
              placeholder="0-100"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Состояние экрана</label>
            <input
              v-model="formData.deviceMetadata.screenCondition"
              type="text"
              class="w-full px-3 py-2 border rounded-lg text-sm"
              placeholder="Отличное, хорошее..."
              maxlength="50"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Дата активации</label>
            <input
              v-model="formData.deviceMetadata.activationDate"
              type="date"
              class="w-full px-3 py-2 border rounded-lg text-sm"
            />
          </div>
        </div>
      </div>

      <div class="mb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Поведение пользователя</h4>
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-xs text-gray-600 mb-1">Время на сайте (мин)</label>
            <input
              v-model.number="formData.userBehavior.timeOnSiteMinutes"
              type="number"
              min="0"
              class="w-full px-3 py-2 border rounded-lg text-sm"
              placeholder="0"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">Просмотров до покупки</label>
            <input
              v-model.number="formData.userBehavior.pageViewsBeforePurchase"
              type="number"
              min="0"
              class="w-full px-3 py-2 border rounded-lg text-sm"
              placeholder="0"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-600 mb-1">% отказов корзины</label>
            <input
              v-model.number="formData.userBehavior.cartAbandonmentRate"
              type="number"
              min="0"
              max="1"
              step="0.01"
              class="w-full px-3 py-2 border rounded-lg text-sm"
              placeholder="0.0-1.0"
            />
          </div>
        </div>
      </div>

      <button
        type="submit"
        :disabled="isLoading"
        class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
      >
        {{ isLoading ? 'Анализируем...' : 'Проверить возврат' }}
      </button>
    </form>

    <div v-if="error" class="error-message mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
      <p class="text-red-600">{{ error }}</p>
    </div>

    <div v-if="result" class="result-section mt-6">
      <div :class="['result-header', result.riskLevel]">
        <h3 class="text-xl font-bold">
          {{ result.is_fraudulent ? '🚨 Обнаружен мошеннический возврат' : '✅ Возврат выглядит легитимным' }}
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
  recommended_action: string;
  hold_duration_minutes: number | null;
}

const formData = ref({
  orderId: '',
  productId: '',
  serialNumber: '',
  returnReason: '',
  condition: '',
  deviceMetadata: {
    imei: '',
    batteryHealth: null as number | null,
    screenCondition: '',
    activationDate: '',
  },
  userBehavior: {
    timeOnSiteMinutes: null as number | null,
    pageViewsBeforePurchase: null as number | null,
    cartAbandonmentRate: null as number | null,
  },
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

async function detectFraud(): Promise<void> {
  isLoading.value = true;
  error.value = null;
  result.value = null;

  try {
    const payload = {
      order_id: formData.value.orderId,
      product_id: formData.value.productId,
      serial_number: formData.value.serialNumber,
      return_reason: formData.value.returnReason,
      condition: formData.value.condition,
      device_metadata: formData.value.deviceMetadata,
      user_behavior: formData.value.userBehavior,
      idempotency_key: `return_${Date.now()}`,
    };

    const response = await axios.post('/api/v1/electronics/v1/fraud/return/detect', payload);
    result.value = response.data;
  } catch (err: unknown) {
    if (axios.isAxiosError(err)) {
      error.value = err.response?.data?.error || err.message || 'Ошибка детекции';
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
  if (probability >= 0.65) return 'text-red-600';
  if (probability >= 0.45) return 'text-orange-600';
  return 'text-green-600';
}

function getActionLabel(action: string): string {
  const labels: Record<string, string> = {
    block_return_and_investigate: 'Блокировать возврат и расследовать',
    manual_review_with_evidence: 'Ручная проверка с доказательствами',
    additional_verification_required: 'Дополнительная верификация',
    flag_for_monitoring: 'Пометить для мониторинга',
    process_with_delay: 'Обработать с задержкой',
    approve_immediately: 'Одобрить немедленно',
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
    excessive_return_rate: 'Чрезмерный процент возвратов',
    high_return_frequency: 'Высокая частота возвратов',
    serial_reuse_detected: 'Повторное использование серийника',
    quick_return: 'Быстрый возврат',
    condition_mismatch: 'Несоответствие состояния',
    reason_condition_mismatch: 'Несоответствие причины и состояния',
    insufficient_metadata: 'Недостаточно метаданных',
    high_cart_abandonment: 'Высокий процент отказов корзины',
  };
  return names[factor] || factor;
}

onMounted(() => {
  loadProducts();
});
</script>

<style scoped>
.detection-form {
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
</style>
