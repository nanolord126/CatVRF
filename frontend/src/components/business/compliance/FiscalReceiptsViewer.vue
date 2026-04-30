<template>
  <div class="fiscal-receipts-viewer">
    <div class="viewer-header">
      <h1>54-ФЗ Fiscal Receipts</h1>
      <p class="subtitle">Online cash register (KKT) fiscalization monitoring</p>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <div class="filter-group">
        <label>View Mode:</label>
        <select v-model="viewMode" class="filter-select" @change="loadReceipts">
          <option value="pending">Pending</option>
          <option value="payment">By Payment Intent</option>
          <option value="all">All Receipts</option>
        </select>
      </div>

      <div v-if="viewMode === 'payment'" class="filter-group">
        <label>Payment Intent UUID:</label>
        <input
          v-model="paymentIntentUuid"
          type="text"
          class="filter-input"
          placeholder="Enter payment UUID"
          @keyup.enter="loadReceipts"
        />
        <button @click="loadReceipts" class="btn-primary">Load</button>
      </div>

      <div class="filter-group">
        <label>Status:</label>
        <select v-model="statusFilter" class="filter-select" @change="loadReceipts">
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="failed">Failed</option>
        </select>
      </div>

      <button @click="refreshReceipts" class="btn-secondary" :disabled="loading">
        🔄 Refresh
      </button>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar">
      <div class="stat-item">
        <span class="stat-label">Total Receipts:</span>
        <span class="stat-value">{{ stats.total }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Pending:</span>
        <span class="stat-value warning">{{ stats.pending }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Confirmed:</span>
        <span class="stat-value success">{{ stats.confirmed }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Failed:</span>
        <span class="stat-value danger">{{ stats.failed }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Retry Rate:</span>
        <span class="stat-value">{{ retryRate }}%</span>
      </div>
    </div>

    <!-- Fiscal Receipts Table -->
    <div class="receipts-table-container">
      <table class="receipts-table">
        <thead>
          <tr>
            <th>UUID</th>
            <th>Payment Intent</th>
            <th>Fiscal Sign</th>
            <th>Fiscal Doc #</th>
            <th>Operation Type</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Fiscal Date</th>
            <th>Retries</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading && receipts.length === 0">
            <td colspan="10" class="loading-cell">Loading fiscal receipts...</td>
          </tr>
          <tr v-else-if="receipts.length === 0">
            <td colspan="10" class="empty-cell">No fiscal receipts found</td>
          </tr>
          <tr v-for="receipt in receipts" :key="receipt.uuid" class="receipt-row" :class="`status-${receipt.status}`">
            <td class="uuid-cell">
              <code>{{ receipt.uuid.substring(0, 8) }}...</code>
            </td>
            <td class="uuid-cell">
              <code>{{ receipt.payment_intent_uuid.substring(0, 8) }}...</code>
            </td>
            <td>
              <code>{{ receipt.fiscal_sign }}</code>
            </td>
            <td>{{ receipt.fiscal_document_number }}</td>
            <td>
              <span class="operation-badge">{{ receipt.operation_type }}</span>
            </td>
            <td class="amount-cell">
              {{ formatAmount(receipt.amount) }} ₽
            </td>
            <td>
              <span class="status-badge" :class="receipt.status">
                {{ formatStatus(receipt.status) }}
              </span>
            </td>
            <td>{{ formatDate(receipt.fiscal_document_datetime) }}</td>
            <td>
              <span class="retry-count" :class="{ warning: receipt.retry_count > 0 }">
                {{ receipt.retry_count }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <button @click="viewDetails(receipt)" class="btn-icon" title="View Details">
                  👁️
                </button>
                <button
                  v-if="receipt.status === 'failed' && receipt.retry_count < 3"
                  @click="retryReceipt(receipt.uuid)"
                  class="btn-icon"
                  title="Retry"
                  :disabled="retrying === receipt.uuid"
                >
                  🔄
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Receipt Details Modal -->
    <div v-if="showDetailsModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h2>Fiscal Receipt Details</h2>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <div v-if="selectedReceipt" class="receipt-details">
            <div class="detail-section">
              <h3>Basic Information</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <span class="label">UUID:</span>
                  <span class="value">{{ selectedReceipt.uuid }}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Payment Intent UUID:</span>
                  <span class="value">{{ selectedReceipt.payment_intent_uuid }}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Status:</span>
                  <span class="value status-badge" :class="selectedReceipt.status">
                    {{ formatStatus(selectedReceipt.status) }}
                  </span>
                </div>
                <div class="detail-item">
                  <span class="label">Created At:</span>
                  <span class="value">{{ formatDate(selectedReceipt.created_at) }}</span>
                </div>
              </div>
            </div>

            <div class="detail-section">
              <h3>Fiscal Data</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <span class="label">Fiscal Sign:</span>
                  <span class="value code">{{ selectedReceipt.fiscal_sign }}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Fiscal Document Number:</span>
                  <span class="value">{{ selectedReceipt.fiscal_document_number }}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Fiscal Document Date:</span>
                  <span class="value">{{ formatDate(selectedReceipt.fiscal_document_datetime) }}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Operation Type:</span>
                  <span class="value">{{ selectedReceipt.operation_type }}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Amount:</span>
                  <span class="value">{{ formatAmount(selectedReceipt.amount) }} ₽</span>
                </div>
                <div class="detail-item">
                  <span class="label">Retry Count:</span>
                  <span class="value">{{ selectedReceipt.retry_count }}</span>
                </div>
              </div>
            </div>

            <div v-if="selectedReceipt.status === 'failed' && selectedReceipt.error_message" class="detail-section">
              <h3>Error Information</h3>
              <div class="error-box">
                <p>{{ selectedReceipt.error_message }}</p>
              </div>
            </div>

            <div v-if="selectedReceipt.status === 'failed' && selectedReceipt.retry_count < 3" class="detail-section">
              <h3>Actions</h3>
              <button
                @click="retryReceipt(selectedReceipt.uuid)"
                class="btn-primary"
                :disabled="retrying === selectedReceipt.uuid"
              >
                {{ retrying === selectedReceipt.uuid ? 'Retrying...' : 'Retry Fiscal Receipt' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import complianceApi from '@/services/complianceApi'
import type { FiscalReceipt } from '@/services/complianceApi'

const loading = ref(false)
const retrying = ref<string | null>(null)
const viewMode = ref<'pending' | 'payment' | 'all'>('pending')
const paymentIntentUuid = ref('')
const statusFilter = ref('')

const receipts = ref<FiscalReceipt[]>([])
const stats = ref({
  total: 0,
  pending: 0,
  confirmed: 0,
  failed: 0
})

const showDetailsModal = ref(false)
const selectedReceipt = ref<FiscalReceipt | null>(null)

const retryRate = computed(() => {
  if (stats.value.total === 0) return 0
  const retried = receipts.value.filter(r => r.retry_count > 0).length
  return ((retried / stats.value.total) * 100).toFixed(1)
})

const formatStatus = (status: string): string => {
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const formatAmount = (amount: number): string => {
  return (amount / 100).toFixed(2)
}

const formatDate = (dateStr: string): string => {
  return new Date(dateStr).toLocaleString('ru-RU')
}

const loadReceipts = async () => {
  loading.value = true
  try {
    let data: FiscalReceipt[] = []

    if (viewMode.value === 'pending') {
      data = await complianceApi.getPendingFiscalReceipts(100)
    } else if (viewMode.value === 'payment' && paymentIntentUuid.value) {
      data = await complianceApi.getPaymentIntentReceipts(paymentIntentUuid.value)
    }

    // Apply status filter
    if (statusFilter.value) {
      data = data.filter(receipt => receipt.status === statusFilter.value)
    }

    receipts.value = data

    // Calculate stats
    stats.value = {
      total: data.length,
      pending: data.filter(r => r.status === 'pending').length,
      confirmed: data.filter(r => r.status === 'confirmed').length,
      failed: data.filter(r => r.status === 'failed').length
    }
  } catch (error) {
    console.error('Failed to load fiscal receipts:', error)
  } finally {
    loading.value = false
  }
}

const refreshReceipts = () => {
  loadReceipts()
}

const viewDetails = async (receipt: FiscalReceipt) => {
  try {
    const detailedReceipt = await complianceApi.getFiscalReceipt(receipt.uuid)
    selectedReceipt.value = detailedReceipt
    showDetailsModal.value = true
  } catch (error) {
    console.error('Failed to load fiscal receipt details:', error)
  }
}

const retryReceipt = async (uuid: string) => {
  retrying.value = uuid
  try {
    await complianceApi.retryFiscalReceipt(uuid)
    await loadReceipts()
    if (selectedReceipt.value && selectedReceipt.value.uuid === uuid) {
      const updated = await complianceApi.getFiscalReceipt(uuid)
      selectedReceipt.value = updated
    }
  } catch (error) {
    console.error('Failed to retry fiscal receipt:', error)
  } finally {
    retrying.value = null
  }
}

const closeModal = () => {
  showDetailsModal.value = false
  selectedReceipt.value = null
}

onMounted(() => {
  loadReceipts()
})
</script>

<style scoped>
.fiscal-receipts-viewer {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

.viewer-header {
  margin-bottom: 24px;
}

.viewer-header h1 {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 8px 0;
  color: #1a1a1a;
}

.subtitle {
  font-size: 14px;
  color: #666;
  margin: 0;
}

.filters-bar {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  align-items: center;
  background: white;
  padding: 16px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  margin-bottom: 16px;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-group label {
  font-size: 13px;
  font-weight: 500;
  color: #374151;
}

.filter-select,
.filter-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 13px;
  min-width: 150px;
}

.filter-input {
  min-width: 200px;
}

.btn-primary,
.btn-secondary,
.btn-icon {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
}

.btn-secondary {
  background: white;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover:not(:disabled) {
  background: #f9fafb;
}

.btn-icon {
  padding: 6px 8px;
  background: transparent;
  font-size: 16px;
}

.btn-icon:hover {
  background: #f3f4f6;
}

.btn-primary:disabled,
.btn-secondary:disabled,
.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.stats-bar {
  display: flex;
  gap: 24px;
  background: white;
  padding: 16px 24px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  margin-bottom: 16px;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.stat-label {
  font-size: 13px;
  color: #666;
}

.stat-value {
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
}

.stat-value.success {
  color: #10b981;
}

.stat-value.warning {
  color: #f59e0b;
}

.stat-value.danger {
  color: #ef4444;
}

.receipts-table-container {
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
  margin-bottom: 16px;
}

.receipts-table {
  width: 100%;
  border-collapse: collapse;
}

.receipts-table th {
  background: #f9fafb;
  padding: 12px 16px;
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
  border-bottom: 1px solid #e5e7eb;
}

.receipts-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
  font-size: 13px;
}

.receipts-table tr:last-child td {
  border-bottom: none;
}

.receipt-row.status-failed {
  background: #fef2f2;
}

.receipt-row.status-pending {
  background: #fffbeb;
}

.loading-cell,
.empty-cell {
  text-align: center;
  padding: 32px;
  color: #666;
}

.uuid-cell code {
  background: #f3f4f6;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
}

.amount-cell {
  font-weight: 600;
  font-family: 'Monaco', 'Consolas', monospace;
}

.operation-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  background: #e0e7ff;
  color: #3730a3;
  font-size: 11px;
  font-weight: 600;
}

.status-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.confirmed {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.failed {
  background: #fee2e2;
  color: #991b1b;
}

.retry-count {
  font-weight: 600;
  color: #6b7280;
}

.retry-count.warning {
  color: #f59e0b;
}

.action-buttons {
  display: flex;
  gap: 4px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 700px;
  width: 90%;
  max-height: 80vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-content.large {
  max-width: 900px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  font-size: 18px;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
}

.close-btn:hover {
  color: #1a1a1a;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
}

.receipt-details {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.detail-section h3 {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 16px 0;
  color: #1a1a1a;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.detail-item .label {
  font-size: 12px;
  color: #666;
}

.detail-item .value {
  font-size: 14px;
  font-weight: 500;
  color: #1a1a1a;
  word-break: break-all;
}

.detail-item .value.code {
  font-family: 'Monaco', 'Consolas', monospace;
  font-size: 12px;
}

.error-box {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 6px;
  padding: 12px;
}

.error-box p {
  margin: 0;
  font-size: 14px;
  color: #991b1b;
}
</style>
