import axios from 'axios'

const API_BASE = '/api/electronics/b2b'

export interface ElectronicsB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'wholesale' | 'enterprise_solutions' | 'maintenance_contract' | 'recycling'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface WholesaleOrder {
  id: number
  uuid: string
  buyer_id: number
  product_category: string
  total_quantity: number
  total_amount: number
  delivery_date: string
  status: 'pending' | 'confirmed' | 'shipped' | 'delivered' | 'cancelled'
  created_at: string
  updated_at: string
}

export interface MaintenanceContract {
  id: number
  uuid: string
  client_id: number
  equipment_count: number
  service_level: 'basic' | 'standard' | 'premium'
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
  category: 'wholesale' | 'enterprise_solutions' | 'maintenance_contract' | 'recycling'
  source: string
  notes?: string
}

class ElectronicsB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<ElectronicsB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<ElectronicsB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getWholesaleOrders(buyerId: number): Promise<WholesaleOrder[]> {
    const response = await axios.get(`${API_BASE}/buyers/${buyerId}/wholesale-orders`)
    return response.data
  }

  async getMaintenanceContracts(clientId: number): Promise<MaintenanceContract[]> {
    const response = await axios.get(`${API_BASE}/clients/${clientId}/maintenance-contracts`)
    return response.data
  }

  async createMaintenanceContract(data: any): Promise<MaintenanceContract> {
    const response = await axios.post(`${API_BASE}/maintenance-contracts`, data)
    return response.data
  }

  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new ElectronicsB2BApi()
