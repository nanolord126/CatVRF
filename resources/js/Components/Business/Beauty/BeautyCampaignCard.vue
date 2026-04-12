<script setup>
/**
 * BeautyCampaignCard — полная карточка рекламной кампании Экосистемы Кота.
 * Детальная аналитика: метрики, воронка конверсии, креативы, динамика по дням,
 * аудитория, бюджет, прогнозы AI, UTM-метки.
 * Получает campaign через props, эмитит @close наверх.
 */
import { ref, computed } from 'vue';
import VButton from '../../UI/VButton.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import VStatCard from '../../UI/VStatCard.vue';

const props = defineProps({
    campaign: { type: Object, required: true },
});

const emit = defineEmits(['close', 'pause', 'edit', 'duplicate', 'archive']);

/* ─── Helpers ─── */
function fmtMoney(n) { return new Intl.NumberFormat('ru-RU').format(n) + ' ₽'; }
function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }
function pct(a, b) { return b > 0 ? ((a / b) * 100).toFixed(2) : '0'; }

/* ═══════════════ TABS ═══════════════ */
const tabs = [
    { key: 'overview',   label: '📊 Обзор' },
    { key: 'creatives',  label: '🎨 Креативы' },
    { key: 'audience',   label: '👥 Аудитория' },
    { key: 'budget',     label: '💰 Бюджет' },
    { key: 'dynamics',   label: '📈 Динамика' },
    { key: 'utm',        label: '🔗 UTM / Ссылки' },
];
const activeTab = ref('overview');

/* ═══════════════ ENRICHED CAMPAIGN DATA ═══════════════ */
const camp = computed(() => {
    const c = props.campaign;
    return {
        ...c,
        ctr: c.clicks > 0 ? ((c.clicks / c.impressions) * 100).toFixed(2) : 0,
        convLeadToBook: c.leads > 0 ? ((c.bookings / c.leads) * 100).toFixed(1) : 0,
        budgetUsedPct: c.budget > 0 ? Math.round((c.spent / c.budget) * 100) : 0,
        costPerClick: c.clicks > 0 ? Math.round(c.spent / c.clicks) : 0,
        costPerLead: c.leads > 0 ? Math.round(c.spent / c.leads) : 0,
        revenueEstimate: (c.bookings || 0) * (c.avgCheck || 8790),
    };
});

/* ═══════════════ KPI CARDS ═══════════════ */
const kpis = computed(() => [
    { label: 'Показы',            value: fmt(camp.value.impressions),    icon: '👁',  color: 'var(--t-text)' },
    { label: 'Клики',             value: fmt(camp.value.clicks),         icon: '🖱',  color: 'var(--t-text)' },
    { label: 'CTR',               value: camp.value.ctr + '%',           icon: '🎯',  color: parseFloat(camp.value.ctr) >= 3 ? '#22c55e' : '#f59e0b' },
    { label: 'Лиды',             value: fmt(camp.value.leads),           icon: '📋',  color: 'var(--t-primary)' },
    { label: 'Записи',           value: fmt(camp.value.bookings),        icon: '✅',  color: '#22c55e' },
    { label: 'Conv. Лид→Запись', value: camp.value.convLeadToBook + '%', icon: '🔄',  color: 'var(--t-primary)' },
    { label: 'CPO',              value: fmtMoney(camp.value.cpo),        icon: '💳',  color: camp.value.cpo < 600 ? '#22c55e' : '#f59e0b' },
    { label: 'ROAS',             value: camp.value.roas + 'x',           icon: '📈',  color: camp.value.roas >= 5 ? '#22c55e' : '#f59e0b' },
    { label: 'CPC',              value: fmtMoney(camp.value.costPerClick), icon: '💰', color: 'var(--t-text)' },
    { label: 'CPL',              value: fmtMoney(camp.value.costPerLead), icon: '📊',  color: 'var(--t-text)' },
    { label: 'Потрачено',        value: fmtMoney(camp.value.spent),      icon: '🔥',  color: '#ef4444' },
    { label: 'Бюджет',           value: fmtMoney(camp.value.budget),     icon: '🏦',  color: 'var(--t-text-2)' },
]);

