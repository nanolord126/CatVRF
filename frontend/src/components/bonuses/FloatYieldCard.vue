<template>
  <div class="float-yield-card">
    <div class="card-header">
      <h3 class="card-title">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
        </svg>
        Float Yield
      </h3>
      <span class="legal-badge">Только бонусы</span>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Загрузка данных...</p>
    </div>

    <div v-else-if="error" class="error-state">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
      </svg>
      <p>{{ error }}</p>
    </div>

    <div v-else class="yield-content">
      <!-- Main Stats -->
      <div class="stats-grid">
        <div class="stat-card total-locked">
          <div class="stat-label">Заблокировано</div>
          <div class="stat-value">{{ formatCurrency(summary?.total_locked || 0) }}</div>
          <div class="stat-subtitle">В холде</div>
        </div>

        <div class="stat-card today-yield">
          <div class="stat-label">Сегодня</div>
          <div class="stat-value">{{ formatCurrency(summary?.today_yield || 0) }}</div>
          <div class="stat-subtitle">Доход в бонусах</div>
        </div>

        <div class="stat-card monthly-yield">
          <div class="stat-label">Месяц</div>
          <div class="stat-value">{{ formatCurrency(summary?.monthly_yield || 0) }}</div>
          <div class="stat-subtitle">Накоплено</div>
        </div>

        <div class="stat-card expected-yield">
          <div class="stat-label">Ожидается</div>
          <div class="stat-value">{{ formatCurrency(summary?.expected_yield || 0) }}</div>
          <div class="stat-subtitle">При текущем холде</div>
        </div>
      </div>

      <!-- Rates Info -->
      <div class="rates-section">
        <h4 class="section-title">Ставки доходности</h4>
        <div class="rates-grid">
          <div class="rate-item">
            <div class="rate-label">Ваша ставка</div>
            <div class="rate-value">{{ formatRate(summary?.user_rate || 0) }}</div>
            <div class="rate-desc">Ежедневно</div>
          </div>
          <div class="rate-item">
            <div class="rate-label">Ставка платформы</div>
            <div class="rate-value">{{ formatRate(summary?.platform_rate || 0) }}</div>
            <div class="rate-desc">Ежедневно</div>
          </div>
        </div>
      </div>

      <!-- Legal Notice -->
      <div class="legal-notice">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
        </svg>
        <span>По законам РФ float yield выплачивается только в бонусных баллах. Вывод как денежных средств запрещён.</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { catfloatApi, type FloatYieldSummary } from '@/services/catfloatApi';

interface Props {
  userId: number;
  tenantId: number;
}

const props = defineProps<Props>();

const summary = ref<FloatYieldSummary | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value);
};

const formatRate = (rate: number): string => {
  const percentage = rate * 100;
  return `${percentage.toFixed(4)}%`;
};

const loadFloatYieldSummary = async () => {
  try {
    loading.value = true;
    error.value = null;
    summary.value = await catfloatApi.getFloatYieldSummary(props.userId, props.tenantId);
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Ошибка загрузки данных';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadFloatYieldSummary();
});
</script>

<style scoped>
.float-yield-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e5e7eb;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.card-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 18px;
  font-weight: 600;
  margin: 0;
}

.legal-badge {
  background: rgba(255, 255, 255, 0.2);
  padding: 4px 12px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 500;
}

.loading-state,
.error-state {
  padding: 40px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  color: #6b7280;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e5e7eb;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.yield-content {
  padding: 20px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: #f9fafb;
  border-radius: 8px;
  padding: 16px;
  text-align: center;
  border: 1px solid #e5e7eb;
}

.stat-card.total-locked {
  border-left: 4px solid #667eea;
}

.stat-card.today-yield {
  border-left: 4px solid #10b981;
}

.stat-card.monthly-yield {
  border-left: 4px solid #f59e0b;
}

.stat-card.expected-yield {
  border-left: 4px solid #8b5cf6;
}

.stat-label {
  font-size: 12px;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 4px;
}

.stat-subtitle {
  font-size: 11px;
  color: #9ca3af;
}

.rates-section {
  margin-bottom: 20px;
}

.section-title {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin: 0 0 12px 0;
}

.rates-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.rate-item {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  padding: 12px;
  text-align: center;
}

.rate-label {
  font-size: 12px;
  color: #166534;
  margin-bottom: 4px;
}

.rate-value {
  font-size: 18px;
  font-weight: 700;
  color: #15803d;
  margin-bottom: 2px;
}

.rate-desc {
  font-size: 11px;
  color: #22c55e;
}

.legal-notice {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: #fef3c7;
  border: 1px solid #fcd34d;
  border-radius: 8px;
  font-size: 12px;
  color: #92400e;
}

.legal-notice svg {
  flex-shrink: 0;
}
</style>
