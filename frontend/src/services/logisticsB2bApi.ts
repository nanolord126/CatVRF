import axios from 'axios'

const API_BASE = '/api/logistics/b2b'

export interface LogisticsB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'freight_contract' | 'warehouse' | 'fleet_management' | 'last_mile'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface FreightContract {
  id: number
  uuid: string
  client_id: number
  route: string
  cargo_type: string
  monthly_volume: number
  monthly_rate: number
  start_date: string
  end_date: string
  status: 'active' | 'expired' | 'cancelled'
  created_at: string
  updated_at: string
}

export interface Shipment {
  id: number
  uuid: string
  contract_id: number
  origin: string
  destination: string
  cargo_weight: number
  cargo_volume: number
  status: 'pending' | 'in_transit' | 'delivered' | 'cancelled'
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
  category: 'freight_contract' | 'warehouse' | 'fleet_management' | 'last_mile'
  source: string
  notes?: string
}

class LogisticsB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<LogisticsB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<LogisticsB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getFreightContracts(clientId: number): Promise<FreightContract[]> {
    const response = await axios.get(`${API_BASE}/clients/${clientId}/contracts`)
    return response.data
  }

  async getShipments(contractId: number): Promise<Shipment[]> {
    const response = await axios.get(`${API_BASE}/contracts/${contractId}/shipments`)
    return response.data
  }

  async createShipment(data: any): Promise<Shipment> {
    const response = await axios.post(`${API_BASE}/shipments`, data)
    return response.data
  }

  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new LogisticsB2BApi()
