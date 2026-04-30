<template>
  <div class="payment-rules-management">
    <div class="management-header">
      <h1>ФЗ-161 Payment Rules Management</h1>
      <p class="subtitle">National Payment System requirements and transaction limits</p>
    </div>

    <!-- Category Tabs -->
    <div class="category-tabs">
      <button
        v-for="category in categories"
        :key="category.id"
        @click="selectedCategory = category.id"
        class="tab-btn"
        :class="{ active: selectedCategory === category.id }"
      >
        {{ category.label }}
      </button>
    </div>

    <!-- Rules Table -->
    <div class="rules-table-container">
      <div class="table-header">
        <h2>{{ currentCategoryLabel }} Rules</h2>
        <div class="header-actions">
          <button @click="exportRules" class="btn-secondary">
            📥 Export for Audit
          </button>
          <button @click="showValidationModal = true" class="btn-primary">
            ✅ Validate Transaction
          </button>
        </div>
      </div>

      <table class="rules-table">
        <thead>
          <tr>
            <th>Code</th>
            <th>Description</th>
            <th>Value</th>
            <th>Effective From</th>
            <th>Effective Until</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="7" class="loading-cell">Loading payment rules...</td>
          </tr>
          <tr v-else-if="currentRules.length === 0">
            <td colspan="7" class="empty-cell">No rules found for this category</td>
          </tr>
          <tr v-for="rule in currentRules" :key="rule.code" class="rule-row">
            <td>
              <code>{{ rule.code }}</code>
            </td>
            <td>{{ rule.description }}</td>
            <td>
              <span class="rule-value">{{ formatRuleValue(rule.value) }}</span>
            </td>
            <td>{{ formatDate(rule.effective_from) }}</td>
            <td>{{ rule.effective_until ? formatDate(rule.effective_until) : 'Active' }}</td>
            <td>
              <span class="status-badge" :class="{ active: rule.is_active }">
                {{ rule.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <button @click="viewRuleHistory(rule.code)" class="btn-icon" title="View History">
                  📜
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Transaction Validation Modal -->
    <div v-if="showValidationModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>Validate Transaction (ФЗ-161)</h2>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="validateTransaction" class="validation-form">
            <div class="form-group">
              <label>Amount (kopecks)</label>
              <input
                v-model.number="validationForm.amount_kopecks"
                type="number"
                min="0"
                class="form-input"
                placeholder="Enter amount in kopecks"
                required
              />
              <small class="form-hint">Example: 15000000 for 150,000.00 ₽</small>
            </div>

            <div class="form-group">
              <label>Settlement Method</label>
              <select v-model="validationForm.settlement_method" class="form-select" required>
                <option value="bank_account">Bank Account</option>
                <option value="sbp">SBP (Faster Payments System)</option>
              </select>
            </div>

            <div class="form-group">
              <label>Fraud Check Passed</label>
              <select v-model="validationForm.fraud_check_passed" class="form-select">
                <option :value="null">Not checked</option>
                <option :value="true">Yes</option>
                <option :value="false">No</option>
              </select>
            </div>

            <div class="form-actions">
              <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
              <button type="submit" class="btn-primary" :disabled="validating">
                {{ validating ? 'Validating...' : 'Validate' }}
              </button>
            </div>
          </form>

          <div v-if="validationResult" class="validation-result">
            <h3>Validation Result</h3>
            <div class="result-content" :class="validationResult.valid ? 'success' : 'error'">
              <div class="result-icon">
                {{ validationResult.valid ? '✅' : '❌' }}
              </div>
              <div class="result-details">
                <p class="result-message">{{ validationResult.message }}</p>
                <div v-if="validationResult.violations && validationResult.violations.length > 0" class="violations">
                  <strong>Violations:</strong>
                  <ul>
                    <li v-for="violation in validationResult.violations" :key="violation">
                      {{ violation }}
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Rule History Modal -->
    <div v-if="showHistoryModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h2>Rule History: {{ selectedRuleCode }}</h2>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <div v-if="loadingHistory" class="loading-cell">Loading history...</div>
          <div v-else-if="ruleHistory.length === 0" class="empty-cell">No history found</div>
          <div v-else class="history-timeline">
            <div v-for="(entry, index) in ruleHistory" :key="index" class="history-item">
              <div class="history-date">{{ formatDate(entry.changed_at) }}</div>
              <div class="history-content">
                <div class="history-field">
                  <span class="field-label">Value:</span>
                  <span class="field-value">{{ formatRuleValue(entry.value) }}</span>
                </div>
                <div class="history-field">
                  <span class="field-label">Changed By:</span>
                  <span class="field-value">{{ entry.changed_by }}</span>
                </div>
                <div v-if="entry.change_reason" class="history-field">
                  <span class="field-label">Reason:</span>
                  <span class="field-value">{{ entry.change_reason }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Export Modal -->
    <div v-if="showExportModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>Export Rules for Audit</h2>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="performExport" class="export-form">
            <div class="form-group">
              <label>From Date</label>
              <input
                v-model="exportForm.from"
                type="date"
                class="form-input"
                required
              />
            </div>

            <div class="form-group">
              <label>To Date</label>
              <input
                v-model="exportForm.to"
                type="date"
                class="form-input"
                required
              />
            </div>

            <div class="form-actions">
              <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
              <button type="submit" class="btn-primary" :disabled="exporting">
                {{ exporting ? 'Exporting...' : 'Export' }}
              </button>
            </div>
          </form>

          <div v-if="exportResult" class="export-result">
            <h3>Export Result</h3>
            <div class="result-content success">
              <p>{{ exportResult.message }}</p>
              <pre class="export-data">{{ JSON.stringify(exportResult.data, null, 2) }}</pre>
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
import type { PaymentRule } from '@/services/complianceApi'

const loading = ref(false)
const loadingHistory = ref(false)
const validating = ref(false)
const exporting = ref(false)

const selectedCategory = ref('transaction_limits')
const categories = [
  { id: 'transaction_limits', label: 'Transaction Limits' },
  { id: 'fraud_detection', label: 'Fraud Detection' },
  { id: 'settlement', label: 'Settlement Rules' },
]

const allRules = ref<Record<string, PaymentRule[]>>({
  transaction_limits: [],
  fraud_detection: [],
  settlement: []
})

const showValidationModal = ref(false)
const showHistoryModal = ref(false)
const showExportModal = ref(false)

const selectedRuleCode = ref('')
const ruleHistory = ref<any[]>([])

const validationForm = ref({
  amount_kopecks: 0,
  user_id: null as number | null,
  tenant_id: null as number | null,
  settlement_method: 'bank_account',
  fraud_check_passed: null as boolean | null
})

const validationResult = ref<any>(null)

const exportForm = ref({
  from: '',
  to: ''
})

const exportResult = ref<any>(null)

const currentCategoryLabel = computed(() => {
  const category = categories.find(c => c.id === selectedCategory.value)
  return category ? category.label : ''
})

const currentRules = computed(() => {
  return allRules.value[selectedCategory.value] || []
})

const formatRuleValue = (value: any): string => {
  if (typeof value === 'boolean') {
    return value ? 'Yes' : 'No'
  }
  if (typeof value === 'number') {
    return value.toLocaleString('ru-RU')
  }
  return String(value)
}

const formatDate = (dateStr: string): string => {
  return new Date(dateStr).toLocaleDateString('ru-RU')
}

const loadRules = async () => {
  loading.value = true
  try {
    const data = await complianceApi.getPaymentRules()
    allRules.value = data
  } catch (error) {
    console.error('Failed to load payment rules:', error)
  } finally {
    loading.value = false
  }
}

const viewRuleHistory = async (code: string) => {
  selectedRuleCode.value = code
  loadingHistory.value = true
  showHistoryModal.value = true
  try {
    const history = await complianceApi.getRuleHistory(code)
    ruleHistory.value = history
  } catch (error) {
    console.error('Failed to load rule history:', error)
  } finally {
    loadingHistory.value = false
  }
}

const validateTransaction = async () => {
  validating.value = true
  validationResult.value = null
  try {
    const result = await complianceApi.validateFZ161(validationForm.value)
    validationResult.value = result
  } catch (error) {
    console.error('Failed to validate transaction:', error)
  } finally {
    validating.value = false
  }
}

const exportRules = () => {
  showExportModal.value = true
}

const performExport = async () => {
  exporting.value = true
  exportResult.value = null
  try {
    const result = await complianceApi.exportRules(exportForm.value.from, exportForm.value.to)
    exportResult.value = {
      message: 'Export successful',
      data: result
    }
  } catch (error) {
    console.error('Failed to export rules:', error)
  } finally {
    exporting.value = false
  }
}

const closeModal = () => {
  showValidationModal.value = false
  showHistoryModal.value = false
  showExportModal.value = false
  validationResult.value = null
  exportResult.value = null
}

onMounted(() => {
  loadRules()
})
</script>

<style scoped>
.payment-rules-management {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

.management-header {
  margin-bottom: 24px;
}

.management-header h1 {
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

.category-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 24px;
  border-bottom: 2px solid #e5e7eb;
  padding-bottom: 0;
}

.tab-btn {
  padding: 12px 24px;
  background: none;
  border: none;
  font-size: 14px;
  font-weight: 500;
  color: #666;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  transition: all 0.2s;
}

.tab-btn:hover {
  color: #374151;
}

.tab-btn.active {
  color: #3b82f6;
  border-bottom-color: #3b82f6;
}

.rules-table-container {
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
}

.table-header h2 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
}

.header-actions {
  display: flex;
  gap: 12px;
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
.btn-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.rules-table {
  width: 100%;
  border-collapse: collapse;
}

.rules-table th {
  background: #f9fafb;
  padding: 12px 16px;
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
  border-bottom: 1px solid #e5e7eb;
}

.rules-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
  font-size: 13px;
}

.rules-table tr:last-child td {
  border-bottom: none;
}

.loading-cell,
.empty-cell {
  text-align: center;
  padding: 32px;
  color: #666;
}

.rule-value {
  font-weight: 600;
  font-family: 'Monaco', 'Consolas', monospace;
}

.status-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  background: #e5e7eb;
  color: #374151;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
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
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-content.large {
  max-width: 700px;
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

.validation-form,
.export-form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 13px;
  font-weight: 500;
  color: #374151;
}

.form-input,
.form-select {
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.form-hint {
  font-size: 12px;
  color: #666;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 8px;
}

.validation-result,
.export-result {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.validation-result h3,
.export-result h3 {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 12px 0;
  color: #1a1a1a;
}

.result-content {
  padding: 16px;
  border-radius: 8px;
  display: flex;
  gap: 12px;
}

.result-content.success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
}

.result-content.error {
  background: #fef2f2;
  border: 1px solid #fecaca;
}

.result-icon {
  font-size: 24px;
}

.result-details {
  flex: 1;
}

.result-message {
  margin: 0 0 8px 0;
  font-weight: 500;
}

.violations {
  margin-top: 8px;
}

.violations strong {
  font-size: 13px;
  display: block;
  margin-bottom: 4px;
}

.violations ul {
  margin: 0;
  padding-left: 20px;
  font-size: 13px;
}

.export-data {
  background: #f9fafb;
  padding: 12px;
  border-radius: 6px;
  font-size: 12px;
  overflow-x: auto;
  max-height: 300px;
  overflow-y: auto;
}

.history-timeline {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.history-item {
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
  border-left: 3px solid #3b82f6;
}

.history-date {
  font-size: 12px;
  color: #666;
  margin-bottom: 8px;
}

.history-content {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.history-field {
  display: flex;
  gap: 8px;
  font-size: 13px;
}

.history-field .field-label {
  color: #666;
  min-width: 100px;
}

.history-field .field-value {
  font-weight: 500;
  color: #1a1a1a;
}
</style>
