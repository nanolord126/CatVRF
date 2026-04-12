<script setup lang="ts">
/**
 * CatVRF 2026 — FoodDashboard
 * Полноценный дашборд вертикали Food: метрики, AI-меню, популярные блюда,
 * очередь кухни, последние заказы, статус доставки.
 */
import { ref, computed } from 'vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import FoodOrderCard from './FoodOrderCard.vue';
import FoodDishCard from './FoodDishCard.vue';

/* ── Типы ── */
interface FoodMetric {
    label: string;
    value: string;
    trend: string;
    icon: string;
    trendUp: boolean;
}

interface KitchenOrder {
    id: string;
    dish: string;
    prep: string;
    station: string;
    status: 'queued' | 'in-progress' | 'ready-soon' | 'done';
    priority: 'normal' | 'high' | 'rush';
}

interface PopularDish {
    id: number;
    name: string;
    orders: number;
    revenue: number;
    rating: number;
    image: string;
    margin: string;
    trend: string;
}

interface DeliveryZone {
    zone: string;
    rides: number;
    avgEta: string;
    couriersOnline: number;
    load: number;
}

interface AIMenuSuggestion {
    id: number;
    dish: string;
    reason: string;
    expectedOrders: number;
    confidence: number;
}

/* ── Метрики ── */
const metrics = ref<FoodMetric[]>([
    { label: 'Заказов сегодня', value: '1 208', trend: '+8.7%', icon: '🍽️', trendUp: true },
    { label: 'Средний чек', value: '1 320 ₽', trend: '+3.4%', icon: '💰', trendUp: true },
    { label: 'Скорость кухни', value: '18 мин', trend: '-1.8 мин', icon: '⏲️', trendUp: true },
    { label: 'Повторные клиенты', value: '41%', trend: '+2.2%', icon: '🔁', trendUp: true },
    { label: 'Отмены', value: '3.1%', trend: '-0.4%', icon: '❌', trendUp: true },
    { label: 'Рейтинг', value: '4.87', trend: '+0.03', icon: '⭐', trendUp: true },
    { label: 'Выручка', value: '1.59M ₽', trend: '+12.6%', icon: '📈', trendUp: true },
    { label: 'SLA доставки', value: '94.2%', trend: '+1.1%', icon: '🎯', trendUp: true },
]);

/* ── Популярные блюда ── */
const popularDishes = ref<PopularDish[]>([
    { id: 1, name: 'Поке с лососем', orders: 186, revenue: 186000, rating: 4.9, image: '🍣', margin: '34%', trend: '+12%' },
    { id: 2, name: 'Том-ям', orders: 162, revenue: 145800, rating: 4.8, image: '🍲', margin: '29%', trend: '+8%' },
    { id: 3, name: 'Паста карбонара', orders: 149, revenue: 119200, rating: 4.7, image: '🍝', margin: '31%', trend: '+5%' },
    { id: 4, name: 'Бургер классик', orders: 138, revenue: 96600, rating: 4.6, image: '🍔', margin: '42%', trend: '+18%' },
    { id: 5, name: 'Цезарь с курицей', orders: 124, revenue: 86800, rating: 4.7, image: '🥗', margin: '38%', trend: '+3%' },
]);

/* ── Очередь кухни ── */
const kitchenQueue = ref<KitchenOrder[]>([
    { id: 'FD-7741', dish: 'Поке x2', prep: '15 мин', station: 'Холодный цех', status: 'in-progress', priority: 'normal' },
    { id: 'FD-7743', dish: 'Том-ям', prep: '8 мин', station: 'Горячий цех', status: 'ready-soon', priority: 'high' },
    { id: 'FD-7746', dish: 'Стейк рибай', prep: '19 мин', station: 'Гриль', status: 'in-progress', priority: 'rush' },
    { id: 'FD-7748', dish: 'Паста x3', prep: '12 мин', station: 'Горячий цех', status: 'queued', priority: 'normal' },
    { id: 'FD-7750', dish: 'Десерт-сет', prep: '6 мин', station: 'Кондитерский', status: 'ready-soon', priority: 'normal' },
]);

/* ── Зоны доставки ── */
const deliveryZones = ref<DeliveryZone[]>([
    { zone: 'Центр', rides: 122, avgEta: '24 мин', couriersOnline: 18, load: 78 },
    { zone: 'Север', rides: 96, avgEta: '29 мин', couriersOnline: 12, load: 65 },
    { zone: 'Юг', rides: 104, avgEta: '27 мин', couriersOnline: 14, load: 71 },
    { zone: 'Запад', rides: 87, avgEta: '31 мин', couriersOnline: 10, load: 82 },
]);

