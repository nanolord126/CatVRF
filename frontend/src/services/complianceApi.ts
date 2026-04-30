import axios from 'axios'

const API_BASE = '/api'

// 152-ФЗ Personal Data Compliance Types
export interface ComplianceCheckResult {
  overall_status: 'compliant' | 'minor_issues' | 'needs_attention' | 'non_compliant'
  score: number
  checks: Record<string, {
    status: 'passed' | 'warning' | 'failed'
    message: string
    recommendations?: string[]
  }>
  critical_issues: Array<{
    check: string
    message: string
  }>
  warnings: Array<{
    check: string
    message: string
  }>
  recommendations: string[]
}

export interface AuditChecklist {
  documents: string[]
  technical_measures: string[]
  evidence: string[]
}

// Warehouse License Types
export interface WarehouseLicense {
  id: string
  warehouse_id: number
  warehouse_name?: string
  license_type: 'pharmacy' | 'medicine_storage' | 'narcotic' | 'psychotropic' | 'poisonous'
  license_number: string
  issued_date: string
  expiry_date: string
  issued_by: string
  is_active: boolean
  days_until_expiry: number
  is_expiring_soon: boolean
  is_expired: boolean
}

// Compliance Integration Types
export interface ComplianceIntegration {
  id: number
  uuid: string
  type: string
  inn: string
  status: string
  last_checked_at: string | null
  error_message: string | null
}

// Dashboard Stats Types
export interface ComplianceDashboardStats {
  personal_data: {
    compliance_score: number
    overall_status: string
  }
  warehouse_licenses: {
    total: number
    active: number
    expiring_soon: number
    expired: number
  }
  integrations: {
    total: number
    connected: number
    disconnected: number
  }
}

// Payment Compliance Types (ФЗ-161, ФЗ-115, 54-ФЗ)
export interface PaymentRule {
  code: string
  category: string
  description: string
  value: number | string | boolean
  effective_from: string
  effective_until: string | null
  is_active: boolean
}

export interface AMLCheck {
  uuid: string
  user_id: number
  risk_score: number
  risk_level: 'low' | 'medium' | 'high' | 'critical'
  checks_performed: string[]
  flags: string[]
  status: 'pending' | 'approved' | 'rejected' | 'manual_review'
  checked_at: string
  reported_to_rosfinmonitoring: boolean
}

export interface FiscalReceipt {
  uuid: string
  payment_intent_uuid: string
  fiscal_sign: string
  fiscal_document_number: number
  fiscal_document_datetime: string
  operation_type: string
  amount: number
  status: 'pending' | 'confirmed' | 'failed'
  retry_count: number
  error_message: string | null
  created_at: string
}

export interface PaymentComplianceStats {
  fz161: {
    violations_count: number
    blocked_payments: number
    transaction_volume: number
  }
  fz115: {
    total_checks: number
    high_risk_count: number
    blocked_count: number
    pending_review: number
  }
  fz54: {
    total_receipts: number
    pending_receipts: number
    failed_receipts: number
    confirmed_receipts: number
  }
}

class ComplianceApi {
  // 152-ФЗ Personal Data Compliance
  async checkCompliance(): Promise<ComplianceCheckResult> {
    const response = await axios.get(`${API_BASE}/compliance/152fz/check`)
    return response.data.data
  }

  async generateAuditReport(): Promise<{ report: string; generated_at: string }> {
    const response = await axios.get(`${API_BASE}/compliance/152fz/audit-report`)
    return response.data.data
  }

  async getAuditChecklist(): Promise<AuditChecklist> {
    const response = await axios.get(`${API_BASE}/compliance/152fz/checklist`)
    return response.data.data
  }

  // Warehouse Licenses
  async getWarehouseLicenses(filters?: {
    tenant_id?: number
    license_type?: string
    is_active?: boolean
  }): Promise<WarehouseLicense[]> {
    const response = await axios.get(`${API_BASE}/compliance/warehouse-licenses`, { params: filters })
    return response.data.data
  }

  async createWarehouseLicense(data: {
    warehouse_id: number
    license_type: string
    license_number: string
    issued_date: string
    expiry_date: string
    issued_by: string
  }): Promise<WarehouseLicense> {
    const response = await axios.post(`${API_BASE}/compliance/warehouse-licenses`, data)
    return response.data.data
  }

  async revokeWarehouseLicense(id: string, reason: string): Promise<WarehouseLicense> {
    const response = await axios.post(`${API_BASE}/compliance/warehouse-licenses/${id}/revoke`, {
      revocation_reason: reason
    })
    return response.data.data
  }

  // Compliance Integrations
  async getIntegrations(filters?: {
    tenant_id?: number
    type?: string
  }): Promise<ComplianceIntegration[]> {
    const response = await axios.get(`${API_BASE}/compliance/integrations`, { params: filters })
    return response.data.data
  }

  async getIntegrationStatus(type: string): Promise<{
    type: string
    has_active_integration: boolean
    integration: ComplianceIntegration | null
  }> {
    const response = await axios.get(`${API_BASE}/compliance/integrations/${type}/status`)
    return response.data.data
  }

  // PII Management
  async getPiiDeletionRequests(filters?: {
    status?: string
  }): Promise<any[]> {
    const response = await axios.get(`${API_BASE}/compliance/pii/deletion-requests`, { params: filters })
    return response.data.data
  }

