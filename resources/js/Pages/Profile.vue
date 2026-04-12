<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTheme, THEMES } from '@/composables/useTheme';

const { currentTheme, setTheme } = useTheme();

const user = {
    name: 'Алексей Петров',
    email: 'alexey@example.com',
    avatar: '👤',
    phone: '+7 (999) 123-45-67',
    memberSince: 'Март 2026',
    bonuses: 1250,
    isB2B: false,
    walletBalance: 24_800,
    ordersCount: 17,
    aiDesignsCount: 5,
};

const isB2B = ref(user.isB2B);
const loggingOut = ref(false);
const hoveredItem = ref(null);

const menuItems = [
    { label: 'Бизнес-кабинет', icon: '🏢', href: '/business', badge: 'PRO', highlight: true },
    { label: 'Мои заказы', icon: '📦', href: '/orders', badge: String(user.ordersCount) },
    { label: 'Мои адреса', icon: '📍', href: '/cabinet/addresses' },
    { label: 'Кошелёк и оплата', icon: '💳', href: '/cabinet/wallet' },
    { label: 'Мой стиль (AI)', icon: '🤖', href: '/cabinet/ai-constructor', badge: String(user.aiDesignsCount) },
    { label: 'Бонусы и скидки', icon: '🎁', href: '/cabinet/wallet' },
    { label: 'Уведомления', icon: '🔔', href: '/cabinet' },
    { label: 'Безопасность и 2FA', icon: '🔒', href: '/cabinet' },
    { label: 'B2B / Юрлицо', icon: '🏛️', href: '/b2b', badge: isB2B.value ? 'Активен' : null },
    { label: 'Помощь', icon: '💬', href: '/cabinet' },
];

const quickStats = computed(() => [
    { label: 'Баланс', value: `${(user.walletBalance).toLocaleString('ru-RU')} ₽`, icon: '💰' },
    { label: 'Бонусы', value: `${user.bonuses} ₽`, icon: '🎁' },
    { label: 'Заказы', value: String(user.ordersCount), icon: '📦' },
    { label: 'AI-дизайны', value: String(user.aiDesignsCount), icon: '🤖' },
]);

function navigate(href) {
    router.visit(href);
}

function logout() {
    loggingOut.value = true;
    router.post('/logout');
}
</script>

