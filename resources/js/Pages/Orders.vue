<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const activeTab = ref('all');
const hoveredOrder = ref(null);

const tabs = [
    { id: 'all', label: 'Все', count: 5 },
    { id: 'active', label: 'Активные', count: 2 },
    { id: 'completed', label: 'Выполнены', count: 2 },
    { id: 'cancelled', label: 'Отменены', count: 1 },
];

const orders = [
    { id: 1001, title: 'Салон «Аврора» — стрижка', status: 'completed', statusLabel: 'Выполнен', date: '05.04.2026', price: 2500, vertical: 'beauty', icon: '💇', type: 'b2c' },
    { id: 1002, title: 'Ресторан «Высота» — доставка', status: 'in_transit', statusLabel: 'В пути', date: '06.04.2026', price: 4200, vertical: 'food', icon: '🍜', type: 'b2c' },
    { id: 1003, title: 'AI-подбор интерьера', status: 'processing', statusLabel: 'Обработка', date: '07.04.2026', price: 1800, vertical: 'furniture', icon: '🛋️', type: 'b2c' },
    { id: 1004, title: 'Оптовая партия косметики', status: 'completed', statusLabel: 'Выполнен', date: '03.04.2026', price: 85000, vertical: 'beauty', icon: '💄', type: 'b2b' },
    { id: 1005, title: 'Абонемент FitPro — 3 мес', status: 'cancelled', statusLabel: 'Отменён', date: '01.04.2026', price: 12000, vertical: 'fitness', icon: '🏋️', type: 'b2c' },
];

const statusColors = {
    completed: { bg: 'rgba(52,211,153,0.12)', text: '#34d399', border: 'rgba(52,211,153,0.3)' },
    in_transit: { bg: 'rgba(251,191,36,0.12)', text: '#fbbf24', border: 'rgba(251,191,36,0.3)' },
    processing: { bg: 'rgba(96,165,250,0.12)', text: '#60a5fa', border: 'rgba(96,165,250,0.3)' },
    cancelled: { bg: 'rgba(248,113,113,0.12)', text: '#f87171', border: 'rgba(248,113,113,0.3)' },
};

const filteredOrders = computed(() => {
    if (activeTab.value === 'all') return orders;
    if (activeTab.value === 'active') return orders.filter(o => ['in_transit', 'processing'].includes(o.status));
    if (activeTab.value === 'completed') return orders.filter(o => o.status === 'completed');
    if (activeTab.value === 'cancelled') return orders.filter(o => o.status === 'cancelled');
    return orders;
});

const totalSpent = computed(() => orders.filter(o => o.status === 'completed').reduce((s, o) => s + o.price, 0));
</script>

<template>
    <Head title="Заказы — CatVRF" />

    <AppLayout show-back title="Заказы">
        <section class="max-w-2xl mx-auto">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl md:text-3xl font-black" style="color: var(--t-text);">📦 Мои заказы</h1>
                <Link href="/business?section=orders"
                      class="text-xs font-bold px-3 py-1.5 rounded-lg border border-amber-500/30 text-amber-400 hover:bg-amber-500/10 active:scale-95 transition-all cursor-pointer">
                    Бизнес →
                </Link>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-3 gap-2 mb-5">
                <div class="p-3 rounded-xl border text-center" style="background: var(--t-surface); border-color: var(--t-border);">
                    <p class="text-lg font-black" style="color: var(--t-text);">{{ orders.length }}</p>
                    <p class="text-[10px]" style="color: var(--t-text-3);">Всего</p>
                </div>
                <div class="p-3 rounded-xl border text-center" style="background: var(--t-surface); border-color: var(--t-border);">
                    <p class="text-lg font-black text-emerald-400">{{ orders.filter(o => o.status === 'completed').length }}</p>
                    <p class="text-[10px]" style="color: var(--t-text-3);">Выполнено</p>
                </div>
                <div class="p-3 rounded-xl border text-center" style="background: var(--t-surface); border-color: var(--t-border);">
                    <p class="text-lg font-black" style="color: var(--t-primary);">{{ totalSpent.toLocaleString('ru-RU') }} ₽</p>
                    <p class="text-[10px]" style="color: var(--t-text-3);">Потрачено</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 mb-5 p-1 rounded-xl border" style="background: var(--t-surface); border-color: var(--t-border);">
                <button v-for="tab in tabs" :key="tab.id"
                    @click="activeTab = tab.id"
                    class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-300 active:scale-95 cursor-pointer"
                    :style="activeTab === tab.id
                        ? { background: 'var(--t-primary-dim)', color: 'var(--t-text)', boxShadow: '0 0 8px var(--t-glow)' }
                        : { color: 'var(--t-text-3)' }">
                    {{ tab.label }}
                    <span v-if="tab.count" class="ml-1 opacity-60">{{ tab.count }}</span>
                </button>
            </div>

            <div v-if="filteredOrders.length === 0" class="text-center py-16">
                <p class="text-5xl mb-4">📭</p>
                <p class="text-lg" style="color: var(--t-text-2);">Заказов пока нет</p>
                <Link href="/" class="inline-block mt-6 px-6 py-3 rounded-xl text-sm font-bold text-white active:scale-95 hover:scale-105 hover:shadow-lg transition-all" style="background: var(--t-btn);">
                    Перейти к покупкам
                </Link>
            </div>

            <div v-else class="space-y-3">
                <div v-for="order in filteredOrders" :key="order.id"
                    @mouseenter="hoveredOrder = order.id"
                    @mouseleave="hoveredOrder = null"
                    class="p-4 md:p-5 rounded-xl border flex items-center justify-between gap-4 transition-all duration-300 cursor-pointer active:scale-[0.98] hover:shadow-lg"
                    :style="{
                        background: hoveredOrder === order.id ? 'var(--t-card-hover)' : 'var(--t-surface)',
                        borderColor: hoveredOrder === order.id ? statusColors[order.status]?.border : 'var(--t-border)',
                    }">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-2xl transition-transform" :class="hoveredOrder === order.id ? 'scale-125' : ''">{{ order.icon }}</span>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="text-xs font-mono" style="color: var(--t-text-3);">№ {{ order.id }}</p>
                                <span v-if="order.type === 'b2b'" class="px-1.5 py-0.5 text-[9px] font-black rounded bg-amber-500/20 text-amber-400">B2B</span>
                            </div>
                            <h3 class="text-sm md:text-base font-bold mt-0.5 truncate" style="color: var(--t-text);">{{ order.title }}</h3>
                            <p class="text-xs mt-0.5" style="color: var(--t-text-3);">{{ order.date }}</p>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide rounded-md"
                              :style="{
                                  background: statusColors[order.status]?.bg,
                                  color: statusColors[order.status]?.text,
                              }">
                            {{ order.statusLabel }}
                        </span>
                        <p class="text-base md:text-lg font-black mt-1" style="color: var(--t-text);">{{ order.price.toLocaleString('ru-RU') }} ₽</p>
                    </div>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
