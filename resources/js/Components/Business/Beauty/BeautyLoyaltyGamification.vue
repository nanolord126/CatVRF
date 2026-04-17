<template>
  <div class="beauty-loyalty-gamification">
    <h3>Программа лояльности</h3>

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

    <div v-if="activeTab === 'status'" class="status-section">
      <div class="status-cards">
        <div class="status-card current-tier">
          <Crown class="icon" />
          <h4>Ваш уровень</h4>
          <div class="tier-badge" :class="loyaltyStatus.tier">
            {{ tierLabel }}
          </div>
          <p class="points">{{ loyaltyStatus.total_points }} баллов</p>
        </div>

        <div class="status-card streak">
          <Flame class="icon" />
          <h4>Серия</h4>
          <p class="streak-count">{{ loyaltyStatus.current_streak }} дней</p>
          <p class="streak-info">
            {{ streakMultiplier }}x множитель
          </p>
        </div>

        <div class="status-card referrals">
          <Users class="icon" />
          <h4>Рефералы</h4>
          <p class="referral-count">{{ loyaltyStatus.referrals_count }}</p>
          <p class="next-tier">
            До следующего: {{ loyaltyStatus.next_tier_points }} баллов
          </p>
        </div>
      </div>

      <div class="referral-section">
        <h4>Реферальная программа</h4>
        <div class="referral-box">
          <div class="referral-code">
            <span class="label">Ваш код:</span>
            <code>{{ referralCode || 'Загрузка...' }}</code>
          </div>
          <button @click="generateReferral" class="btn-generate">
            Сгенерировать код
          </button>
        </div>
        <p class="referral-info">
          Пригласите друга и получите 500 баллов. Друг получит 1000 баллов!
        </p>
      </div>
    </div>

    <div v-if="activeTab === 'actions'" class="actions-section">
      <div class="action-inputs">
        <div class="input-group">
          <label>ID пользователя:</label>
          <input type="number" v-model="actionUserId" min="1" />
        </div>

        <div class="input-group">
          <label>Действие:</label>
          <select v-model="action">
            <option value="appointment_completed">Запись завершена (+100)</option>
            <option value="review_left">Оставлен отзыв (+50)</option>
            <option value="video_call_completed">Видеозвонок завершен (+25)</option>
            <option value="profile_completed">Профиль заполнен (+200)</option>
            <option value="first_booking">Первая запись (+500)</option>
          </select>
        </div>

        <div class="input-group">
          <label>ID записи:</label>
          <input type="number" v-model="appointmentId" min="1" />
        </div>

        <div class="input-group">
          <label>Реферальный код:</label>
          <input type="text" v-model="referralCodeInput" placeholder="BEAUTYXXXXXXXX" />
        </div>
      </div>

      <button @click="processAction" :disabled="loading" class="btn-process">
        <Star class="icon" />
        {{ loading ? 'Обработка...' : 'Начислить баллы' }}
      </button>

      <div v-if="actionResult" class="action-result">
        <h4>Результат:</h4>
        <div class="result-item">
          <span class="label">Начислено:</span>
          <span class="value">{{ actionResult.points_earned }} баллов</span>
        </div>
        <div class="result-item">
          <span class="label">Базовые баллы:</span>
          <span class="value">{{ actionResult.base_points }}</span>
        </div>
        <div class="result-item">
          <span class="label">Множитель серии:</span>
          <span class="value">{{ actionResult.streak_multiplier }}x</span>
        </div>
        <div class="result-item">
          <span class="label">Всего баллов:</span>
          <span class="value">{{ actionResult.total_points }}</span>
        </div>
        <div v-if="actionResult.referral_bonus" class="result-item bonus">
          <span class="label">Бонус за реферал:</span>
          <span class="value">+{{ actionResult.referral_bonus.referee_bonus }}</span>
        </div>
      </div>
    </div>

    <div v-if="activeTab === 'tiers'" class="tiers-section">
      <div class="tier-progress">
        <div class="progress-bar">
          <div class="progress-fill" :style="{ width: progressPercent }"></div>
        </div>
        <div class="tier-labels">
          <span>Bronze</span>
          <span>Silver</span>
          <span>Gold</span>
          <span>Platinum</span>
        </div>
      </div>

      <div class="tier-info">
        <div class="tier-card" :class="{ active: loyaltyStatus.tier === 'bronze' }">
          <h4>Bronze</h4>
          <p>0 - 1999 баллов</p>
          <ul>
            <li>Базовые привилегии</li>
            <li>1% кэшбэк</li>
          </ul>
        </div>

        <div class="tier-card" :class="{ active: loyaltyStatus.tier === 'silver' }">
          <h4>Silver</h4>
          <p>2000 - 4999 баллов</p>
          <ul>
            <li>Все Bronze привилегии</li>
            <li>2% кэшбэк</li>
            <li>Приоритетная запись</li>
          </ul>
        </div>

        <div class="tier-card" :class="{ active: loyaltyStatus.tier === 'gold' }">
          <h4>Gold</h4>
          <p>5000 - 9999 баллов</p>
          <ul>
            <li>Все Silver привилегии</li>
            <li>3% кэшбэк</li>
            <li>Бесплатная консультация</li>
            <li>Скидка 10%</li>
          </ul>
        </div>

        <div class="tier-card" :class="{ active: loyaltyStatus.tier === 'platinum' }">
          <h4>Platinum</h4>
          <p>10000+ баллов</p>
          <ul>
            <li>Все Gold привилегии</li>
            <li>5% кэшбэк</li>
            <li>Персональный мастер</li>
            <li>Скидка 20%</li>
            <li>VIP поддержка</li>
          </ul>
        </div>
      </div>
    </div>

    <div v-if="error" class="error-message">
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Crown, Flame, Users, Star } from 'lucide-vue-next';

