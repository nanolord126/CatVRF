import axios from 'axios'

const API_BASE = '/api/hotels/b2b'

export interface HotelsB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'corporate_contracts' | 'event_venue' | 'chain_partnership' | 'franchise'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface CorporateContract {
  id: number
  uuid: string
  company_id: number
  hotel_id: number
  room_type: string
  nightly_rate: number
  rooms_allocated: number
  start_date: string
  end_date: string
  status: 'active' | 'expired' | 'cancelled'
  created_at: string
  updated_at: string
}

export interface EventBooking {
  id: number
  uuid: string
  client_id: number
  hotel_id: number
  event_type: string
  event_date: string
  attendees: number
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
  category: 'corporate_contracts' | 'event_venue' | 'chain_partnership' | 'franchise'
  source: string
  notes?: string
}

class HotelsB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<HotelsB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<HotelsB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getCorporateContracts(companyId: number): Promise<CorporateContract[]> {
    const response = await axios.get(`${API_BASE}/companies/${companyId}/contracts`)
    return response.data
  }

  async getEventBookings(clientId: number): Promise<EventBooking[]> {
    const response = await axios.get(`${API_BASE}/clients/${clientId}/events`)
    return response.data
  }

  async createEventBooking(data: any): Promise<EventBooking> {
    const response = await axios.post(`${API_BASE}/events`, data)
    return response.data
  }

  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new HotelsB2BApi()
