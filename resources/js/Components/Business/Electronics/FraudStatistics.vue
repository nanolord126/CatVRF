<template>
  <div class="fraud-statistics">
    <h2 class="text-2xl font-bold mb-6">Статистика мошенничества</h2>

    <div v-if="isLoading" class="loading-container">
      <div class="spinner"></div>
      <p class="mt-4 text-gray-600">Загрузка статистики...</p>
    </div>

    <div v-else-if="error" class="error-message p-4 bg-red-50 border border-red-200 rounded-lg">
      <p class="text-red-600">{{ error }}</p>
    </div>

    <div v-else-if="stats" class="statistics-content">
      <div class="stats-grid grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="stat-card">
          <div class="stat-icon validation">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="stat-content">
            <p class="text-sm text-gray-600">Валидаций серийников</p>
            <p class="text-3xl font-bold">{{ stats.total_serial_validations }}</p>
            <p class="text-sm text-red-600 mt-1">{{ stats.fraudulent_serials }} мошеннических</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon returns">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </div>
          <div class="stat-content">
            <p class="text-sm text-gray-600">Детекций возвратов</p>
            <p class="text-3xl font-bold">{{ stats.total_return_detections }}</p>
            <p class="text-sm text-red-600 mt-1">{{ stats.fraudulent_returns }} мошеннических</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon probability">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
          </div>
          <div class="stat-content">
            <p class="text-sm text-gray-600">Средняя вероятность подлога</p>
            <p class="text-3xl font-bold" :class="getProbabilityColor(stats.avg_fraud_probability)">
              {{ (stats.avg_fraud_probability * 100).toFixed(1) }}%
            </p>
            <p class="text-sm text-gray-500 mt-1">за 30 дней</p>
          </div>
        </div>
      </div>

      <div class="high-risk-section mt-8">
        <h3 class="text-lg font-semibold mb-4">Высокорисковые возвраты (7 дней)</h3>
        <div class="high-risk-card">
          <div class="high-risk-icon">⚠️</div>
          <div class="high-risk-content">
            <p class="text-4xl font-bold text-orange-600">{{ stats.high_risk_returns_7d }}</p>
            <p class="text-sm text-gray-600">высокорисковых возвратов</p>
          </div>
        </div>
      </div>

      <div class="charts-section mt-8">
        <h3 class="text-lg font-semibold mb-4">Распределение по риску</h3>
        <div class="risk-distribution">
          <div class="risk-bar critical">
            <div class="risk-label">Критический</div>
            <div class="risk-progress">
              <div class="progress-fill critical" :style="{ width: '15%' }"></div>
            </div>
            <div class="risk-value">15%</div>
          </div>
          <div class="risk-bar high">
            <div class="risk-label">Высокий</div>
            <div class="risk-progress">
              <div class="progress-fill high" :style="{ width: '25%' }"></div>
            </div>
            <div class="risk-value">25%</div>
          </div>
          <div class="risk-bar medium">
            <div class="risk-label">Средний</div>
            <div class="risk-progress">
              <div class="progress-fill medium" :style="{ width: '35%' }"></div>
            </div>
            <div class="risk-value">35%</div>
          </div>
          <div class="risk-bar low">
            <div class="risk-label">Низкий</div>
            <div class="risk-progress">
              <div class="progress-fill low" :style="{ width: '25%' }"></div>
            </div>
            <div class="risk-value">25%</div>
          </div>
        </div>
      </div>

      <div class="actions-section mt-8">
        <h3 class="text-lg font-semibold mb-4">Быстрые действия</h3>
        <div class="actions-grid grid grid-cols-1 md:grid-cols-3 gap-4">
          <button @click="exportReport" class="action-button">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>Экспорт отчёта</span>
          </button>
          <button @click="refreshData" class="action-button">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Обновить данные</span>
          </button>
          <button @click="configureAlerts" class="action-button">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span>Настроить оповещения</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface FraudStatistics {
  total_serial_validations: number;
  fraudulent_serials: number;
  total_return_detections: number;
  fraudulent_returns: number;
  high_risk_returns_7d: number;
  avg_fraud_probability: number;
}

const isLoading = ref(false);
const error = ref<string | null>(null);
const stats = ref<FraudStatistics | null>(null);

async function loadStatistics(): Promise<void> {
  isLoading.value = true;
  error.value = null;

  try {
    const response = await axios.get('/api/v1/electronics/v1/fraud/statistics');
    stats.value = response.data;
  } catch (err: unknown) {
    if (axios.isAxiosError(err)) {
      error.value = err.response?.data?.error || err.message || 'Ошибка загрузки статистики';
    } else {
      error.value = 'Неизвестная ошибка';
    }
  } finally {
    isLoading.value = false;
  }
}

function getProbabilityColor(probability: number): string {
  if (probability >= 0.7) return 'text-red-600';
  if (probability >= 0.4) return 'text-orange-600';
  return 'text-green-600';
}

function exportReport(): void {
  console.log('Exporting fraud report...');
}

function refreshData(): void {
  loadStatistics();
}

function configureAlerts(): void {
  console.log('Configuring fraud alerts...');
}

onMounted(() => {
  loadStatistics();
});
</script>

<style scoped>
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 200px;
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

.stats-grid {
  margin-bottom: 24px;
}

.stat-card {
  display: flex;
  align-items: center;
  background: white;
  padding: 24px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 16px;
}

.stat-icon.validation {
  background: #dbeafe;
  color: #3b82f6;
}

.stat-icon.returns {
  background: #fee2e2;
  color: #ef4444;
}

.stat-icon.probability {
  background: #d1fae5;
  color: #10b981;
}

.stat-content p {
  margin: 0;
}

.high-risk-section {
  background: white;
  padding: 24px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.high-risk-card {
  display: flex;
  align-items: center;
  background: #fff7ed;
  padding: 20px;
  border-radius: 8px;
  border: 2px solid #f97316;
}

.high-risk-icon {
  font-size: 48px;
  margin-right: 20px;
}

.high-risk-content p {
  margin: 0;
}

.charts-section {
  background: white;
  padding: 24px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.risk-distribution {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.risk-bar {
  display: flex;
  align-items: center;
  gap: 12px;
}

.risk-label {
  width: 100px;
  font-size: 14px;
  font-weight: 500;
}

.risk-progress {
  flex: 1;
  height: 24px;
  background: #f3f4f6;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  transition: width 0.5s ease;
}

.progress-fill.critical {
  background: #ef4444;
}

.progress-fill.high {
  background: #f97316;
}

.progress-fill.medium {
  background: #eab308;
}

.progress-fill.low {
  background: #22c55e;
}

.risk-value {
  width: 50px;
  text-align: right;
  font-weight: 600;
  font-size: 14px;
}

.actions-section {
  background: white;
  padding: 24px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.actions-grid {
  display: grid;
  gap: 12px;
}

.action-button {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 24px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.action-button:hover {
  background: #2563eb;
}
</style>
