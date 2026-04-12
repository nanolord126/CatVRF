<script setup>
/**
 * BusinessLayout — главный layout личного кабинета бизнеса.
 * Sidebar + Topbar + Content area.
 * Glassmorphism, адаптивный, полностью кликабельный.
 */
import { ref, computed, onMounted } from 'vue';

import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import { useAuth, useNotifications } from '@/stores'

const auth = useAuth();
const notifs = useNotifications();

const sidebarOpen = ref(true);
const sidebarMobile = ref(false);
const activeSection = ref('dashboard');
const searchQuery = ref('');
const showUserMenu = ref(false);
const showNotifPanel = ref(false);

const isMobile = ref(false);
function checkMobile() {
    isMobile.value = window.innerWidth < 1024;
    if (isMobile.value) sidebarOpen.value = false;
    else sidebarOpen.value = true;
}
onMounted(() => {
    checkMobile();
    window.addEventListener('resize', checkMobile);
    auth.fetchUser();
    notifs.fetchNotifications();
});

const navigation = computed(() => [
    { key: 'dashboard', label: 'Дашборд', icon: '📊', badge: null },
    { key: 'orders', label: 'Заказы', icon: '📦', badge: 12 },
    { key: 'products', label: 'Товары и услуги', icon: '🏷️', badge: null },
    { key: 'wallet', label: 'Кошелёк', icon: '💰', badge: null },
    { key: 'analytics', label: 'Аналитика', icon: '📈', badge: null },
    { key: 'employees', label: 'Персонал', icon: '👥', badge: null },
    { key: 'warehouses', label: 'Склады', icon: '🏭', badge: 2 },
    { key: 'delivery', label: 'Доставка', icon: '🚚', badge: null },
    { key: 'taxi', label: 'Taxi', icon: '🚕', badge: null },
    { key: 'marketing', label: 'Маркетинг', icon: '📣', badge: null },
    { key: 'crm', label: 'CRM и клиенты', icon: '🎯', badge: null },
    { key: 'ai', label: 'AI-конструкторы', icon: '🤖', badge: null },
    { key: 'integrations', label: 'Интеграции', icon: '🔌', badge: null },
    { key: 'beauty', label: 'Beauty B2B', icon: '💇', badge: null },
    { key: 'auto', label: 'Auto', icon: '🔧', badge: null },
    { key: 'food', label: 'Food', icon: '🍽️', badge: null },
    { key: 'furniture', label: 'Furniture', icon: '🛋️', badge: null },
    { key: 'realestate', label: 'RealEstate', icon: '🏢', badge: null },
    { key: 'medical', label: 'Medical', icon: '🩺', badge: null },
    { key: 'travel', label: 'Travel', icon: '✈️', badge: null },
    { key: 'hotels', label: 'Hotels', icon: '🏨', badge: null },
    { key: 'fashion', label: 'Fashion', icon: '👗', badge: null },
    { key: 'fitness', label: 'Fitness', icon: '🏋️', badge: null },
    { key: 'advertising', label: 'Advertising', icon: '📣', badge: null },
    { key: 'aiVertical', label: 'AI Vertical', icon: '🧠', badge: null },
    { key: 'analyticsVertical', label: 'Analytics Vertical', icon: '📈', badge: null },
    { key: 'art', label: 'Art', icon: '🎨', badge: null },
    { key: 'booksandliterature', label: 'Books', icon: '📚', badge: null },
    { key: 'carrental', label: 'CarRental', icon: '🚗', badge: null },
    { key: 'cleaningservices', label: 'Cleaning', icon: '🧹', badge: null },
    { key: 'collectibles', label: 'Collectibles', icon: '🧿', badge: null },
    { key: 'commonVertical', label: 'Common Vertical', icon: '🧩', badge: null },
    { key: 'confectionery', label: 'Confectionery', icon: '🧁', badge: null },
    { key: 'constructionandrepair', label: 'Build&Repair', icon: '🏗️', badge: null },
    { key: 'consulting', label: 'Consulting', icon: '💼', badge: null },
    { key: 'contentVertical', label: 'Content Vertical', icon: '📝', badge: null },
    { key: 'education', label: 'Education', icon: '🎓', badge: null },
    { key: 'electronics', label: 'Electronics', icon: '🔌', badge: null },
    { key: 'eventplanning', label: 'EventPlanning', icon: '🎉', badge: null },
    { key: 'farmdirect', label: 'FarmDirect', icon: '🌾', badge: null },
    { key: 'flowers', label: 'Flowers', icon: '💐', badge: null },
    { key: 'freelance', label: 'Freelance', icon: '💻', badge: null },
    { key: 'gardening', label: 'Gardening', icon: '🌱', badge: null },
    { key: 'geo', label: 'Geo', icon: '📍', badge: null },
    { key: 'geologistics', label: 'GeoLogistics', icon: '🚚', badge: null },
    { key: 'groceryanddelivery', label: 'GroceryDelivery', icon: '🛒', badge: null },
    { key: 'homeservices', label: 'HomeServices', icon: '🏠', badge: null },
    { key: 'b2b', label: 'B2B Панель', icon: '🏢', badge: null, isB2B: true },
    { key: 'profile', label: 'Профиль бизнеса', icon: '🏪', badge: null },
    { key: 'settings', label: 'Настройки', icon: '⚙️', badge: null },
]);

