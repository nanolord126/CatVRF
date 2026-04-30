import axios from 'axios'

const API_BASE = '/api/officecatering/b2b'

export interface OfficeCateringB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'daily_meals' | 'event_catering' | 'coffee_service' | 'subscription'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface CateringSubscription {
  id: number
  uuid: string
  company_id: number
  meal_type: 'breakfast' | 'lunch' | 'dinner' | 'full_day'
  employees_count: number
  daily_meals: number
  monthly_amount: number
  start_date: string
  end_date: string
  status: 'active' | 'paused' | 'cancelled'
  created_at: string
  updated_at: string
}

export interface EventOrder {
  id: number
  uuid: string
  company_id: number
  event_type: string
  event_date: string
  attendees: number
  menu_requirements: string
  total_amount: number
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled'
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
  category: 'daily_meals' | 'event_catering' | 'coffee_service' | 'subscription'
  source: string
  notes?: string
}

class OfficeCateringB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<OfficeCateringB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<OfficeCateringB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getCateringSubscriptions(companyId: number): Promise<CateringSubscription[]> {
    const response = await axios.get(`${API_BASE}/companies/${companyId}/subscriptions`)
    return response.data
  }

  async getEventOrders(companyId: number): Promise<EventOrder[]> {
    const response = await axios.get(`${API_BASE}/companies/${companyId}/events`)
    return response.data
  }

  async createEventOrder(data: any): Promise<EventOrder> {
    const response = await axios.post(`${API_BASE}/events`, data)
    return response.data
  }

  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new OfficeCateringB2BApi()
