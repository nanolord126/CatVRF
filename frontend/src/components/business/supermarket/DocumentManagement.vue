<template>
  <div class="document-management">
    <div class="header">
      <h2>{{ $t('supermarket.documents.title') }}</h2>
      <button @click="showCreateModal = true" class="btn-primary">
        {{ $t('supermarket.documents.create') }}
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-value">{{ stats.total }}</div>
        <div class="stat-label">{{ $t('supermarket.documents.stats.total') }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.active }}</div>
        <div class="stat-label">{{ $t('supermarket.documents.stats.active') }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.expired }}</div>
        <div class="stat-label">{{ $t('supermarket.documents.stats.expired') }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.closed }}</div>
        <div class="stat-label">{{ $t('supermarket.documents.stats.closed') }}</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters">
      <select v-model="filters.status" @change="loadDocuments">
        <option value="">{{ $t('supermarket.documents.filters.allStatuses') }}</option>
        <option value="draft">{{ $t('supermarket.documents.status.draft') }}</option>
        <option value="published">{{ $t('supermarket.documents.status.published') }}</option>
        <option value="expired">{{ $t('supermarket.documents.status.expired') }}</option>
        <option value="closed">{{ $t('supermarket.documents.status.closed') }}</option>
      </select>
      <select v-model="filters.type" @change="loadDocuments">
        <option value="">{{ $t('supermarket.documents.filters.allTypes') }}</option>
        <option value="certificate">{{ $t('supermarket.documents.types.certificate') }}</option>
        <option value="license">{{ $t('supermarket.documents.types.license') }}</option>
        <option value="contract">{{ $t('supermarket.documents.types.contract') }}</option>
        <option value="invoice">{{ $t('supermarket.documents.types.invoice') }}</option>
      </select>
      <input 
        v-model="filters.search" 
        type="text" 
        :placeholder="$t('supermarket.documents.filters.search')"
        @input="debouncedSearch"
      />
    </div>

    <!-- Documents Table -->
    <div class="documents-table" v-if="!loading">
      <table>
        <thead>
          <tr>
            <th>{{ $t('supermarket.documents.table.number') }}</th>
            <th>{{ $t('supermarket.documents.table.type') }}</th>
            <th>{{ $t('supermarket.documents.table.batchNumber') }}</th>
            <th>{{ $t('supermarket.documents.table.barcode') }}</th>
            <th>{{ $t('supermarket.documents.table.batchWeight') }}</th>
            <th>{{ $t('supermarket.documents.table.delivered') }}</th>
            <th>{{ $t('supermarket.documents.table.remaining') }}</th>
            <th>{{ $t('supermarket.documents.table.validFrom') }}</th>
            <th>{{ $t('supermarket.documents.table.validTo') }}</th>
            <th>{{ $t('supermarket.documents.table.temperature') }}</th>
            <th>{{ $t('supermarket.documents.table.paymentDelay') }}</th>
            <th>{{ $t('supermarket.documents.table.status') }}</th>
            <th>{{ $t('supermarket.documents.table.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="document in documents" :key="document.id">
            <td>{{ document.document_number }}</td>
            <td>{{ getTypeLabel(document.document_type) }}</td>
            <td>{{ document.batch_number || '-' }}</td>
            <td>{{ document.barcode || '-' }}</td>
            <td>{{ document.batch_weight ? formatNumber(document.batch_weight) + ' кг' : '-' }}</td>
            <td>{{ document.delivered_quantity ? formatNumber(document.delivered_quantity) + ' кг' : '-' }}</td>
            <td>
              <span :class="{ 'text-danger': getUsagePercentage(document) >= 100 }">
                {{ document.remaining_quantity ? formatNumber(document.remaining_quantity) + ' кг' : '-' }}
              </span>
            </td>
            <td>{{ formatDate(document.valid_from) }}</td>
            <td>
              <span :class="{ 'text-danger': isExpired(document) }">
                {{ formatDate(document.valid_to) }}
              </span>
            </td>
            <td>
              <span v-if="document.requires_temperature_compliance" :class="getTemperatureComplianceColor(document)">
                {{ getTemperatureComStatusLabel(document) }}
                <span v-if="document.temperature_violation_count > 0" class="text-xs">
                  ({{ document.temperature_violation_count }} {{ $t('supermarket.documents.violations') }})
                </span>
              </span>
              <span v-else>-</span>
            </td>
            <td>
              <span v-if="document.payment_delay_days > 0" class="text-blue-600">
                {{ document.payment_delay_days }} {{ $t('supermarket.documents.days') }}
                <span v-if="document.payment_delay_until" class="text-xs">
                  ({{ formatDate(document.payment_delay_until) }})
                </span>
              </span>
              <span v-else>-</span>
            </td>
            <td>
              <span :class="getStatusClass(document.status)">
                {{ getStatusLabel(document.status) }}
              </span>
            </td>
            <td>
              <button @click="viewDocument(document)" class="btn-sm">
                {{ $t('supermarket.documents.actions.view') }}
              </button>
              <button @click="editDocument(document)" class="btn-sm btn-secondary">
                {{ $t('supermarket.documents.actions.edit') }}
              </button>
              <button 
                v-if="document.batch_weight && !document.is_closed" 
                @click="showAddQuantityModal(document)"
                class="btn-sm btn-success"
              >
                {{ $t('supermarket.documents.actions.addQuantity') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="loading">
      {{ $t('common.loading') }}
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="modal-overlay" @click="closeModal">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>{{ showEditModal ? $t('supermarket.documents.edit') : $t('supermarket.documents.create') }}</h3>
          <button @click="closeModal" class="btn-close">&times;</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveDocument">
            <div class="form-group">
              <label>{{ $t('supermarket.documents.form.type') }}</label>
              <select v-model="form.document_type" required>
                <option value="certificate">{{ $t('supermarket.documents.types.certificate') }}</option>
                <option value="license">{{ $t('supermarket.documents.types.license') }}</option>
                <option value="contract">{{ $t('supermarket.documents.types.contract') }}</option>
                <option value="invoice">{{ $t('supermarket.documents.types.invoice') }}</option>
                <option value="quality_cert">{{ $t('supermarket.documents.types.quality_cert') }}</option>
                <option value="honest_mark">{{ $t('supermarket.documents.types.honest_mark') }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>{{ $t('supermarket.documents.form.title') }}</label>
              <input v-model="form.title" type="text" required />
            </div>
            <div class="form-group">
              <label>{{ $t('supermarket.documents.form.description') }}</label>
              <textarea v-model="form.description"></textarea>
            </div>
            <div class="form-group">
              <label>{{ $t('supermarket.documents.form.file') }}</label>
              <input 
                type="file" 
                @change="handleFileUpload"
                accept=".pdf,.jpeg,.jpg,.tif,.tiff"
                :required="!showEditModal"
              />
              <small>{{ $t('supermarket.documents.form.formats') }}: PDF, JPEG, TIFF</small>
            </div>
            <div class="form-group">
              <label>{{ $t('supermarket.documents.form.documentNumber') }}</label>
              <input v-model="form.document_number" type="text" />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>{{ $t('supermarket.documents.form.validFrom') }}</label>
                <input v-model="form.valid_from" type="date" />
              </div>
              <div class="form-group">
                <label>{{ $t('supermarket.documents.form.validTo') }}</label>
                <input v-model="form.valid_to" type="date" />
              </div>
            </div>
            <div class="form-group">
              <label>{{ $t('supermarket.documents.form.batchNumber') }}</label>
              <input v-model="form.batch_number" type="text" />
            </div>
            <div class="form-group">
              <label>{{ $t('supermarket.documents.form.barcode') }}</label>
              <input v-model="form.barcode" type="text" />
            </div>
            <div class="form-group">
              <label>{{ $t('supermarket.documents.form.batchWeight') }} (кг)</label>
              <input v-model="form.batch_weight" type="number" step="0.001" min="0" />
            </div>
            <div class="form-group">
              <label>{{ $t('supermarket.documents.form.autoDistribute') }}</label>
              <input v-model="form.auto_distribute" type="checkbox" />
            </div>
            <div class="form-actions">
              <button type="submit" class="btn-primary">
                {{ showEditModal ? $t('common.save') : $t('common.create') }}
              </button>
              <button type="button" @click="closeModal" class="btn-secondary">
                {{ $t('common.cancel') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Add Quantity Modal -->
    <div v-if="showAddQuantity" class="modal-overlay" @click="showAddQuantity = false">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>{{ $t('supermarket.documents.addQuantity') }}</h3>
          <button @click="showAddQuantity = false" class="btn-close">&times;</button>
        </div>
        <div class="modal-body">
          <div class="info-box">
            <p>{{ $t('supermarket.documents.batchWeight') }}: {{ formatNumber(selectedDocument?.batch_weight) }} кг</p>
            <p>{{ $t('supermarket.documents.delivered') }}: {{ formatNumber(selectedDocument?.delivered_quantity) }} кг</p>
            <p>{{ $t('supermarket.documents.remaining') }}: {{ formatNumber(selectedDocument?.remaining_quantity) }} кг</p>
          </div>
          <form @submit.prevent="addQuantity">
            <div class="form-group">
              <label>{{ $t('supermarket.documents.form.quantity') }} (кг)</label>
              <input 
                v-model="quantityForm.quantity" 
                type="number" 
                step="0.001" 
                min="0"
                :max="selectedDocument?.remaining_quantity"
                required
              />
            </div>
            <div class="form-actions">
              <button type="submit" class="btn-primary">{{ $t('common.add') }}</button>
              <button type="button" @click="showAddQuantity = false" class="btn-secondary">
                {{ $t('common.cancel') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Document History Modal -->
    <div v-if="showHistory" class="modal-overlay" @click="showHistory = false">
      <div class="modal large" @click.stop>
        <div class="modal-header">
          <h3>{{ $t('supermarket.documents.history') }}</h3>
          <button @click="showHistory = false" class="btn-close">&times;</button>
        </div>
        <div class="modal-body">
          <div class="history-list">
            <div v-for="entry in history" :key="entry.id" class="history-item">
              <div class="history-action">{{ entry.action }}</div>
              <div class="history-date">{{ formatDate(entry.created_at) }}</div>
              <div class="history-user">{{ entry.user_type }}</div>
              <div v-if="entry.changes_summary" class="history-summary">
                {{ entry.changes_summary }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

interface Document {
  id: number
  document_number: string
  document_type: string
  title: string
  description?: string
  batch_number?: string
  barcode?: string
  batch_weight?: number
  delivered_quantity: number
  remaining_quantity?: number
  valid_from?: string
  valid_to?: string
  status: string
  is_closed: boolean
  requires_temperature_compliance?: boolean
  temperature_compliance_status?: string
  temperature_violation_count?: number
  payment_delay_days?: number
  payment_delay_until?: string
  price_with_delay?: number
  price_markup_percent?: number
}

const documents = ref<Document[]>([])
const stats = ref({
  total: 0,
  active: 0,
  expired: 0,
  closed: 0
})
const loading = ref(false)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showAddQuantity = ref(false)
const showHistory = ref(false)
const selectedDocument = ref<Document | null>(null)
const history = ref<any[]>([])

const filters = ref({
  status: '',
  type: '',
  search: ''
})

const form = ref({
  document_type: 'certificate',
  title: '',
  description: '',
  file: null as File | null,
  document_number: '',
  valid_from: '',
  valid_to: '',
  batch_number: '',
  barcode: '',
  batch_weight: '',
  auto_distribute: false
})

const quantityForm = ref({
  quantity: ''
})

let searchTimeout: NodeJS.Timeout

onMounted(() => {
  loadDocuments()
  loadStats()
})

const loadDocuments = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.status) params.append('status', filters.value.status)
    if (filters.value.type) params.append('type', filters.value.type)
    if (filters.value.search) params.append('search', filters.value.search)
    
    const response = await fetch(`/api/supermarket/documents?${params}`)
    documents.value = await response.json()
  } catch (error) {
    console.error('Failed to load documents:', error)
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const response = await fetch('/api/supermarket/documents/stats')
    stats.value = await response.json()
  } catch (error) {
    console.error('Failed to load stats:', error)
  }
}

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    loadDocuments()
  }, 500)
}

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    form.value.file = target.files[0]
  }
}

const saveDocument = async () => {
  try {
    const formData = new FormData()
    Object.keys(form.value).forEach(key => {
      if (key !== 'file' || form.value.file) {
        formData.append(key, form.value[key as keyof typeof form.value] as any)
      }
    })

    const url = showEditModal.value 
      ? `/api/supermarket/documents/${selectedDocument.value?.id}`
      : '/api/supermarket/documents'
    
    const method = showEditModal.value ? 'PUT' : 'POST'
    
    await fetch(url, {
      method,
      body: formData
    })

    closeModal()
    loadDocuments()
    loadStats()
  } catch (error) {
    console.error('Failed to save document:', error)
  }
}

const editDocument = (document: Document) => {
  selectedDocument.value = document
  form.value = {
    document_type: document.document_type,
    title: document.title,
    description: document.description || '',
    file: null,
    document_number: document.document_number,
    valid_from: document.valid_from?.split('T')[0] || '',
    valid_to: document.valid_to?.split('T')[0] || '',
    batch_number: document.batch_number || '',
    barcode: document.barcode || '',
    batch_weight: document.batch_weight?.toString() || '',
    auto_distribute: false
  }
  showEditModal.value = true
}

const addQuantity = async () => {
  try {
    await fetch(`/api/supermarket/documents/${selectedDocument.value?.id}/add-quantity`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        quantity: parseFloat(quantityForm.value.quantity)
      })
    })

    showAddQuantity.value = false
    quantityForm.value.quantity = ''
    loadDocuments()
    loadStats()
  } catch (error) {
    console.error('Failed to add quantity:', error)
  }
}

