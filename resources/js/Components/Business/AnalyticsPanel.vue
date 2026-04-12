<script setup>
/**
 * AnalyticsPanel — бизнес-аналитика, метрики, графики, отчёты.
 * ClickHouse + Redis реал-тайм дашборд.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VStatCard from '../UI/VStatCard.vue';
import VModal from '../UI/VModal.vue';
import VInput from '../UI/VInput.vue';

const period = ref('30d');
const periods = [
    { key: '7d', label: '7 дней' },
    { key: '30d', label: '30 дней' },
    { key: '90d', label: '90 дней' },
    { key: '1y', label: 'Год' },
];

const activeSection = ref('overview');
const sections = [
    { key: 'overview', label: 'Обзор' },
    { key: 'sales', label: 'Продажи' },
    { key: 'users', label: 'Пользователи' },
    { key: 'traffic', label: 'Трафик' },
    { key: 'ltv', label: 'LTV и когорты' },
    { key: 'verticals', label: 'Вертикали' },
    { key: 'ai', label: 'AI-конструкторы' },
];

const showExportModal = ref(false);
const exportFormat = ref('xlsx');

/* Fake chart bars */
const chartDays = Array.from({length: 14}, (_, i) => ({
    day: `${i + 1}.04`,
    value: Math.floor(Math.random() * 80 + 20),
    revenue: Math.floor(Math.random() * 500 + 100) * 1000,
}));
const maxChart = computed(() => Math.max(...chartDays.map(d => d.value)));

const topVerticals = [
    { name: 'Beauty', icon: '💄', revenue: 2_840_000, orders: 1240, share: 32 },
    { name: 'Furniture', icon: '🛋️', revenue: 2_100_000, orders: 380, share: 24 },
    { name: 'Food', icon: '🍔', revenue: 1_600_000, orders: 2100, share: 18 },
    { name: 'Fashion', icon: '👗', revenue: 1_200_000, orders: 890, share: 14 },
    { name: 'Hotel', icon: '🏨', revenue: 680_000, orders: 120, share: 8 },
    { name: 'Другие', icon: '📦', revenue: 380_000, orders: 340, share: 4 },
];

const aiUsage = [
    { vertical: 'Beauty', icon: '💄', uses: 3200, avgScore: 0.94, arUsed: 1800 },
    { vertical: 'Interior', icon: '🏠', uses: 1800, avgScore: 0.91, arUsed: 1200 },
    { vertical: 'Food', icon: '🍔', uses: 2400, avgScore: 0.95, arUsed: 0 },
    { vertical: 'Fashion', icon: '👗', uses: 2100, avgScore: 0.92, arUsed: 1500 },
    { vertical: 'Fitness', icon: '🏋️', uses: 980, avgScore: 0.90, arUsed: 450 },
];

const trafficSources = ref([
    { name: 'Органика (SEO)', visits: 48200, pct: 38, trend: 5.2, icon: '🔍' },
    { name: 'Прямые заходы', visits: 31400, pct: 25, trend: 2.1, icon: '🔗' },
    { name: 'Реклама (Яндекс)', visits: 22600, pct: 18, trend: 12.8, icon: '📢' },
    { name: 'Соцсети', visits: 14200, pct: 11, trend: -3.4, icon: '📱' },
    { name: 'Рефералы', visits: 10100, pct: 8, trend: 18.5, icon: '🤝' },
]);

const deviceStats = ref([
    { name: 'Мобильные', pct: 62, sessions: 78400, bounce: 34, icon: '📱' },
    { name: 'Десктоп', pct: 30, sessions: 37800, bounce: 22, icon: '💻' },
    { name: 'Планшеты', pct: 8, sessions: 10100, bounce: 28, icon: '📟' },
]);

