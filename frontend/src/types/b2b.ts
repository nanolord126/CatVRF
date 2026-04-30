// Common B2B Types
export interface BaseB2BLead {
  id: number
  uuid: string
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  status: 'new' | 'contacted' | 'qualified' | 'converted' | 'lost'
  source: string
  created_at: string
  updated_at: string
}

export interface BaseB2BContact {
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

export interface BaseLeadCreateData {
  company_name: string
  contact_person: string
  contact_email: string
  contact_phone: string
  requirement: string
  budget_range: string
  source: string
  notes?: string
}

export interface BaseContactCreateData {
  name: string
  company: string
  email: string
  phone: string
  type: 'client' | 'supplier' | 'partner'
  is_primary?: boolean
  position?: string
  notes?: string
}

// Restaurant B2B
export interface RestaurantB2BLead extends BaseB2BLead {
  category: 'catering' | 'bulk_orders' | 'corporate_events' | 'franchise'
}

export interface RestaurantB2BContact extends BaseB2BContact {}

// Auto B2B
export interface AutoB2BLead extends BaseB2BLead {
  category: 'fleet' | 'service_contract' | 'parts' | 'leasing'
}

// Beauty B2B
export interface BeautyB2BLead extends BaseB2BLead {
  category: 'salon_chain' | 'wholesale_products' | 'equipment' | 'franchise'
}

// Dental B2B
export interface DentalB2BLead extends BaseB2BLead {
  category: 'clinic_chain' | 'equipment' | 'supplies' | 'lab_services'
}

// Fashion B2B
export interface FashionB2BLead extends BaseB2BLead {
  category: 'wholesale' | 'boutique_chain' | 'manufacturing' | 'franchise'
}

// Flowers B2B
export interface FlowersB2BLead extends BaseB2BLead {
  category: 'wholesale' | 'event_contract' | 'subscription' | 'franchise'
}

// Hotels B2B
export interface HotelsB2BLead extends BaseB2BLead {
  category: 'corporate_contracts' | 'event_venue' | 'chain_partnership' | 'franchise'
}

// Real Estate B2B
export interface RealEstateB2BLead extends BaseB2BLead {
  category: 'commercial_lease' | 'property_management' | 'investment' | 'development'
}

// Logistics B2B
export interface LogisticsB2BLead extends BaseB2BLead {
  category: 'freight_contract' | 'warehouse' | 'fleet_management' | 'last_mile'
}

// Construction B2B
export interface ConstructionB2BLead extends BaseB2BLead {
  category: 'materials_supply' | 'equipment_rental' | 'contracting' | 'consulting'
}

// Electronics B2B
export interface ElectronicsB2BLead extends BaseB2BLead {
  category: 'wholesale' | 'enterprise_solutions' | 'maintenance_contract' | 'recycling'
}

// Medical B2B
export interface MedicalB2BLead extends BaseB2BLead {
  category: 'equipment' | 'supplies' | 'software' | 'consulting'
}

// Pharmacy B2B
export interface PharmacyB2BLead extends BaseB2BLead {
  category: 'wholesale' | 'chain_partnership' | 'distribution' | 'franchise'
}

// Food B2B
export interface FoodB2BLead extends BaseB2BLead {
  category: 'wholesale' | 'distribution' | 'manufacturing' | 'private_label'
}

// Grocery B2B
export interface GroceryB2BLead extends BaseB2BLead {
  category: 'wholesale' | 'distribution' | 'private_label' | 'franchise'
}

// Office Catering B2B
export interface OfficeCateringB2BLead extends BaseB2BLead {
  category: 'daily_meals' | 'event_catering' | 'coffee_service' | 'subscription'
}

// Staff B2B
export interface StaffB2BLead extends BaseB2BLead {
  category: 'staffing_contract' | 'recruitment' | 'training' | 'outsourcing'
}

// Legal B2B
export interface LegalB2BLead extends BaseB2BLead {
  category: 'retainer' | 'project' | 'consulting' | 'compliance'
}
