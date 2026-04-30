import axios from 'axios'

const API_BASE = '/api/restaurant/b2b'

export interface RestaurantB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'catering' | 'bulk_orders' | 'corporate_events' | 'franchise'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  assigned_to: number | null
  created_at: string
  updated_at: string
}

export interface RestaurantB2BContact {
  id: number
  uuid: string
  name: string
  company: string
  email: string
  phone: string
  type: 'client' | 'supplier' | 'partner'
  is_primary: boolean
  position: string | null
  created_at: string
  updated_at: string
}

export interface CateringOrder {
  id: number
  uuid: string
  client_id: number
  event_type: string
  event_date: string
  guest_count: number
  menu_requirements: string
  budget_per_person: number
  total_budget: number
  delivery_required: boolean
  staff_required: boolean
  status: 'pending' | 'confirmed' | 'in_progress' | 'completed' | 'cancelled'
  created_at: string
  updated_at: string
}

export interface CateringOrderCreateData {
  client_id: number
  event_type: string
  event_date: string
  guest_count: number
  menu_requirements: string
  budget_per_person: number
  delivery_required: boolean
  staff_required: boolean
}

export interface LeadCreateData {
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'catering' | 'bulk_orders' | 'corporate_events' | 'franchise'
  source: string
  notes?: string
}

export interface ContactCreateData {
  name: string
  company: string
  email: string
  phone: string
  type: 'client' | 'supplier' | 'partner'
  is_primary?: boolean
  position?: string
  notes?: string
}

class RestaurantB2BApi {
  // Leads
  async getLeads(filters?: { status?: string; category?: string }): Promise<RestaurantB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async getLead(id: number): Promise<RestaurantB2BLead> {
    const response = await axios.get(`${API_BASE}/leads/${id}`)
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<RestaurantB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async updateLead(id: number, data: Partial<LeadCreateData>): Promise<RestaurantB2BLead> {
    const response = await axios.put(`${API_BASE}/leads/${id}`, data)
    return response.data
  }

  async deleteLead(id: number): Promise<void> {
    await axios.delete(`${API_BASE}/leads/${id}`)
  }

  // Contacts
  async getContacts(filters?: { type?: string }): Promise<RestaurantB2BContact[]> {
    const response = await axios.get(`${API_BASE}/contacts`, { params: filters })
    return response.data
  }

  async getContact(id: number): Promise<RestaurantB2BContact> {
    const response = await axios.get(`${API_BASE}/contacts/${id}`)
    return response.data
  }

  async createContact(data: ContactCreateData): Promise<RestaurantB2BContact> {
    const response = await axios.post(`${API_BASE}/contacts`, data)
    return response.data
  }

  async updateContact(id: number, data: Partial<ContactCreateData>): Promise<RestaurantB2BContact> {
    const response = await axios.put(`${API_BASE}/contacts/${id}`, data)
    return response.data
  }

  async deleteContact(id: number): Promise<void> {
    await axios.delete(`${API_BASE}/contacts/${id}`)
  }

  // Catering Orders
  async getCateringOrders(filters?: { status?: string }): Promise<CateringOrder[]> {
    const response = await axios.get(`${API_BASE}/catering-orders`, { params: filters })
    return response.data
  }

  async getCateringOrder(id: number): Promise<CateringOrder> {
    const response = await axios.get(`${API_BASE}/catering-orders/${id}`)
    return response.data
  }

  async createCateringOrder(data: CateringOrderCreateData): Promise<CateringOrder> {
    const response = await axios.post(`${API_BASE}/catering-orders`, data)
    return response.data
  }

  async updateCateringOrder(id: number, data: Partial<CateringOrderCreateData>): Promise<CateringOrder> {
    const response = await axios.put(`${API_BASE}/catering-orders/${id}`, data)
    return response.data
  }

  // Analytics
  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new RestaurantB2BApi()
