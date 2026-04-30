import axios from 'axios'

const API_BASE = '/api/pharmacy/b2b'

export interface PharmacyB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'wholesale' | 'chain_partnership' | 'distribution' | 'franchise'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface Pharmacy {
  id: number
  uuid: string
  chain_id: number | null
  name: string
  address: string
  city: string
  license_number: string
  status: 'active' | 'inactive'
  created_at: string
  updated_at: string
}

export interface WholesaleOrder {
  id: number
  uuid: string
  buyer_id: number
  product_category: string
  total_amount: number
  delivery_date: string
  status: 'pending' | 'confirmed' | 'shipped' | 'delivered' | 'cancelled'
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
  category: 'wholesale' | 'chain_partnership' | 'distribution' | 'franchise'
  source: string
  notes?: string
}

class PharmacyB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<PharmacyB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<PharmacyB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getPharmacies(chainId?: number): Promise<Pharmacy[]> {
    const params = chainId ? { chain_id: chainId } : {}
    const response = await axios.get(`${API_BASE}/pharmacies`, { params })
    return response.data
  }

  async getWholesaleOrders(buyerId: number): Promise<WholesaleOrder[]> {
    const response = await axios.get(`${API_BASE}/buyers/${buyerId}/wholesale-orders`)
    return response.data
  }

  async createWholesaleOrder(data: any): Promise<WholesaleOrder> {
    const response = await axios.post(`${API_BASE}/wholesale-orders`, data)
    return response.data
  }

  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new PharmacyB2BApi()
