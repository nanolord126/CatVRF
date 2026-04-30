import axios from 'axios'

const API_BASE = '/api/legal/b2b'

export interface LegalB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'retainer' | 'project' | 'consulting' | 'compliance'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface RetainerAgreement {
  id: number
  uuid: string
  client_id: number
  practice_area: string
  monthly_hours: number
  monthly_fee: number
  start_date: string
  end_date: string
  status: 'active' | 'expired' | 'cancelled'
  created_at: string
  updated_at: string
}

export interface ProjectEngagement {
  id: number
  uuid: string
  client_id: number
  project_type: string
  description: string
  estimated_hours: number
  hourly_rate: number
  total_budget: number
  status: 'pending' | 'in_progress' | 'completed' | 'cancelled'
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
  category: 'retainer' | 'project' | 'consulting' | 'compliance'
  source: string
  notes?: string
}

class LegalB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<LegalB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<LegalB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getRetainerAgreements(clientId: number): Promise<RetainerAgreement[]> {
    const response = await axios.get(`${API_BASE}/clients/${clientId}/retainers`)
    return response.data
  }

  async getProjectEngagements(clientId: number): Promise<ProjectEngagement[]> {
    const response = await axios.get(`${API_BASE}/clients/${clientId}/projects`)
    return response.data
  }

  async createProjectEngagement(data: any): Promise<ProjectEngagement> {
    const response = await axios.post(`${API_BASE}/projects`, data)
    return response.data
  }

  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new LegalB2BApi()
