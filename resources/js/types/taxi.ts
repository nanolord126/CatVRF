/**
 * CatVRF 2026 — Taxi Vertical Types
 * Типы для водителей, поездок, финансов, бонусов/штрафов
 */

export interface TaxiDriver {
    id: number;
    full_name: string;
    phone: string;
    avatar_url: string | null;
    vehicle: TaxiVehicle;
    license_number: string;
    rating: number;
    reviews_count: number;
    total_rides: number;
    is_online: boolean;
    is_active: boolean;
    current_location: { lat: number; lon: number } | null;
    status: 'available' | 'on_ride' | 'offline' | 'blocked';
    balance: number;
    tags: string[];
    created_at: string;
}

export interface TaxiVehicle {
    brand: string;
    model: string;
    color: string;
    plate_number: string;
    year: number;
    vehicle_class: 'economy' | 'comfort' | 'business' | 'premium';
}

export interface TaxiRide {
    id: number;
    driver_id: number;
    passenger_name: string;
    passenger_phone: string;
    pickup_address: string;
    dropoff_address: string;
    pickup_location: { lat: number; lon: number };
    dropoff_location: { lat: number; lon: number };
    status: 'searching' | 'accepted' | 'arrived' | 'in_progress' | 'completed' | 'cancelled';
    distance_km: number;
    duration_minutes: number;
    fare: number;
    commission: number;
    driver_payout: number;
    payment_method: 'card' | 'cash' | 'wallet';
    rating: number | null;
    created_at: string;
    completed_at: string | null;
}

export interface TaxiFinanceSummary {
    period: string;
    total_earnings: number;
    total_commission: number;
    total_bonuses: number;
    total_penalties: number;
    net_payout: number;
    rides_count: number;
    average_rating: number;
}

export interface TaxiBonusPenalty {
    id: number;
    driver_id: number;
    type: 'bonus' | 'penalty';
    amount: number;
    reason: string;
    description: string | null;
    created_at: string;
    created_by: string;
}

export interface TaxiDriverStats {
    today_rides: number;
    today_earnings: number;
    week_rides: number;
    week_earnings: number;
    month_rides: number;
    month_earnings: number;
    acceptance_rate: number;
    cancellation_rate: number;
    online_hours_today: number;
}
