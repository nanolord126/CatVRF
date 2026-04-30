import axios from 'axios'

const API_BASE = '/api'

export interface Tender {
  id: number
  business_id: number
  tenant_id: number
  vertical_id: number
  title: string
  description?: string
  type: 'supply' | 'one_time'
  min_amount: number
  duration_months?: number
  starts_at: string
  ends_at: string
  delivery_start_date?: string
  delivery_end_date?: string
  status: 'draft' | 'active' | 'closed' | 'cancelled' | 'completed'
  winning_bid_id?: number
  requirements?: any
  delivery_terms?: any
  payment_terms?: any
  requires_platform_guarantee?: boolean
  guarantee_fee_amount?: number
  guarantee_paid_at?: string
  hold_amount?: number
  hold_frozen_at?: string
  hold_release_date?: string
  credit_score?: number
  credit_risk_level?: string
  fraud_check_passed?: boolean
  documents_signed?: boolean
  created_at: string
  updated_at: string
}

export interface TenderBid {
  id: number
  tender_id: number
  supplier_id: number
  tenant_id: number
  bid_amount: number
  proposal?: string
  terms?: any
  status: 'submitted' | 'withdrawn' | 'under_review' | 'accepted' | 'rejected'
  submitted_at: string
  withdrawn_at?: string
  reviewed_at?: string
  rejection_reason?: string
  guarantee_amount?: number
  guarantee_frozen_at?: string
  guarantee_released_at?: string
  created_at: string
}

export interface TenderDocument {
  id: number
  tender_id: number
  bid_id?: number
  type: 'contract' | 'upd' | 'invoice' | 'act'
  number: string
  date: string
  amount?: number
  commission_amount?: number
  commission_percent: number
  file_path?: string
  status: 'draft' | 'generated' | 'signed' | 'cancelled'
  generated_at?: string
  signed_at?: string
  signed_by?: number
}

export interface TenderCreateData {
  title: string
  description?: string
  type: 'supply' | 'one_time'
  min_amount: number
  duration_months?: number
  ends_at: string
  delivery_start_date?: string
  delivery_end_date?: string
  requirements?: any
  delivery_terms?: any
  payment_terms?: any
}

export interface BidCreateData {
  bid_amount: number
  proposal?: string
  terms?: any
}

export interface GuaranteeAssessment {
  eligible: boolean
  fee_amount: number
  credit_assessment: {
    score: number
    risk_level: string
    eligible: boolean
    vertical: string
    features: any
  }
  fraud_check: {
    passed: boolean
    notes?: string
    vertical_risk: boolean
    vertical_indicators: any[]
  }
}

class TenderApi {
  // Business endpoints
  async listTenders(status?: string): Promise<any> {
    const params = status ? { status } : {}
    const response = await axios.get(`${API_BASE}/business/tenders`, { params })
    return response.data
  }

  async getTender(id: number): Promise<Tender> {
    const response = await axios.get(`${API_BASE}/business/tenders/${id}`)
    return response.data
  }

  async createTender(data: TenderCreateData, verticalId: number): Promise<Tender> {
    const response = await axios.post(`${API_BASE}/business/tenders`, {
      ...data,
      vertical_id: verticalId
    })
    return response.data
  }

  async activateTender(id: number): Promise<Tender> {
    const response = await axios.post(`${API_BASE}/business/tenders/${id}/activate`)
    return response.data
  }

  async closeTender(id: number): Promise<Tender> {
    const response = await axios.post(`${API_BASE}/business/tenders/${id}/close`)
    return response.data
  }

  async selectWinner(tenderId: number, bidId: number): Promise<any> {
    const response = await axios.post(
      `${API_BASE}/business/tenders/${tenderId}/select-winner/${bidId}`
    )
    return response.data
  }

  async requestGuarantee(tenderId: number): Promise<GuaranteeAssessment> {
    const response = await axios.post(
      `${API_BASE}/business/tenders/${tenderId}/request-guarantee`
    )
    return response.data
  }

  async payGuaranteeFee(tenderId: number): Promise<Tender> {
    const response = await axios.post(
      `${API_BASE}/business/tenders/${tenderId}/pay-guarantee-fee`
    )
    return response.data
  }

  async addReview(tenderId: number, bidId: number, data: { rating: number; review?: string; is_public?: boolean }): Promise<any> {
    const response = await axios.post(
      `${API_BASE}/business/tenders/${tenderId}/bids/${bidId}/review`,
      data
    )
    return response.data
  }

  // Supplier endpoints
  async listAvailableTenders(verticalId?: number): Promise<any> {
    const params = verticalId ? { vertical_id: verticalId } : {}
    const response = await axios.get(`${API_BASE}/supplier/tenders/available`, { params })
    return response.data
  }

  async listMyBids(status?: string): Promise<any> {
    const params = status ? { status } : {}
    const response = await axios.get(`${API_BASE}/supplier/tenders/my-bids`, { params })
    return response.data
  }

  async submitBid(tenderId: number, data: BidCreateData): Promise<TenderBid> {
    const response = await axios.post(`${API_BASE}/supplier/tenders/${tenderId}/bids`, data)
    return response.data
  }

  async withdrawBid(bidId: number): Promise<TenderBid> {
    const response = await axios.post(`${API_BASE}/supplier/tenders/bids/${bidId}/withdraw`)
    return response.data
  }

  async checkParticipation(): Promise<{ can_participate: boolean; current_balance: number; required_balance: number; shortage: number }> {
    const response = await axios.get(`${API_BASE}/supplier/tenders/check-participation`)
    return response.data
  }

  // Document endpoints
  async generateTenderDocuments(tenderId: number, bidId: number): Promise<Record<string, TenderDocument>> {
    const response = await axios.post(
      `${API_BASE}/b2b/documents/tenders/${tenderId}/bids/${bidId}/generate`
    )
    return response.data
  }

  async signDocument(type: 'tender' | 'b2b', documentId: number): Promise<TenderDocument> {
    const response = await axios.post(`${API_BASE}/b2b/documents/${type}/${documentId}/sign`)
    return response.data
  }

  async releaseHold(tenderId: number): Promise<Tender> {
    const response = await axios.post(`${API_BASE}/b2b/documents/tenders/${tenderId}/release-hold`)
    return response.data
  }

  // Public endpoints
  async getVerticalStatistics(verticalId: number): Promise<any> {
    const response = await axios.get(`${API_BASE}/public/tenders/statistics/${verticalId}`)
    return response.data
  }

  async getSupplierReviews(supplierId: number, verticalId: number, tenantId: number): Promise<any> {
    const response = await axios.get(
      `${API_BASE}/public/tenders/suppliers/${supplierId}/reviews/${verticalId}`,
      { params: { tenant_id: tenantId } }
    )
    return response.data
  }
}

export default new TenderApi()