/* ═══════════════ CONVERSION FUNNEL ═══════════════ */
const funnelSteps = computed(() => [
    { label: 'Показы',          value: camp.value.impressions, pct: 100 },
    { label: 'Клики',           value: camp.value.clicks,      pct: parseFloat(pct(camp.value.clicks, camp.value.impressions)) },
    { label: 'Лиды',            value: camp.value.leads,       pct: parseFloat(pct(camp.value.leads, camp.value.impressions)) },
    { label: 'Записи',          value: camp.value.bookings,    pct: parseFloat(pct(camp.value.bookings, camp.value.impressions)) },
]);

/* ═══════════════ CREATIVES ═══════════════ */
const creatives = ref([
    { id: 1, name: 'Видео "Преображение за 3 часа"',  type: 'video',    impressions: 34200, clicks: 1280, ctr: 3.74, bookings: 18, spend: 9200,  roas: 7.2, status: 'active' },
    { id: 2, name: 'Карусель до/после',                type: 'carousel', impressions: 28800, clicks: 960,  ctr: 3.33, bookings: 14, spend: 7800,  roas: 5.8, status: 'active' },
    { id: 3, name: 'Баннер "Весенний образ"',          type: 'banner',   impressions: 42000, clicks: 1130, ctr: 2.69, bookings: 11, spend: 11400, roas: 3.5, status: 'paused' },
    { id: 4, name: 'Шортс "До и после окрашивания"',   type: 'shorts',   impressions: 36000, clicks: 910,  ctr: 2.53, bookings: 24, spend: 8200,  roas: 9.1, status: 'active' },
]);

/* ═══════════════ AUDIENCE ═══════════════ */
const audienceGeo = ref([
    { city: 'Москва',           pct: 52, clicks: 5540 },
    { city: 'Санкт-Петербург',  pct: 18, clicks: 1920 },
    { city: 'Казань',           pct: 8,  clicks: 850 },
    { city: 'Краснодар',        pct: 6,  clicks: 640 },
    { city: 'Другие',           pct: 16, clicks: 1700 },
]);
const audienceDemo = ref([
    { group: 'Ж 18–24', pct: 24 }, { group: 'Ж 25–34', pct: 42 },
    { group: 'Ж 35–44', pct: 20 }, { group: 'Ж 45+',   pct: 6 },
    { group: 'М 18–34', pct: 5 },  { group: 'М 35+',   pct: 3 },
]);
const audienceDevices = ref([
    { device: '📱 Mobile',  pct: 72 },
    { device: '💻 Desktop', pct: 22 },
    { device: '📟 Tablet',  pct: 6 },
]);

/* ═══════════════ BUDGET TIMELINE ═══════════════ */
const budgetDaily = ref([
    { date: '01.04', spent: 1200, clicks: 310, bookings: 2 },
    { date: '02.04', spent: 1400, clicks: 380, bookings: 3 },
    { date: '03.04', spent: 1100, clicks: 290, bookings: 1 },
    { date: '04.04', spent: 1600, clicks: 420, bookings: 4 },
    { date: '05.04', spent: 1500, clicks: 400, bookings: 3 },
    { date: '06.04', spent: 1300, clicks: 350, bookings: 2 },
    { date: '07.04', spent: 1800, clicks: 480, bookings: 5 },
    { date: '08.04', spent: 1500, clicks: 390, bookings: 2 },
]);
const budgetTotalSpent = computed(() => budgetDaily.value.reduce((s, d) => s + d.spent, 0));
const maxDailySpend = computed(() => Math.max(...budgetDaily.value.map(d => d.spent)));

