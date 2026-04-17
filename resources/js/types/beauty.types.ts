// Beauty Vertical TypeScript Types

export interface MasterMatchingByPhotoDto {
  tenant_id: number;
  business_group_id?: number;
  user_id: number;
  photo: string;
  service_type?: string;
  preferred_gender?: string;
  max_distance?: number;
  min_rating?: number;
  price_min?: number;
  price_max?: number;
  correlation_id: string;
  idempotency_key?: string;
  is_b2b?: boolean;
}

export interface MasterMatchResult {
  success: boolean;
  analysis: {
    skin_tone: string;
    hair_type: string;
    face_shape: string;
    confidence: number;
  };
  matched_masters: MasterMatch[];
  total_matches: number;
  correlation_id: string;
}

export interface MasterMatch {
  id: number;
  uuid: string;
  name: string;
  avatar: string;
  rating: number;
  reviews_count: number;
  base_price: number;
  specializations: string[];
  ml_score: number;
  match_percentage: number;
  salon: {
    id: number;
    name: string;
    address: string;
    lat: number;
    lon: number;
  };
  services: Service[];
}

export interface DynamicPricingDto {
  tenant_id: number;
  business_group_id?: number;
  master_id: number;
  service_id: number;
  time_slot?: string;
  base_price?: number;
  correlation_id: string;
  idempotency_key?: string;
  is_b2b?: boolean;
}

export interface DynamicPricingResult {
  success: boolean;
  base_price: number;
  demand_score: number;
  surge_multiplier: number;
  flash_discount_percent: number;
  final_price: number;
  is_surge_pricing: boolean;
  is_flash_discount: boolean;
  correlation_id: string;
}

export interface VideoCallDto {
  tenant_id: number;
  business_group_id?: number;
  user_id: number;
  master_id: number;
  scheduled_for?: string;
  duration_minutes?: number;
  correlation_id: string;
  idempotency_key?: string;
  is_b2b?: boolean;
}

export interface VideoCallResult {
  success: boolean;
  call_id: string;
  room_name: string;
  token: string;
  master_id: number;
  master_name: string;
  duration_seconds: number;
  scheduled_for: string;
  expires_at: string;
  correlation_id: string;
}

export interface BeautyLoyaltyDto {
  tenant_id: number;
  business_group_id?: number;
  user_id: number;
  action: string;
  appointment_id?: number;
  referral_code?: string;
  correlation_id: string;
  idempotency_key?: string;
  is_b2b?: boolean;
}

export interface LoyaltyActionResult {
  success: boolean;
  points_earned: number;
  base_points: number;
  streak_multiplier: number;
  total_points: number;
  current_streak: number;
  tier: string;
  referral_bonus?: {
    referrer_bonus: number;
    referee_bonus: number;
  };
  correlation_id: string;
}

export interface LoyaltyStatus {
  total_points: number;
  current_streak: number;
  tier: 'bronze' | 'silver' | 'gold' | 'platinum';
  referral_code?: string;
  referrals_count: number;
  next_tier_points: number;
}

export interface BeautyFraudDetectionDto {
  tenant_id: number;
  business_group_id?: number;
  user_id: number;
  action: string;
  appointment_id?: number;
  master_id?: number;
  amount?: number;
  ip_address?: string;
  user_agent?: string;
  correlation_id: string;
  idempotency_key?: string;
  is_b2b?: boolean;
}

export interface FraudDetectionResult {
  success: boolean;
  fraud_score: number;
  ml_score: number;
  rule_score: number;
  behavior_score: number;
  risk_level: 'low' | 'medium' | 'high' | 'critical';
  action_required: 'allow' | 'enhanced_monitoring' | 'manual_review' | 'block';
  flags: string[];
  correlation_id: string;
}

// Common Beauty Types
export interface Master {
  id: number;
  uuid: string;
  salon_id: number;
  user_id: number;
  tenant_id: number;
  business_group_id: number;
  full_name: string;
  specialization: string;
  rating: number;
  tags: string[];
  avatar_url: string;
  base_price: number;
  b2b_price: number;
  is_active: boolean;
  reviews_count: number;
}

export interface Salon {
  id: number;
  uuid: string;
  tenant_id: number;
  business_group_id: number;
  name: string;
  address: string;
  lat: number;
  lon: number;
  rating: number;
  is_active: boolean;
}

export interface BeautyService {
  id: number;
  uuid: string;
  tenant_id: number;
  business_group_id: number;
  salon_id: number;
  name: string;
  description: string;
  duration: number;
  price: number;
  category: string;
  is_active: boolean;
}

export interface Appointment {
  id: number;
  uuid: string;
  tenant_id: number;
  business_group_id: number;
  user_id: number;
  master_id: number;
  salon_id: number;
  service_id: number;
  scheduled_at: string;
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled';
  price: number;
  notes?: string;
}

export interface Slot {
  id: number;
  uuid: string;
  tenant_id: number;
  master_id: number;
  salon_id: number;
  start_time: string;
  end_time: string;
  is_available: boolean;
}

// Event Types
export interface MasterMatchedEvent {
  user_id: number;
  matched_masters: MasterMatch[];
  correlation_id: string;
}

export interface PriceUpdatedEvent {
  master_id: number;
  service_id: number;
  old_price: number;
  new_price: number;
  correlation_id: string;
}

export interface VideoCallInitiatedEvent {
  user_id: number;
  master_id: number;
  call_id: string;
  correlation_id: string;
}

export interface VideoCallEndedEvent {
  call_id: string;
  user_id: number;
  master_id: number;
  duration_seconds: number;
  reason: string;
  correlation_id: string;
}

export interface LoyaltyPointsEarnedEvent {
  user_id: number;
  points: number;
  action: string;
  correlation_id: string;
}

export interface FraudDetectedEvent {
  user_id: number;
  fraud_score: number;
  risk_level: string;
  action: string;
  correlation_id: string;
}

// API Response Types
export interface ApiResponse<T = any> {
  success: boolean;
  data?: T;
  error?: string;
  correlation_id?: string;
}

export interface PaginatedResponse<T = any> {
  success: boolean;
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}
