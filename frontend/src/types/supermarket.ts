export interface SupermarketOrder {
  id: string
  uuid: string
  order_number: string
  delivery_type: 'courier' | 'pickup'
  order_status: 'pending' | 'confirmed' | 'processing' | 'ready' | 'delivering' | 'delivered' | 'picked_up' | 'cancelled' | 'failed'
  total_amount: number
  courier_name?: string
  courier_phone?: string
  courier_location_lat?: number
  courier_location_lng?: number
  delivery_eta?: string
  delivery_address?: string
  pickup_zone?: string
  locker_number?: string
  pickup_window_start?: string
  pickup_window_end?: string
  qr_code?: string
  total_calories?: number
  total_proteins?: number
  total_fats?: number
  total_carbs?: number
  allergen_warnings?: Array<{ severity: string; message: string }>
  dietary_restrictions?: string[]
  created_at: string
  confirmed_at?: string
  processing_started_at?: string
  pickup_ready_at?: string
  courier_assigned_at?: string
  delivery_actual_at?: string
  pickup_actual_at?: string
}

export interface DynamicPricingSettings {
  enabled: boolean
  min_price?: number
  max_price?: number
  platform_margin: number
}

export interface DynamicPriceCalculation {
  product_id: string
  base_price: number
  dynamic_price: number
  price_change_percentage: number
  price_increase: number
  platform_margin: number
  seller_amount: number
  seller_settings: {
    min_price: number
    max_price: number
    platform_margin_percent: number
  }
  factors: Record<string, any>
  adjustments: Record<string, any>
  calculated_at: string
  valid_until: string
}

export interface Review {
  id: string
  customer_id: string
  customer_name: string
  order_id: string
  product_id: string
  rating: number
  comment: string
  attachments: string[]
  status: 'pending' | 'approved' | 'rejected' | 'published'
  type: 'free' | 'paid'
  payment_amount?: number
  justification_score?: number
  validation_notes?: string[]
  created_at: string
  moderated_at?: string
  published_at?: string
}

export interface CourierLocation {
  order_id: string
  courier_id: string
  latitude: number
  longitude: number
  eta_minutes?: number
  speed?: number
  distance?: number
}