const viewDocument = async (document: Document) => {
  selectedDocument.value = document
  try {
    const response = await fetch(`/api/supermarket/documents/${document.id}/history`)
    history.value = await response.json()
    showHistory.value = true
  } catch (error) {
    console.error('Failed to load history:', error)
  }
}

const closeModal = () => {
  showCreateModal.value = false
  showEditModal.value = false
  selectedDocument.value = null
  form.value = {
    document_type: 'certificate',
    title: '',
    description: '',
    file: null,
    document_number: '',
    valid_from: '',
    valid_to: '',
    batch_number: '',
    barcode: '',
    batch_weight: '',
    auto_distribute: false
  }
}

const showAddQuantityModal = (document: Document) => {
  selectedDocument.value = document
  showAddQuantity.value = true
}

const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    certificate: t('supermarket.documents.types.certificate'),
    license: t('supermarket.documents.types.license'),
    contract: t('supermarket.documents.types.contract'),
    invoice: t('supermarket.documents.types.invoice'),
    quality_cert: t('supermarket.documents.types.quality_cert'),
    honest_mark: t('supermarket.documents.types.honest_mark')
  }
  return labels[type] || type
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    draft: t('supermarket.documents.status.draft'),
    published: t('supermarket.documents.status.published'),
    expired: t('supermarket.documents.status.expired'),
    closed: t('supermarket.documents.status.closed')
  }
  return labels[status] || status
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    draft: 'status-draft',
    published: 'status-active',
    expired: 'status-expired',
    closed: 'status-closed'
  }
  return classes[status] || ''
}