function navigate(key) {
    activeSection.value = key;
    if (isMobile.value) sidebarMobile.value = false;
    showUserMenu.value = false;
}
</script>

<template>
    <div class="flex h-screen overflow-hidden" style="background: var(--t-bg);">

        <!-- Mobile overlay -->
        <Transition name="fade">
            <div
                v-if="sidebarMobile && isMobile"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 lg:hidden"
                @click="sidebarMobile = false"
            />
        </Transition>

        <!-- ===== SIDEBAR ===== -->
        <aside
            :class="[
                'flex flex-col z-50 h-full border-r border-(--t-border) bg-(--t-bg)/80 backdrop-blur-2xl transition-all duration-300',
                isMobile
                    ? sidebarMobile ? 'fixed left-0 top-0 w-72 translate-x-0' : 'fixed -translate-x-full w-72'
                    : sidebarOpen ? 'w-64' : 'w-18',
            ]"
        >
            <!-- Logo + collapse -->
            <div class="flex items-center justify-between px-4 h-16 border-b border-(--t-border)">
                <div v-if="sidebarOpen || sidebarMobile" class="flex items-center gap-2 overflow-hidden">
                    <div class="w-8 h-8 rounded-xl bg-linear-to-br from-(--t-primary) to-(--t-accent) flex items-center justify-center text-sm font-bold text-white shadow-(--t-glow)">
                        🐱
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-sm font-bold text-(--t-text) truncate">CatVRF</span>
                        <span class="text-[10px] text-(--t-text-3)">Бизнес-кабинет</span>
                    </div>
                </div>
                <div v-else class="mx-auto w-8 h-8 rounded-xl bg-linear-to-br from-(--t-primary) to-(--t-accent) flex items-center justify-center text-sm shadow-(--t-glow)">
                    🐱
                </div>
                <button
                    v-if="!isMobile"
                    @click="sidebarOpen = !sidebarOpen"
                    class="p-1.5 rounded-lg hover:bg-(--t-surface) text-(--t-text-3) hover:text-(--t-text) transition-all active:scale-90"
                >
                    <svg class="w-4 h-4 transition-transform" :class="sidebarOpen ? '' : 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <!-- Tenant selector -->
            <div v-if="sidebarOpen || sidebarMobile" class="px-3 pt-3 pb-1">
                <button class="w-full flex items-center gap-2 p-2 rounded-xl bg-(--t-surface) border border-(--t-border) hover:border-(--t-primary)/30 transition-all cursor-pointer active:scale-[0.98]">
                    <div class="w-8 h-8 rounded-lg bg-(--t-primary-dim) flex items-center justify-center text-sm">
                        🏪
                    </div>
                    <div class="flex-1 text-left overflow-hidden">
                        <div class="text-xs font-semibold text-(--t-text) truncate">
                            {{ auth.tenantName || 'Мой бизнес' }}
                        </div>
                        <div class="text-[10px] text-(--t-text-3) truncate">
                            {{ auth.isB2BMode ? 'B2B режим' : 'B2C режим' }}
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-(--t-text-3)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto px-3 py-2 space-y-0.5">
                <button
                    v-for="item in navigation"
                    :key="item.key"
                    @click="navigate(item.key)"
                    :class="[
                        'group w-full flex items-center rounded-xl transition-all duration-200 cursor-pointer',
                        'focus:outline-none focus-visible:ring-2 focus-visible:ring-(--t-primary)',
                        'active:scale-[0.97]',
                        sidebarOpen || sidebarMobile ? 'gap-3 px-3 py-2.5' : 'justify-center py-2.5',
                        activeSection === item.key
                            ? item.isB2B
                                ? 'bg-linear-to-r from-amber-500/15 to-orange-500/10 text-amber-300 shadow-amber-500/10'
                                : 'bg-(--t-primary-dim) text-(--t-primary) shadow-(--t-glow)'
                            : 'text-(--t-text-2) hover:bg-(--t-card-hover) hover:text-(--t-text)',
                    ]"
                    :title="(!sidebarOpen && !sidebarMobile) ? item.label : undefined"
                >
                    <span class="text-base shrink-0 transition-transform group-hover:scale-110 duration-200">{{ item.icon }}</span>

                    <template v-if="sidebarOpen || sidebarMobile">
                        <span class="text-sm font-medium truncate flex-1 text-left">
                            {{ item.label }}
                        </span>

                        <!-- B2B highlight -->
                        <VBadge v-if="item.isB2B" text="PRO" variant="b2b" size="xs" />

                        <!-- Badge -->
                        <span v-else-if="item.badge" class="min-w-5 h-5 px-1 flex items-center justify-center text-[10px] font-bold rounded-full bg-red-500/80 text-white">
                            {{ item.badge }}
                        </span>
                    </template>

                    <!-- Collapsed badge dot -->
                    <span v-if="(!sidebarOpen && !sidebarMobile) && item.badge" class="absolute right-2 top-1 w-2 h-2 rounded-full bg-red-500" />
                </button>
            </nav>

            <!-- Wallet preview -->
            <div class="border-t border-(--t-border) p-3">
                <button
                    @click="navigate('wallet')"
                    :class="[
                        'w-full rounded-xl p-3 bg-linear-to-r from-emerald-500/10 to-teal-500/10 border border-emerald-500/20',
                        'hover:from-emerald-500/15 hover:to-teal-500/15 transition-all cursor-pointer active:scale-[0.98]',
                    ]"
                >
                    <template v-if="sidebarOpen || sidebarMobile">
                        <div class="text-[10px] text-emerald-400/70 uppercase tracking-wider mb-1">Баланс</div>
                        <div class="text-lg font-bold text-emerald-300">
                            {{ Number(auth.walletBalance).toLocaleString('ru') }} ₽
                        </div>
                        <div class="text-xs text-(--t-text-3) mt-1">
                            Бонусы: {{ Number(auth.bonusBalance).toLocaleString('ru') }} ₽
                        </div>
                    </template>
                    <template v-else>
                        <div class="text-center text-lg">💰</div>
                    </template>
                </button>
            </div>
        </aside>

        <!-- ===== MAIN ===== -->
        <main class="flex-1 flex flex-col overflow-hidden">

            <!-- TOPBAR -->
            <header class="shrink-0 h-16 border-b border-(--t-border) bg-(--t-header) backdrop-blur-2xl flex items-center justify-between px-4 lg:px-6 z-30">
                <!-- Burger + breadcrumb -->
                <div class="flex items-center gap-3">
                    <!-- Mobile burger -->
                    <button
                        v-if="isMobile"
                        @click="sidebarMobile = !sidebarMobile"
                        class="p-2 rounded-xl hover:bg-(--t-surface) text-(--t-text-2) active:scale-90 transition-all"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Breadcrumb -->
                    <div class="flex items-center gap-1.5 text-sm">
                        <span class="text-(--t-text-3)">Кабинет</span>
                        <span class="text-(--t-text-3)">/</span>
                        <span class="text-(--t-text) font-medium capitalize">
                            {{ navigation.find(n => n.key === activeSection)?.label || activeSection }}
                        </span>
                    </div>
                </div>

                <!-- Center: Search -->
                <div class="hidden md:flex flex-1 max-w-md mx-6">
                    <div class="relative w-full">
                        <input
                            v-model="searchQuery"
                            placeholder="Поиск по кабинету…"
                            class="w-full pl-9 pr-4 py-2 rounded-xl bg-(--t-surface) border border-(--t-border) text-sm text-(--t-text) placeholder-(--t-text-3) focus:outline-none focus:border-(--t-primary)/50 focus:shadow-[0_0_20px_var(--t-glow)] transition-all"
                        />
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-(--t-text-3)">🔍</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2">
                    <!-- B2B Toggle -->
                    <button
                        @click="auth.toggleB2B()"
                        :class="[
                            'hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold transition-all active:scale-95 cursor-pointer',
                            auth.isB2BMode
                                ? 'bg-linear-to-r from-amber-500/20 to-orange-500/20 text-amber-300 border border-amber-500/30 shadow-amber-500/10'
                                : 'bg-(--t-surface) text-(--t-text-3) border border-(--t-border) hover:text-(--t-text) hover:border-(--t-primary)/30',
                        ]"
                    >
                        🏢
                        <span>{{ auth.isB2BMode ? 'B2B' : 'B2C' }}</span>
                    </button>

                    <!-- Notifications -->
                    <button
                        @click="showNotifPanel = !showNotifPanel"
                        class="relative p-2 rounded-xl hover:bg-(--t-surface) text-(--t-text-2) hover:text-(--t-text) transition-all active:scale-90"
                    >
                        🔔
                        <span
                            v-if="notifs.unreadCount > 0"
                            class="absolute -top-0.5 -right-0.5 min-w-4.5 h-4.5 px-1 flex items-center justify-center text-[10px] font-bold rounded-full bg-red-500 text-white ring-2 ring-(--t-bg) animate-pulse"
                        >
                            {{ notifs.unreadCount > 99 ? '99+' : notifs.unreadCount }}
                        </span>
                    </button>

                    <!-- User Avatar -->
                    <button
                        @click="showUserMenu = !showUserMenu"
                        class="flex items-center gap-2 p-1 pl-2 rounded-xl hover:bg-(--t-surface) transition-all active:scale-95 cursor-pointer"
                    >
                        <span class="hidden sm:block text-xs text-(--t-text-2)">{{ auth.userName }}</span>
                        <div class="w-8 h-8 rounded-xl bg-linear-to-br from-(--t-primary) to-(--t-accent) flex items-center justify-center text-sm text-white font-bold shadow-md">
                            {{ auth.userName?.charAt(0) || '?' }}
                        </div>
                    </button>
                </div>
            </header>

            <!-- CONTENT AREA -->
            <div class="flex-1 overflow-y-auto">
                <div class="p-4 lg:p-6 max-w-7xl mx-auto w-full">
                    <slot :active-section="activeSection" :navigate="navigate" />
                </div>
            </div>
        </main>

        <!-- Notification Panel (slide-in) -->
        <Transition name="slide-right">
            <div v-if="showNotifPanel" class="fixed right-0 top-0 h-full w-80 sm:w-96 bg-(--t-bg)/95 backdrop-blur-2xl border-l border-(--t-border) z-50 flex flex-col shadow-2xl">
                <div class="flex items-center justify-between px-4 h-16 border-b border-(--t-border)">
                    <span class="text-sm font-bold text-(--t-text)">Уведомления</span>
                    <div class="flex items-center gap-2">
                        <button @click="notifs.markAllRead()" class="text-xs text-(--t-primary) hover:underline cursor-pointer">Прочитать все</button>
                        <button @click="showNotifPanel = false" class="p-1 rounded-lg hover:bg-(--t-surface) active:scale-90 text-(--t-text-3)">✕</button>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-3 space-y-2">
                    <div
                        v-for="n in notifs.recent"
                        :key="n.id"
                        @click="notifs.markAsRead(n.id)"
                        :class="[
                            'p-3 rounded-xl border cursor-pointer transition-all hover:-translate-y-0.5 active:scale-[0.98]',
                            n.read_at
                                ? 'border-(--t-border) bg-(--t-surface)/50'
                                : 'border-(--t-primary)/20 bg-(--t-primary-dim) shadow-(--t-glow)',
                        ]"
                    >
                        <div class="text-sm font-medium text-(--t-text)">{{ n.title || 'Уведомление' }}</div>
                        <div class="text-xs text-(--t-text-3) mt-1">{{ n.message || n.body || '' }}</div>
                    </div>
                    <div v-if="notifs.recent.length === 0" class="text-center py-12 text-(--t-text-3)">
                        <div class="text-3xl mb-2">🔕</div>
                        <div class="text-sm">Нет уведомлений</div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-right-enter-active, .slide-right-leave-active { transition: transform 0.3s ease; }
.slide-right-enter-from, .slide-right-leave-to { transform: translateX(100%); }
</style>
