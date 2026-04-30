import axios from 'axios'

const API_BASE = '/api/flowers/b2b'

export interface FlowersB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'wholesale' | 'event_contract' | 'subscription' | 'franchise'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface EventContract {
  id: number
  uuid: string
  client_id: number
  event_type: string
  event_date: string
  flower_types: string[]
  total_amount: number
  status: 'pending' | 'confirmed' | 'delivered' | 'cancelled'
  created_at: string
  updated_at: string
}

export interface Subscription {
  id: number
  uuid: string
  client_id: number
  delivery_frequency: 'weekly' | 'biweekly' | 'monthly'
  flower_types: string[]
  monthly_amount: number
  status: 'active' | 'paused' | 'cancelled'
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
  category: 'wholesale' | 'event_contract' | 'subscription' | 'franchise'
  source: string
  notes?: string
}

class FlowersB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<FlowersB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<FlowersB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getEventContracts(clientId: number): Promise<EventContract[]> {
    const response = await axios.get(`${API_BASE}/clients/${clientId}/event-contracts`)
    return response.data
  }

  async getSubscriptions(clientId: number): Promise<Subscription[]> {
    const response = await axios.get(`${API_BASE}/clients/${clientId}/subscriptions`)
    return response.data
  }

  async createSubscription(data: any): Promise<Subscription> {
    const response = await axios.post(`${API_BASE}/subscriptions`, data)
    return response.data
  }

  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new FlowersB2BApi()
