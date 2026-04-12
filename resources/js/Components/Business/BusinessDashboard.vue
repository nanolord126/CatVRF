<script setup>
/**
 * BusinessDashboard — центральный дашборд владельца бизнеса.
 * Метрики, графики, быстрые действия, AI-блоки, последние заказы.
 */
import { ref, onMounted } from 'vue';

import VStatCard from '../UI/VStatCard.vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VTable from '../UI/VTable.vue';
import { useAuth, useTenant } from '@/stores'

const biz = useTenant();
const auth = useAuth();

const period = ref('30d');
const periods = [
    { key: '7d', label: '7 дней', icon: '📅' },
    { key: '30d', label: '30 дней', icon: '📆' },
    { key: '90d', label: '90 дней', icon: '🗓️' },
    { key: '1y', label: 'Год', icon: '📊' },
];

onMounted(() => biz.fetchDashboard(period.value));

function changePeriod(p) {
    period.value = p;
    biz.fetchDashboard(p);
}

const quickActions = [
    { key: 'add-product', label: 'Добавить товар', icon: '➕', color: 'from-emerald-500/15 to-teal-500/10 border-emerald-500/20 hover:border-emerald-400/40' },
    { key: 'new-order', label: 'Новый заказ', icon: '📦', color: 'from-sky-500/15 to-blue-500/10 border-sky-500/20 hover:border-sky-400/40' },
    { key: 'ai-constructor', label: 'AI-конструктор', icon: '🤖', color: 'from-violet-500/15 to-purple-500/10 border-violet-500/20 hover:border-violet-400/40' },
    { key: 'marketing', label: 'Запустить рекламу', icon: '📣', color: 'from-amber-500/15 to-orange-500/10 border-amber-500/20 hover:border-amber-400/40' },
    { key: 'warehouse', label: 'Инвентаризация', icon: '📋', color: 'from-rose-500/15 to-pink-500/10 border-rose-500/20 hover:border-rose-400/40' },
    { key: 'team', label: 'Управление командой', icon: '👥', color: 'from-indigo-500/15 to-blue-500/10 border-indigo-500/20 hover:border-indigo-400/40' },
];

const orderColumns = [
    { key: 'id', label: '#', align: 'center' },
    { key: 'customer', label: 'Клиент', sortable: true },
    { key: 'total', label: 'Сумма', sortable: true, align: 'right' },
    { key: 'status', label: 'Статус', align: 'center' },
    { key: 'created_at', label: 'Дата', sortable: true },
];

const demoOrders = [
    { id: 1042, customer: 'Анна К.', total: '12 450 ₽', status: 'delivered', created_at: '2026-04-07' },
    { id: 1041, customer: 'ООО «Альфа»', total: '89 300 ₽', status: 'in_transit', created_at: '2026-04-07' },
    { id: 1040, customer: 'Дмитрий П.', total: '3 200 ₽', status: 'pending', created_at: '2026-04-06' },
    { id: 1039, customer: 'ИП Сидоров', total: '156 000 ₽', status: 'assigned', created_at: '2026-04-06' },
    { id: 1038, customer: 'Елена В.', total: '7 800 ₽', status: 'delivered', created_at: '2026-04-05' },
];

const statusMap = {
    pending: { text: 'Ожидает', variant: 'warning' },
    assigned: { text: 'Назначен', variant: 'info' },
    in_transit: { text: 'В пути', variant: 'info', pulse: true },
    delivered: { text: 'Доставлен', variant: 'success' },
    failed: { text: 'Ошибка', variant: 'danger' },
};

const revenueBreakdown = ref({
    today: 42500,
    week: 289700,
    month: 1245000,
    trend: { today: 12.3, week: 8.1, month: 15.7 },
});

const verticalMetrics = ref([
    { name: 'Beauty', icon: '💄', revenue: 480000, orders: 342, conversion: 4.8 },
    { name: 'Мебель', icon: '🛋️', revenue: 390000, orders: 87, conversion: 3.2 },
    { name: 'Еда', icon: '🍕', revenue: 245000, orders: 1240, conversion: 7.1 },
    { name: 'Одежда', icon: '👗', revenue: 130000, orders: 196, conversion: 5.4 },
]);

