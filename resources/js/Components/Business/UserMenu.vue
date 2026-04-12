<script setup lang="ts">
/**
 * CatVRF 2026 — UserMenu
 * Меню пользователя в хедере: аватар, имя, роль, навигация, logout
 */
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useAuth } from '@/stores/core/useAuth';
import { router } from '@inertiajs/vue3';
import {
    UserCircleIcon,
    Cog6ToothIcon,
    ArrowRightOnRectangleIcon,
    WalletIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';

const auth = useAuth();
const isOpen = ref(false);
const menuRef = ref<HTMLElement | null>(null);

interface MenuItem {
    label: string;
    icon: typeof UserCircleIcon;
    href?: string;
    action?: () => void;
    danger?: boolean;
}

const menuItems: MenuItem[] = [
    { label: 'Профиль', icon: UserCircleIcon, href: '/profile' },
    { label: 'Кошелёк', icon: WalletIcon, href: '/wallet' },
    { label: 'Безопасность', icon: ShieldCheckIcon, href: '/profile/security' },
    { label: 'Настройки', icon: Cog6ToothIcon, href: '/settings' },
    {
        label: 'Выйти',
        icon: ArrowRightOnRectangleIcon,
        danger: true,
        action: () => {
            auth.logout();
            router.post('/logout');
        },
    },
];

function navigate(item: MenuItem): void {
    isOpen.value = false;
    if (item.action) {
        item.action();
    } else if (item.href) {
        router.visit(item.href);
    }
}

function onClickOutside(event: MouseEvent): void {
    if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
        isOpen.value = false;
    }
}

onMounted(() => document.addEventListener('click', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', onClickOutside));
</script>

<template>
    <div ref="menuRef" class="relative">
        <button
            class="flex items-center gap-2 rounded-lg p-1.5 hover:bg-(--t-surface-hover)"
            @click="isOpen = !isOpen"
        >
            <img
                v-if="auth.avatarUrl"
                :src="auth.avatarUrl"
                class="size-8 rounded-full object-cover ring-2 ring-(--t-border)"
                :alt="auth.userName"
            />
            <div
                v-else
                class="flex size-8 items-center justify-center rounded-full bg-(--t-primary) text-sm font-bold text-white"
            >
                {{ auth.userName.charAt(0).toUpperCase() }}
            </div>
            <span class="hidden text-sm font-medium text-(--t-text) lg:block">
                {{ auth.userName }}
            </span>
        </button>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 top-full z-50 mt-2 w-56 rounded-xl border border-(--t-border) bg-(--t-surface) p-2 shadow-xl"
            >
                <!-- User info header -->
                <div class="mb-2 border-b border-(--t-border) px-3 pb-3 pt-1">
                    <p class="font-medium text-(--t-text)">{{ auth.userName }}</p>
                    <p class="text-xs text-(--t-text-secondary)">
                        {{ auth.isB2BMode ? 'B2B' : 'B2C' }} ·
                        {{ auth.tenantName }}
                    </p>
                    <p class="mt-1 text-xs text-(--t-primary)">
                        Баланс: {{ auth.walletBalance.toLocaleString('ru-RU') }} ₽
                    </p>
                </div>

                <!-- Menu items -->
                <button
                    v-for="item in menuItems"
                    :key="item.label"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm transition-colors"
                    :class="item.danger
                        ? 'text-(--t-danger) hover:bg-(--t-danger)/10'
                        : 'text-(--t-text) hover:bg-(--t-surface-hover)'"
                    @click="navigate(item)"
                >
                    <component :is="item.icon" class="size-5 shrink-0" />
                    <span>{{ item.label }}</span>
                </button>
            </div>
        </Transition>
    </div>
</template>
