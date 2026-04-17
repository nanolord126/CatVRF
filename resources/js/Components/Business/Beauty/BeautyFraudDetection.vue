<template>
  <div class="beauty-fraud-detection">
    <h3>Детекция мошенничества</h3>

    <div class="tabs">
      <button 
        v-for="tab in tabs" 
        :key="tab.key"
        @click="activeTab = tab.key"
        :class="{ active: activeTab === tab.key }"
        class="tab-btn"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="activeTab === 'analyze'" class="analyze-section">
      <div class="input-section">
        <div class="input-group">
          <label>ID пользователя:</label>
          <input type="number" v-model="userId" min="1" />
        </div>

        <div class="input-group">
          <label>Действие:</label>
          <select v-model="action">
            <option value="appointment_booking">Бронирование записи</option>
            <option value="payment">Оплата</option>
            <option value="cancellation">Отмена</option>
            <option value="review">Отзыв</option>
            <option value="profile_update">Обновление профиля</option>
          </select>
        </div>

        <div class="input-group">
          <label>ID записи:</label>
          <input type="number" v-model="appointmentId" min="1" />
        </div>

        <div class="input-group">
          <label>ID мастера:</label>
          <input type="number" v-model="masterId" min="1" />
        </div>

        <div class="input-group">
          <label>Сумма:</label>
          <input type="number" v-model="amount" min="0" />
        </div>
      </div>

      <button @click="analyze" :disabled="loading" class="btn-analyze">
        <Shield class="icon" />
        {{ loading ? 'Анализ...' : 'Анализировать' }}
      </button>

      <div v-if="error" class="error-message">
        {{ error }}
      </div>

      <div v-if="result" class="results-section">
        <div class="score-display">
          <div class="score-circle" :class="result.risk_level">
            <span class="score-value">{{ (result.fraud_score * 100).toFixed(0) }}%</span>
            <span class="score-label">Риск</span>
          </div>
          <div class="score-details">
            <div class="score-item">
              <span class="label">ML-оценка:</span>
              <span class="value">{{ (result.ml_score * 100).toFixed(1) }}%</span>
            </div>
            <div class="score-item">
              <span class="label">Правила:</span>
              <span class="value">{{ (result.rule_score * 100).toFixed(1) }}%</span>
            </div>
            <div class="score-item">
              <span class="label">Поведение:</span>
              <span class="value">{{ (result.behavior_score * 100).toFixed(1) }}%</span>
            </div>
          </div>
        </div>

        <div class="risk-badge" :class="result.risk_level">
          <AlertTriangle class="icon" />
          Уровень риска: {{ riskLabel }}
        </div>

        <div class="action-required" :class="result.action_required">
          <span class="icon">⚡</span>
          Требуемое действие: {{ actionLabel }}
        </div>

        <div v-if="result.flags.length > 0" class="flags-section">
          <h4>Флаги:</h4>
          <div class="flags-list">
            <span v-for="flag in result.flags" :key="flag" class="flag-badge">
              {{ formatFlag(flag) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <div v-if="activeTab === 'suspicious'" class="suspicious-section">
      <div class="input-group">
        <label>IP-адрес:</label>
        <input type="text" v-model="suspiciousIP" placeholder="192.168.1.1" />
      </div>

      <button @click="addSuspiciousIP" class="btn-add">
        <AlertCircle class="icon" />
        Добавить в список подозрительных
      </button>

      <div class="info-box">
        <Info class="icon" />
        <p>Подозрительные IP-адреса будут автоматически блокироваться при попытках действий.</p>
      </div>
    </div>

    <div v-if="activeTab === 'failed-payments'" class="failed-payments-section">
      <div class="input-group">
        <label>ID пользователя:</label>
        <input type="number" v-model="failedPaymentUserId" min="1" />
      </div>

      <button @click="recordFailedPayment" class="btn-record">
        <XCircle class="icon" />
        Записать неудачную оплату
      </button>

      <div class="info-box">
        <Info class="icon" />
        <p>Неудачные оплаты используются для выявления подозрительных паттернов поведения.</p>
      </div>
    </div>

    <div v-if="activeTab === 'statistics'" class="statistics-section">
      <div class="stats-grid">
        <div class="stat-card">
          <h4>Всего обнаружений</h4>
          <p class="stat-value">1,247</p>
          <p class="stat-trend">+12% за неделю</p>
        </div>

        <div class="stat-card">
          <h4>Критические</h4>
          <p class="stat-value critical">89</p>
          <p class="stat-trend">+5 за сегодня</p>
        </div>

        <div class="stat-card">
          <h4>Высокий риск</h4>
          <p class="stat-value high">234</p>
          <p class="stat-trend">-8% за неделю</p>
        </div>

        <div class="stat-card">
          <h4>Средний риск</h4>
          <p class="stat-value medium">456</p>
          <p class="stat-trend">+3% за неделю</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { Shield, AlertTriangle, AlertCircle, Info, XCircle } from 'lucide-vue-next';

const tabs = [
  { key: 'analyze', label: 'Анализ' },
  { key: 'suspicious', label: 'Подозрительные IP' },
  { key: 'failed-payments', label: 'Неудачные оплаты' },
  { key: 'statistics', label: 'Статистика' },
];

const activeTab = ref('analyze');
const userId = ref<number>(1);
const action = ref('appointment_booking');
const appointmentId = ref<number | null>(null);
const masterId = ref<number | null>(null);
const amount = ref<number | null>(null);
const suspiciousIP = ref('');
const failedPaymentUserId = ref<number>(1);
const loading = ref(false);
const error = ref<string | null>(null);
const result = ref<any>(null);

const riskLabel = computed(() => {
  const labels: Record<string, string> = {
    low: 'Низкий',
    medium: 'Средний',
    high: 'Высокий',
    critical: 'Критический',
  };
  return labels[result.value?.risk_level] || 'Неизвестно';
});

const actionLabel = computed(() => {
  const labels: Record<string, string> = {
    allow: 'Разрешить',
    enhanced_monitoring: 'Усиленный мониторинг',
    manual_review: 'Ручная проверка',
    block: 'Блокировать',
  };
  return labels[result.value?.action_required] || 'Неизвестно';
});

const analyze = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await fetch('/api/beauty/fraud/analyze', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': crypto.randomUUID(),
        'X-Tenant-ID': '1',
      },
      body: JSON.stringify({
        user_id: userId.value,
        action: action.value,
        appointment_id: appointmentId.value,
        master_id: masterId.value,
        amount: amount.value,
      }),
    });

    const data = await response.json();
    
    if (data.success) {
      result.value = data.data;
    } else {
      error.value = 'Ошибка анализа';
    }
  } catch (err) {
    error.value = 'Произошла ошибка. Попробуйте позже.';
  } finally {
    loading.value = false;
  }
};