const tabs = [
  { key: 'status', label: 'Статус' },
  { key: 'actions', label: 'Действия' },
  { key: 'tiers', label: 'Уровни' },
];

const activeTab = ref('status');
const actionUserId = ref<number>(1);
const action = ref('appointment_completed');
const appointmentId = ref<number | null>(null);
const referralCodeInput = ref('');
const loading = ref(false);
const error = ref<string | null>(null);
const loyaltyStatus = ref<any>(null);
const referralCode = ref<string | null>(null);
const actionResult = ref<any>(null);

const tierLabel = computed(() => {
  const labels: Record<string, string> = {
    bronze: 'Бронза',
    silver: 'Серебро',
    gold: 'Золото',
    platinum: 'Платина',
  };
  return labels[loyaltyStatus.value?.tier] || 'Бронза';
});

const streakMultiplier = computed(() => {
  const streak = loyaltyStatus.value?.current_streak || 0;
  if (streak >= 7) return '2.0';
  if (streak >= 3) return '1.5';
  return '1.0';
});

const progressPercent = computed(() => {
  const points = loyaltyStatus.value?.total_points || 0;
  if (points >= 10000) return '100%';
  if (points >= 5000) return '75%';
  if (points >= 2000) return '50%';
  return `${(points / 2000 * 25).toFixed(0)}%`;
});

const loadStatus = async () => {
  try {
    const response = await fetch(`/api/beauty/loyalty/status?user_id=${actionUserId.value}`, {
      headers: {
        'X-Tenant-ID': '1',
      },
    });

    const data = await response.json();
    
    if (data.success) {
      loyaltyStatus.value = data.data;
    }
  } catch (err) {
    error.value = 'Ошибка загрузки статуса';
  }
};

const generateReferral = async () => {
  try {
    const response = await fetch('/api/beauty/loyalty/referral/generate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Tenant-ID': '1',
      },
      body: JSON.stringify({
        user_id: actionUserId.value,
      }),
    });

    const data = await response.json();
    
    if (data.success) {
      referralCode.value = data.data.referral_code;
    }
  } catch (err) {
    error.value = 'Ошибка генерации кода';
  }
};

const processAction = async () => {
  loading.value = true;
  error.value = null;

  try {
    const response = await fetch('/api/beauty/loyalty/action', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Correlation-ID': crypto.randomUUID(),
        'X-Tenant-ID': '1',
      },
      body: JSON.stringify({
        user_id: actionUserId.value,
        action: action.value,
        appointment_id: appointmentId.value,
        referral_code: referralCodeInput.value || null,
      }),
    });

    const data = await response.json();
    
    if (data.success) {
      actionResult.value = data.data;
      await loadStatus();
    } else {
      error.value = 'Ошибка обработки действия';
    }
  } catch (err) {
    error.value = 'Произошла ошибка. Попробуйте позже.';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadStatus();
});
</script>