const notifications = ref([
    { id: 1, type: 'order', text: 'Новый заказ #1043 на 23 400 ₽', time: '2 мин', icon: '📦', unread: true },
    { id: 2, type: 'fraud', text: 'Fraud-alert: подозрительная транзакция', time: '15 мин', icon: '🚨', unread: true },
    { id: 3, type: 'stock', text: 'Товар «Крем-уход» скоро закончится', time: '1 ч', icon: '📉', unread: true },
    { id: 4, type: 'delivery', text: 'Заказ #1041 доставлен клиенту', time: '2 ч', icon: '✅', unread: false },
    { id: 5, type: 'ai', text: 'AI-конструктор: 12 новых рекомендаций', time: '3 ч', icon: '🤖', unread: false },
]);
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-(--t-text)">
                    Добро пожаловать 👋
                </h1>
                <p class="text-sm text-(--t-text-2) mt-1">
                    Управляйте бизнесом из единого центра
                </p>
            </div>

            <!-- Period selector -->
            <VTabs :tabs="periods" v-model="period" variant="segment" size="sm" @update:model-value="changePeriod" />
        </div>

        <!-- B2B Credit Banner (if B2B) -->
        <div v-if="auth.isB2BMode" class="relative overflow-hidden rounded-2xl bg-linear-to-r from-amber-500/10 via-orange-500/10 to-amber-500/10 border border-amber-500/20 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <VBadge text="B2B PRO" variant="b2b" size="sm" />
                        <span class="text-sm font-semibold text-amber-200">Кредитная линия</span>
                    </div>
                    <div class="text-2xl font-bold text-amber-100">
                        {{ Number(auth.creditAvailable).toLocaleString('ru') }} ₽
                        <span class="text-sm font-normal text-amber-300/60">доступно</span>
                    </div>
                    <div class="text-xs text-amber-300/50 mt-1">
                        Использовано: {{ Number(auth.creditUsed).toLocaleString('ru') }} ₽
                        из {{ Number(auth.creditLimit).toLocaleString('ru') }} ₽
                    </div>
                    <!-- Progress bar -->
                    <div class="mt-2 h-1.5 rounded-full bg-amber-900/30 overflow-hidden">
                        <div
                            class="h-full rounded-full bg-linear-to-r from-amber-400 to-orange-400 transition-all duration-500"
                            :style="{ width: (auth.creditLimit ? (auth.creditUsed / auth.creditLimit * 100) : 0) + '%' }"
                        />
                    </div>
                </div>
                <VButton variant="b2b" size="md">
                    💳 Увеличить лимит
                </VButton>
            </div>
            <div class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-amber-500/5 blur-2xl pointer-events-none" />
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4">
            <VStatCard
                title="GMV"
                :value="Number(biz.metrics.gmv).toLocaleString('ru') + ' ₽'"
                icon="💰"
                :trend="12.5"
                trend-label="vs прошлый период"
                color="emerald"
                :loading="biz.isLoading"
            />
            <VStatCard
                title="Заказы"
                :value="biz.metrics.ordersCount"
                icon="📦"
                :trend="8.3"
                trend-label="vs прошлый период"
                color="primary"
                :loading="biz.isLoading"
            />
            <VStatCard
                title="Новые клиенты"
                :value="biz.metrics.newUsers"
                icon="👤"
                :trend="15.2"
                color="indigo"
                :loading="biz.isLoading"
            />
            <VStatCard
                title="Конверсия"
                :value="biz.metrics.conversionRate + '%'"
                icon="🎯"
                :trend="-1.3"
                trend-label="требует внимания"
                color="rose"
                :loading="biz.isLoading"
            />
        </div>

        <!-- Revenue Breakdown + Notifications -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Revenue by Period -->
            <div class="lg:col-span-2">
                <VCard title="💵 Выручка" subtitle="Динамика по периодам">
                    <div class="grid grid-cols-3 gap-3 mb-4">
                        <div class="p-3 rounded-xl bg-linear-to-br from-emerald-500/10 to-emerald-500/5 border border-emerald-500/15">
                            <div class="text-[10px] text-(--t-text-3) mb-1">Сегодня</div>
                            <div class="text-lg font-bold text-emerald-400">{{ (revenueBreakdown.today / 1000).toFixed(1) }}k ₽</div>
                            <div class="text-[10px] text-emerald-400/70">+{{ revenueBreakdown.trend.today }}%</div>
                        </div>
                        <div class="p-3 rounded-xl bg-linear-to-br from-sky-500/10 to-sky-500/5 border border-sky-500/15">
                            <div class="text-[10px] text-(--t-text-3) mb-1">Неделя</div>
                            <div class="text-lg font-bold text-sky-400">{{ (revenueBreakdown.week / 1000).toFixed(0) }}k ₽</div>
                            <div class="text-[10px] text-sky-400/70">+{{ revenueBreakdown.trend.week }}%</div>
                        </div>
                        <div class="p-3 rounded-xl bg-linear-to-br from-violet-500/10 to-violet-500/5 border border-violet-500/15">
                            <div class="text-[10px] text-(--t-text-3) mb-1">Месяц</div>
                            <div class="text-lg font-bold text-violet-400">{{ (revenueBreakdown.month / 1000000).toFixed(2) }}M ₽</div>
                            <div class="text-[10px] text-violet-400/70">+{{ revenueBreakdown.trend.month }}%</div>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div v-for="v in verticalMetrics" :key="v.name" class="flex items-center gap-3 p-2.5 rounded-xl bg-(--t-card-hover)">
                            <span class="text-lg shrink-0">{{ v.icon }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium text-(--t-text)">{{ v.name }}</div>
                                <div class="mt-1 h-1 rounded-full bg-(--t-border) overflow-hidden">
                                    <div class="h-full rounded-full bg-(--t-primary)" :style="{ width: Math.round(v.revenue / 5000) + '%' }" />
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <div class="text-xs font-bold text-(--t-text)">{{ (v.revenue / 1000).toFixed(0) }}k ₽</div>
                                <div class="text-[9px] text-(--t-text-3)">{{ v.orders }} заказов</div>
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>

            <!-- Notifications Feed -->
            <VCard title="🔔 Уведомления">
                <div class="space-y-2">
                    <div
                        v-for="n in notifications"
                        :key="n.id"
                        :class="[
                            'flex items-start gap-2.5 p-2.5 rounded-xl transition-all cursor-pointer active:scale-[0.98]',
                            n.unread ? 'bg-(--t-primary-dim) border border-(--t-primary)/10' : 'hover:bg-(--t-card-hover)',
                        ]"
                    >
                        <span class="text-lg shrink-0 mt-0.5">{{ n.icon }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs text-(--t-text) leading-snug">{{ n.text }}</div>
                            <div class="text-[9px] text-(--t-text-3) mt-0.5">{{ n.time }} назад</div>
                        </div>
                        <div v-if="n.unread" class="w-2 h-2 rounded-full bg-(--t-primary) shrink-0 mt-1.5" />
                    </div>
                </div>
                <template #footer>
                    <button class="w-full py-2 text-xs text-(--t-primary) hover:text-(--t-accent) transition-colors">
                        Все уведомления →
                    </button>
                </template>
            </VCard>
        </div>

        <!-- Quick Actions -->
        <VCard title="Быстрые действия" subtitle="Что хотите сделать?">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 lg:gap-3">
                <button
                    v-for="action in quickActions"
                    :key="action.key"
                    :class="[
                        'flex flex-col items-center gap-2 p-4 rounded-xl border bg-linear-to-br transition-all duration-200',
                        'cursor-pointer hover:-translate-y-0.5 active:scale-[0.96]',
                        'focus:outline-none focus-visible:ring-2 focus-visible:ring-(--t-primary)',
                        action.color,
                    ]"
                >
                    <span class="text-2xl transition-transform group-hover:scale-110">{{ action.icon }}</span>
                    <span class="text-xs font-medium text-(--t-text) text-center leading-tight">{{ action.label }}</span>
                </button>
            </div>
        </VCard>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6">
            <!-- Recent Orders (2 cols) -->
            <div class="lg:col-span-2">
                <VCard title="Последние заказы" subtitle="Актуальные заказы вашего бизнеса">
                    <template #header-action>
                        <VButton variant="ghost" size="sm">Все заказы →</VButton>
                    </template>

                    <VTable
                        :columns="orderColumns"
                        :rows="demoOrders"
                        :loading="biz.isLoading"
                        compact
                    >
                        <template #cell-status="{ value }">
                            <VBadge
                                :text="statusMap[value]?.text || value"
                                :variant="statusMap[value]?.variant || 'neutral'"
                                :pulse="statusMap[value]?.pulse"
                                :dot="true"
                                size="xs"
                            />
                        </template>
                        <template #cell-total="{ value }">
                            <span class="font-semibold text-(--t-text)">{{ value }}</span>
                        </template>
                    </VTable>
                </VCard>
            </div>

            <!-- Right column -->
            <div class="space-y-4">
                <!-- AI Constructor CTA -->
                <VCard glow clickable>
                    <div class="text-center py-2">
                        <div class="text-3xl mb-3">🤖</div>
                        <h3 class="text-base font-bold text-(--t-text) mb-1">AI-конструкторы</h3>
                        <p class="text-xs text-(--t-text-3) mb-4">Создайте дизайн, меню или образ за секунды с помощью ИИ</p>
                        <VButton variant="primary" size="sm" full-width>
                            Запустить AI →
                        </VButton>
                    </div>
                </VCard>

                <!-- Wallet Mini -->
                <VCard title="💰 Финансы">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-(--t-text-3)">Баланс</span>
                            <span class="text-sm font-bold text-emerald-400">
                                {{ Number(auth.walletBalance).toLocaleString('ru') }} ₽
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-(--t-text-3)">Бонусы</span>
                            <span class="text-sm font-semibold text-(--t-accent)">
                                {{ Number(auth.bonusBalance).toLocaleString('ru') }} ₽
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-(--t-text-3)">Сегодня</span>
                            <span class="text-sm font-semibold text-(--t-primary)">+4 520 ₽</span>
                        </div>
                        <VButton variant="secondary" size="sm" full-width>
                            Вывести средства
                        </VButton>
                    </div>
                </VCard>

                <!-- Live Deliveries -->
                <VCard title="🚚 Доставки">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3 p-2 rounded-lg bg-(--t-card-hover) cursor-pointer hover:bg-(--t-primary-dim) transition-colors active:scale-[0.98]">
                            <VBadge text="В пути" variant="live" size="xs" dot pulse />
                            <div class="flex-1">
                                <div class="text-xs font-medium text-(--t-text)">Заказ #1041</div>
                                <div class="text-[10px] text-(--t-text-3)">ETA 15 мин</div>
                            </div>
                            <span class="text-sm">📍</span>
                        </div>
                        <div class="flex items-center gap-3 p-2 rounded-lg bg-(--t-card-hover) cursor-pointer hover:bg-(--t-primary-dim) transition-colors active:scale-[0.98]">
                            <VBadge text="Забран" variant="info" size="xs" dot />
                            <div class="flex-1">
                                <div class="text-xs font-medium text-(--t-text)">Заказ #1039</div>
                                <div class="text-[10px] text-(--t-text-3)">Курьер назначен</div>
                            </div>
                            <span class="text-sm">📍</span>
                        </div>
                        <VButton variant="ghost" size="xs" full-width>Все доставки →</VButton>
                    </div>
                </VCard>
            </div>
        </div>

        <!-- Bottom: Marketing + Employees overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
            <!-- Marketing -->
            <VCard title="📣 Маркетинг" subtitle="Активные кампании">
                <template #header-action>
                    <VBadge text="3 активных" variant="success" size="xs" dot />
                </template>
                <div class="space-y-2">
                    <div
                        v-for="c in [{name:'Весенняя акция',budget:50000,spent:32400,status:'active'},{name:'Ретаргетинг B2B',budget:120000,spent:89700,status:'active'},{name:'Шортсы Beauty',budget:15000,spent:15000,status:'completed'}]"
                        :key="c.name"
                        class="flex items-center gap-3 p-2.5 rounded-xl bg-(--t-card-hover) cursor-pointer hover:bg-(--t-primary-dim) transition-all active:scale-[0.98]"
                    >
                        <div class="flex-1">
                            <div class="text-xs font-medium text-(--t-text)">{{ c.name }}</div>
                            <div class="mt-1 h-1 rounded-full bg-(--t-border) overflow-hidden">
                                <div class="h-full rounded-full bg-(--t-primary) transition-all" :style="{width: (c.spent/c.budget*100)+'%'}" />
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-semibold text-(--t-text)">{{ (c.spent/1000).toFixed(0) }}k ₽</div>
                            <VBadge :text="c.status === 'active' ? 'Активна' : 'Завершена'" :variant="c.status === 'active' ? 'success' : 'neutral'" size="xs" />
                        </div>
                    </div>
                </div>
            </VCard>

            <!-- Employees -->
            <VCard title="👥 Команда" subtitle="Ваши сотрудники">
                <template #header-action>
                    <VButton variant="ghost" size="xs">Управление →</VButton>
                </template>
                <div class="space-y-2">
                    <div
                        v-for="e in [{name:'Алексей М.',role:'Курьер',status:'online'},{name:'Ольга С.',role:'Мастер',status:'online'},{name:'Игорь П.',role:'Менеджер',status:'offline'},{name:'Анна В.',role:'Бариста',status:'busy'}]"
                        :key="e.name"
                        class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-(--t-card-hover) cursor-pointer transition-all active:scale-[0.98]"
                    >
                        <div class="relative">
                            <div class="w-8 h-8 rounded-full bg-(--t-primary-dim) flex items-center justify-center text-xs font-bold text-(--t-primary)">
                                {{ e.name.charAt(0) }}
                            </div>
                            <span
                                :class="[
                                    'absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-(--t-bg)',
                                    e.status === 'online' ? 'bg-emerald-400' : e.status === 'busy' ? 'bg-amber-400' : 'bg-gray-400',
                                ]"
                            />
                        </div>
                        <div class="flex-1">
                            <div class="text-xs font-medium text-(--t-text)">{{ e.name }}</div>
                            <div class="text-[10px] text-(--t-text-3)">{{ e.role }}</div>
                        </div>
                        <VBadge
                            :text="e.status === 'online' ? 'Онлайн' : e.status === 'busy' ? 'Занят' : 'Офлайн'"
                            :variant="e.status === 'online' ? 'success' : e.status === 'busy' ? 'warning' : 'neutral'"
                            size="xs"
                            :dot="e.status === 'online'"
                        />
                    </div>
                </div>
            </VCard>
        </div>
    </div>
</template>