const isExpired = (document: Document) => {
  if (!document.valid_to) return false
  return new Date(document.valid_to) < new Date()
}

const getTemperatureComplianceColor = (document: Document) => {
  const status = document.temperature_compliance_status || 'unknown'
  const colors: Record<string, string> = {
    compliant: 'text-green-600',
    violation: 'text-red-600',
    warning: 'text-yellow-600',
    unknown: 'text-gray-600'
  }
  return colors[status] || 'text-gray-600'
}

const getTemperatureComStatusLabel = (document: Document) => {
  const status = document.temperature_compliance_status || 'unknown'
  const labels: Record<string, string> = {
    compliant: t('supermarket.documents.temperature.compliant'),
    violation: t('supermarket.documents.temperature.violation'),
    warning: t('supermarket.documents.temperature.warning'),
    unknown: t('supermarket.documents.temperature.unknown')
  }
  return labels[status] || status
}

const getPaymentDelayLabel = (document: Document): string => {
  if (!document.payment_delay_days || document.payment_delay_days === 0) {
    return '-'
  }
  return `${document.payment_delay_days} ${t('supermarket.documents.days')}`
}

const getUsagePercentage = (document: Document) => {
  if (!document.batch_weight || !document.delivered_quantity) return 0
  return (document.delivered_quantity / document.batch_weight) * 100
}