<style scoped>
.beauty-loyalty-gamification {
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

.status-section {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.status-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.status-card {
  padding: 24px;
  background: white;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  text-align: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.status-card .icon {
  width: 32px;
  height: 32px;
  margin-bottom: 12px;
  color: #6366f1;
}

.status-card h4 {
  margin: 0 0 12px 0;
  color: #111827;
}

.status-card .points {
  font-size: 24px;
  font-weight: 700;
  color: #10b981;
  margin: 8px 0;
}

.status-card .streak-count {
  font-size: 28px;
  font-weight: 700;
  color: #f59e0b;
  margin: 8px 0;
}

.status-card .streak-info {
  color: #6b7280;
  font-size: 14px;
}

.status-card .referral-count {
  font-size: 28px;
  font-weight: 700;
  color: #6366f1;
  margin: 8px 0;
}

.status-card .next-tier {
  color: #6b7280;
  font-size: 14px;
}

.tier-badge {
  display: inline-block;
  padding: 6px 16px;
  border-radius: 20px;
  font-weight: 700;
  font-size: 14px;
}

.tier-badge.bronze {
  background: #d97706;
  color: white;
}

.tier-badge.silver {
  background: #6b7280;
  color: white;
}

.tier-badge.gold {
  background: #f59e0b;
  color: white;
}

.tier-badge.platinum {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.referral-section {
  padding: 20px;
  background: #f9fafb;
  border-radius: 8px;
}

.referral-section h4 {
  margin: 0 0 16px 0;
  color: #111827;
}

.referral-box {
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
}

.referral-code {
  flex: 1;
  display: flex;
  gap: 8px;
  align-items: center;
}

.referral-code .label {
  font-weight: 600;
  color: #374151;
}

.referral-code code {
  padding: 8px 16px;
  background: #1f2937;
  color: #10b981;
  border-radius: 6px;
  font-family: monospace;
  font-size: 14px;
}

.btn-generate {
  padding: 8px 16px;
  background: #6366f1;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}

.referral-info {
  margin-top: 12px;
  color: #6b7280;
  font-size: 14px;
}

.actions-section {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.action-inputs {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
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

.btn-process {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 14px 28px;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  transition: transform 0.2s;
}

.btn-process:hover:not(:disabled) {
  transform: scale(1.02);
}

.btn-process:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.action-result {
  padding: 20px;
  background: #f9fafb;
  border-radius: 8px;
}

.action-result h4 {
  margin: 0 0 16px 0;
  color: #111827;
}

.result-item {
  display: flex;
  justify-content: space-between;
  padding: 12px;
  background: white;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  margin-bottom: 8px;
}

.result-item.bonus {
  background: #d1fae5;
  border-color: #10b981;
}

.result-item .label {
  font-weight: 600;
  color: #374151;
}

.result-item .value {
  font-weight: 700;
  color: #111827;
}

.tiers-section {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.tier-progress {
  padding: 20px;
  background: #f9fafb;
  border-radius: 8px;
}

.progress-bar {
  height: 12px;
  background: #e5e7eb;
  border-radius: 6px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981 0%, #6366f1 100%);
  transition: width 0.5s ease;
}

.tier-labels {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
  font-weight: 600;
  color: #6b7280;
}

.tier-info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}

.tier-card {
  padding: 20px;
  background: white;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  transition: all 0.2s;
}

.tier-card.active {
  border-color: #6366f1;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.tier-card h4 {
  margin: 0 0 8px 0;
  color: #111827;
}

.tier-card p {
  margin: 0 0 12px 0;
  color: #6b7280;
  font-size: 14px;
}

.tier-card ul {
  margin: 0;
  padding-left: 20px;
  color: #374151;
  font-size: 14px;
}

.tier-card li {
  margin: 4px 0;
}

.error-message {
  margin-top: 16px;
  padding: 12px;
  background: #fee2e2;
  color: #dc2626;
  border-radius: 6px;
  text-align: center;
}

.icon {
  width: 24px;
  height: 24px;
}
</style>