const ltvCohorts = ref([
    { cohort: 'Янв 2026', users: 1200, ltv30: 1850, ltv60: 3200, ltv90: 4800, retention30: 45, retention60: 32, retention90: 24 },
    { cohort: 'Фев 2026', users: 1450, ltv30: 2100, ltv60: 3800, ltv90: null, retention30: 48, retention60: 35, retention90: null },
    { cohort: 'Мар 2026', users: 1680, ltv30: 2400, ltv60: null, ltv90: null, retention30: 52, retention60: null, retention90: null },
    { cohort: 'Апр 2026', users: 890, ltv30: null, ltv60: null, ltv90: null, retention30: null, retention60: null, retention90: null },
]);
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl lg:text-2xl font-bold text-(--t-text)">📊 Аналитика</h1>
                <p class="text-xs text-(--t-text-3)">Бизнес-метрики, продажи, пользователи и AI</p>
            </div>
            <div class="flex items-center gap-2">
                <button v-for="p in periods" :key="p.key"
                        :class="['px-3 py-1.5 text-xs rounded-lg transition-all cursor-pointer active:scale-95',
                                 period === p.key ? 'bg-(--t-primary) text-white' : 'bg-(--t-card-hover) text-(--t-text-3) hover:text-(--t-text)']"
                        @click="period = p.key"
                >{{ p.label }}</button>
                <VButton variant="secondary" size="xs" @click="showExportModal = true">📥 Экспорт</VButton>
            </div>
        </div>

        <!-- Main Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <VStatCard title="GMV" value="8.8M ₽" icon="💰" :trend="18.2" color="primary" clickable />
            <VStatCard title="Заказы" value="5 070" icon="📦" :trend="12.5" color="indigo" clickable />
            <VStatCard title="Новые клиенты" value="1 230" icon="👤" :trend="9.1" color="emerald" clickable />
            <VStatCard title="Conversion" value="4.8%" icon="🎯" :trend="0.6" color="amber" clickable />
            <VStatCard title="ARPU" value="1 735 ₽" icon="📈" :trend="5.3" color="rose" clickable />
        </div>

        <!-- Section Tabs -->
        <VTabs :tabs="sections" v-model="activeSection" variant="pills" />

        <!-- Overview -->
        <template v-if="activeSection === 'overview'">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Revenue Chart -->
                <VCard title="📈 Выручка" subtitle="Последние 14 дней" class="lg:col-span-2">
                    <div class="flex items-end gap-1 h-40">
                        <div v-for="(d, i) in chartDays" :key="i"
                             class="flex-1 flex flex-col items-center group cursor-pointer"
                        >
                            <div class="relative w-full">
                                <div class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 rounded bg-(--t-surface) border border-(--t-border) text-[9px] text-(--t-text) whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                    {{ (d.revenue / 1000).toFixed(0) }}k ₽
                                </div>
                                <div class="w-full rounded-t-md transition-all duration-300 group-hover:opacity-80"
                                     :class="i === chartDays.length - 1 ? 'bg-linear-to-t from-(--t-primary) to-(--t-accent)' : 'bg-(--t-primary-dim)'"
                                     :style="{height: (d.value / maxChart * 128) + 'px'}"
                                />
                            </div>
                            <span class="text-[8px] text-(--t-text-3) mt-1 group-hover:text-(--t-text) transition-colors">{{ d.day }}</span>
                        </div>
                    </div>
                </VCard>

                <!-- Quick Stats -->
                <VCard title="⚡ Быстрые показатели">
                    <div class="space-y-3">
                        <div v-for="stat in [
                            {label: 'Средний чек', value: '1 735 ₽', trend: '+5.3%', up: true},
                            {label: 'Повторные покупки', value: '34%', trend: '+2.1%', up: true},
                            {label: 'Churn rate', value: '2.4%', trend: '-0.8%', up: false},
                            {label: 'NPS', value: '72', trend: '+4', up: true},
                            {label: 'AI-конверсия', value: '18%', trend: '+3.2%', up: true},
                        ]" :key="stat.label"
                           class="flex items-center justify-between p-2.5 rounded-lg hover:bg-(--t-card-hover) transition-colors cursor-pointer"
                        >
                            <span class="text-xs text-(--t-text-2)">{{ stat.label }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-(--t-text)">{{ stat.value }}</span>
                                <span class="text-[10px]" :class="stat.up ? 'text-emerald-400' : 'text-rose-400'">{{ stat.trend }}</span>
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- Sales -->
        <template v-if="activeSection === 'sales'">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <VCard title="🛒 Продажи по каналам">
                    <div class="space-y-3">
                        <div v-for="ch in [
                            {name: 'Маркетплейс', pct: 58, color: 'from-(--t-primary) to-(--t-accent)'},
                            {name: 'B2B API', pct: 22, color: 'from-amber-500 to-amber-300'},
                            {name: 'Мобильное приложение', pct: 14, color: 'from-emerald-500 to-emerald-300'},
                            {name: 'Прямые продажи', pct: 6, color: 'from-violet-500 to-violet-300'},
                        ]" :key="ch.name"
                           class="cursor-pointer group"
                        >
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-(--t-text-2) group-hover:text-(--t-text) transition-colors">{{ ch.name }}</span>
                                <span class="font-bold text-(--t-text)">{{ ch.pct }}%</span>
                            </div>
                            <div class="h-2.5 rounded-full bg-(--t-border) overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-700"
                                     :class="'bg-linear-to-r ' + ch.color"
                                     :style="{width: ch.pct + '%'}"
                                />
                            </div>
                        </div>
                    </div>
                </VCard>

                <VCard title="💳 Типы оплат">
                    <div class="space-y-3">
                        <div v-for="pm in [
                            {name: 'Карта', pct: 64, icon: '💳'},
                            {name: 'СБП', pct: 21, icon: '📱'},
                            {name: 'Кредит B2B', pct: 10, icon: '🏦'},
                            {name: 'Бонусы', pct: 5, icon: '⭐'},
                        ]" :key="pm.name"
                           class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-(--t-card-hover) transition-colors cursor-pointer"
                        >
                            <span class="text-lg">{{ pm.icon }}</span>
                            <div class="flex-1">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-(--t-text)">{{ pm.name }}</span>
                                    <span class="font-bold text-(--t-text)">{{ pm.pct }}%</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-(--t-border) overflow-hidden">
                                    <div class="h-full rounded-full bg-(--t-primary)" :style="{width: pm.pct + '%'}" />
                                </div>
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- Users -->
        <template v-if="activeSection === 'users'">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                <VStatCard title="Новые" value="1 230" icon="🆕" color="emerald" clickable />
                <VStatCard title="Возврат" value="3 840" icon="🔄" color="indigo" clickable />
                <VStatCard title="B2B клиенты" value="142" icon="🏢" color="amber" clickable />
                <VStatCard title="LTV" value="12 400 ₽" icon="💎" color="rose" clickable />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <VCard title="👤 Сегменты пользователей">
                    <div class="space-y-3">
                        <div v-for="seg in [
                            {name: 'VIP (LTV > 50k)', count: 120, pct: 3, color: 'amber'},
                            {name: 'Активные (3+ покупки)', count: 1_800, pct: 35, color: 'emerald'},
                            {name: 'Новички (< 7 дней)', count: 1_230, pct: 24, color: 'blue'},
                            {name: 'Спящие (30+ дней)', count: 950, pct: 18, color: 'rose'},
                            {name: 'Рисковые (churn)', count: 420, pct: 8, color: 'red'},
                        ]" :key="seg.name"
                           class="flex items-center gap-3 p-2.5 rounded-lg hover:bg-(--t-card-hover) transition-colors cursor-pointer"
                        >
                            <div class="w-2 h-8 rounded-full" :class="`bg-${seg.color}-400`" />
                            <div class="flex-1">
                                <div class="text-xs text-(--t-text)">{{ seg.name }}</div>
                                <div class="text-[10px] text-(--t-text-3)">{{ seg.count.toLocaleString('ru') }} пользователей</div>
                            </div>
                            <span class="text-xs font-bold text-(--t-text)">{{ seg.pct }}%</span>
                        </div>
                    </div>
                </VCard>

                <VCard title="🌍 География">
                    <div class="space-y-3">
                        <div v-for="geo in [
                            {city: 'Москва', users: 2_100, pct: 41},
                            {city: 'Санкт-Петербург', users: 980, pct: 19},
                            {city: 'Новосибирск', users: 420, pct: 8},
                            {city: 'Екатеринбург', users: 380, pct: 7},
                            {city: 'Остальные', users: 1_190, pct: 25},
                        ]" :key="geo.city"
                           class="flex items-center justify-between p-2.5 rounded-lg hover:bg-(--t-card-hover) transition-colors cursor-pointer"
                        >
                            <div>
                                <div class="text-xs text-(--t-text)">{{ geo.city }}</div>
                                <div class="text-[10px] text-(--t-text-3)">{{ geo.users.toLocaleString('ru') }} пользователей</div>
                            </div>
                            <span class="text-xs font-bold text-(--t-text)">{{ geo.pct }}%</span>
                        </div>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- Traffic -->
        <template v-if="activeSection === 'traffic'">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <VCard title="🔍 Источники трафика">
                    <div class="space-y-3">
                        <div v-for="s in trafficSources" :key="s.name"
                             class="flex items-center gap-3 p-3 rounded-xl hover:bg-(--t-card-hover) transition-colors cursor-pointer"
                        >
                            <span class="text-xl shrink-0">{{ s.icon }}</span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-xs font-medium text-(--t-text)">{{ s.name }}</span>
                                    <span class="text-xs font-bold text-(--t-text)">{{ s.pct }}%</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-(--t-border) overflow-hidden">
                                    <div class="h-full rounded-full bg-(--t-primary) transition-all" :style="{ width: s.pct + '%' }" />
                                </div>
                            </div>
                            <div class="text-right shrink-0 ml-2">
                                <div class="text-[10px] text-(--t-text-3)">{{ s.visits.toLocaleString('ru') }}</div>
                                <div class="text-[10px]" :class="s.trend > 0 ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ s.trend > 0 ? '+' : '' }}{{ s.trend }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </VCard>

                <VCard title="📱 Устройства">
                    <div class="space-y-4">
                        <div v-for="d in deviceStats" :key="d.name" class="space-y-2">
                            <div class="flex items-center gap-3">
                                <span class="text-xl">{{ d.icon }}</span>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="font-medium text-(--t-text)">{{ d.name }}</span>
                                        <span class="font-bold text-(--t-text)">{{ d.pct }}%</span>
                                    </div>
                                    <div class="h-3 rounded-full bg-(--t-border) overflow-hidden">
                                        <div class="h-full rounded-full bg-linear-to-r from-(--t-primary) to-(--t-accent)" :style="{ width: d.pct + '%' }" />
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-4 ml-9 text-[10px] text-(--t-text-3)">
                                <span>Сессии: {{ d.sessions.toLocaleString('ru') }}</span>
                                <span>Bounce: {{ d.bounce }}%</span>
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- LTV & Cohorts -->
        <template v-if="activeSection === 'ltv'">
            <VCard title="💎 LTV-когорты" subtitle="Удержание и монетизация по месяцам">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b border-(--t-border)">
                                <th class="text-left py-2 px-3 text-(--t-text-3) font-medium">Когорта</th>
                                <th class="text-center py-2 px-3 text-(--t-text-3) font-medium">Пользователей</th>
                                <th class="text-center py-2 px-3 text-(--t-text-3) font-medium">LTV 30д</th>
                                <th class="text-center py-2 px-3 text-(--t-text-3) font-medium">LTV 60д</th>
                                <th class="text-center py-2 px-3 text-(--t-text-3) font-medium">LTV 90д</th>
                                <th class="text-center py-2 px-3 text-(--t-text-3) font-medium">Ret 30д</th>
                                <th class="text-center py-2 px-3 text-(--t-text-3) font-medium">Ret 60д</th>
                                <th class="text-center py-2 px-3 text-(--t-text-3) font-medium">Ret 90д</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in ltvCohorts" :key="c.cohort" class="border-b border-(--t-border)/30 hover:bg-(--t-card-hover) transition-colors">
                                <td class="py-2.5 px-3 font-medium text-(--t-text)">{{ c.cohort }}</td>
                                <td class="py-2.5 px-3 text-center text-(--t-text-2)">{{ c.users.toLocaleString('ru') }}</td>
                                <td class="py-2.5 px-3 text-center font-bold" :class="c.ltv30 ? 'text-emerald-400' : 'text-(--t-text-3)'">{{ c.ltv30 ? c.ltv30.toLocaleString('ru') + ' ₽' : '—' }}</td>
                                <td class="py-2.5 px-3 text-center font-bold" :class="c.ltv60 ? 'text-sky-400' : 'text-(--t-text-3)'">{{ c.ltv60 ? c.ltv60.toLocaleString('ru') + ' ₽' : '—' }}</td>
                                <td class="py-2.5 px-3 text-center font-bold" :class="c.ltv90 ? 'text-violet-400' : 'text-(--t-text-3)'">{{ c.ltv90 ? c.ltv90.toLocaleString('ru') + ' ₽' : '—' }}</td>
                                <td class="py-2.5 px-3 text-center">
                                    <span v-if="c.retention30" :class="c.retention30 > 40 ? 'text-emerald-400' : 'text-amber-400'" class="font-bold">{{ c.retention30 }}%</span>
                                    <span v-else class="text-(--t-text-3)">—</span>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span v-if="c.retention60" :class="c.retention60 > 30 ? 'text-emerald-400' : 'text-amber-400'" class="font-bold">{{ c.retention60 }}%</span>
                                    <span v-else class="text-(--t-text-3)">—</span>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span v-if="c.retention90" :class="c.retention90 > 20 ? 'text-emerald-400' : 'text-rose-400'" class="font-bold">{{ c.retention90 }}%</span>
                                    <span v-else class="text-(--t-text-3)">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </VCard>
        </template>

        <!-- Verticals -->
        <template v-if="activeSection === 'verticals'">
            <VCard title="📊 Вертикали по выручке">
                <div class="space-y-3">
                    <div v-for="v in topVerticals" :key="v.name"
                         class="flex items-center gap-3 p-3 rounded-xl hover:bg-(--t-card-hover) transition-colors cursor-pointer active:scale-[0.99]"
                    >
                        <span class="text-2xl">{{ v.icon }}</span>
                        <div class="flex-1">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm font-medium text-(--t-text)">{{ v.name }}</span>
                                <span class="text-sm font-bold text-(--t-text)">{{ (v.revenue / 1_000_000).toFixed(1) }}M ₽</span>
                            </div>
                            <div class="h-2 rounded-full bg-(--t-border) overflow-hidden">
                                <div class="h-full rounded-full bg-linear-to-r from-(--t-primary) to-(--t-accent) transition-all duration-700"
                                     :style="{width: v.share + '%'}" />
                            </div>
                            <div class="flex justify-between mt-1">
                                <span class="text-[10px] text-(--t-text-3)">{{ v.orders.toLocaleString('ru') }} заказов</span>
                                <span class="text-[10px] font-medium text-(--t-primary)">{{ v.share }}%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- AI Usage -->
        <template v-if="activeSection === 'ai'">
            <VCard title="🤖 Использование AI-конструкторов">
                <div class="space-y-3">
                    <div v-for="a in aiUsage" :key="a.vertical"
                         class="flex items-center gap-3 p-3 rounded-xl hover:bg-(--t-card-hover) transition-colors cursor-pointer"
                    >
                        <span class="text-2xl">{{ a.icon }}</span>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-(--t-text)">{{ a.vertical }}</div>
                            <div class="text-[10px] text-(--t-text-3)">Score: {{ a.avgScore }} • AR: {{ a.arUsed > 0 ? a.arUsed.toLocaleString('ru') : '—' }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-(--t-text)">{{ a.uses.toLocaleString('ru') }}</div>
                            <div class="text-[9px] text-(--t-text-3)">использований</div>
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- Export Modal -->
        <VModal v-model="showExportModal" title="📥 Экспорт отчёта" size="sm">
            <div class="space-y-4">
                <p class="text-xs text-(--t-text-3)">Выберите формат и период для экспорта данных аналитики.</p>
                <div class="grid grid-cols-3 gap-2">
                    <button v-for="fmt in ['xlsx', 'csv', 'pdf']" :key="fmt"
                            :class="['p-3 rounded-xl border text-center transition-all cursor-pointer active:scale-95',
                                     exportFormat === fmt ? 'border-(--t-primary) bg-(--t-primary-dim) text-(--t-primary)' : 'border-(--t-border) text-(--t-text-2) hover:border-(--t-primary)/30']"
                            @click="exportFormat = fmt"
                    >
                        <div class="text-lg mb-1">{{ fmt === 'xlsx' ? '📊' : fmt === 'csv' ? '📋' : '📄' }}</div>
                        <div class="text-xs font-medium uppercase">{{ fmt }}</div>
                    </button>
                </div>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showExportModal = false">Отмена</VButton>
                <VButton variant="primary">Скачать</VButton>
            </template>
        </VModal>
    </div>
</template>
