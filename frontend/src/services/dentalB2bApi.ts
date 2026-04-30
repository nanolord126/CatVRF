import axios from 'axios'

const API_BASE = '/api/dental/b2b'

export interface DentalB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'clinic_chain' | 'equipment' | 'supplies' | 'lab_services'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface Clinic {
  id: number
  uuid: string
  chain_id: number | null
  name: string
  address: string
  city: string
  chairs_count: number
  status: 'active' | 'inactive'
  created_at: string
  updated_at: string
}

export interface EquipmentOrder {
  id: number
  uuid: string
  clinic_id: number
  equipment_type: string
  quantity: number
  total_amount: number
  delivery_date: string
  status: 'pending' | 'processing' | 'delivered' | 'cancelled'
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
  category: 'clinic_chain' | 'equipment' | 'supplies' | 'lab_services'
  source: string
  notes?: string
}

class DentalB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<DentalB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<DentalB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getClinics(chainId?: number): Promise<Clinic[]> {
    const params = chainId ? { chain_id: chainId } : {}
    const response = await axios.get(`${API_BASE}/clinics`, { params })
    return response.data
  }

  async getEquipmentOrders(clinicId: number): Promise<EquipmentOrder[]> {
    const response = await axios.get(`${API_BASE}/clinics/${clinicId}/equipment-orders`)
    return response.data
  }

  async createEquipmentOrder(data: any): Promise<EquipmentOrder> {
    const response = await axios.post(`${API_BASE}/equipment-orders`, data)
    return response.data
  }

  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new DentalB2BApi()