<template>
    <Head title="Профиль — CatVRF" />

    <AppLayout show-back title="Профиль">
        <section class="max-w-lg mx-auto space-y-6">
            <!-- Аватар -->
            <div class="flex items-center gap-4 group">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-3xl shadow-lg transition-all duration-500 group-hover:scale-110 group-hover:rotate-3 cursor-pointer"
                     style="background: var(--t-primary-dim); box-shadow: 0 4px 14px var(--t-glow);">
                    {{ user.avatar }}
                </div>
                <div class="flex-1">
                    <h1 class="text-lg md:text-xl font-black" style="color: var(--t-text);">{{ user.name }}</h1>
                    <p class="text-sm" style="color: var(--t-text-2);">{{ user.email }}</p>
                    <p class="text-xs mt-0.5" style="color: var(--t-text-3);">{{ user.phone }} · с {{ user.memberSince }}</p>
                </div>
                <button class="w-9 h-9 rounded-xl border flex items-center justify-center hover:scale-110 active:scale-95 transition-all cursor-pointer"
                        style="border-color: var(--t-border); color: var(--t-text-3);"
                        title="Редактировать профиль">
                    ✏️
                </button>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-4 gap-2">
                <div v-for="stat in quickStats" :key="stat.label"
                     class="p-3 rounded-xl border text-center transition-all duration-300 hover:scale-105 active:scale-95 cursor-pointer"
                     style="background: var(--t-surface); border-color: var(--t-border);">
                    <span class="text-lg block">{{ stat.icon }}</span>
                    <p class="text-xs font-black mt-1" style="color: var(--t-text);">{{ stat.value }}</p>
                    <p class="text-[10px]" style="color: var(--t-text-3);">{{ stat.label }}</p>
                </div>
            </div>

            <!-- Business CTA -->
            <Link href="/business"
                class="block p-4 rounded-xl border-2 border-amber-500/30 transition-all duration-300 hover:border-amber-500/50 hover:shadow-lg active:scale-[0.98] cursor-pointer group"
                style="background: linear-gradient(135deg, rgba(245,158,11,0.06), rgba(217,119,6,0.02));">
                <div class="flex items-center gap-3">
                    <span class="text-2xl group-hover:scale-110 transition-transform">🏢</span>
                    <div class="flex-1">
                        <p class="text-sm font-black" style="color: var(--t-text);">Бизнес-кабинет</p>
                        <p class="text-xs" style="color: var(--t-text-3);">Управление, аналитика, AI, B2B</p>
                    </div>
                    <span class="px-2 py-0.5 text-[10px] font-black rounded-md bg-amber-500/20 text-amber-400">PRO</span>
                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" style="color: var(--t-text-3);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
            </Link>

            <!-- Бонусы -->
            <div class="p-4 rounded-xl flex items-center justify-between border transition-all duration-500 hover:shadow-lg cursor-pointer active:scale-[0.98]"
                 style="background: var(--t-primary-dim); border-color: var(--t-border); box-shadow: 0 0 20px var(--t-glow);"
                 @click="navigate('/cabinet/wallet')">
                <div>
                    <p class="text-xs uppercase tracking-widest font-bold" style="color: var(--t-text-3);">Бонусы</p>
                    <p class="text-2xl font-black" style="color: var(--t-text);">{{ user.bonuses.toLocaleString('ru-RU') }} ₽</p>
                    <p class="text-xs mt-1" style="color: var(--t-primary);">+120 ₽ за реферала →</p>
                </div>
                <span class="text-3xl hover:scale-125 transition-transform">🎁</span>
            </div>

            <!-- Выбор темы -->
            <div class="space-y-2">
                <h3 class="text-xs font-bold uppercase tracking-widest" style="color: var(--t-text-3);">Цветовая схема</h3>
                <div class="flex gap-2">
                    <button v-for="t in THEMES" :key="t.id" @click="setTheme(t.id)"
                        class="flex-1 py-2.5 rounded-xl border text-center text-sm font-bold transition-all duration-300 active:scale-90 hover:scale-105 cursor-pointer"
                        :style="currentTheme === t.id
                            ? { background: 'var(--t-primary-dim)', borderColor: 'var(--t-primary)', color: 'var(--t-text)', boxShadow: '0 0 12px var(--t-glow)' }
                            : { background: 'var(--t-surface)', borderColor: 'var(--t-border)', color: 'var(--t-text-2)' }">
                        {{ t.short }}
                    </button>
                </div>
            </div>

            <!-- Меню -->
            <div class="space-y-2">
                <button v-for="item in menuItems" :key="item.label"
                    @click="navigate(item.href)"
                    @mouseenter="hoveredItem = item.label"
                    @mouseleave="hoveredItem = null"
                    class="w-full flex items-center gap-3 p-3.5 rounded-xl border active:scale-[0.97] transition-all duration-300 text-left cursor-pointer hover:shadow-md"
                    :class="item.highlight ? 'border-amber-500/30 hover:border-amber-500/50' : ''"
                    :style="{
                        background: hoveredItem === item.label ? 'var(--t-card-hover)' : 'var(--t-surface)',
                        borderColor: item.highlight ? undefined : 'var(--t-border)',
                    }">
                    <span class="text-lg transition-transform" :class="hoveredItem === item.label ? 'scale-125' : ''">{{ item.icon }}</span>
                    <span class="text-sm font-bold flex-1" style="color: var(--t-text);">{{ item.label }}</span>
                    <span v-if="item.badge"
                          class="px-2 py-0.5 text-[10px] font-black rounded-md"
                          :class="item.highlight ? 'bg-amber-500/20 text-amber-400' : ''"
                          :style="!item.highlight ? { background: 'var(--t-primary-dim)', color: 'var(--t-primary)' } : {}">
                        {{ item.badge }}
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="hoveredItem === item.label ? 'translate-x-1' : ''" style="color: var(--t-text-3);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

            <!-- Выйти -->
            <button @click="logout" :disabled="loggingOut"
                class="w-full p-3.5 border border-red-500/30 rounded-xl text-red-400 font-bold text-sm hover:bg-red-500/10 hover:border-red-500/50 active:scale-[0.97] transition-all cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                <template v-if="loggingOut">
                    <span class="inline-block w-4 h-4 border-2 border-red-400 border-t-transparent rounded-full animate-spin mr-2"></span>
                    Выходим...
                </template>
                <template v-else>Выйти из аккаунта</template>
            </button>
        </section>
    </AppLayout>
</template>
