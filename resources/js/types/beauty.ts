/**
 * CatVRF 2026 — Beauty Vertical Types
 * Типы для клиентов, мастеров, записей, визитов
 */

export interface BeautyClient {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    avatar_url: string | null;
    visits_count: number;
    total_spent: number;
    last_visit: string | null;
    loyalty_level: 'bronze' | 'silver' | 'gold' | 'platinum';
    tags: string[];
    notes: string | null;
    allergens: string[];
    preferred_master_id: number | null;
    created_at: string;
}

export interface BeautyMaster {
    id: number;
    full_name: string;
    specialization: string[];
    rating: number;
    reviews_count: number;
    avatar_url: string | null;
    is_active: boolean;
    is_online: boolean;
    schedule: MasterSchedule;
    services: BeautyService[];
    portfolio: PortfolioItem[];
}

export interface MasterSchedule {
    [dayOfWeek: string]: {
        start: string;
        end: string;
        breaks: { start: string; end: string }[];
    };
}

export interface BeautyService {
    id: number;
    name: string;
    category: string;
    duration_minutes: number;
    price: number;
    price_b2b: number | null;
    description: string | null;
    is_active: boolean;
}

export interface PortfolioItem {
    id: number;
    image_url: string;
    description: string | null;
    service_id: number | null;
    created_at: string;
}

export interface BeautyAppointment {
    id: number;
    client_id: number;
    master_id: number;
    service_ids: number[];
    date: string;
    time_start: string;
    time_end: string;
    status: 'pending' | 'confirmed' | 'in_progress' | 'completed' | 'cancelled' | 'no_show';
    total_price: number;
    notes: string | null;
    correlation_id: string;
}

export interface BeautyVisit {
    id: number;
    client_id: number;
    master_id: number;
    master_name: string;
    services: { name: string; price: number }[];
    date: string;
    total: number;
    rating: number | null;
    comment: string | null;
    photos: string[];
    status: 'completed' | 'cancelled' | 'no_show';
}

export interface BeautyCalendarSlot {
    time: string;
    is_available: boolean;
    appointment: BeautyAppointment | null;
}

export interface BeautyCalendarDay {
    date: string;
    slots: BeautyCalendarSlot[];
    appointments_count: number;
}

export interface AIBeautyAnalysis {
    face_type: string;
    skin_tone: string;
    hair_color: string;
    brow_shape: string;
    age_estimate: number;
    skin_condition: string;
    recommendations: {
        hairstyles: string[];
        coloring: string[];
        makeup: string[];
        skincare: string[];
    };
    ar_link: string | null;
    confidence_score: number;
}
