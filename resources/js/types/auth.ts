/**
 * CatVRF 2026 — Auth Types
 * Типы для аутентификации и пользователей
 */

export interface User {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    avatar_url: string | null;
    is_b2b_owner: boolean;
    active_business_group_id: number | null;
    permissions: string[];
    created_at: string;
    updated_at: string;
}

export interface Tenant {
    id: number;
    name: string;
    slug: string;
    logo_url: string | null;
    vertical: string;
    plan: 'free' | 'basic' | 'pro' | 'enterprise';
    is_active: boolean;
    settings: Record<string, unknown>;
    created_at: string;
}

export interface BusinessGroup {
    id: number;
    tenant_id: number;
    legal_name: string;
    inn: string;
    kpp: string | null;
    legal_address: string;
    bank_account: string;
    b2b_tier: 'standard' | 'silver' | 'gold' | 'platinum';
    credit_limit: number;
    credit_used: number;
    payment_term_days: number;
    b2b_data: Record<string, unknown> | null;
    created_at: string;
}

export interface AuthState {
    user: User | null;
    tenant: Tenant | null;
    businessGroup: BusinessGroup | null;
    isB2B: boolean;
    isLoading: boolean;
    permissions: string[];
    walletBalance: number;
    bonusBalance: number;
    creditLimit: number;
    creditUsed: number;
    unreadNotifications: number;
}

export interface AuthResponse {
    user: User;
    tenant: Tenant | null;
    business_group: BusinessGroup | null;
    wallet_balance: number;
    bonus_balance: number;
    unread_notifications: number;
}