const addSuspiciousIP = async () => {
  if (!suspiciousIP.value) return;

  try {
    const response = await fetch('/api/beauty/fraud/suspicious-ip', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Tenant-ID': '1',
      },
      body: JSON.stringify({
        ip_address: suspiciousIP.value,
      }),
    });

    const data = await response.json();
    
    if (data.success) {
      suspiciousIP.value = '';
    } else {
      error.value = 'Ошибка добавления IP';
    }
  } catch (err) {
    error.value = 'Произошла ошибка';
  }
};

const recordFailedPayment = async () => {
  try {
    const response = await fetch('/api/beauty/fraud/failed-payment', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Tenant-ID': '1',
      },
      body: JSON.stringify({
        user_id: failedPaymentUserId.value,
      }),
    });

    const data = await response.json();
    
    if (!data.success) {
      error.value = 'Ошибка записи';
    }
  } catch (err) {
    error.value = 'Произошла ошибка';
  }
};

const formatFlag = (flag: string): string => {
  const labels: Record<string, string> = {
    suspicious_ip: 'Подозрительный IP',
    excessive_actions: 'Чрезмерное количество действий',
    unusual_amount: 'Необычная сумма',
    recent_failed_payments: 'Недавние неудачные оплаты',
    new_account: 'Новый аккаунт',
  };
  return labels[flag] || flag;
};
</script>

