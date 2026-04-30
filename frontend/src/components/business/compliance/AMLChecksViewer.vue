<template>
  <div class="aml-checks-viewer">
    <div class="viewer-header">
      <h1>ФЗ-115 AML/KYC Checks</h1>
      <p class="subtitle">Anti-Money Laundering and Know Your Customer monitoring</p>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <div class="filter-group">
        <label>View Mode:</label>
        <select v-model="viewMode" class="filter-select">
          <option value="pending">Pending Review</option>
          <option value="user">By User</option>
          <option value="all">All Checks</option>
        </select>
      </div>

      <div v-if="viewMode === 'user'" class="filter-group">
        <label>User ID:</label>
        <input
          v-model.number="userIdFilter"
          type="number"
          class="filter-input"
          placeholder="Enter user ID"
          @keyup.enter="loadChecks"
        />
        <button @click="loadChecks" class="btn-primary">Load</button>
      </div>

      <div class="filter-group">
        <label>Risk Level:</label>
        <select v-model="riskFilter" class="filter-select" @change="loadChecks">
          <option value="">All</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="critical">Critical</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Status:</label>
        <select v-model="statusFilter" class="filter-select" @change="loadChecks">
          <option value="">All</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="manual_review">Manual Review</option>
        </select>
      </div>

      <button @click="refreshChecks" class="btn-secondary" :disabled="loading">
        🔄 Refresh
      </button>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar">
      <div class="stat-item">
        <span class="stat-label">Total Checks:</span>
        <span class="stat-value">{{ stats.total }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">High Risk:</span>
        <span class="stat-value danger">{{ stats.high_risk }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Critical:</span>
        <span class="stat-value danger">{{ stats.critical }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Pending Review:</span>
        <span class="stat-value warning">{{ stats.pending_review }}</span>
      </div>
      <div class="stat-item">
        <span class="stat-label">Blocked:</span>
        <span class="stat-value">{{ stats.blocked }}</span>
      </div>
    </div>

    <!-- AML Checks Table -->
    <div class="checks-table-container">
      <table class="checks-table">
        <thead>
          <tr>
            <th>UUID</th>
            <th>User ID</th>
            <th>Risk Score</th>
            <th>Risk Level</th>
            <th>Status</th>
            <th>Checked At</th>
            <th>Reported</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading && checks.length === 0">
            <td colspan="8" class="loading-cell">Loading AML checks...</td>
          </tr>
          <tr v-else-if="checks.length === 0">
            <td colspan="8" class="empty-cell">No AML checks found</td>
          </tr>
          <tr v-for="check in checks" :key="check.uuid" class="check-row" :class="`risk-${check.risk_level}`">
            <td class="uuid-cell">
              <code>{{ check.uuid.substring(0, 8) }}...</code>
            </td>
            <td>{{ check.user_id }}</td>
            <td>
              <div class="risk-score">
                <div class="score-bar">
                  <div
                    class="score-fill"
                    :class="check.risk_level"
                    :style="{ width: `${check.risk_score * 100}%` }"
                  ></div>
                </div>
                <span>{{ (check.risk_score * 100).toFixed(1) }}%</span>
              </div>
            </td>
            <td>
              <span class="risk-badge" :class="check.risk_level">
                {{ check.risk_level.toUpperCase() }}
              </span>
            </td>
            <td>
              <span class="status-badge" :class="check.status">
                {{ formatStatus(check.status) }}
              </span>
            </td>
            <td>{{ formatDate(check.checked_at) }}</td>
            <td>
              <span v-if="check.reported_to_rosfinmonitoring" class="reported-badge">✓ Yes</span>
              <span v-else class="not-reported-badge">✗ No</span>
            </td>
            <td>
              <div class="action-buttons">
                <button @click="viewDetails(check)" class="btn-icon" title="View Details">
                  👁️
                </button>
                <button
                  v-if="!check.reported_to_rosfinmonitoring && check.risk_level === 'critical'"
                  @click="markAsReported(check.uuid)"
                  class="btn-icon"
                  title="Mark as Reported to Rosfinmonitoring"
                >
                  📤
                </button>
                <button
                  v-if="check.status === 'manual_review'"
                  @click="approveCheck(check.uuid)"
                  class="btn-icon"
                  title="Approve"
                >
                  ✅
                </button>
                <button
                  v-if="check.status === 'manual_review'"
                  @click="rejectCheck(check.uuid)"
                  class="btn-icon"
                  title="Reject"
                >
                  ❌
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
      <button
        @click="previousPage"
        :disabled="offset === 0 || loading"
        class="btn-secondary"
      >
        ← Previous
      </button>
      <span class="page-info">Showing {{ offset + 1 }}-{{ offset + checks.length }}</span>
      <button
        @click="nextPage"
        :disabled="checks.length < limit || loading"
        class="btn-secondary"
      >
        Next →
      </button>
    </div>

    <!-- Check Details Modal -->
    <div v-if="showDetailsModal" class="modal-overlay" @click="closeModal">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h2>AML Check Details</h2>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <div v-if="selectedCheck" class="check-details">
            <div class="detail-section">
              <h3>Basic Information</h3>
              <div class="detail-grid">
                <div class="detail-item">
                  <span class="label">UUID:</span>
                  <span class="value">{{ selectedCheck.uuid }}</span>
                </div>
                <div class="detail-item">
                  <span class="label">User ID:</span>
                  <span class="value">{{ selectedCheck.user_id }}</span>
                </div>
                <div class="detail-item">
                  <span class="label">Risk Score:</span>
                  <span class="value">{{ (selectedCheck.risk_score * 100).toFixed(2) }}%</span>
                </div>
                <div class="detail-item">
                  <span class="label">Risk Level:</span>
                  <span class="value risk-badge" :class="selectedCheck.risk_level">
                    {{ selectedCheck.risk_level.toUpperCase() }}
                  </span>
                </div>
                <div class="detail-item">
                  <span class="label">Status:</span>
                  <span class="value status-badge" :class="selectedCheck.status">
                    {{ formatStatus(selectedCheck.status) }}
                  </span>
                </div>
                <div class="detail-item">
                  <span class="label">Checked At:</span>
                  <span class="value">{{ formatDate(selectedCheck.checked_at) }}</span>
                </div>
              </div>
            </div>

            <div class="detail-section">
              <h3>Checks Performed</h3>
              <ul class="checks-list">
                <li v-for="check in selectedCheck.checks_performed" :key="check">
                  {{ check }}
                </li>
              </ul>
            </div>

            <div v-if="selectedCheck.flags && selectedCheck.flags.length > 0" class="detail-section">
              <h3>Flags Raised</h3>
              <div class="flags-list">
                <div v-for="flag in selectedCheck.flags" :key="flag" class="flag-item danger">
                  ⚠️ {{ flag }}
                </div>
              </div>
            </div>

            <div class="detail-section">
              <h3>Rosfinmonitoring Reporting</h3>
              <div class="detail-item">
                <span class="label">Reported:</span>
                <span class="value">
                  {{ selectedCheck.reported_to_rosfinmonitoring ? 'Yes' : 'No' }}
                </span>
              </div>
              <button
                v-if="!selectedCheck.reported_to_rosfinmonitoring && selectedCheck.risk_level === 'critical'"
                @click="markAsReported(selectedCheck.uuid)"
                class="btn-primary"
              >
                Mark as Reported to Rosfinmonitoring
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import complianceApi from '@/services/complianceApi'
import type { AMLCheck } from '@/services/complianceApi'

const loading = ref(false)
const viewMode = ref<'pending' | 'user' | 'all'>('pending')
const userIdFilter = ref<number | null>(null)
const riskFilter = ref('')
const statusFilter = ref('')
const offset = ref(0)
const limit = ref(50)

const checks = ref<AMLCheck[]>([])
const stats = ref({
  total: 0,
  high_risk: 0,
  critical: 0,
  pending_review: 0,
  blocked: 0
})

const showDetailsModal = ref(false)
const selectedCheck = ref<AMLCheck | null>(null)

const formatStatus = (status: string): string => {
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const formatDate = (dateStr: string): string => {
  return new Date(dateStr).toLocaleString('ru-RU')
}

const loadChecks = async () => {
  loading.value = true
  try {
    let data: AMLCheck[] = []

    if (viewMode.value === 'pending') {
      data = await complianceApi.getPendingAMLChecks(limit.value)
    } else if (viewMode.value === 'user' && userIdFilter.value) {
      data = await complianceApi.getUserAMLChecks(userIdFilter.value, limit.value, offset.value)
    }

    // Apply filters
    if (riskFilter.value) {
      data = data.filter(check => check.risk_level === riskFilter.value)
    }
    if (statusFilter.value) {
      data = data.filter(check => check.status === statusFilter.value)
    }

    checks.value = data

    // Calculate stats
    stats.value = {
      total: data.length,
      high_risk: data.filter(c => c.risk_level === 'high').length,
      critical: data.filter(c => c.risk_level === 'critical').length,
      pending_review: data.filter(c => c.status === 'manual_review').length,
      blocked: data.filter(c => c.status === 'rejected').length
    }
  } catch (error) {
    console.error('Failed to load AML checks:', error)
  } finally {
    loading.value = false
  }
}

const refreshChecks = () => {
  offset.value = 0
  loadChecks()
}

const previousPage = () => {
  if (offset.value > 0) {
    offset.value -= limit.value
    loadChecks()
  }
}

const nextPage = () => {
  offset.value += limit.value
  loadChecks()
}

const viewDetails = async (check: AMLCheck) => {
  try {
    const detailedCheck = await complianceApi.getAMLCheck(check.uuid)
    selectedCheck.value = detailedCheck
    showDetailsModal.value = true
  } catch (error) {
    console.error('Failed to load AML check details:', error)
  }
}

const markAsReported = async (uuid: string) => {
  try {
    await complianceApi.markAMLAsReported(uuid)
    await loadChecks()
    if (selectedCheck.value && selectedCheck.value.uuid === uuid) {
      selectedCheck.value.reported_to_rosfinmonitoring = true
    }
  } catch (error) {
    console.error('Failed to mark as reported:', error)
  }
}

const approveCheck = async (uuid: string) => {
  // This would need a backend endpoint
  console.log('Approve check:', uuid)
}

const rejectCheck = async (uuid: string) => {
  // This would need a backend endpoint
  console.log('Reject check:', uuid)
}

const closeModal = () => {
  showDetailsModal.value = false
  selectedCheck.value = null
}

watch(viewMode, () => {
  offset.value = 0
  loadChecks()
})

onMounted(() => {
  loadChecks()
})
</script>

<style scoped>
.aml-checks-viewer {
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
  min-width: 120px;
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

.stat-value.danger {
  color: #ef4444;
}

.stat-value.warning {
  color: #f59e0b;
}

.checks-table-container {
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
  margin-bottom: 16px;
}

.checks-table {
  width: 100%;
  border-collapse: collapse;
}

.checks-table th {
  background: #f9fafb;
  padding: 12px 16px;
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
  border-bottom: 1px solid #e5e7eb;
}

.checks-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
  font-size: 13px;
}

.checks-table tr:last-child td {
  border-bottom: none;
}

.check-row.risk-critical {
  background: #fef2f2;
}

.check-row.risk-high {
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

.risk-score {
  display: flex;
  align-items: center;
  gap: 8px;
}

.score-bar {
  width: 60px;
  height: 6px;
  background: #e5e7eb;
  border-radius: 3px;
  overflow: hidden;
}

.score-fill {
  height: 100%;
  transition: width 0.3s;
}

.score-fill.low {
  background: #10b981;
}

.score-fill.medium {
  background: #f59e0b;
}

.score-fill.high {
  background: #f97316;
}

.score-fill.critical {
  background: #ef4444;
}

.risk-badge,
.status-badge {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.risk-badge.low,
.status-badge.approved {
  background: #d1fae5;
  color: #065f46;
}

.risk-badge.medium,
.status-badge.manual_review {
  background: #fef3c7;
  color: #92400e;
}

.risk-badge.high,
.status-badge.pending {
  background: #ffedd5;
  color: #9a3412;
}

.risk-badge.critical,
.status-badge.rejected {
  background: #fee2e2;
  color: #991b1b;
}

.reported-badge {
  color: #10b981;
  font-weight: 600;
}

.not-reported-badge {
  color: #ef4444;
}

.action-buttons {
  display: flex;
  gap: 4px;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
}

.page-info {
  font-size: 13px;
  color: #666;
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

.check-details {
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
}

.checks-list {
  margin: 0;
  padding-left: 20px;
}

.checks-list li {
  margin-bottom: 8px;
  font-size: 14px;
}

.flags-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.flag-item {
  padding: 12px;
  border-radius: 6px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  font-size: 14px;
}

.flag-item.danger {
  background: #fef2f2;
  border-color: #fecaca;
}
</style>
