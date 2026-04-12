/**
 * CatVRF 2026 — Pinia: useNotifications Store
 * Стор уведомлений: список, unread, mark as read
 */

import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import apiClient from '@/api/apiClient';

export interface Notification {
    id: number;
    type: string;
    title: string;
    message: string;
    data: Record<string, unknown> | null;
    read_at: string | null;
    created_at: string;
}

export const useNotifications = defineStore('notifications', () => {
    /* ── State ──────────────────────────────────────────────────────── */
    const notifications = ref<Notification[]>([]);
    const isOpen = ref(false);
    const isLoading = ref(false);

    /* ── Getters ────────────────────────────────────────────────────── */
    const unreadCount = computed(() => notifications.value.filter(n => !n.read_at).length);
    const recent = computed(() => notifications.value.slice(0, 20));

    /* ── Actions ────────────────────────────────────────────────────── */
    async function fetchNotifications(): Promise<void> {
        isLoading.value = true;
        try {
            const { data } = await apiClient.get<{ data: Notification[] }>('/notifications');
            notifications.value = data.data ?? [];
        } catch {
            /* offline-first */
        } finally {
            isLoading.value = false;
        }
    }

    async function markAsRead(id: number): Promise<void> {
        try {
            await apiClient.post(`/notifications/${id}/read`);
            const notif = notifications.value.find(n => n.id === id);
            if (notif) notif.read_at = new Date().toISOString();
        } catch {
            /* silent */
        }
    }

    async function markAllRead(): Promise<void> {
        try {
            await apiClient.post('/notifications/read-all');
            notifications.value.forEach(n => {
                n.read_at = n.read_at ?? new Date().toISOString();
            });
        } catch {
            /* silent */
        }
    }

    function toggle(): void {
        isOpen.value = !isOpen.value;
    }

    function addNotification(notif: Notification): void {
        notifications.value.unshift(notif);
    }

    return {
        notifications,
        isOpen,
        isLoading,
        unreadCount,
        recent,
        fetchNotifications,
        markAsRead,
        markAllRead,
        toggle,
        addNotification,
    };
});