  async getPiiConsents(filters?: {
    user_id?: number
    consent_type?: string
  }): Promise<any[]> {
    const response = await axios.get(`${API_BASE}/compliance/pii/consents`, { params: filters })
    return response.data.data
  }

  // Dashboard Stats
  async getDashboardStats(): Promise<ComplianceDashboardStats> {
    const response = await axios.get(`${API_BASE}/compliance/dashboard/stats`)
    return response.data.data
  }

  // Payment Compliance - ФЗ-161
  async validateFZ161(data: {
    amount_kopecks: number
    user_id?: number
    tenant_id?: number
    settlement_method: string
    fraud_check_passed?: boolean
  }): Promise<any> {
    const response = await axios.post(`${API_BASE}/compliance/fz161/validate`, data)
    return response.data.data
  }

  async getPaymentRules(category?: string): Promise<Record<string, PaymentRule[]>> {
    const response = await axios.get(`${API_BASE}/compliance/fz161/rules`, { params: { category } })
    return response.data.data
  }

  async getRuleHistory(code: string): Promise<any[]> {
    const response = await axios.get(`${API_BASE}/compliance/fz161/rules/${code}/history`)
    return response.data.data
  }

  async exportRules(from: string, to: string): Promise<any> {
    const response = await axios.get(`${API_BASE}/compliance/fz161/rules/export`, {
      params: { from, to }
    })
    return response.data.data
  }

  // Payment Compliance - ФЗ-115 (AML)
  async getAMLCheck(uuid: string): Promise<AMLCheck> {
    const response = await axios.get(`${API_BASE}/compliance/fz115/aml/${uuid}`)
    return response.data.data
  }

  async getUserAMLChecks(userId: number, limit?: number, offset?: number): Promise<AMLCheck[]> {
    const response = await axios.get(`${API_BASE}/compliance/fz115/aml/user/${userId}`, {
      params: { limit, offset }
    })
    return response.data.data
  }

  async getPendingAMLChecks(limit?: number): Promise<AMLCheck[]> {
    const response = await axios.get(`${API_BASE}/compliance/fz115/aml/pending`, {
      params: { limit }
    })
    return response.data.data
  }

  async markAMLAsReported(uuid: string): Promise<AMLCheck> {
    const response = await axios.post(`${API_BASE}/compliance/fz115/aml/${uuid}/mark-reported`)
    return response.data.data
  }

  // Payment Compliance - 54-ФЗ (Fiscalization)
  async getFiscalReceipt(uuid: string): Promise<FiscalReceipt> {
    const response = await axios.get(`${API_BASE}/compliance/fz54/fiscal/${uuid}`)
    return response.data.data
  }

  async getPaymentIntentReceipts(paymentIntentUuid: string): Promise<FiscalReceipt[]> {
    const response = await axios.get(`${API_BASE}/compliance/fz54/fiscal/payment/${paymentIntentUuid}`)
    return response.data.data
  }

  async getPendingFiscalReceipts(limit?: number): Promise<FiscalReceipt[]> {
    const response = await axios.get(`${API_BASE}/compliance/fz54/fiscal/pending`, {
      params: { limit }
    })
    return response.data.data
  }

  async retryFiscalReceipt(uuid: string): Promise<{ message: string }> {
    const response = await axios.post(`${API_BASE}/compliance/fz54/fiscal/${uuid}/retry`)
    return response.data
  }

  // Domain AML Routes (using App\Domains\Payments\AML\AMLService)
  async getDomainAMLCheck(uuid: string): Promise<AMLCheck> {
    const response = await axios.get(`${API_BASE}/compliance/aml/check/${uuid}`)
    return response.data.data
  }

  async getDomainUserAMLChecks(userId: number, limit?: number, offset?: number): Promise<AMLCheck[]> {
    const response = await axios.get(`${API_BASE}/compliance/aml/user/${userId}`, {
      params: { limit, offset }
    })
    return response.data.data
  }

  async getDomainPendingAMLChecks(limit?: number): Promise<AMLCheck[]> {
    const response = await axios.get(`${API_BASE}/compliance/aml/pending`, {
      params: { limit }
    })
    return response.data.data
  }

  async getDomainAMLStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/compliance/aml/stats`)
    return response.data.data
  }

  async getHighRiskAMLUsers(threshold?: number, limit?: number): Promise<any[]> {
    const response = await axios.get(`${API_BASE}/compliance/aml/high-risk-users`, {
      params: { threshold, limit }
    })
    return response.data.data
  }

  async getSuspiciousOperations(limit?: number): Promise<any[]> {
    const response = await axios.get(`${API_BASE}/compliance/aml/suspicious`, {
      params: { limit }
    })
    return response.data.data
  }

  // Payment Compliance Dashboard
  async getPaymentComplianceStats(period?: string, tenantId?: number): Promise<PaymentComplianceStats> {
    const response = await axios.get(`${API_BASE}/compliance/dashboard/stats`, {
      params: { period, tenant_id: tenantId }
    })
    return response.data.data
  }

  async getComplianceTrends(metric: string, period?: string): Promise<any> {
    const response = await axios.get(`${API_BASE}/compliance/dashboard/trends`, {
      params: { metric, period }
    })
    return response.data.data
  }
}

export default new ComplianceApi()
