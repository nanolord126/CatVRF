/**
 * CatVRF 2026 — Pinia: useTenant Store
 * Стор для бизнес-дашборда тенанта: метрики, сотрудники, склады, кампании
 */

import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import apiClient from '@/api/apiClient';
import type {
    TenantMetrics,
    Employee,
    Warehouse,
    Campaign,
    Order,
    Vertical,
    DashboardResponse,
} from '@/types/tenant';

export const useTenant = defineStore('tenant', () => {
    /* ── State ──────────────────────────────────────────────────────── */
    const metrics = ref<TenantMetrics>({
        gmv: 0,
        ordersCount: 0,
        newUsers: 0,
        returningUsers: 0,
        conversionRate: 0,
        arpu: 0,
        aiUsage: 0,
        deliveryActive: 0,
    });
    const employees = ref<Employee[]>([]);
    const warehouses = ref<Warehouse[]>([]);
    const campaigns = ref<Campaign[]>([]);
    const recentOrders = ref<Order[]>([]);
    const verticals = ref<Vertical[]>([]);
    const isLoading = ref(false);
    const period = ref('30d');
    const activeTab = ref('overview');

    /* ── Getters ────────────────────────────────────────────────────── */
    const totalEmployees = computed(() => employees.value.length);
    const activeWarehouses = computed(() => warehouses.value.filter((w: Warehouse) => w.is_active).length);
    const activeCampaigns = computed(() => campaigns.value.filter((c: Campaign) => c.status === 'active').length);
    const pendingOrders = computed(() => recentOrders.value.filter((o: Order) => o.status === 'pending').length);

    /* ── Actions ────────────────────────────────────────────────────── */
    async function fetchDashboard(p: string = '30d'): Promise<void> {
        isLoading.value = true;
        period.value = p;
        try {
            const { data } = await apiClient.get<DashboardResponse>('/business/dashboard', {
                params: { period: p },
            });
            Object.assign(metrics.value, data.metrics ?? {});
            employees.value = data.employees ?? [];
            warehouses.value = data.warehouses ?? [];
            campaigns.value = data.campaigns ?? [];
            recentOrders.value = data.recent_orders ?? [];
            verticals.value = data.verticals ?? [];
        } catch {
            /* offline-first — не блокируем UI */
        } finally {
            isLoading.value = false;
        }
    }

    function setActiveTab(tab: string): void {
        activeTab.value = tab;
    }

    function $reset(): void {
        metrics.value = {
            gmv: 0, ordersCount: 0, newUsers: 0, returningUsers: 0,
            conversionRate: 0, arpu: 0, aiUsage: 0, deliveryActive: 0,
        };
        employees.value = [];
        warehouses.value = [];
        campaigns.value = [];
        recentOrders.value = [];
        verticals.value = [];
        isLoading.value = false;
        period.value = '30d';
        activeTab.value = 'overview';
    }

    return {
        /* state */
        metrics,
        employees,
        warehouses,
        campaigns,
        recentOrders,
        verticals,
        isLoading,
        period,
        activeTab,
        /* getters */
        totalEmployees,
        activeWarehouses,
        activeCampaigns,
        pendingOrders,
        /* actions */
        fetchDashboard,
        setActiveTab,
        $reset,
    };
});
