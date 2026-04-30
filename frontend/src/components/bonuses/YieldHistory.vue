<template>
  <div class="yield-history">
    <div class="header">
      <h3>💵 Float Yield History</h3>
      <div class="header-actions">
        <select v-model="limit" @change="loadYieldHistory" class="limit-select">
          <option value="7">Last 7 days</option>
          <option value="30">Last 30 days</option>
          <option value="90">Last 90 days</option>
        </select>
        <button @click="loadYieldHistory" class="refresh-btn" :disabled="loading">
          🔄
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading">
      Loading yield history...
    </div>

    <div v-else-if="error" class="error">
      {{ error }}
    </div>

    <div v-else-if="transactions.length === 0" class="empty">
      <div class="empty-icon">📊</div>
      <p>No yield transactions yet</p>
      <p class="empty-text">Yield is calculated daily from your locked bonuses</p>
    </div>

    <div v-else>
      <div class="summary">
        <div class="summary-card">
          <span class="label">Total User Yield</span>
          <span class="value">{{ formatCurrency(totalUserYield) }}</span>
        </div>
        <div class="summary-card">
          <span class="label">Total Platform Yield</span>
          <span class="value">{{ formatCurrency(totalPlatformYield) }}</span>
        </div>
        <div class="summary-card">
          <span class="label">Average Daily Yield</span>
          <span class="value">{{ formatCurrency(averageDailyYield) }}</span>
        </div>
      </div>

      <div class="transactions-list">
        <div
          v-for="tx in transactions"
          :key="tx.date"
          class="transaction-item"
        >
          <div class="transaction-date">
            {{ formatDate(tx.date) }}
          </div>
          <div class="transaction-details">
            <div class="detail-row">
              <span class="label">Total Float:</span>
              <span class="value">{{ formatCurrency(tx.total_float) }}</span>
            </div>
            <div class="detail-row">
              <span class="label">User Yield:</span>
              <span class="value user">{{ formatCurrency(tx.user_yield) }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Platform Yield:</span>
              <span class="value platform">{{ formatCurrency(tx.platform_yield) }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Yield Rate:</span>
              <span class="value rate">{{ (tx.yield_rate * 100).toFixed(4) }}%</span>
            </div>
          </div>
        </div>
      </div>

      <button @click="calculateYield" class="calculate-btn" :disabled="calculating">
        {{ calculating ? 'Calculating...' : 'Calculate Today\'s Yield' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { catfloatApi, type YieldTransaction } from '@/services/catfloatApi';

const transactions = ref<YieldTransaction[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const calculating = ref(false);
const limit = ref(30);

const totalUserYield = computed(() => {
  return transactions.value.reduce((sum, tx) => sum + tx.user_yield, 0);
});

const totalPlatformYield = computed(() => {
  return transactions.value.reduce((sum, tx) => sum + tx.platform_yield, 0);
});

const averageDailyYield = computed(() => {
  if (transactions.value.length === 0) return 0;
  return totalUserYield.value / transactions.value.length;
});

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(amount);
};

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU', {
    weekday: 'short',
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const loadYieldHistory = async () => {
  loading.value = true;
  error.value = null;
  
  try {
    transactions.value = await catfloatApi.getYieldHistory(Number(limit.value));
  } catch (err: any) {
    error.value = err.message || 'Failed to load yield history';
  } finally {
    loading.value = false;
  }
};

const calculateYield = async () => {
  calculating.value = true;
  
  try {
    await catfloatApi.calculateYield();
    await loadYieldHistory();
  } catch (err: any) {
    error.value = err.message || 'Failed to calculate yield';
  } finally {
    calculating.value = false;
  }
};

onMounted(() => {
  loadYieldHistory();
});
</script>

<style scoped>
.yield-history {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.limit-select {
  padding: 6px 12px;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  font-size: 13px;
  color: #1a1a1a;
}

.refresh-btn {
  background: none;
  border: none;
  font-size: 14px;
  cursor: pointer;
  padding: 6px;
  border-radius: 4px;
  transition: background 0.2s;
}

.refresh-btn:hover:not(:disabled) {
  background: #f3f4f6;
}

.refresh-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loading, .error, .empty {
  text-align: center;
  padding: 32px;
  color: #666;
  font-size: 14px;
}

.error {
  color: #ef4444;
}

.empty-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

.empty-text {
  color: #999;
  font-size: 13px;
}

.summary {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}

.summary-card {
  background: #f9fafb;
  border-radius: 8px;
  padding: 16px;
  text-align: center;
}

.summary-card .label {
  display: block;
  font-size: 12px;
  color: #666;
  margin-bottom: 8px;
}

.summary-card .value {
  font-size: 18px;
  font-weight: 700;
  color: #1a1a1a;
}

.transactions-list {
  max-height: 400px;
  overflow-y: auto;
  margin-bottom: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.transaction-item {
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.transaction-item:last-child {
  border-bottom: none;
}

.transaction-date {
  font-weight: 600;
  color: #1a1a1a;
  font-size: 14px;
  margin-bottom: 12px;
}

.transaction-details {
  display: grid;
  gap: 6px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

.detail-row .label {
  color: #666;
}

.detail-row .value {
  font-weight: 600;
  color: #1a1a1a;
}

.detail-row .value.user {
  color: #10b981;
}

.detail-row .value.platform {
  color: #6366f1;
}

.detail-row .value.rate {
  color: #f59e0b;
}

.calculate-btn {
  width: 100%;
  padding: 12px;
  border-radius: 8px;
  border: none;
  background: #10b981;
  color: white;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.calculate-btn:hover:not(:disabled) {
  background: #059669;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.calculate-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
