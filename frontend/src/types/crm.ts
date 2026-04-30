export interface CRMLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  category: string
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  assigned_to: number | null
  assigned_to_name: string | null
  created_at: string
  updated_at: string
  converted_to_tender_id: number | null
}

export interface CRMContact {
  id: number
  uuid: string
  name: string
  initials: string
  company: string
  email: string
  phone: string
  type: 'customer' | 'supplier' | 'partner' | 'employee'
  is_primary: boolean
  position: string | null
  department: string | null
  tenant_id: number | null
  created_at: string
  updated_at: string
}

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

export interface Tender {
  id: number
  uuid: string
  creator_id: number
  crm_lead_id: number | null
  type: 'procurement' | 'sale'
  status: 'draft' | 'published' | 'active' | 'closed' | 'awarded' | 'cancelled'
  title: string
  description: string | null
  published_at: string | null
  starts_at: string | null
  ends_at: string | null
  estimated_budget: number | null
  currency: string
  delivery_terms: any | null
  payment_terms: any | null
  requires_guarantee_letter: boolean
  guarantee_letter_path: string | null
  allowed_supplier_tiers: string[] | null
  restricted_regions: string[] | null
  awarded_supplier_id: number | null
  awarded_at: string | null
  final_amount: number | null
  metadata: any | null
  created_at: string
  updated_at: string
}

export interface TenderLot {
  id: number
  uuid: string
  tender_id: number
  product_id: number | null
  product_name: string
  product_sku: string | null
  quantity: number
  unit: string
  allowed_units: string[] | null
  starting_price: number | null
  reserve_price: number | null
  request_for_price: boolean
  specifications: Record<string, any> | null
  brand: string | null
  manufacturer: string | null
  country_of_origin: string | null
  expiry_date: string | null
  requires_cold_chain: boolean
  required_documents: string[] | null
  metadata: any | null
  created_at: string
  updated_at: string
}

export interface TenderBid {
  id: number
  uuid: string
  tender_id: number
  tender_lot_id: number
  supplier_id: number
  supplier_name: string
  supplier_tier_id: number | null
  supplier_tier_name: string | null
  offered_price: number
  offered_quantity: number
  unit: string
  available_from: string | null
  delivery_date: string | null
  guarantee_letter_path: string | null
  attached_documents: string[] | null
  status: 'submitted' | 'reviewed' | 'accepted' | 'rejected' | 'withdrawn'
  rating: number | null
  review_comment: string | null
  metadata: any | null
  created_at: string
  updated_at: string
}
