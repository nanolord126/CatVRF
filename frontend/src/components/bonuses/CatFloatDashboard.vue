<template>
  <div class="catfloat-dashboard">
    <div class="header">
      <h2>CatFloat Rewards</h2>
      <p class="subtitle">Your 15-day Smart Hold bonus system</p>
    </div>

    <div v-if="loading" class="loading">
      Loading dashboard...
    </div>

    <div v-else-if="error" class="error">
      {{ error }}
    </div>

    <div v-else class="dashboard-content">
      <!-- Balance Cards -->
      <div class="balance-cards">
        <div class="balance-card locked">
          <div class="card-icon">🔒</div>
          <div class="card-content">
            <h3>Locked Balance</h3>
            <p class="amount">{{ formatCurrency(dashboard.balances.locked_balance) }}</p>
            <p class="label">Vesting over 15 days</p>
          </div>
        </div>

        <div class="balance-card available">
          <div class="card-icon">💰</div>
          <div class="card-content">
            <h3>Available Balance</h3>
            <p class="amount">{{ formatCurrency(dashboard.balances.available_balance) }}</p>
            <p class="label">Ready to spend</p>
          </div>
        </div>

        <div class="balance-card total">
          <div class="card-icon">💎</div>
          <div class="card-content">
            <h3>Total Balance</h3>
            <p class="amount">{{ formatCurrency(dashboard.balances.total_balance) }}</p>
            <p class="label">All bonuses combined</p>
          </div>
        </div>
      </div>

      <!-- Streak Card -->
      <div class="streak-card" :class="{ premium: dashboard.streak.is_premium }">
        <div class="streak-header">
          <h3>🔥 Activity Streak</h3>
          <span class="streak-level" :style="{ color: dashboard.streak.color }">
            {{ dashboard.streak.streak_level }}
          </span>
        </div>
        <div class="streak-content">
          <div class="streak-days">
            <span class="days">{{ dashboard.streak.streak_days }}</span>
            <span class="label">days</span>
          </div>
          <div class="streak-info">
            <p><strong>Multiplier:</strong> {{ dashboard.streak.multiplier }}x</p>
            <p><strong>Acceleration Bonus:</strong> +{{ dashboard.streak.acceleration_bonus }} days</p>
            <p v-if="dashboard.streak.next_level">
              <strong>Next Level:</strong> {{ dashboard.streak.next_level }} ({{ dashboard.streak.days_to_next_level }} days)
            </p>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="quick-actions">
        <button @click="refreshDashboard" class="action-btn refresh">
          🔄 Refresh
        </button>
        <button @click="claimYield" class="action-btn claim">
          💵 Claim Yield
        </button>
        <button @click="goToBatches" class="action-btn batches">
          📋 View Batches
        </button>
        <button @click="goToMarketplace" class="action-btn marketplace">
          🏪 Marketplace
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { catfloatApi, type CatFloatDashboard } from '@/services/catfloatApi';

const dashboard = ref<CatFloatDashboard>({
  balances: {
    locked_balance: 0,
    available_balance: 0,
    total_balance: 0,
  },
  streak: {
    streak_days: 0,
    streak_level: 'Bronze',
    multiplier: 1.0,
    color: '#cd7f32',
    is_premium: false,
    acceleration_bonus: 0,
    next_level: null,
    days_to_next_level: null,
  },
  multiplier: 1.0,
});

const loading = ref(true);
const error = ref<string | null>(null);

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(amount);
};

const refreshDashboard = async () => {
  loading.value = true;
  error.value = null;
  
  try {
    dashboard.value = await catfloatApi.getDashboard();
  } catch (err: any) {
    error.value = err.message || 'Failed to load dashboard';
  } finally {
    loading.value = false;
  }
};

const claimYield = async () => {
  try {
    await catfloatApi.claimYield();
    await refreshDashboard();
  } catch (err: any) {
    error.value = err.message || 'Failed to claim yield';
  }
};

const goToBatches = () => {
  // Navigate to batches list
  console.log('Navigate to batches');
};

const goToMarketplace = () => {
  // Navigate to marketplace
  console.log('Navigate to marketplace');
};

onMounted(() => {
  refreshDashboard();
});
</script>

<style scoped>
.catfloat-dashboard {
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  margin-bottom: 32px;
}

.header h2 {
  font-size: 28px;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 8px;
}

.subtitle {
  color: #666;
  font-size: 14px;
}

.balance-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.balance-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 16px;
}

.balance-card.locked {
  border-left: 4px solid #f59e0b;
}

.balance-card.available {
  border-left: 4px solid #10b981;
}

.balance-card.total {
  border-left: 4px solid #6366f1;
}

.card-icon {
  font-size: 40px;
}

.card-content h3 {
  font-size: 14px;
  color: #666;
  margin-bottom: 8px;
}

.card-content .amount {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 4px;
}

.card-content .label {
  font-size: 12px;
  color: #999;
}

.streak-card {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  padding: 24px;
  color: white;
  margin-bottom: 24px;
}

.streak-card.premium {
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.streak-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.streak-header h3 {
  font-size: 18px;
  font-weight: 600;
}

.streak-level {
  font-size: 14px;
  font-weight: 600;
  text-transform: uppercase;
}

.streak-content {
  display: flex;
  gap: 24px;
}

.streak-days {
  text-align: center;
}

.streak-days .days {
  font-size: 48px;
  font-weight: 700;
  line-height: 1;
}

.streak-days .label {
  font-size: 14px;
  opacity: 0.8;
}

.streak-info p {
  margin: 4px 0;
  font-size: 14px;
  opacity: 0.9;
}

.quick-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.action-btn {
  padding: 12px 24px;
  border-radius: 8px;
  border: none;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.action-btn.refresh {
  background: #f3f4f6;
  color: #1a1a1a;
}

.action-btn.claim {
  background: #10b981;
  color: white;
}

.action-btn.batches {
  background: #6366f1;
  color: white;
}

.action-btn.marketplace {
  background: #f59e0b;
  color: white;
}

.loading, .error {
  text-align: center;
  padding: 48px;
  color: #666;
}

.error {
  color: #ef4444;
}
</style>