const formatNumber = (num: number) => {
  return num.toFixed(3)
}

const formatDate = (date: string | undefined) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}
</script>

<style scoped>
.document-management {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-value {
  font-size: 32px;
  font-weight: bold;
  color: #333;
}

.stat-label {
  color: #666;
  margin-top: 5px;
}

.filters {
  display: flex;
  gap: 10px;
  margin-bottom: 20px;
}

.filters select,
.filters input {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.documents-table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #eee;
}

th {
  background: #f5f5f5;
  font-weight: 600;
}

.btn-primary {
  background: #007bff;
  color: white;
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-success {
  background: #28a745;
  color: white;
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.btn-sm {
  padding: 4px 8px;
  font-size: 12px;
  margin-right: 5px;
}

.btn-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 8px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal.large {
  max-width: 800px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 15px;
}

.form-row {
  display: flex;
  gap: 15px;
}

.form-row .form-group {
  flex: 1;
}

label {
  display: block;
  margin-bottom: 5px;
  font-weight: 500;
}

input, select, textarea {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.form-actions {
  display: flex;
  gap: 10px;
  margin-top: 20px;
}

.info-box {
  background: #f0f0f0;
  padding: 15px;
  border-radius: 4px;
  margin-bottom: 15px;
}

.info-box p {
  margin: 5px 0;
}

.status-active {
  color: #28a745;
}

.status-expired {
  color: #dc3545;
}

.status-closed {
  color: #6c757d;
}

.status-draft {
  color: #ffc107;
}

.text-danger {
  color: #dc3545;
}

.history-list {
  max-height: 400px;
  overflow-y: auto;
}

.history-item {
  padding: 10px;
  border-bottom: 1px solid #eee;
}

.history-action {
  font-weight: 600;
}

.history-date {
  color: #666;
  font-size: 12px;
}

.history-user {
  color: #999;
  font-size: 12px;
}

.history-summary {
  color: #666;
  margin-top: 5px;
}

.loading {
  text-align: center;
  padding: 40px;
  color: #666;
}
</style>
