<script setup lang="ts">
/**
 * CatVRF 2026 — TenantHeader
 * Верхний хедер бизнес-панели: логотип, поиск, уведомления, user menu, switcher
 */
import { computed } from 'vue';
import { useAuth } from '@/stores/core/useAuth';
import { useUI } from '@/stores/core/useUI';
import TenantSwitcher from './TenantSwitcher.vue';
import UserMenu from './UserMenu.vue';
import { BellIcon, Bars3Icon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

const auth = useAuth();
const ui = useUI();

const unreadBadge = computed(() => {
    const count = auth.unreadNotifications;
    if (count === 0) return '';
    return count > 99 ? '99+' : String(count);
});

function onToggleSidebar(): void {
    ui.toggleSidebar();
}

function onOpenNotifications(): void {
    ui.openModal('NotificationsPanel');
}
</script>

<template>
    <header
        class="sticky top-0 z-40 flex h-16 items-center justify-between border-b border-(--t-border) bg-(--t-surface) px-4 lg:px-6"
    >
        <!-- Left: burger + logo -->
        <div class="flex items-center gap-3">
            <button
                class="rounded-lg p-2 text-(--t-text-secondary) hover:bg-(--t-surface-hover) lg:hidden"
                @click="onToggleSidebar"
            >
                <Bars3Icon class="size-6" />
            </button>
            <div class="flex items-center gap-2">
                <img
                    v-if="auth.tenant?.logo_url"
                    :src="auth.tenant.logo_url"
                    :alt="auth.tenantName"
                    class="size-8 rounded-lg object-cover"
                />
                <span class="hidden text-lg font-semibold text-(--t-text) sm:block">
                    {{ auth.tenantName || 'CatVRF' }}
                </span>
            </div>
        </div>

        <!-- Center: search (desktop) -->
        <div class="hidden max-w-md flex-1 px-8 md:block">
            <div class="relative">
                <MagnifyingGlassIcon class="absolute left-3 top-1/2 size-5 -translate-y-1/2 text-(--t-text-secondary)" />
                <input
                    type="search"
                    placeholder="Поиск по CRM, заказам, товарам…"
                    class="w-full rounded-xl border border-(--t-border) bg-(--t-surface-secondary) py-2 pl-10 pr-4 text-sm text-(--t-text) placeholder:text-(--t-text-secondary) focus:border-(--t-primary) focus:outline-none focus:ring-2 focus:ring-(--t-primary)/20"
                />
            </div>
        </div>

        <!-- Right: notifications, switcher, user menu -->
        <div class="flex items-center gap-2">
            <TenantSwitcher />

            <button
                class="relative rounded-lg p-2 text-(--t-text-secondary) hover:bg-(--t-surface-hover)"
                @click="onOpenNotifications"
            >
                <BellIcon class="size-6" />
                <span
                    v-if="unreadBadge"
                    class="absolute -right-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full bg-(--t-danger) px-1 text-xs font-bold text-white"
                >
                    {{ unreadBadge }}
                </span>
            </button>

            <UserMenu />
        </div>
    </header>
</template>
