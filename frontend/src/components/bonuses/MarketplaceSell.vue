<template>
  <div class="marketplace-sell">
    <div class="header">
      <h3>🏪 Bonus Marketplace</h3>
      <button @click="loadMarketplaceInfo" class="refresh-btn" :disabled="loading">
        🔄
      </button>
    </div>

    <div v-if="loading" class="loading">
      Loading marketplace info...
    </div>

    <div v-else-if="error" class="error">
      {{ error }}
    </div>

    <div v-else>
      <div class="marketplace-info">
        <div class="info-card">
          <span class="label">Commission Rate</span>
          <span class="value">{{ (marketplaceInfo.commission_rate * 100).toFixed(0) }}%</span>
        </div>
        <div class="info-card">
          <span class="label">Min Discount</span>
          <span class="value">{{ marketplaceInfo.min_discount }}%</span>
        </div>
        <div class="info-card">
          <span class="label">Max Discount</span>
          <span class="value">{{ marketplaceInfo.max_discount }}%</span>
        </div>
      </div>

      <div class="sell-form">
        <div class="form-group">
          <label>Select Batch to Sell</label>
          <select v-model="selectedBatchId" class="form-select">
            <option value="">-- Select a batch --</option>
            <option v-for="batch in availableBatches" :key="batch.id" :value="batch.id">
              #{{ batch.id }} - {{ formatCurrency(batch.remaining_locked) }} ({{ batch.source }})
            </option>
          </select>
        </div>

        <div class="form-group">
          <label>Discount Percentage ({{ marketplaceInfo.min_discount }}-{{ marketplaceInfo.max_discount }}%)</label>
          <input
            v-model.number="discount"
            type="range"
            :min="marketplaceInfo.min_discount"
            :max="marketplaceInfo.max_discount"
            step="1"
            class="form-range"
          />
          <div class="range-value">{{ discount }}%</div>
        </div>

        <div class="preview">
          <div class="preview-row">
            <span class="label">Original Amount:</span>
            <span class="value">{{ formatCurrency(selectedBatch?.original_amount || 0) }}</span>
          </div>
          <div class="preview-row">
            <span class="label">Discount:</span>
            <span class="value discount">-{{ discount }}%</span>
          </div>
          <div class="preview-row">
            <span class="label">Sale Price:</span>
            <span class="value">{{ formatCurrency(salePrice) }}</span>
          </div>
          <div class="preview-row">
            <span class="label">Commission ({{ (marketplaceInfo.commission_rate * 100).toFixed(0) }}%):</span>
            <span class="value commission">-{{ formatCurrency(commission) }}</span>
          </div>
          <div class="preview-row total">
            <span class="label">You Receive:</span>
            <span class="value">{{ formatCurrency(netAmount) }}</span>
          </div>
        </div>

        <button
          @click="sellBonus"
          class="sell-btn"
          :disabled="!canSell || selling"
        >
          {{ selling ? 'Selling...' : 'Sell on Marketplace' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { catfloatApi, type MarketplaceInfo, type BonusBatch } from '@/services/catfloatApi';

const marketplaceInfo = ref<MarketplaceInfo>({
  commission_rate: 0.12,
  min_discount: 15,
  max_discount: 40,
});

const availableBatches = ref<BonusBatch[]>([]);
const selectedBatchId = ref<number | null>(null);
const discount = ref(20);

const loading = ref(true);
const error = ref<string | null>(null);
const selling = ref(false);

const selectedBatch = computed(() => {
  return availableBatches.value.find(b => b.id === selectedBatchId.value);
});

const salePrice = computed(() => {
  if (!selectedBatch.value) return 0;
  return selectedBatch.value.remaining_locked * (1 - discount.value / 100);
});

const commission = computed(() => {
  return salePrice.value * marketplaceInfo.value.commission_rate;
});

const netAmount = computed(() => {
  return salePrice.value - commission.value;
});

const canSell = computed(() => {
  return selectedBatchId.value !== null
    && discount.value >= marketplaceInfo.value.min_discount
    && discount.value <= marketplaceInfo.value.max_discount;
});

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(amount);
};

const loadMarketplaceInfo = async () => {
  loading.value = true;
  error.value = null;
  
  try {
    marketplaceInfo.value = await catfloatApi.getMarketplaceInfo();
    availableBatches.value = (await catfloatApi.getBatches()).filter(
      b => !b.is_fully_vested && b.remaining_locked > 0
    );
  } catch (err: any) {
    error.value = err.message || 'Failed to load marketplace info';
  } finally {
    loading.value = false;
  }
};

const sellBonus = async () => {
  if (!selectedBatchId.value) return;

  selling.value = true;
  
  try {
    await catfloatApi.sellLockedBonus({
      batch_id: selectedBatchId.value,
      discount: discount.value,
    });
    
    // Reset form and reload
    selectedBatchId.value = null;
    discount.value = 20;
    await loadMarketplaceInfo();
  } catch (err: any) {
    error.value = err.message || 'Failed to sell bonus';
  } finally {
    selling.value = false;
  }
};

onMounted(() => {
  loadMarketplaceInfo();
});
</script>

<style scoped>
.marketplace-sell {
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

.loading, .error {
  text-align: center;
  padding: 32px;
  color: #666;
  font-size: 14px;
}

.error {
  color: #ef4444;
}

.marketplace-info {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}

.info-card {
  background: #f9fafb;
  border-radius: 8px;
  padding: 16px;
  text-align: center;
}

.info-card .label {
  display: block;
  font-size: 12px;
  color: #666;
  margin-bottom: 8px;
}

.info-card .value {
  font-size: 18px;
  font-weight: 700;
  color: #1a1a1a;
}

.sell-form {
  border-top: 1px solid #e5e7eb;
  padding-top: 20px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 8px;
}

.form-select {
  width: 100%;
  padding: 10px 12px;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  font-size: 14px;
  color: #1a1a1a;
}

.form-range {
  width: 100%;
  height: 6px;
  border-radius: 3px;
  background: #e5e7eb;
  outline: none;
  -webkit-appearance: none;
}

.form-range::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #f59e0b;
  cursor: pointer;
}

.range-value {
  text-align: center;
  font-weight: 600;
  color: #f59e0b;
  margin-top: 8px;
}

.preview {
  background: #f9fafb;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 20px;
}

.preview-row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px solid #e5e7eb;
}

.preview-row:last-child {
  border-bottom: none;
}

.preview-row.total {
  border-top: 2px solid #1a1a1a;
  margin-top: 8px;
  padding-top: 12px;
  font-weight: 700;
}

.preview-row .label {
  color: #666;
  font-size: 14px;
}

.preview-row .value {
  font-weight: 600;
  color: #1a1a1a;
  font-size: 14px;
}

.preview-row .value.discount {
  color: #10b981;
}

.preview-row .value.commission {
  color: #ef4444;
}

.sell-btn {
  width: 100%;
  padding: 12px;
  border-radius: 8px;
  border: none;
  background: #f59e0b;
  color: white;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.sell-btn:hover:not(:disabled) {
  background: #d97706;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.sell-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
