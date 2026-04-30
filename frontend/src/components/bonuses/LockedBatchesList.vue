<template>
  <div class="locked-batches-list">
    <div class="header">
      <h2>Locked Bonus Batches</h2>
      <p class="subtitle">Your bonus batches with 15-day Smart Hold</p>
    </div>

    <div v-if="loading" class="loading">
      Loading batches...
    </div>

    <div v-else-if="error" class="error">
      {{ error }}
    </div>

    <div v-else-if="batches.length === 0" class="empty">
      <div class="empty-icon">📦</div>
      <p>No locked bonus batches yet</p>
      <p class="empty-text">Start making purchases to earn locked bonuses</p>
    </div>

    <div v-else class="batches-grid">
      <div
        v-for="batch in batches"
        :key="batch.id"
        class="batch-card"
        :class="{ vested: batch.is_fully_vested }"
      >
        <div class="batch-header">
          <span class="batch-id">#{{ batch.id }}</span>
          <span class="batch-source">{{ formatSource(batch.source) }}</span>
        </div>

        <div class="batch-amounts">
          <div class="amount-row">
            <span class="label">Original:</span>
            <span class="value">{{ formatCurrency(batch.original_amount) }}</span>
          </div>
          <div class="amount-row">
            <span class="label">Locked:</span>
            <span class="value locked">{{ formatCurrency(batch.remaining_locked) }}</span>
          </div>
          <div class="amount-row">
            <span class="label">Unlocked:</span>
            <span class="value unlocked">{{ formatCurrency(batch.unlocked_amount) }}</span>
          </div>
        </div>

        <div class="batch-progress">
          <div class="progress-bar">
            <div
              class="progress-fill"
              :style="{ width: `${batch.unlock_percentage}%` }"
            ></div>
          </div>
          <span class="progress-text">{{ batch.unlock_percentage.toFixed(1) }}% unlocked</span>
        </div>

        <div class="batch-info">
          <div class="info-row">
            <span class="label">Vested until:</span>
            <span class="value">{{ formatDate(batch.vested_until) }}</span>
          </div>
          <div class="info-row">
            <span class="label">Days remaining:</span>
            <span class="value">{{ batch.days_remaining }} days</span>
          </div>
        </div>

        <div class="batch-actions">
          <button
            v-if="!batch.is_fully_vested && batch.remaining_locked > 0"
            @click="instantUnlock(batch)"
            class="action-btn instant"
            :disabled="unlocking"
          >
            ⚡ Instant Unlock
          </button>
          <button
            v-if="!batch.is_fully_vested"
            @click="sellBatch(batch)"
            class="action-btn sell"
            :disabled="selling"
          >
            🏪 Sell on Marketplace
          </button>
          <span v-else class="status vested">✅ Fully Vested</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { catfloatApi, type BonusBatch } from '@/services/catfloatApi';

const batches = ref<BonusBatch[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const unlocking = ref(false);
const selling = ref(false);

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(amount);
};

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU');
};

const formatSource = (source: string): string => {
  const sources: Record<string, string> = {
    purchase: 'Purchase',
    referral: 'Referral',
    streak_bonus: 'Streak Bonus',
    campaign: 'Campaign',
    marketplace_sale: 'Marketplace Sale',
    manual: 'Manual',
  };
  return sources[source] || source;
};

const loadBatches = async () => {
  loading.value = true;
  error.value = null;
  
  try {
    batches.value = await catfloatApi.getBatches();
  } catch (err: any) {
    error.value = err.message || 'Failed to load batches';
  } finally {
    loading.value = false;
  }
};

const instantUnlock = async (batch: BonusBatch) => {
  // Calculate instant unlock price (e.g., 10% of remaining locked)
  const price = batch.remaining_locked * 0.1;
  
  if (!confirm(`Instant unlock for ${formatCurrency(price)}?`)) {
    return;
  }

  unlocking.value = true;
  
  try {
    await catfloatApi.unlockBonus({
      batch_id: batch.id,
      instant: true,
      instant_unlock_price: price,
    });
    await loadBatches();
  } catch (err: any) {
    error.value = err.message || 'Failed to unlock bonus';
  } finally {
    unlocking.value = false;
  }
};

const sellBatch = async (batch: BonusBatch) => {
  // Prompt for discount percentage
  const discount = prompt('Enter discount percentage (15-40):', '20');
  
  if (!discount || isNaN(Number(discount))) {
    return;
  }

  const discountNum = Number(discount);
  
  if (discountNum < 15 || discountNum > 40) {
    error.value = 'Discount must be between 15% and 40%';
    return;
  }

  selling.value = true;
  
  try {
    await catfloatApi.sellLockedBonus({
      batch_id: batch.id,
      discount: discountNum,
    });
    await loadBatches();
  } catch (err: any) {
    error.value = err.message || 'Failed to sell bonus';
  } finally {
    selling.value = false;
  }
};

onMounted(() => {
  loadBatches();
});
</script>

<style scoped>
.locked-batches-list {
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

.loading, .error, .empty {
  text-align: center;
  padding: 48px;
  color: #666;
}

.error {
  color: #ef4444;
}

.empty-icon {
  font-size: 64px;
  margin-bottom: 16px;
}

.empty-text {
  color: #999;
  font-size: 14px;
}

.batches-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 16px;
}

.batch-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border-left: 4px solid #f59e0b;
}

.batch-card.vested {
  border-left-color: #10b981;
}

.batch-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.batch-id {
  font-weight: 600;
  color: #1a1a1a;
}

.batch-source {
  font-size: 12px;
  padding: 4px 8px;
  background: #f3f4f6;
  border-radius: 4px;
  color: #666;
}

.batch-amounts {
  margin-bottom: 16px;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  padding: 4px 0;
}

.amount-row .label {
  color: #666;
  font-size: 14px;
}

.amount-row .value {
  font-weight: 600;
  color: #1a1a1a;
}

.amount-row .value.locked {
  color: #f59e0b;
}

.amount-row .value.unlocked {
  color: #10b981;
}

.batch-progress {
  margin-bottom: 16px;
}

.progress-bar {
  height: 8px;
  background: #f3f4f6;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #f59e0b 0%, #10b981 100%);
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 12px;
  color: #666;
}

.batch-info {
  margin-bottom: 16px;
  padding-top: 16px;
  border-top: 1px solid #f3f4f6;
}

.info-row {
  display: flex;
  justify-content: space-between;
  padding: 4px 0;
}

.info-row .label {
  color: #666;
  font-size: 13px;
}

.info-row .value {
  font-weight: 600;
  color: #1a1a1a;
  font-size: 13px;
}

.batch-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.action-btn {
  padding: 8px 16px;
  border-radius: 6px;
  border: none;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.action-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.action-btn.instant {
  background: #6366f1;
  color: white;
}

.action-btn.sell {
  background: #f59e0b;
  color: white;
}

.status.vested {
  color: #10b981;
  font-weight: 600;
  font-size: 13px;
}
</style>