/* ═══════════════ DYNAMICS ═══════════════ */
const dynamicsData = ref([
    { date: '01.04', impressions: 18200, clicks: 520,  ctr: 2.86, leads: 42, bookings: 8 },
    { date: '02.04', impressions: 19400, clicks: 580,  ctr: 2.99, leads: 48, bookings: 9 },
    { date: '03.04', impressions: 17800, clicks: 490,  ctr: 2.75, leads: 38, bookings: 7 },
    { date: '04.04', impressions: 21200, clicks: 640,  ctr: 3.02, leads: 56, bookings: 12 },
    { date: '05.04', impressions: 20800, clicks: 610,  ctr: 2.93, leads: 52, bookings: 10 },
    { date: '06.04', impressions: 18600, clicks: 530,  ctr: 2.85, leads: 44, bookings: 8 },
    { date: '07.04', impressions: 22800, clicks: 680,  ctr: 2.98, leads: 62, bookings: 14 },
    { date: '08.04', impressions: 20200, clicks: 590,  ctr: 2.92, leads: 50, bookings: 11 },
]);
const maxImpressions = computed(() => Math.max(...dynamicsData.value.map(d => d.impressions)));

/* ═══════════════ UTM LINKS ═══════════════ */
const utmLinks = ref([
    { label: 'Основная ссылка',           url: 'https://kotvrf.ru/beauty/salon-12?utm_source=ecosystem&utm_medium=cpc&utm_campaign=spring2026', clicks: 3200 },
    { label: 'Шортс-лендинг',             url: 'https://kotvrf.ru/s/bty-spring?utm_source=ecosystem&utm_medium=shorts&utm_campaign=spring2026', clicks: 1840 },
    { label: 'Каталог окрашиваний',       url: 'https://kotvrf.ru/beauty/coloring?utm_source=ecosystem&utm_medium=carousel&utm_campaign=spring2026', clicks: 1420 },
    { label: 'Прайс-лист',               url: 'https://kotvrf.ru/beauty/price?utm_source=ecosystem&utm_medium=banner&utm_campaign=spring2026', clicks: 960 },
]);

/* ═══════════════ AI FORECAST ═══════════════ */
const aiForecast = ref({
    predictedBookings7d: 52,
    predictedRevenue7d: 457000,
    recommendedDailyBudget: 2200,
    bestCreativeFormat: 'shorts',
    bestTimeToShow: '18:00–22:00',
    audienceExpansionTip: 'Расширить на аудиторию 35–44 — потенциал +18% записей',
    churnRisk: 'low',
});
</script>

