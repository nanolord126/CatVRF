import axios from 'axios'

const API_BASE = '/api/grocery/b2b'

export interface GroceryB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'wholesale' | 'distribution' | 'private_label' | 'franchise'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface DistributionAgreement {
  id: number
  uuid: string
  supplier_id: number
  retailer_id: number
  product_categories: string[]
  territory: string
  start_date: string
  end_date: string
  status: 'active' | 'expired' | 'cancelled'
  created_at: string
  updated_at: string
}

export interface BulkOrder {
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

export interface LeadCreateData {
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'wholesale' | 'distribution' | 'private_label' | 'franchise'
  source: string
  notes?: string
}

class GroceryB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<GroceryB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<GroceryB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getDistributionAgreements(supplierId: number): Promise<DistributionAgreement[]> {
    const response = await axios.get(`${API_BASE}/suppliers/${supplierId}/agreements`)
    return response.data
  }

  async getBulkOrders(buyerId: number): Promise<BulkOrder[]> {
    const response = await axios.get(`${API_BASE}/buyers/${buyerId}/bulk-orders`)
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

export default new GroceryB2BApi()
