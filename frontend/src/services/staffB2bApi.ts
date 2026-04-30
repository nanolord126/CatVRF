import axios from 'axios'

const API_BASE = '/api/staff/b2b'

export interface StaffB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'staffing_contract' | 'recruitment' | 'training' | 'outsourcing'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface StaffingContract {
  id: number
  uuid: string
  client_id: number
  staff_type: string
  positions_count: number
  monthly_fee: number
  start_date: string
  end_date: string
  status: 'active' | 'expired' | 'cancelled'
  created_at: string
  updated_at: string
}

export interface RecruitmentRequest {
  id: number
  uuid: string
  company_id: number
  position: string
  department: string
  experience_level: string
  salary_range: string
  vacancies_count: number
  status: 'open' | 'in_progress' | 'filled' | 'cancelled'
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
  category: 'staffing_contract' | 'recruitment' | 'training' | 'outsourcing'
  source: string
  notes?: string
}

class StaffB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<StaffB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<StaffB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getStaffingContracts(clientId: number): Promise<StaffingContract[]> {
    const response = await axios.get(`${API_BASE}/clients/${clientId}/contracts`)
    return response.data
  }

  async getRecruitmentRequests(companyId: number): Promise<RecruitmentRequest[]> {
    const response = await axios.get(`${API_BASE}/companies/${companyId}/recruitment`)
    return response.data
  }

  async createRecruitmentRequest(data: any): Promise<RecruitmentRequest> {
    const response = await axios.post(`${API_BASE}/recruitment`, data)
    return response.data
  }

  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new StaffB2BApi()
