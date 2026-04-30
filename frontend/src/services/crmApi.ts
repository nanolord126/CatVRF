import axios from 'axios'

const API_BASE = '/api/crm'

export interface CRMLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: string
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  assigned_to: number | null
  assigned_to_name: string | null
  created_at: string
  updated_at: string
  converted_to_tender_id: number | null
}

export interface CRMContact {
  id: number
  uuid: string
  name: string
  initials: string
  company: string
  email: string
  phone: string
  type: 'customer' | 'supplier' | 'partner' | 'employee'
  is_primary: boolean
  position: string | null
  department: string | null
  tenant_id: number | null
  created_at: string
  updated_at: string
}

export interface LeadCreateData {
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: string
  source: string
  notes?: string
}

export interface ContactCreateData {
  name: string
  company: string
  email: string
  phone: string
  type: 'customer' | 'supplier' | 'partner' | 'employee'
  is_primary?: boolean
  position?: string
  department?: string
  notes?: string
}

export interface CRMStats {
  total_contacts: number
  active_leads: number
  tenders: number
  conversion: number
}

class CRMApi {
  // Leads
  async getLeads(filters?: { status?: string; assigned_to?: number }): Promise<CRMLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async getLead(id: number): Promise<CRMLead> {
    const response = await axios.get(`${API_BASE}/leads/${id}`)
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<CRMLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async updateLead(id: number, data: Partial<LeadCreateData>): Promise<CRMLead> {
    const response = await axios.put(`${API_BASE}/leads/${id}`, data)
    return response.data
  }

  async updateLeadStatus(id: number, status: string): Promise<CRMLead> {
    const response = await axios.patch(`${API_BASE}/leads/${id}/status`, { status })
    return response.data
  }

  async convertLeadToTender(leadId: number): Promise<any> {
    const response = await axios.post(`${API_BASE}/leads/${leadId}/convert-to-tender`)
    return response.data
  }

  async deleteLead(id: number): Promise<void> {
    await axios.delete(`${API_BASE}/leads/${id}`)
  }

  async getLeadsForTenders(): Promise<CRMLead[]> {
    const response = await axios.get(`${API_BASE}/leads/for-tenders`)
    return response.data
  }

  // Contacts
  async getContacts(filters?: { type?: string; is_primary?: boolean }): Promise<CRMContact[]> {
    const response = await axios.get(`${API_BASE}/contacts`, { params: filters })
    return response.data
  }

  async getContact(id: number): Promise<CRMContact> {
    const response = await axios.get(`${API_BASE}/contacts/${id}`)
    return response.data
  }

  async createContact(data: ContactCreateData): Promise<CRMContact> {
    const response = await axios.post(`${API_BASE}/contacts`, data)
    return response.data
  }

  async updateContact(id: number, data: Partial<ContactCreateData>): Promise<CRMContact> {
    const response = await axios.put(`${API_BASE}/contacts/${id}`, data)
    return response.data
  }

  async deleteContact(id: number): Promise<void> {
    await axios.delete(`${API_BASE}/contacts/${id}`)
  }

  // Analytics
  async getStats(): Promise<CRMStats> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }

  async getFunnelStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/analytics/funnel`)
    return response.data
  }

  async getConversionByChannel(): Promise<any[]> {
    const response = await axios.get(`${API_BASE}/analytics/conversion-by-channel`)
    return response.data
  }

  async getActivityStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/analytics/activity`)
    return response.data
  }

  async getTopSegments(): Promise<any[]> {
    const response = await axios.get(`${API_BASE}/analytics/top-segments`)
    return response.data
  }

  async getMonthlyLeads(): Promise<any[]> {
    const response = await axios.get(`${API_BASE}/analytics/monthly-leads`)
    return response.data
  }

  async getStatusDistribution(): Promise<any[]> {
    const response = await axios.get(`${API_BASE}/analytics/status-distribution`)
    return response.data
  }
}

export default new CRMApi()
