import axios from 'axios'

const API_BASE = '/api/realestate/b2b'

export interface RealEstateB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'commercial_lease' | 'property_management' | 'investment' | 'development'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface CommercialProperty {
  id: number
  uuid: string
  owner_id: number
  property_type: 'office' | 'retail' | 'warehouse' | 'mixed_use'
  address: string
  city: string
  area_sqm: number
  monthly_rent: number
  status: 'available' | 'rented' | 'maintenance'
  created_at: string
  updated_at: string
}

export interface LeaseAgreement {
  id: number
  uuid: string
  tenant_id: number
  property_id: number
  start_date: string
  end_date: string
  monthly_rent: number
  deposit_amount: number
  status: 'active' | 'expired' | 'terminated'
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
  category: 'commercial_lease' | 'property_management' | 'investment' | 'development'
  source: string
  notes?: string
}

class RealEstateB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<RealEstateB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<RealEstateB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getCommercialProperties(filters?: { type?: string; city?: string }): Promise<CommercialProperty[]> {
    const response = await axios.get(`${API_BASE}/properties`, { params: filters })
    return response.data
  }

  async getLeaseAgreements(tenantId: number): Promise<LeaseAgreement[]> {
    const response = await axios.get(`${API_BASE}/tenants/${tenantId}/leases`)
    return response.data
  }

  async createLeaseAgreement(data: any): Promise<LeaseAgreement> {
    const response = await axios.post(`${API_BASE}/leases`, data)
    return response.data
  }

  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new RealEstateB2BApi()
