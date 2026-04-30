<template>
  <div class="compliance-dashboard">
    <div class="dashboard-header">
      <h1>Compliance Dashboard</h1>
      <p class="subtitle">Federal law compliance monitoring (152-ФЗ, ФЗ-161, ФЗ-115, 54-ФЗ)</p>
    </div>

    <!-- Overall Compliance Score -->
    <div class="compliance-score-card" :class="scoreClass">
      <div class="score-circle">
        <div class="score-value">{{ dashboardStats.personal_data.compliance_score }}%</div>
        <div class="score-label">Compliance Score</div>
      </div>
      <div class="score-details">
        <h3>{{ dashboardStats.personal_data.overall_status }}</h3>
        <p>{{ statusMessage }}</p>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <!-- Warehouse Licenses -->
      <div class="stat-card">
        <div class="stat-icon warehouse">📋</div>
        <div class="stat-content">
          <h3>Warehouse Licenses</h3>
          <div class="stat-numbers">
            <div class="stat-item">
              <span class="label">Total</span>
              <span class="value">{{ dashboardStats.warehouse_licenses.total }}</span>
            </div>
            <div class="stat-item">
              <span class="label">Active</span>
              <span class="value active">{{ dashboardStats.warehouse_licenses.active }}</span>
            </div>
            <div class="stat-item warning" v-if="dashboardStats.warehouse_licenses.expiring_soon > 0">
              <span class="label">Expiring Soon</span>
              <span class="value">{{ dashboardStats.warehouse_licenses.expiring_soon }}</span>
            </div>
            <div class="stat-item danger" v-if="dashboardStats.warehouse_licenses.expired > 0">
              <span class="label">Expired</span>
              <span class="value">{{ dashboardStats.warehouse_licenses.expired }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Integrations -->
      <div class="stat-card">
        <div class="stat-icon integrations">🔗</div>
        <div class="stat-content">
          <h3>Integrations</h3>
          <div class="stat-numbers">
            <div class="stat-item">
              <span class="label">Total</span>
              <span class="value">{{ dashboardStats.integrations.total }}</span>
            </div>
            <div class="stat-item">
              <span class="label">Connected</span>
              <span class="value active">{{ dashboardStats.integrations.connected }}</span>
            </div>
            <div class="stat-item" v-if="dashboardStats.integrations.disconnected > 0">
              <span class="label">Disconnected</span>
              <span class="value">{{ dashboardStats.integrations.disconnected }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Compliance -->
      <div class="stat-card">
        <div class="stat-icon payment">💳</div>
        <div class="stat-content">
          <h3>Payment Compliance</h3>
          <div class="stat-numbers">
            <div class="stat-item">
              <span class="label">ФЗ-161</span>
              <span class="value" :class="{ danger: paymentStats.fz161.violations_count > 0 }">
                {{ paymentStats.fz161.violations_count }} violations
              </span>
            </div>
            <div class="stat-item">
              <span class="label">ФЗ-115</span>
              <span class="value" :class="{ danger: paymentStats.fz115.high_risk_count > 0 }">
                {{ paymentStats.fz115.high_risk_count }} high risk
              </span>
            </div>
            <div class="stat-item">
              <span class="label">54-ФЗ</span>
              <span class="value" :class="{ warning: paymentStats.fz54.pending_receipts > 0 }">
                {{ paymentStats.fz54.pending_receipts }} pending
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
      <button @click="runComplianceCheck" class="action-btn primary" :disabled="loading">
        <span v-if="!loading">🔍 Run Compliance Check</span>
        <span v-else>Running...</span>
      </button>
      <button @click="generateAuditReport" class="action-btn secondary" :disabled="loading">
        📄 Generate Audit Report
      </button>
      <button @click="viewAuditChecklist" class="action-btn secondary">
        ✅ View Audit Checklist
      </button>
    </div>

    <!-- Compliance Check Results Modal -->
    <div v-if="showComplianceResults" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>Compliance Check Results</h2>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <div class="check-results">
            <div
              v-for="(check, name) in complianceResults.checks"
              :key="name"
              class="check-item"
              :class="check.status"
            >
              <div class="check-icon">
                {{ check.status === 'passed' ? '✅' : check.status === 'warning' ? '⚠️' : '❌' }}
              </div>
              <div class="check-details">
                <h4>{{ formatCheckName(name) }}</h4>
                <p>{{ check.message }}</p>
                <div v-if="check.recommendations && check.recommendations.length > 0" class="recommendations">
                  <strong>Recommendations:</strong>
                  <ul>
                    <li v-for="rec in check.recommendations" :key="rec">{{ rec }}</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Audit Report Modal -->
    <div v-if="showAuditReport" class="modal-overlay" @click="closeModal">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h2>Audit Report</h2>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <pre class="report-content">{{ auditReport.report }}</pre>
        </div>
      </div>
    </div>

    <!-- Audit Checklist Modal -->
    <div v-if="showAuditChecklist" class="modal-overlay" @click="closeModal">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h2>Roskomnadzor Audit Checklist</h2>
          <button @click="closeModal" class="close-btn">&times;</button>
        </div>
        <div class="modal-body">
          <div class="checklist-section">
            <h3>Documents</h3>
            <ul>
              <li v-for="doc in auditChecklist.documents" :key="doc">{{ doc }}</li>
            </ul>
          </div>
          <div class="checklist-section">
            <h3>Technical Measures</h3>
            <ul>
              <li v-for="measure in auditChecklist.technical_measures" :key="measure">{{ measure }}</li>
            </ul>
          </div>
          <div class="checklist-section">
            <h3>Evidence</h3>
            <ul>
              <li v-for="evidence in auditChecklist.evidence" :key="evidence">{{ evidence }}</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import complianceApi from '@/services/complianceApi'
import type { ComplianceCheckResult, ComplianceDashboardStats, PaymentComplianceStats, AuditChecklist } from '@/services/complianceApi'

const loading = ref(false)
const dashboardStats = ref<ComplianceDashboardStats>({
  personal_data: { compliance_score: 0, overall_status: 'unknown' },
  warehouse_licenses: { total: 0, active: 0, expiring_soon: 0, expired: 0 },
  integrations: { total: 0, connected: 0, disconnected: 0 }
})
const paymentStats = ref<PaymentComplianceStats>({
  fz161: { violations_count: 0, blocked_payments: 0, transaction_volume: 0 },
  fz115: { total_checks: 0, high_risk_count: 0, blocked_count: 0, pending_review: 0 },
  fz54: { total_receipts: 0, pending_receipts: 0, failed_receipts: 0, confirmed_receipts: 0 }
})
const complianceResults = ref<ComplianceCheckResult | null>(null)
const auditReport = ref<{ report: string; generated_at: string } | null>(null)
const auditChecklist = ref<AuditChecklist | null>(null)

const showComplianceResults = ref(false)
const showAuditReport = ref(false)
const showAuditChecklist = ref(false)

const scoreClass = computed(() => {
  const score = dashboardStats.value.personal_data.compliance_score
  if (score === 100) return 'excellent'
  if (score >= 90) return 'good'
  if (score >= 70) return 'warning'
  return 'danger'
})

const statusMessage = computed(() => {
  const status = dashboardStats.value.personal_data.overall_status
  switch (status) {
    case 'compliant': return 'All compliance requirements met'
    case 'minor_issues': return 'Minor issues found, review recommended'
    case 'needs_attention': return 'Attention required for compliance'
    case 'non_compliant': return 'Critical compliance issues detected'
    default: return 'Compliance status unknown'
  }
})

const formatCheckName = (name: string): string => {
  return name
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const loadDashboardStats = async () => {
  try {
    const stats = await complianceApi.getDashboardStats()
    dashboardStats.value = stats
  } catch (error) {
    console.error('Failed to load dashboard stats:', error)
  }
}

const loadPaymentStats = async () => {
  try {
    const stats = await complianceApi.getPaymentComplianceStats('24h')
    paymentStats.value = stats
  } catch (error) {
    console.error('Failed to load payment stats:', error)
  }
}

const runComplianceCheck = async () => {
  loading.value = true
  try {
    const results = await complianceApi.checkCompliance()
    complianceResults.value = results
    showComplianceResults.value = true
    // Refresh stats after check
    await loadDashboardStats()
  } catch (error) {
    console.error('Failed to run compliance check:', error)
  } finally {
    loading.value = false
  }
}

const generateAuditReport = async () => {
  loading.value = true
  try {
    const report = await complianceApi.generateAuditReport()
    auditReport.value = report
    showAuditReport.value = true
  } catch (error) {
    console.error('Failed to generate audit report:', error)
  } finally {
    loading.value = false
  }
}

const viewAuditChecklist = async () => {
  try {
    const checklist = await complianceApi.getAuditChecklist()
    auditChecklist.value = checklist
    showAuditChecklist.value = true
  } catch (error) {
    console.error('Failed to load audit checklist:', error)
  }
}

const closeModal = () => {
  showComplianceResults.value = false
  showAuditReport.value = false
  showAuditChecklist.value = false
}

onMounted(() => {
  loadDashboardStats()
  loadPaymentStats()
})
</script>

<style scoped>
.compliance-dashboard {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

.dashboard-header {
  margin-bottom: 32px;
}

.dashboard-header h1 {
  font-size: 28px;
  font-weight: 700;
  margin: 0 0 8px 0;
  color: #1a1a1a;
}

.subtitle {
  font-size: 14px;
  color: #666;
  margin: 0;
}

.compliance-score-card {
  display: flex;
  align-items: center;
  gap: 32px;
  padding: 32px;
  border-radius: 12px;
  margin-bottom: 32px;
  color: white;
}

.compliance-score-card.excellent {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.compliance-score-card.good {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.compliance-score-card.warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.compliance-score-card.danger {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.score-circle {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  border: 4px solid rgba(255, 255, 255, 0.3);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.score-value {
  font-size: 32px;
  font-weight: 700;
}

.score-label {
  font-size: 12px;
  opacity: 0.9;
}

.score-details h3 {
  font-size: 24px;
  margin: 0 0 8px 0;
}

.score-details p {
  margin: 0;
  opacity: 0.9;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
  margin-bottom: 32px;
}

.stat-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
  display: flex;
  gap: 16px;
}

.stat-icon {
  font-size: 32px;
}

.stat-content h3 {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 16px 0;
  color: #1a1a1a;
}

.stat-numbers {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stat-item .label {
  font-size: 13px;
  color: #666;
}

.stat-item .value {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a1a;
}

.stat-item .value.active {
  color: #10b981;
}

.stat-item .value.warning {
  color: #f59e0b;
}

.stat-item .value.danger {
  color: #ef4444;
}

.stat-item.warning .value {
  color: #f59e0b;
}

.stat-item.danger .value {
  color: #ef4444;
}

.quick-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.action-btn {
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.action-btn.primary {
  background: #3b82f6;
  color: white;
}

.action-btn.primary:hover:not(:disabled) {
  background: #2563eb;
}

.action-btn.secondary {
  background: white;
  color: #1a1a1a;
  border: 1px solid #e5e7eb;
}

.action-btn.secondary:hover {
  background: #f9fafb;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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
  max-width: 600px;
  width: 90%;
  max-height: 80vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-content.large {
  max-width: 800px;
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

.check-results {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.check-item {
  display: flex;
  gap: 12px;
  padding: 16px;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.check-item.passed {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.check-item.warning {
  background: #fffbeb;
  border-color: #fde68a;
}

.check-item.failed {
  background: #fef2f2;
  border-color: #fecaca;
}

.check-icon {
  font-size: 20px;
}

.check-details h4 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.check-details p {
  margin: 0 0 8px 0;
  font-size: 13px;
  color: #666;
}

.recommendations {
  margin-top: 8px;
}

.recommendations strong {
  font-size: 12px;
  display: block;
  margin-bottom: 4px;
}

.recommendations ul {
  margin: 0;
  padding-left: 16px;
  font-size: 12px;
}

.recommendations li {
  margin-bottom: 2px;
}

.report-content {
  background: #f9fafb;
  padding: 16px;
  border-radius: 8px;
  font-size: 12px;
  line-height: 1.6;
  white-space: pre-wrap;
  max-height: 500px;
  overflow-y: auto;
}

.checklist-section {
  margin-bottom: 24px;
}

.checklist-section:last-child {
  margin-bottom: 0;
}

.checklist-section h3 {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 12px 0;
  color: #1a1a1a;
}

.checklist-section ul {
  margin: 0;
  padding-left: 20px;
}

.checklist-section li {
  margin-bottom: 8px;
  font-size: 14px;
  color: #374151;
}
</style>