/* ── AI-рекомендации для меню ── */
const aiSuggestions = ref<AIMenuSuggestion[]>([
    { id: 1, dish: 'Рамен мисо', reason: 'Растёт спрос на японскую кухню +22%', expectedOrders: 45, confidence: 0.87 },
    { id: 2, dish: 'Боул с авокадо', reason: 'Тренд healthy-food в сегменте 25-34', expectedOrders: 38, confidence: 0.82 },
    { id: 3, dish: 'Пицца трюфель', reason: 'Высокая маржа + выходные', expectedOrders: 52, confidence: 0.79 },
]);

/* ── Computed ── */
const kitchenInProgress = computed<number>(() =>
    kitchenQueue.value.filter((k: KitchenOrder) => k.status === 'in-progress').length
);
const kitchenReady = computed<number>(() =>
    kitchenQueue.value.filter((k: KitchenOrder) => k.status === 'ready-soon' || k.status === 'done').length
);
const totalRevenue = computed<string>(() =>
    popularDishes.value.reduce((acc: number, d: PopularDish) => acc + d.revenue, 0).toLocaleString('ru-RU') + ' ₽'
);

/* ── Helpers ── */
function kitchenStatusColor(status: KitchenOrder['status']): string {
    switch (status) {
        case 'queued': return 'bg-zinc-500';
        case 'in-progress': return 'bg-blue-500';
        case 'ready-soon': return 'bg-amber-500';
        case 'done': return 'bg-emerald-500';
        default: return 'bg-zinc-400';
    }
}

function kitchenStatusLabel(status: KitchenOrder['status']): string {
    switch (status) {
        case 'queued': return 'В очереди';
        case 'in-progress': return 'Готовится';
        case 'ready-soon': return 'Почти готово';
        case 'done': return 'Готово';
        default: return status;
    }
}

function priorityBadge(priority: KitchenOrder['priority']): { text: string; variant: string } {
    switch (priority) {
        case 'rush': return { text: '🔥 СРОЧНО', variant: 'danger' };
        case 'high': return { text: '⚡ Приоритет', variant: 'warning' };
        default: return { text: '', variant: '' };
    }
}

function loadColor(load: number): string {
    if (load >= 80) return 'bg-red-500';
    if (load >= 60) return 'bg-amber-500';
    return 'bg-emerald-500';
}

function confidencePercent(score: number): string {
    return Math.round(score * 100) + '%';
}
</script>

