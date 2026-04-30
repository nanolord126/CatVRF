import axios from 'axios'

const API_BASE = '/api/beauty/b2b'

export interface BeautyB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'salon_chain' | 'wholesale_products' | 'equipment' | 'franchise'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface Salon {
  id: number
  uuid: string
  chain_id: number | null
  name: string
  address: string
  city: string
  services_count: number
  staff_count: number
  status: 'active' | 'inactive'
  created_at: string
  updated_at: string
}

export interface BulkOrder {
  id: number
  uuid: string
  client_id: number
  product_category: string
  total_amount: number
  delivery_date: string
  status: 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled'
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
  category: 'salon_chain' | 'wholesale_products' | 'equipment' | 'franchise'
  source: string
  notes?: string
}

class BeautyB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<BeautyB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<BeautyB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getSalons(chainId?: number): Promise<Salon[]> {
    const params = chainId ? { chain_id: chainId } : {}
    const response = await axios.get(`${API_BASE}/salons`, { params })
    return response.data
  }

  async getBulkOrders(clientId: number): Promise<BulkOrder[]> {
    const response = await axios.get(`${API_BASE}/clients/${clientId}/bulk-orders`)
    return response.data
  }

  async createBulkOrder(data: any): Promise<BulkOrder> {
    const response = await axios.post(`${API_BASE}/bulk-orders`, data)
    return response.data
  }

  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new BeautyB2BApi()
