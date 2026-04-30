import axios from 'axios'

const API_BASE = '/api/medical/b2b'

export interface MedicalB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'equipment' | 'supplies' | 'software' | 'consulting'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface EquipmentOrder {
  id: number
  uuid: string
  facility_id: number
  equipment_type: string
  quantity: number
  total_amount: number
  delivery_date: string
  status: 'pending' | 'processing' | 'delivered' | 'cancelled'
  created_at: string
  updated_at: string
}

export interface SoftwareLicense {
  id: number
  uuid: string
  client_id: number
  software_type: string
  user_count: number
  monthly_fee: number
  start_date: string
  end_date: string
  status: 'active' | 'expired' | 'cancelled'
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
  category: 'equipment' | 'supplies' | 'software' | 'consulting'
  source: string
  notes?: string
}

class MedicalB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<MedicalB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<MedicalB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getEquipmentOrders(facilityId: number): Promise<EquipmentOrder[]> {
    const response = await axios.get(`${API_BASE}/facilities/${facilityId}/equipment-orders`)
    return response.data
  }

  async getSoftwareLicenses(clientId: number): Promise<SoftwareLicense[]> {
    const response = await axios.get(`${API_BASE}/clients/${clientId}/software-licenses`)
    return response.data
  }

  async createSoftwareLicense(data: any): Promise<SoftwareLicense> {
    const response = await axios.post(`${API_BASE}/software-licenses`, data)
    return response.data
  }

  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new MedicalB2BApi()
