/**
 * CatVRF 2026 — Pinia: useAuth Store
 * Стор аутентификации: пользователь, тенант, B2B, wallet, permissions
 */

import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import apiClient from '@/api/apiClient';
import type { User, Tenant, BusinessGroup, AuthResponse } from '@/types/auth';

export const useAuth = defineStore('auth', () => {
    /* ── State ──────────────────────────────────────────────────────── */
    const user = ref<User | null>(null);
    const tenant = ref<Tenant | null>(null);
    const businessGroup = ref<BusinessGroup | null>(null);
    const isB2B = ref(false);
    const isLoading = ref(false);
    const permissions = ref<string[]>([]);
    const walletBalance = ref(0);
    const bonusBalance = ref(0);
    const creditLimit = ref(0);
    const creditUsed = ref(0);
    const unreadNotifications = ref(0);

    /* ── Getters ────────────────────────────────────────────────────── */
    const isAuthenticated = computed(() => !!user.value);
    const isTenantOwner = computed(() => !!tenant.value);
    const isB2BMode = computed(() => isB2B.value);
    const creditAvailable = computed(() => creditLimit.value - creditUsed.value);
    const userName = computed(() => user.value?.name ?? 'Гость');
    const tenantName = computed(() => tenant.value?.name ?? '');
    const businessGroupName = computed(() => businessGroup.value?.legal_name ?? '');
    const avatarUrl = computed(() => user.value?.avatar_url ?? null);

    /* ── Actions ────────────────────────────────────────────────────── */
    function setUser(u: User | null): void {
        user.value = u;
        permissions.value = u?.permissions ?? [];
    }

    function setTenant(t: Tenant | null): void {
        tenant.value = t;
    }

    function setBusinessGroup(group: BusinessGroup | null): void {
        businessGroup.value = group;
        isB2B.value = !!group;
        if (group) {
            creditLimit.value = group.credit_limit ?? 0;
            creditUsed.value = group.credit_used ?? 0;
        }
    }

    function setWallet(balance: number, bonus: number): void {
        walletBalance.value = balance ?? 0;
        bonusBalance.value = bonus ?? 0;
    }

    function setNotificationCount(count: number): void {
        unreadNotifications.value = count;
    }

    function hasPermission(perm: string): boolean {
        return permissions.value.includes(perm);
    }

    function toggleB2B(): void {
        isB2B.value = !isB2B.value;
    }

    function logout(): void {
        user.value = null;
        tenant.value = null;
        businessGroup.value = null;
        isB2B.value = false;
        permissions.value = [];
        walletBalance.value = 0;
        bonusBalance.value = 0;
        creditLimit.value = 0;
        creditUsed.value = 0;
        unreadNotifications.value = 0;
    }

    async function fetchUser(): Promise<void> {
        isLoading.value = true;
        try {
            const { data } = await apiClient.get<AuthResponse>('/me');
            setUser(data.user);
            setTenant(data.tenant);
            setBusinessGroup(data.business_group);
            setWallet(data.wallet_balance, data.bonus_balance);
            setNotificationCount(data.unread_notifications ?? 0);
        } catch {
            /* offline-first — не блокируем UI */
        } finally {
            isLoading.value = false;
        }
    }

    return {
        /* state */
        user,
        tenant,
        businessGroup,
        isB2B,
        isLoading,
        permissions,
        walletBalance,
        bonusBalance,
        creditLimit,
        creditUsed,
        unreadNotifications,
        /* getters */
        isAuthenticated,
        isTenantOwner,
        isB2BMode,
        creditAvailable,
        userName,
        tenantName,
        businessGroupName,
        avatarUrl,
        /* actions */
        setUser,
        setTenant,
        setBusinessGroup,
        setWallet,
        setNotificationCount,
        hasPermission,
        toggleB2B,
        logout,
        fetchUser,
    };
}, {
    persist: {
        pick: ['user', 'tenant', 'businessGroup', 'isB2B', 'walletBalance', 'bonusBalance'],
    },
});