<template>
<div class="space-y-4">

    <!-- ═══ HEADER ═══ -->
    <div class="relative rounded-2xl overflow-hidden p-5"
         style="background:linear-gradient(135deg,var(--t-gradient-from),var(--t-gradient-via),var(--t-gradient-to))">
        <div class="absolute inset-0 opacity-10" style="background:repeating-linear-gradient(45deg,transparent,transparent 10px,rgba(255,255,255,.05) 10px,rgba(255,255,255,.05) 20px)"></div>
        <div class="relative flex items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-2xl"
                     style="background:rgba(255,255,255,.15);backdrop-filter:blur(6px)">🐱</div>
                <div>
                    <h2 class="text-lg font-bold text-white">{{ camp.name }}</h2>
                    <div class="flex items-center gap-2 mt-1">
                        <VBadge :color="camp.status === 'active' ? 'green' : camp.status === 'paused' ? 'yellow' : 'gray'" size="sm">
                            {{ camp.status === 'active' ? '▶ Активна' : camp.status === 'paused' ? '⏸ Пауза' : '✅ Завершена' }}
                        </VBadge>
                        <span class="text-xs text-white/70">{{ camp.startDate }} → {{ camp.endDate }}</span>
                    </div>
                </div>
            </div>
            <VButton size="sm" variant="outline" style="color:#fff;border-color:rgba(255,255,255,.3)" @click="$emit('close')">✕ Закрыть</VButton>
        </div>
    </div>

    <!-- ═══ ACTION BAR ═══ -->
    <div class="flex flex-wrap gap-2">
        <VButton size="sm" @click="$emit('edit', campaign)">✏️ Редактировать</VButton>
        <VButton size="sm" variant="outline" @click="$emit('pause', campaign)">⏸ Пауза</VButton>
        <VButton size="sm" variant="outline" @click="$emit('duplicate', campaign)">📋 Дублировать</VButton>
        <VButton size="sm" variant="outline">📊 Скачать отчёт</VButton>
        <VButton size="sm" variant="outline" style="color:#ef4444;border-color:#ef4444" @click="$emit('archive', campaign)">🗑 Архивировать</VButton>
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="flex gap-1 flex-wrap">
        <button v-for="t in tabs" :key="t.key"
                class="px-3 py-1.5 text-xs rounded-full border transition-all"
                :style="activeTab === t.key
                    ? 'background:var(--t-primary);color:#fff;border-color:var(--t-primary)'
                    : 'background:var(--t-surface);color:var(--t-text-2);border-color:var(--t-border)'"
                @click="activeTab = t.key">
            {{ t.label }}
        </button>
    </div>

    <!-- ═══ OVERVIEW TAB ═══ -->
    <div v-if="activeTab === 'overview'" class="space-y-4">
        <!-- KPI grid -->
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
            <div v-for="k in kpis" :key="k.label"
                 class="p-2.5 rounded-xl border text-center"
                 style="background:var(--t-bg);border-color:var(--t-border)">
                <div class="text-lg mb-0.5">{{ k.icon }}</div>
                <div class="text-[10px] leading-tight mb-1" style="color:var(--t-text-3)">{{ k.label }}</div>
                <div class="text-sm font-bold" :style="`color:${k.color}`">{{ k.value }}</div>
            </div>
        </div>

        <!-- Conversion funnel -->
        <VCard title="🔄 Воронка конверсии">
            <div class="flex items-end gap-2 h-32">
                <div v-for="(step, si) in funnelSteps" :key="si" class="flex-1 text-center">
                    <div class="mx-auto rounded-t-lg transition-all"
                         :style="`width:80%;height:${Math.max(Math.round(step.value / funnelSteps[0].value * 120), 8)}px;background:var(--t-primary);opacity:${1 - si * 0.2}`"></div>
                    <div class="text-xs font-bold mt-1.5" style="color:var(--t-text)">{{ fmt(step.value) }}</div>
                    <div class="text-[10px]" style="color:var(--t-text-3)">{{ step.label }}</div>
                    <div class="text-[9px] font-bold" style="color:var(--t-primary)">{{ step.pct }}%</div>
                </div>
            </div>
        </VCard>

        <!-- AI Forecast -->
        <VCard title="🤖 AI-прогноз на 7 дней">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Прогноз записей</div>
                    <div class="text-xl font-bold" style="color:var(--t-primary)">{{ aiForecast.predictedBookings7d }}</div>
                </div>
                <div class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Прогноз выручки</div>
                    <div class="text-xl font-bold" style="color:var(--t-primary)">{{ fmtMoney(aiForecast.predictedRevenue7d) }}</div>
                </div>
                <div class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Рекомендуемый бюджет / день</div>
                    <div class="text-xl font-bold" style="color:var(--t-text)">{{ fmtMoney(aiForecast.recommendedDailyBudget) }}</div>
                </div>
            </div>
            <div class="mt-3 p-3 rounded-lg text-xs" style="background:var(--t-surface);color:var(--t-text-2)">
                💡 <strong>AI-рекомендация:</strong> Лучший формат: <VBadge color="purple" size="sm">{{ aiForecast.bestCreativeFormat }}</VBadge>.
                Лучшее время: <strong style="color:var(--t-primary)">{{ aiForecast.bestTimeToShow }}</strong>.
                {{ aiForecast.audienceExpansionTip }}
            </div>
        </VCard>

        <!-- Budget progress -->
        <VCard title="💰 Расход бюджета">
            <div class="space-y-2">
                <div class="flex justify-between text-xs">
                    <span style="color:var(--t-text-2)">Потрачено: {{ fmtMoney(camp.spent) }}</span>
                    <span style="color:var(--t-text-3)">Бюджет: {{ fmtMoney(camp.budget) }}</span>
                </div>
                <div class="h-4 rounded-full overflow-hidden" style="background:var(--t-bg)">
                    <div class="h-full rounded-full transition-all"
                         :style="`width:${camp.budgetUsedPct}%;background:${camp.budgetUsedPct > 90 ? '#ef4444' : camp.budgetUsedPct > 70 ? '#f59e0b' : 'var(--t-primary)'}`"></div>
                </div>
                <div class="text-xs text-right font-bold" :style="`color:${camp.budgetUsedPct > 90 ? '#ef4444' : 'var(--t-text)'}`">{{ camp.budgetUsedPct }}%</div>
            </div>
        </VCard>
    </div>

    <!-- ═══ CREATIVES TAB ═══ -->
    <div v-if="activeTab === 'creatives'" class="space-y-4">
        <VCard title="🎨 Все креативы кампании">
            <div class="space-y-3">
                <div v-for="cr in creatives" :key="cr.id"
                     class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center gap-3 mb-2">
                        <VBadge :color="cr.type === 'video' ? 'blue' : cr.type === 'shorts' ? 'purple' : cr.type === 'carousel' ? 'green' : 'gray'" size="sm">{{ cr.type }}</VBadge>
                        <span class="flex-1 text-sm font-medium" style="color:var(--t-text)">{{ cr.name }}</span>
                        <VBadge :color="cr.status === 'active' ? 'green' : 'yellow'" size="sm">{{ cr.status === 'active' ? '▶ Активен' : '⏸ Пауза' }}</VBadge>
                    </div>
                    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 text-center text-[10px]">
                        <div>
                            <div style="color:var(--t-text-3)">Показы</div>
                            <div class="font-bold" style="color:var(--t-text)">{{ fmt(cr.impressions) }}</div>
                        </div>
                        <div>
                            <div style="color:var(--t-text-3)">Клики</div>
                            <div class="font-bold" style="color:var(--t-text)">{{ fmt(cr.clicks) }}</div>
                        </div>
                        <div>
                            <div style="color:var(--t-text-3)">CTR</div>
                            <div class="font-bold" :style="`color:${cr.ctr >= 3 ? '#22c55e' : '#f59e0b'}`">{{ cr.ctr }}%</div>
                        </div>
                        <div>
                            <div style="color:var(--t-text-3)">Записи</div>
                            <div class="font-bold" style="color:var(--t-primary)">{{ cr.bookings }}</div>
                        </div>
                        <div>
                            <div style="color:var(--t-text-3)">Расход</div>
                            <div class="font-bold" style="color:var(--t-text)">{{ fmtMoney(cr.spend) }}</div>
                        </div>
                        <div>
                            <div style="color:var(--t-text-3)">ROAS</div>
                            <div class="font-bold" :style="`color:${cr.roas >= 6 ? '#22c55e' : '#f59e0b'}`">{{ cr.roas }}x</div>
                        </div>
                    </div>
                    <!-- mini bar -->
                    <div class="mt-2 h-2 rounded-full overflow-hidden" style="background:var(--t-border)">
                        <div class="h-full rounded-full" :style="`width:${Math.round(cr.bookings / 30 * 100)}%;background:var(--t-primary)`"></div>
                    </div>
                </div>
            </div>
        </VCard>

        <div class="flex gap-2">
            <VButton size="sm" variant="outline" class="flex-1">🎨 Создать креатив</VButton>
            <VButton size="sm" variant="outline" class="flex-1">🤖 AI-генерация</VButton>
        </div>
    </div>

    <!-- ═══ AUDIENCE TAB ═══ -->
    <div v-if="activeTab === 'audience'" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Geography -->
            <VCard title="🌍 География кликов">
                <div class="space-y-2">
                    <div v-for="g in audienceGeo" :key="g.city" class="flex items-center gap-2">
                        <span class="text-xs w-28 truncate" style="color:var(--t-text)">{{ g.city }}</span>
                        <div class="flex-1 h-4 rounded-full overflow-hidden" style="background:var(--t-bg)">
                            <div class="h-full rounded-full" :style="`width:${g.pct}%;background:var(--t-primary)`"></div>
                        </div>
                        <span class="text-[10px] w-10 text-right font-bold" style="color:var(--t-text)">{{ g.pct }}%</span>
                        <span class="text-[10px] w-14 text-right" style="color:var(--t-text-3)">{{ fmt(g.clicks) }} кл.</span>
                    </div>
                </div>
            </VCard>

            <!-- Demographics -->
            <VCard title="👥 Демография">
                <div class="space-y-2">
                    <div v-for="d in audienceDemo" :key="d.group" class="flex items-center gap-2">
                        <span class="text-xs w-16" style="color:var(--t-text)">{{ d.group }}</span>
                        <div class="flex-1 h-4 rounded-full overflow-hidden" style="background:var(--t-bg)">
                            <div class="h-full rounded-full" :style="`width:${d.pct * 2}%;background:var(--t-accent)`"></div>
                        </div>
                        <span class="text-[10px] w-8 text-right font-bold" style="color:var(--t-text)">{{ d.pct }}%</span>
                    </div>
                </div>
            </VCard>
        </div>

        <!-- Devices -->
        <VCard title="📱 Устройства">
            <div class="flex gap-3">
                <div v-for="d in audienceDevices" :key="d.device"
                     class="flex-1 p-3 rounded-xl border text-center"
                     style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-lg mb-1">{{ d.device.split(' ')[0] }}</div>
                    <div class="text-sm font-bold" style="color:var(--t-text)">{{ d.pct }}%</div>
                    <div class="text-[10px]" style="color:var(--t-text-3)">{{ d.device.split(' ')[1] }}</div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ BUDGET TAB ═══ -->
    <div v-if="activeTab === 'budget'" class="space-y-4">
        <VCard title="💰 Расход по дням">
            <div class="space-y-1.5">
                <div v-for="day in budgetDaily" :key="day.date"
                     class="flex items-center gap-3 text-xs">
                    <span class="w-12 font-mono" style="color:var(--t-text-3)">{{ day.date }}</span>
                    <div class="flex-1 h-5 rounded-full overflow-hidden" style="background:var(--t-bg)">
                        <div class="h-full rounded-full flex items-center justify-end pr-1"
                             :style="`width:${Math.round(day.spent / maxDailySpend * 100)}%;background:var(--t-primary)`">
                            <span class="text-[9px] text-white font-bold">{{ fmtMoney(day.spent) }}</span>
                        </div>
                    </div>
                    <span class="w-12 text-right" style="color:var(--t-text-2)">{{ day.clicks }} кл.</span>
                    <span class="w-8 text-right font-bold" style="color:var(--t-primary)">{{ day.bookings }} зап.</span>
                </div>
            </div>
            <div class="mt-3 pt-3 border-t flex justify-between text-xs" style="border-color:var(--t-border)">
                <span style="color:var(--t-text-3)">Итого за период:</span>
                <span class="font-bold" style="color:var(--t-text)">{{ fmtMoney(budgetTotalSpent) }}</span>
            </div>
        </VCard>

        <div class="grid grid-cols-3 gap-3">
            <VCard>
                <div class="text-center">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Средний расход / день</div>
                    <div class="text-xl font-bold" style="color:var(--t-primary)">{{ fmtMoney(Math.round(budgetTotalSpent / budgetDaily.length)) }}</div>
                </div>
            </VCard>
            <VCard>
                <div class="text-center">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Осталось бюджета</div>
                    <div class="text-xl font-bold" style="color:var(--t-text)">{{ fmtMoney(camp.budget - camp.spent) }}</div>
                </div>
            </VCard>
            <VCard>
                <div class="text-center">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Дней до исчерпания</div>
                    <div class="text-xl font-bold" :style="`color:${Math.round((camp.budget - camp.spent) / (budgetTotalSpent / budgetDaily.length)) < 3 ? '#ef4444' : 'var(--t-text)'}`">
                        {{ Math.round((camp.budget - camp.spent) / (budgetTotalSpent / budgetDaily.length)) }}
                    </div>
                </div>
            </VCard>
        </div>
    </div>

    <!-- ═══ DYNAMICS TAB ═══ -->
    <div v-if="activeTab === 'dynamics'" class="space-y-4">
        <VCard title="📈 Динамика показателей по дням">
            <!-- Impressions bar chart -->
            <div class="mb-4">
                <div class="text-xs font-semibold mb-2" style="color:var(--t-text-3)">Показы</div>
                <div class="flex items-end gap-1.5 h-24">
                    <div v-for="d in dynamicsData" :key="d.date" class="flex-1 text-center">
                        <div class="mx-auto rounded-t-sm w-full"
                             :style="`height:${Math.round(d.impressions / maxImpressions * 90)}px;background:var(--t-primary);opacity:0.7`"></div>
                        <div class="text-[8px] mt-0.5" style="color:var(--t-text-3)">{{ d.date.slice(0,5) }}</div>
                    </div>
                </div>
            </div>

            <!-- Metrics table -->
            <div class="overflow-x-auto">
                <table class="w-full text-[11px]">
                    <thead>
                        <tr class="border-b" style="border-color:var(--t-border)">
                            <th class="text-left px-2 py-1" style="color:var(--t-text-3)">Дата</th>
                            <th class="text-right px-2 py-1" style="color:var(--t-text-3)">Показы</th>
                            <th class="text-right px-2 py-1" style="color:var(--t-text-3)">Клики</th>
                            <th class="text-right px-2 py-1" style="color:var(--t-text-3)">CTR</th>
                            <th class="text-right px-2 py-1" style="color:var(--t-text-3)">Лиды</th>
                            <th class="text-right px-2 py-1" style="color:var(--t-text-3)">Записи</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in dynamicsData" :key="d.date" class="border-b" style="border-color:var(--t-border)">
                            <td class="px-2 py-1.5 font-mono" style="color:var(--t-text-2)">{{ d.date }}</td>
                            <td class="text-right px-2 py-1.5" style="color:var(--t-text)">{{ fmt(d.impressions) }}</td>
                            <td class="text-right px-2 py-1.5" style="color:var(--t-text)">{{ fmt(d.clicks) }}</td>
                            <td class="text-right px-2 py-1.5" :style="`color:${d.ctr >= 3 ? '#22c55e' : '#f59e0b'}`">{{ d.ctr }}%</td>
                            <td class="text-right px-2 py-1.5" style="color:var(--t-text-2)">{{ d.leads }}</td>
                            <td class="text-right px-2 py-1.5 font-bold" style="color:var(--t-primary)">{{ d.bookings }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>
    </div>

    <!-- ═══ UTM TAB ═══ -->
    <div v-if="activeTab === 'utm'" class="space-y-4">
        <VCard title="🔗 UTM-ссылки и метки">
            <div class="space-y-3">
                <div v-for="link in utmLinks" :key="link.label"
                     class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium" style="color:var(--t-text)">{{ link.label }}</span>
                        <span class="text-xs font-bold" style="color:var(--t-primary)">{{ fmt(link.clicks) }} кликов</span>
                    </div>
                    <code class="text-[10px] block break-all p-1.5 rounded-lg" style="background:var(--t-surface);color:var(--t-text-2)">{{ link.url }}</code>
                    <div class="flex gap-2 mt-2">
                        <VButton size="sm" variant="outline">📋 Копировать</VButton>
                        <VButton size="sm" variant="outline">📊 QR-код</VButton>
                    </div>
                </div>
            </div>
        </VCard>
    </div>
</div>
</template>
