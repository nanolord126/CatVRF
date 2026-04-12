/**
 * CatVRF 2026 — Tenant Types
 * Типы для тенантов, бизнес-групп и дашборда
 */

export interface TenantMetrics {
    gmv: number;
    ordersCount: number;
    newUsers: number;
    returningUsers: number;
    conversionRate: number;
    arpu: number;
    aiUsage: number;
    deliveryActive: number;
}

export interface Employee {
    id: number;
    full_name: string;
    position: string;
    employment_type: 'full_time' | 'part_time' | 'contract' | 'freelance';
    is_active: boolean;
    rating: number;
}

export interface Warehouse {
    id: number;
    name: string;
    address: string;
    is_active: boolean;
    lat: number;
    lon: number;
}

export interface Campaign {
    id: number;
    name: string;
    type: 'email' | 'push' | 'shorts' | 'banner';
    status: 'active' | 'paused' | 'completed' | 'draft';
    budget: number;
    spent: number;
}

export interface Order {
    id: number;
    status: 'pending' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
    total: number;
    items_count: number;
    created_at: string;
    customer_name: string;
}

export interface Vertical {
    slug: string;
    name: string;
    icon: string;
    is_active: boolean;
    orders_count: number;
}

export interface TenantState {
    metrics: TenantMetrics;
    employees: Employee[];
    warehouses: Warehouse[];
    campaigns: Campaign[];
    recentOrders: Order[];
    verticals: Vertical[];
    isLoading: boolean;
    period: string;
    activeTab: string;
}

export interface DashboardResponse {
    metrics: Partial<TenantMetrics>;
    employees: Employee[];
    warehouses: Warehouse[];
    campaigns: Campaign[];
    recent_orders: Order[];
    verticals: Vertical[];
}

export interface TenantSwitchItem {
    id: number;
    name: string;
    slug: string;
    logo_url: string | null;
    vertical: string;
}