<style scoped>
.beauty-fraud-detection {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px;
}

.tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  border-bottom: 2px solid #e5e7eb;
}

.tab-btn {
  padding: 12px 24px;
  background: transparent;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  color: #6b7280;
  transition: all 0.2s;
}

.tab-btn:hover {
  color: #111827;
}

.tab-btn.active {
  color: #6366f1;
  border-bottom-color: #6366f1;
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

.input-group input,
.input-group select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.btn-analyze {
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

.btn-analyze:hover:not(:disabled) {
  transform: scale(1.02);
}

.btn-analyze:disabled {
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
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.score-display {
  display: flex;
  gap: 24px;
  align-items: center;
  padding: 24px;
  background: #f9fafb;
  border-radius: 12px;
}

.score-circle {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  border: 6px solid;
}

.score-circle.low {
  border-color: #10b981;
  background: #d1fae5;
}

.score-circle.medium {
  border-color: #f59e0b;
  background: #fef3c7;
}

.score-circle.high {
  border-color: #f97316;
  background: #ffedd5;
}

.score-circle.critical {
  border-color: #ef4444;
  background: #fee2e2;
}

.score-circle .score-value {
  font-size: 28px;
  font-weight: 700;
  color: #111827;
}

.score-circle .score-label {
  font-size: 12px;
  color: #6b7280;
}

.score-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.score-item {
  display: flex;
  justify-content: space-between;
  padding: 8px 12px;
  background: white;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.score-item .label {
  font-weight: 600;
  color: #374151;
}

.score-item .value {
  font-weight: 700;
  color: #111827;
}

.risk-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
}

.risk-badge.low {
  background: #d1fae5;
  color: #059669;
}

.risk-badge.medium {
  background: #fef3c7;
  color: #d97706;
}

.risk-badge.high {
  background: #ffedd5;
  color: #ea580c;
}

.risk-badge.critical {
  background: #fee2e2;
  color: #dc2626;
}

.action-required {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
}

.action-required.allow {
  background: #d1fae5;
  color: #059669;
}

.action-required.enhanced_monitoring {
  background: #fef3c7;
  color: #d97706;
}

.action-required.manual_review {
  background: #ffedd5;
  color: #ea580c;
}

.action-required.block {
  background: #fee2e2;
  color: #dc2626;
}

.flags-section h4 {
  margin: 0 0 12px 0;
  color: #111827;
}

.flags-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.flag-badge {
  padding: 6px 12px;
  background: #fee2e2;
  color: #dc2626;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.suspicious-section,
.failed-payments-section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.btn-add,
.btn-record {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
}

.info-box {
  display: flex;
  gap: 12px;
  padding: 16px;
  background: #dbeafe;
  border-radius: 8px;
  color: #1e40af;
}

.info-box .icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.info-box p {
  margin: 0;
  font-size: 14px;
}

.statistics-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}

.stat-card {
  padding: 20px;
  background: white;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stat-card h4 {
  margin: 0 0 12px 0;
  color: #6b7280;
  font-size: 14px;
}

.stat-card .stat-value {
  font-size: 32px;
  font-weight: 700;
  margin: 8px 0;
}

.stat-card .stat-value.critical {
  color: #ef4444;
}

.stat-card .stat-value.high {
  color: #f97316;
}

.stat-card .stat-value.medium {
  color: #f59e0b;
}

.stat-card .stat-trend {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
}

.icon {
  width: 20px;
  height: 20px;
}
</style>
