import axios from 'axios'

const API_BASE = '/api/auto/b2b'

export interface AutoB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'fleet' | 'service_contract' | 'parts' | 'leasing'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface FleetVehicle {
  id: number
  uuid: string
  fleet_id: number
  vehicle_type: string
  make: string
  model: string
  year: number
  vin: string
  license_plate: string
  acquisition_date: string
  status: 'active' | 'maintenance' | 'retired'
  created_at: string
  updated_at: string
}

export interface ServiceContract {
  id: number
  uuid: string
  client_id: number
  vehicle_count: number
  service_level: 'basic' | 'standard' | 'premium'
  start_date: string
  end_date: string
  monthly_fee: number
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
  category: 'fleet' | 'service_contract' | 'parts' | 'leasing'
  source: string
  notes?: string
}

class AutoB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<AutoB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<AutoB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getFleetVehicles(fleetId: number): Promise<FleetVehicle[]> {
    const response = await axios.get(`${API_BASE}/fleets/${fleetId}/vehicles`)
    return response.data
  }

  async getServiceContracts(clientId: number): Promise<ServiceContract[]> {
    const response = await axios.get(`${API_BASE}/clients/${clientId}/contracts`)
    return response.data
  }

  async createServiceContract(data: any): Promise<ServiceContract> {
    const response = await axios.post(`${API_BASE}/contracts`, data)
    return response.data
  }

  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new AutoB2BApi()
