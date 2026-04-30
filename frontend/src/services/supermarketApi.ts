import axios from 'axios'

const API_BASE = '/api/supermarket'

export interface SupplierPenalty {
  id: number
  uuid: string
  supplier_id: number
  supplier_name: string
  affected_business_id: number | null
  affected_business_name: string | null
  document_id: number | null
  supply_chain_link_id: number | null
  supply_amount: number
  penalty_amount: number
  platform_share: number
  business_share: number
  status: 'pending' | 'charged' | 'paid' | 'disputed' | 'waived'
  reason: string | null
  expired_products: any[] | null
  expired_quantity: number
  charged_at: string | null
  paid_at: string | null
  created_at: string
  updated_at: string
}

export interface Warehouse {
  id: number
  uuid: string
  owner_id: number
  owner_name: string
  type: 'b2b' | 'b2c' | 'mixed'
  name: string
  address: string
  city: string
  region: string
  postal_code: string
  latitude: number | null
  longitude: number | null
  area: number | null
  capacity: number | null
  has_cold_storage: boolean
  has_freezer: boolean
  storage_zones: any[] | null
  status: 'active' | 'inactive' | 'maintenance'
  created_at: string
  updated_at: string
}

export interface SupplierRegistration {
  id: number
  uuid: string
  user_id: number
  supplier_tier_id: number
  supplier_tier_name: string
  crm_contact_id: number | null
  registration_type: 'b2b_only' | 'b2c_only' | 'both'
  company_name: string
  inn: string
  kpp: string | null
  ogrn: string | null
  legal_address: string
  actual_address: string | null
  bank_name: string | null
  bik: string | null
  account_number: string | null
  correspondent_account: string | null
  contact_person: string
  contact_phone: string
  contact_email: string
  guarantee_letter_path: string | null
  attached_documents: string[] | null
  warehouse_ids: number[] | null
  status: 'pending' | 'under_review' | 'approved' | 'rejected' | 'suspended'
  approved_at: string | null
  approved_by: number | null
  rejection_reason: string | null
  created_at: string
  updated_at: string
}

export interface WarehouseCreateData {
  name: string
  address: string
  city: string
  region: string
  postal_code: string
  latitude?: number
  longitude?: number
  area?: number
  capacity?: number
  has_cold_storage?: boolean
  has_freezer?: boolean
  type?: 'b2b' | 'b2c' | 'mixed'
}

export interface SupplierRegistrationCreateData {
  user_id: number
  supplier_tier_id: number
  crm_contact_id?: number
  registration_type: 'b2b_only' | 'b2c_only' | 'both'
  company_name: string
  inn: string
  kpp?: string
  ogrn?: string
  legal_address: string
  actual_address?: string
  bank_name?: string
  bik?: string
  account_number?: string
  correspondent_account?: string
  contact_person: string
  contact_phone: string
  contact_email: string
  warehouses?: WarehouseCreateData[]
  guarantee_letter?: File
  attached_documents?: File[]
}

class SupermarketApi {
  // Supplier Penalties
  async getPenalties(filters?: { status?: string; supplier_id?: number }): Promise<SupplierPenalty[]> {
    const response = await axios.get(`${API_BASE}/penalties`, { params: filters })
    return response.data
  }

  async getPenalty(id: number): Promise<SupplierPenalty> {
    const response = await axios.get(`${API_BASE}/penalties/${id}`)
    return response.data
  }

  async getPenaltyStats(supplierId?: number): Promise<any> {
    const params = supplierId ? { supplier_id: supplierId } : {}
    const response = await axios.get(`${API_BASE}/penalties/stats`, { params })
    return response.data
  }

  async markPenaltyAsPaid(id: number): Promise<SupplierPenalty> {
    const response = await axios.post(`${API_BASE}/penalties/${id}/mark-paid`)
    return response.data
  }

  async waivePenalty(id: number, reason: string): Promise<SupplierPenalty> {
    const response = await axios.post(`${API_BASE}/penalties/${id}/waive`, { reason })
    return response.data
  }

  // Warehouses
  async getWarehouses(filters?: { type?: string; status?: string; owner_id?: number }): Promise<Warehouse[]> {
    const response = await axios.get(`${API_BASE}/warehouses`, { params: filters })
    return response.data
  }

  async getWarehouse(id: number): Promise<Warehouse> {
    const response = await axios.get(`${API_BASE}/warehouses/${id}`)
    return response.data
  }

  async createWarehouse(data: WarehouseCreateData): Promise<Warehouse> {
    const response = await axios.post(`${API_BASE}/warehouses`, data)
    return response.data
  }

  async updateWarehouse(id: number, data: Partial<WarehouseCreateData>): Promise<Warehouse> {
    const response = await axios.put(`${API_BASE}/warehouses/${id}`, data)
    return response.data
  }

  async deleteWarehouse(id: number): Promise<void> {
    await axios.delete(`${API_BASE}/warehouses/${id}`)
  }

  async getWarehouseStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/warehouses/stats`)
    return response.data
  }

  // Supplier Registrations
  async getRegistrations(filters?: { status?: string; type?: string }): Promise<SupplierRegistration[]> {
    const response = await axios.get(`${API_BASE}/registrations`, { params: filters })
    return response.data
  }

  async getRegistration(id: number): Promise<SupplierRegistration> {
    const response = await axios.get(`${API_BASE}/registrations/${id}`)
    return response.data
  }

  async createRegistration(data: FormData): Promise<SupplierRegistration> {
    const response = await axios.post(`${API_BASE}/registrations`, data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  }

  async approveRegistration(id: number): Promise<SupplierRegistration> {
    const response = await axios.post(`${API_BASE}/registrations/${id}/approve`)
    return response.data
  }

  async rejectRegistration(id: number, reason: string): Promise<SupplierRegistration> {
    const response = await axios.post(`${API_BASE}/registrations/${id}/reject`, { reason })
    return response.data
  }

  async suspendRegistration(id: number): Promise<SupplierRegistration> {
    const response = await axios.post(`${API_BASE}/registrations/${id}/suspend`)
    return response.data
  }

  async getRegistrationStats(): Promise<any> {
    const response = await axios.get(`${API_BASE}/registrations/stats`)
    return response.data
  }

  // Stock Movement
  async moveStockToWarehouse(warehouseId: number, data: {
    product_id: number
    quantity: number
    batch_number?: string
  }): Promise<any> {
    const response = await axios.post(`${API_BASE}/warehouses/${warehouseId}/move-stock`, data)
    return response.data
  }

  async getWarehouseStock(warehouseId: number): Promise<any> {
    const response = await axios.get(`${API_BASE}/warehouses/${warehouseId}/stock`)
    return response.data
  }
}

export default new SupermarketApi()