<template>
    <section class="space-y-4">
        <!-- ═══ Метрики ═══ -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <article
                v-for="item in metrics"
                :key="item.label"
                class="rounded-2xl border border-(--t-border) bg-(--t-surface) p-4 transition-all hover:-translate-y-0.5"
            >
                <div class="flex items-center justify-between">
                    <span class="text-2xl">{{ item.icon }}</span>
                    <span
                        class="text-xs font-medium px-2 py-0.5 rounded-full"
                        :class="item.trendUp ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'"
                    >
                        {{ item.trend }}
                    </span>
                </div>
                <div class="mt-3 text-xl font-bold text-(--t-text)">{{ item.value }}</div>
                <div class="mt-1 text-xs text-(--t-text-3)">{{ item.label }}</div>
            </article>
        </div>

        <!-- ═══ Популярные блюда + AI-рекомендации ═══ -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Топ блюд -->
            <VCard title="🍽️ Топ блюд" subtitle="По кол-ву заказов сегодня" class="lg:col-span-2">
                <div class="space-y-2">
                    <article
                        v-for="(dish, idx) in popularDishes"
                        :key="dish.id"
                        class="flex items-center gap-3 rounded-xl border border-(--t-border) p-3 transition-colors hover:bg-(--t-surface)"
                    >
                        <div class="text-2xl w-10 h-10 rounded-lg bg-(--t-surface) flex items-center justify-center shrink-0">
                            {{ dish.image }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-(--t-text-3)">#{{ idx + 1 }}</span>
                                <span class="font-semibold text-(--t-text) truncate">{{ dish.name }}</span>
                                <span class="text-xs text-emerald-400">{{ dish.trend }}</span>
                            </div>
                            <div class="flex gap-3 mt-1 text-xs text-(--t-text-3)">
                                <span>{{ dish.orders }} заказов</span>
                                <span>⭐ {{ dish.rating }}</span>
                                <span>Маржа {{ dish.margin }}</span>
                            </div>
                        </div>
                        <div class="text-sm font-bold text-(--t-primary) shrink-0">
                            {{ dish.revenue.toLocaleString('ru-RU') }} ₽
                        </div>
                    </article>
                </div>
                <template #footer>
                    <div class="flex items-center justify-between text-xs text-(--t-text-3)">
                        <span>Итого выручка топ-5: {{ totalRevenue }}</span>
                        <button class="text-(--t-primary) hover:underline">Все блюда →</button>
                    </div>
                </template>
            </VCard>

            <!-- AI-рекомендации меню -->
            <VCard title="🤖 AI-конструктор меню" subtitle="Рекомендации на завтра">
                <div class="space-y-3">
                    <article
                        v-for="suggestion in aiSuggestions"
                        :key="suggestion.id"
                        class="rounded-xl border border-(--t-border) p-3"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-(--t-text)">{{ suggestion.dish }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-(--t-primary)/10 text-(--t-primary)">
                                {{ confidencePercent(suggestion.confidence) }}
                            </span>
                        </div>
                        <p class="text-xs text-(--t-text-3) mt-1">{{ suggestion.reason }}</p>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-xs text-(--t-text-2)">Ожидание: ~{{ suggestion.expectedOrders }} заказов</span>
                            <button class="text-xs text-(--t-primary) hover:underline">Добавить в меню</button>
                        </div>
                    </article>
                </div>
            </VCard>
        </div>

        <!-- ═══ Очередь кухни ═══ -->
        <VCard title="👨‍🍳 Очередь кухни" :subtitle="`В работе: ${kitchenInProgress} · Почти готово: ${kitchenReady}`">
            <div class="space-y-2">
                <article
                    v-for="order in kitchenQueue"
                    :key="order.id"
                    class="flex items-center gap-3 rounded-xl border border-(--t-border) p-3 transition-colors hover:bg-(--t-surface)"
                >
                    <div
                        class="w-2 h-8 rounded-full shrink-0"
                        :class="kitchenStatusColor(order.status)"
                    />
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-sm font-bold text-(--t-text)">{{ order.id }}</span>
                            <span class="text-sm text-(--t-text-2)">{{ order.dish }}</span>
                            <VBadge
                                v-if="priorityBadge(order.priority).text"
                                :text="priorityBadge(order.priority).text"
                                :variant="priorityBadge(order.priority).variant"
                                size="xs"
                            />
                        </div>
                        <div class="text-xs text-(--t-text-3) mt-0.5">{{ order.station }} · {{ order.prep }}</div>
                    </div>
                    <VBadge
                        :text="kitchenStatusLabel(order.status)"
                        :variant="order.status === 'ready-soon' ? 'warning' : order.status === 'done' ? 'success' : 'info'"
                        size="xs"
                    />
                </article>
            </div>
        </VCard>

        <!-- ═══ Зоны доставки ═══ -->
        <VCard title="🛵 Доставка по зонам" subtitle="Нагрузка курьеров в реальном времени">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <article
                    v-for="zone in deliveryZones"
                    :key="zone.zone"
                    class="rounded-xl border border-(--t-border) p-4"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-(--t-text)">{{ zone.zone }}</span>
                        <span
                            class="w-2.5 h-2.5 rounded-full"
                            :class="loadColor(zone.load)"
                        />
                    </div>
                    <div class="mt-3 space-y-1.5">
                        <div class="flex justify-between text-xs">
                            <span class="text-(--t-text-3)">Рейсов</span>
                            <span class="font-medium text-(--t-text)">{{ zone.rides }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-(--t-text-3)">Среднее ETA</span>
                            <span class="font-medium text-(--t-text)">{{ zone.avgEta }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-(--t-text-3)">Курьеров</span>
                            <span class="font-medium text-(--t-text)">{{ zone.couriersOnline }}</span>
                        </div>
                    </div>
                    <!-- Load bar -->
                    <div class="mt-3">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-(--t-text-3)">Нагрузка</span>
                            <span class="font-bold" :class="zone.load >= 80 ? 'text-red-400' : 'text-(--t-text-2)'">{{ zone.load }}%</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-(--t-surface)">
                            <div
                                class="h-full rounded-full transition-all"
                                :class="loadColor(zone.load)"
                                :style="{ width: zone.load + '%' }"
                            />
                        </div>
                    </div>
                </article>
            </div>
        </VCard>
    </section>
</template>
