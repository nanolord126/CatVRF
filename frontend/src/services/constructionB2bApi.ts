import axios from 'axios'

const API_BASE = '/api/construction/b2b'

export interface ConstructionB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: 'materials_supply' | 'equipment_rental' | 'contracting' | 'consulting'
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface MaterialsOrder {
  id: number
  uuid: string
  client_id: number
  material_type: string
  quantity: number
  unit: string
  unit_price: number
  total_amount: number
  delivery_date: string
  status: 'pending' | 'confirmed' | 'delivered' | 'cancelled'
  created_at: string
  updated_at: string
}

export interface EquipmentRental {
  id: number
  uuid: string
  client_id: number
  equipment_type: string
  start_date: string
  end_date: string
  daily_rate: number
  total_amount: number
  status: 'active' | 'completed' | 'cancelled'
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
  category: 'materials_supply' | 'equipment_rental' | 'contracting' | 'consulting'
  source: string
  notes?: string
}

class ConstructionB2BApi {
  async getLeads(filters?: { status?: string; category?: string }): Promise<ConstructionB2BLead[]> {
    const response = await axios.get(`${API_BASE}/leads`, { params: filters })
    return response.data
  }

  async createLead(data: LeadCreateData): Promise<ConstructionB2BLead> {
    const response = await axios.post(`${API_BASE}/leads`, data)
    return response.data
  }

  async getMaterialsOrders(clientId: number): Promise<MaterialsOrder[]> {
    const response = await axios.get(`${API_BASE}/clients/${clientId}/materials-orders`)
    return response.data
  }

  async getEquipmentRentals(clientId: number): Promise<EquipmentRental[]> {
    const response = await axios.get(`${API_BASE}/clients/${clientId}/equipment-rentals`)
    return response.data
  }

  async createMaterialsOrder(data: any): Promise<MaterialsOrder> {
    const response = await axios.post(`${API_BASE}/materials-orders`, data)
    return response.data
  }

  async getStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/stats`)
    return response.data
  }
}

export default new ConstructionB2BApi()
