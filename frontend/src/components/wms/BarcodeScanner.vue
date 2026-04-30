<template>
  <div class="barcode-scanner">
    <div class="scanner-header">
      <h2>Barcode Scanner</h2>
      <div class="scanner-mode">
        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="autoSubmit" class="checkbox" />
          <span>Auto Submit</span>
        </label>
      </div>
    </div>

    <div class="scanner-input-section">
      <div class="input-group">
        <input
          ref="barcodeInput"
          v-model="barcode"
          @keyup.enter="processBarcode"
          @focus="isFocused = true"
          @blur="isFocused = false"
          type="text"
          placeholder="Scan barcode or type manually..."
          class="scanner-input"
          :class="{ 'input-focused': isFocused }"
          :disabled="processing"
        />
        <button @click="processBarcode" class="btn btn-primary" :disabled="!barcode || processing">
          <Scan class="w-4 h-4 mr-2" />
          Scan
        </button>
        <button @click="clearBarcode" class="btn btn-secondary" :disabled="!barcode">
          <X class="w-4 h-4" />
        </button>
      </div>
      <div class="input-hint">
        <Info class="w-4 h-4" />
        Press Enter or click Scan to process barcode
      </div>
    </div>

    <div v-if="result" class="result-section">
      <div class="result-card" :class="{ 'result-success': result.found, 'result-error': !result.found }">
        <CheckCircle v-if="result.found" class="w-8 h-8 text-green-500" />
        <XCircle v-else class="w-8 h-8 text-red-500" />
        <div class="result-content">
          <div class="result-title">
            {{ result.found ? 'Item Found' : 'Item Not Found' }}
          </div>
          <div v-if="result.found" class="result-details">
            <div class="detail-row">
              <span class="label">SKU:</span>
              <span class="value">{{ result.item.sku }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Name:</span>
              <span class="value">{{ result.item.name }}</span>
            </div>
            <div class="detail-row">
              <span class="label">Stock:</span>
              <span class="value" :class="{ 'text-red-600': result.item.current_stock < result.item.min_stock_threshold }">
                {{ result.item.current_stock }}
              </span>
            </div>
            <div class="detail-row" v-if="result.item.current_stock < result.item.min_stock_threshold">
              <span class="label text-red-600">Low Stock Alert</span>
            </div>
          </div>
          <div v-else class="error-message">
            {{ result.message }}
          </div>
        </div>
        <div v-if="result.found" class="result-actions">
          <button @click="adjustStock(result.item)" class="btn btn-sm btn-primary">
            Adjust Stock
          </button>
          <button @click="viewDetails(result.item)" class="btn btn-sm btn-secondary">
            View Details
          </button>
        </div>
      </div>
    </div>

    <div v-if="recentScans.length > 0" class="recent-scans-section">
      <h3>Recent Scans</h3>
      <div class="scans-list">
        <div
          v-for="scan in recentScans"
          :key="scan.id"
          class="scan-item"
          @click="loadScan(scan)"
        >
          <Barcode class="w-4 h-4" />
          <div class="scan-info">
            <div class="scan-barcode">{{ scan.barcode }}</div>
            <div class="scan-time">{{ formatTime(scan.scanned_at) }}</div>
          </div>
          <div class="scan-status" :class="scan.found ? 'status-success' : 'status-error'">
            {{ scan.found ? 'Found' : 'Not Found' }}
          </div>
        </div>
      </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <div v-if="showAdjustModal" class="modal-overlay" @click="closeAdjustModal">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>Adjust Stock</h3>
          <button @click="closeAdjustModal" class="btn-icon">
            <X class="w-5 h-5" />
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Item</label>
            <input :value="adjustItem?.name" disabled class="input" />
          </div>
          <div class="form-group">
            <label>Current Stock</label>
            <input :value="adjustItem?.current_stock" disabled class="input" />
          </div>
          <div class="form-group">
            <label>Adjustment Type</label>
            <select v-model="adjustmentType" class="select">
              <option value="in">Add Stock</option>
              <option value="out">Remove Stock</option>
              <option value="adjustment">Adjustment</option>
            </select>
          </div>
          <div class="form-group">
            <label>Quantity</label>
            <input v-model.number="adjustmentQuantity" type="number" min="1" class="input" />
          </div>
          <div class="form-group">
            <label>Reason</label>
            <select v-model="adjustmentReason" class="select">
              <option value="count">Count Adjustment</option>
              <option value="damage">Damage</option>
              <option value="loss">Loss</option>
              <option value="return">Return</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Notes</label>
            <textarea v-model="adjustmentNotes" class="textarea" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeAdjustModal" class="btn btn-secondary">Cancel</button>
          <button @click="submitAdjustment" class="btn btn-primary" :disabled="!adjustmentQuantity">
            Submit
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue';
import { Scan, X, Info, CheckCircle, XCircle, Barcode } from 'lucide-vue-next';
import { useWMSApi } from '@/composables/useWMSApi';

const { lookupBarcode, adjustStock: apiAdjustStock } = useWMSApi();

const barcode = ref('');
const isFocused = ref(false);
const processing = ref(false);
const autoSubmit = ref(true);
const result = ref<any>(null);
const recentScans = ref<any[]>([]);
const barcodeInput = ref<HTMLInputElement | null>(null);

const showAdjustModal = ref(false);
const adjustItem = ref<any>(null);
const adjustmentType = ref('in');
const adjustmentQuantity = ref(1);
const adjustmentReason = ref('count');
const adjustmentNotes = ref('');

const processBarcode = async () => {
  if (!barcode.value || processing.value) return;

  processing.value = true;
  try {
    const response = await lookupBarcode(barcode.value);
    result.value = response;

    recentScans.value.unshift({
      id: Date.now(),
      barcode: barcode.value,
      found: response.found,
      item: response.item,
      scanned_at: new Date(),
    });

    if (recentScans.value.length > 10) {
      recentScans.value = recentScans.value.slice(0, 10);
    }

    if (autoSubmit.value && response.found) {
      // Auto-submit logic here if needed
    }
  } catch (error) {
    result.value = {
      found: false,
      message: 'Error processing barcode',
    };
  } finally {
    processing.value = false;
    if (autoSubmit.value) {
      barcode.value = '';
      nextTick(() => barcodeInput.value?.focus());
    }
  }
};

const clearBarcode = () => {
  barcode.value = '';
  result.value = null;
  barcodeInput.value?.focus();
};

const adjustStock = (item: any) => {
  adjustItem.value = item;
  showAdjustModal.value = true;
};

const viewDetails = (item: any) => {
  // Navigate to item details
  window.location.href = `/wms/inventory/${item.id}`;
};

const closeAdjustModal = () => {
  showAdjustModal.value = false;
  adjustItem.value = null;
  adjustmentQuantity.value = 1;
  adjustmentNotes.value = '';
};

const submitAdjustment = async () => {
  if (!adjustItem.value || !adjustmentQuantity.value) return;

  try {
    await apiAdjustStock({
      inventory_item_id: adjustItem.value.id,
      type: adjustmentType.value,
      quantity: adjustmentType.value === 'out' ? -adjustmentQuantity.value : adjustmentQuantity.value,
      reason: adjustmentReason.value,
      notes: adjustmentNotes.value,
    });

    closeAdjustModal();
    result.value = null;
  } catch (error) {
    console.error('Failed to adjust stock:', error);
  }
};

const loadScan = (scan: any) => {
  barcode.value = scan.barcode;
  result.value = scan.found ? { found: true, item: scan.item } : { found: false, message: 'Item not found' };
};

const formatTime = (date: Date) => {
  return new Date(date).toLocaleTimeString();
};

onMounted(() => {
  barcodeInput.value?.focus();
});
</script>

<style scoped>
.barcode-scanner {
  padding: 24px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.scanner-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.scanner-header h2 {
  font-size: 20px;
  font-weight: 600;
  color: #1e293b;
}

.scanner-mode {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #64748b;
}

.checkbox {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.scanner-input-section {
  margin-bottom: 24px;
}

.input-group {
  display: flex;
  gap: 8px;
}

.scanner-input {
  flex: 1;
  padding: 12px 16px;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 16px;
  transition: all 0.2s;
}

.scanner-input:focus {
  outline: none;
  border-color: #3b82f6;
}

.input-focused {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.scanner-input:disabled {
  background-color: #f1f5f9;
  cursor: not-allowed;
}

.btn {
  display: inline-flex;
  align-items: center;
  padding: 12px 16px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
}

.btn-secondary {
  background-color: #64748b;
  color: white;
}

.btn-secondary:hover {
  background-color: #475569;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-sm {
  padding: 8px 12px;
  font-size: 12px;
}

.btn-icon {
  padding: 8px;
  background: transparent;
  border: none;
  cursor: pointer;
  color: #64748b;
}

.btn-icon:hover {
  color: #1e293b;
}

.input-hint {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 8px;
  color: #64748b;
  font-size: 14px;
}

.result-section {
  margin-bottom: 24px;
}

.result-card {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 20px;
  border-radius: 8px;
  border: 1px solid;
}

.result-success {
  background-color: #f0fdf4;
  border-color: #bbf7d0;
}

.result-error {
  background-color: #fef2f2;
  border-color: #fecaca;
}

.result-content {
  flex: 1;
}

.result-title {
  font-weight: 600;
  font-size: 16px;
  margin-bottom: 8px;
}

.result-success .result-title {
  color: #166534;
}

.result-error .result-title {
  color: #991b1b;
}

.result-details {
  margin-top: 12px;
}

.detail-row {
  display: flex;
  gap: 12px;
  margin-bottom: 4px;
  font-size: 14px;
}

.detail-row .label {
  color: #64748b;
  font-weight: 500;
}

.detail-row .value {
  color: #1e293b;
  font-weight: 600;
}

.error-message {
  margin-top: 8px;
  color: #991b1b;
}

.result-actions {
  display: flex;
  gap: 8px;
}

.recent-scans-section {
  border-top: 1px solid #e2e8f0;
  padding-top: 24px;
}

.recent-scans-section h3 {
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 16px;
}

.scans-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.scan-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.scan-item:hover {
  border-color: #3b82f6;
  background-color: #f8fafc;
}

.scan-info {
  flex: 1;
}

.scan-barcode {
  font-weight: 600;
  color: #1e293b;
}

.scan-time {
  color: #64748b;
  font-size: 12px;
}

.scan-status {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 600;
}

.status-success {
  background-color: #d1fae5;
  color: #065f46;
}

.status-error {
  background-color: #fee2e2;
  color: #991b1b;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
}

.modal-body {
  padding: 20px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px;
  border-top: 1px solid #e2e8f0;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-weight: 500;
  color: #1e293b;
  margin-bottom: 8px;
}

.input,
.select,
.textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 14px;
}

.input:focus,
.select:focus,
.textarea:focus {
  outline: none;
  border-color: #3b82f6;
}

.input:disabled {
  background-color: #f1f5f9;
  cursor: not-allowed;
}

.textarea {
  resize: vertical;
}
</style>
