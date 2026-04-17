<template>
  <div class="beauty-dynamic-pricing">
    <h3>Динамическое ценообразование</h3>

    <div class="input-section">
      <div class="input-group">
        <label>ID мастера:</label>
        <input type="number" v-model="masterId" min="1" />
      </div>

      <div class="input-group">
        <label>ID услуги:</label>
        <input type="number" v-model="serviceId" min="1" />
      </div>

      <div class="input-group">
        <label>Время слота:</label>
        <input type="datetime-local" v-model="timeSlot" />
      </div>

      <div class="input-group">
        <label>Базовая цена:</label>
        <input type="number" v-model="basePrice" min="100" />
      </div>
    </div>

    <button @click="calculatePrice" :disabled="loading" class="btn-calculate">
      <Calculator class="icon" />
      {{ loading ? 'Расчет...' : 'Рассчитать цену' }}
    </button>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>

    <div v-if="result" class="results-section">
      <div class="price-breakdown">
        <div class="price-item">
          <span class="label">Базовая цена:</span>
          <span class="value">{{ result.base_price }} ₽</span>
        </div>

        <div class="price-item">
          <span class="label">Спрос:</span>
          <span class="value">{{ (result.demand_score * 100).toFixed(0) }}%</span>
        </div>

        <div class="price-item">
          <span class="label">Множитель пика:</span>
          <span class="value" :class="{ surge: result.is_surge_pricing }">
            {{ result.surge_multiplier }}x
          </span>
        </div>

        <div class="price-item">
          <span class="label">Скидка:</span>
          <span class="value" :class="{ discount: result.is_flash_discount }">
            {{ result.flash_discount_percent }}%
          </span>
        </div>

        <div class="price-item final">
          <span class="label">Итоговая цена:</span>
          <span class="value">{{ result.final_price }} ₽</span>
        </div>

        <div class="price-item">
          <span class="label">Изменение:</span>
          <span class="value" :class="priceChangeClass">
            {{ priceChange }}
          </span>
        </div>
      </div>

      <div class="status-badges">
        <span v-if="result.is_surge_pricing" class="badge surge">Пиковый тариф</span>
        <span v-if="result.is_flash_discount" class="badge discount">Скидка</span>
      </div>
    </div>

    <div class="history-section">
      <h4>История цен</h4>
      <button @click="loadPriceHistory" class="btn-history">
        Загрузить историю
      </button>

      <div v-if="priceHistory.length > 0" class="history-list">
        <div v-for="(item, index) in priceHistory" :key="index" class="history-item">
          <span class="time">{{ new Date(item.timestamp).toLocaleString() }}</span>
          <span class="old-price">{{ item.old_price }} ₽</span>
          <span class="arrow">→</span>
          <span class="new-price">{{ item.new_price }} ₽</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Calculator } from 'lucide-vue-next';

const masterId = ref<number>(1);
const serviceId = ref<number>(1);
const timeSlot = ref<string>('');
const basePrice = ref<number>(1000);
const loading = ref(false);
const error = ref<string | null>(null);
const result = ref<any>(null);
const priceHistory = ref<any[]>([]);

const priceChange = computed(() => {
  if (!result.value) return '0%';
  const change = ((result.value.final_price - result.value.base_price) / result.value.base_price * 100);
  return `${change > 0 ? '+' : ''}${change.toFixed(1)}%`;
});

const priceChangeClass = computed(() => {
  if (!result.value) return '';
  const change = result.value.final_price - result.value.base_price;
  return change > 0 ? 'increase' : change < 0 ? 'decrease' : '';
});

const calculatePrice = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await fetch('/api/beauty/pricing/calculate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': crypto.randomUUID(),
        'X-Tenant-ID': '1',
      },
      body: JSON.stringify({
        master_id: masterId.value,
        service_id: serviceId.value,
        time_slot: timeSlot.value || null,
        base_price: basePrice.value,
      }),
    });

    const data = await response.json();
    
    if (data.success) {
      result.value = data.data;
    } else {
      error.value = 'Ошибка при расчете цены';
    }
  } catch (err) {
    error.value = 'Произошла ошибка. Попробуйте позже.';
  } finally {
    loading.value = false;
  }
};

const loadPriceHistory = async () => {
  try {
    const response = await fetch(`/api/beauty/pricing/history?service_id=${serviceId.value}`, {
      headers: {
        'X-Tenant-ID': '1',
      },
    });

    const data = await response.json();
    
    if (data.success) {
      priceHistory.value = data.data;
    }
  } catch (err) {
    error.value = 'Ошибка при загрузке истории';
  }
};
</script>

<style scoped>
.beauty-dynamic-pricing {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

.input-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.input-group label {
  font-weight: 600;
  font-size: 14px;
  color: #374151;
}

.input-group input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.btn-calculate {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px 28px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  transition: transform 0.2s;
  width: 100%;
}

.btn-calculate:hover:not(:disabled) {
  transform: scale(1.02);
}

.btn-calculate:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-message {
  margin-top: 16px;
  padding: 12px;
  background: #fee2e2;
  color: #dc2626;
  border-radius: 6px;
  text-align: center;
}

.results-section {
  margin-top: 24px;
  padding: 20px;
  background: #f9fafb;
  border-radius: 8px;
}

.price-breakdown {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.price-item {
  display: flex;
  justify-content: space-between;
  padding: 12px;
  background: white;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.price-item.final {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  font-weight: 700;
  font-size: 18px;
}

.price-item .label {
  font-weight: 600;
  color: #374151;
}

.price-item.final .label {
  color: white;
}

.price-item .value {
  font-weight: 700;
  color: #111827;
}

.price-item.final .value {
  color: white;
}

.price-item .value.surge {
  color: #ef4444;
}

.price-item .value.discount {
  color: #10b981;
}

.price-item .value.increase {
  color: #ef4444;
}

.price-item .value.decrease {
  color: #10b981;
}

.status-badges {
  display: flex;
  gap: 8px;
  margin-top: 16px;
}

.badge {
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.badge.surge {
  background: #fee2e2;
  color: #dc2626;
}

.badge.discount {
  background: #d1fae5;
  color: #059669;
}

.history-section {
  margin-top: 24px;
}

.history-section h4 {
  margin-bottom: 12px;
}

.btn-history {
  padding: 8px 16px;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
}

.history-list {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.history-item {
  display: flex;
  justify-content: space-between;
  padding: 12px;
  background: white;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  font-size: 14px;
}

.history-item .time {
  color: #6b7280;
}

.history-item .old-price {
  color: #9ca3af;
  text-decoration: line-through;
}

.history-item .arrow {
  color: #6b7280;
}

.history-item .new-price {
  font-weight: 700;
  color: #10b981;
}

.icon {
  width: 20px;
  height: 20px;
}
</style>
