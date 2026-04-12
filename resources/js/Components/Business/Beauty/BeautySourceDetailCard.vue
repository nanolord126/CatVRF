<script setup>
/**
 * BeautySourceDetailCard — универсальная карточка источника привлечения.
 * Используется для: Онлайн-запись, Поиск/SEO, Лента новостей,
 * Рекомендации, Администратор, Партнёры.
 * Получает source (строку из srcMainTable) + sourceType (string).
 * Внутри определяет layout и данные по типу.
 */
import { ref, computed } from 'vue';
import VButton from '../../UI/VButton.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import VStatCard from '../../UI/VStatCard.vue';

const props = defineProps({
    source: { type: Object, required: true },
    sourceType: { type: String, required: true },
});

const emit = defineEmits(['close', 'export']);

/* ─── Helpers ─── */
function fmtMoney(n) { return new Intl.NumberFormat('ru-RU').format(n) + ' ₽'; }
function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }

/* ═══════════════ TABS ═══════════════ */
const tabs = [
    { key: 'overview',  label: '📊 Обзор' },
    { key: 'details',   label: '📋 Детали' },
    { key: 'dynamics',  label: '📈 Динамика' },
    { key: 'actions',   label: '⚡ Действия' },
];
const activeTab = ref('overview');

/* ═══════════════ KPI (из source row) ═══════════════ */
const kpis = computed(() => {
    const s = props.source;
    return [
        { label: 'Клиенты',      value: s.clients ?? s.newClients ?? 0,                icon: '👥', color: 'var(--t-primary)' },
        { label: 'Новые',         value: s.newClients ?? 0,                              icon: '🆕', color: '#22c55e' },
        { label: 'Выручка',       value: fmtMoney(s.revenue ?? 0),                      icon: '💰', color: 'var(--t-primary)' },
        { label: 'Ср. чек',       value: fmtMoney(s.avgCheck ?? 0),                     icon: '🧾', color: 'var(--t-text)' },
        { label: 'Доля',          value: (s.share ?? 0) + '%',                          icon: '📊', color: 'var(--t-accent)' },
        { label: 'Динамика',      value: s.dynamics ?? '—',                              icon: '📈', color: (s.dynamics ?? '').startsWith('+') ? '#22c55e' : '#ef4444' },
    ];
});

/* ═══════════════ ICONS MAP ═══════════════ */
const sourceIcons = {
    'online_booking': '📱', 'search': '🔍', 'news_feed': '📰',
    'recommendations': '💬', 'admin': '📞', 'partners': '🤝',
};
const sourceIcon = computed(() => sourceIcons[props.sourceType] || '📡');

/* ═══════════════ DETAILS — CONDITIONAL BY TYPE ═══════════════ */

/* === Online booking === */
const onlineBookingData = ref({
    conversionFunnel: [
        { step: 'Открыли виджет', value: 2400 },
        { step: 'Выбрали услугу', value: 1840 },
        { step: 'Выбрали время',  value: 1200 },
        { step: 'Заполнили форму', value: 820 },
        { step: 'Подтвердили',    value: 680 },
    ],
    topServices: [
        { name: 'Стрижка + окрашивание', bookings: 42, revenue: 378000 },
        { name: 'Маникюр + педикюр',     bookings: 38, revenue: 152000 },
        { name: 'SPA-программа Премиум',  bookings: 12, revenue: 180000 },
    ],
    peakHours: [
        { hour: '09–12', bookings: 22 }, { hour: '12–15', bookings: 45 },
        { hour: '15–18', bookings: 68 }, { hour: '18–21', bookings: 52 },
    ],
    widgetStats: { avgFillTime: '2 мин 14 сек', abandonment: '17%', mobileShare: '72%' },
});

/* === Search / SEO === */
const searchData = ref({
    topKeywords: [
        { keyword: 'салон красоты рядом',          impressions: 12400, clicks: 840,  ctr: 6.8, position: 3 },
        { keyword: 'окрашивание AirTouch Москва',  impressions: 5200,  clicks: 520,  ctr: 10.0, position: 2 },
        { keyword: 'маникюр цена',                  impressions: 8400,  clicks: 380,  ctr: 4.5, position: 5 },
        { keyword: 'салон красоты Арбат',           impressions: 3200,  clicks: 420,  ctr: 13.1, position: 1 },
    ],
    landingPages: [
        { url: '/services/coloring', sessions: 1200, bounceRate: 28, bookings: 42 },
        { url: '/masters',           sessions: 840,  bounceRate: 35, bookings: 28 },
        { url: '/prices',            sessions: 620,  bounceRate: 42, bookings: 18 },
    ],
    overallSEO: { totalImpressions: 42000, totalClicks: 3200, avgPosition: 4.2, avgCTR: 7.6 },
});

/* === News feed === */
const newsFeedData = ref({
    posts: [
        { id: 1, title: 'Весенние тренды маникюра 2026', views: 8200,  clicks: 420, bookings: 6, date: '12.03' },
        { id: 2, title: 'Скидка 20% на SPA-программы',   views: 12400, clicks: 680, bookings: 12, date: '10.03' },
        { id: 3, title: 'Новый мастер-колорист',          views: 6800,  clicks: 310, bookings: 4, date: '08.03' },
    ],
    reachDynamics: [
        { week: '04–10 мар', reach: 14200 }, { week: '11–17 мар', reach: 18400 },
    ],
    avgER: 3.4, subscribers: 2800, newSubscribers: 120,
});

/* === Recommendations === */
const recsData = ref({
    topReferrers: [
        { name: 'Анна Петрова',       referrals: 8, revenue: 84000, bonus: 4200 },
        { name: 'Мария Иванова',      referrals: 5, revenue: 52000, bonus: 2600 },
        { name: 'Екатерина Сидорова', referrals: 4, revenue: 48000, bonus: 2400 },
    ],
    programStats: { totalReferrals: 42, conversionRate: 68, avgBonusPerReferral: 2100, totalBonusesPaid: 88200 },
});

/* === Admin (telephone / walk-in) === */
const adminData = ref({
    callStats: { totalCalls: 340, answeredCalls: 312, avgWaitTime: '14 сек', avgCallDuration: '3 мин 42 сек' },
    walkInStats: { totalWalkIns: 86, converted: 72, convRate: 83.7 },
    topTimeSlots: [
        { slot: '10:00–12:00', calls: 82, walkIns: 18 },
        { slot: '14:00–16:00', calls: 64, walkIns: 24 },
        { slot: '18:00–20:00', calls: 48, walkIns: 12 },
    ],
    peakDay: 'Понедельник',
});

/* === Partners === */
const partnersData = ref({
    partnersList: [
        { name: 'Fitness Club «Олимп»',  clients: 18, revenue: 162000, commission: 12, active: true },
        { name: 'Hotel «Ritz Boutique»',  clients: 8,  revenue: 96000,  commission: 10, active: true },
        { name: 'Цветочный салон «Rosa»', clients: 4,  revenue: 42000,  commission: 15, active: true },
    ],
    totalFromPartners: { clients: 30, revenue: 300000, commissionPaid: 38400 },
});

/* ═══════════════ DYNAMICS (universal) ═══════════════ */
const weeklyDynamics = ref([
    { week: '24 фев – 02 мар', clients: 28, revenue: 296000 },
    { week: '03 мар – 09 мар', clients: 34, revenue: 362000 },
    { week: '10 мар – 17 мар', clients: 42, revenue: 441000 },
]);
const maxDynClients = computed(() => Math.max(...weeklyDynamics.value.map(w => w.clients)));

/* ═══════════════ SOURCE TITLE ═══════════════ */
const titleMap = {
    'online_booking': '📱 Онлайн-запись',
    'search': '🔍 Поиск / SEO',
    'news_feed': '📰 Лента новостей',
    'recommendations': '💬 Рекомендации / сарафан',
    'admin': '📞 Администратор / звонки',
    'partners': '🤝 Партнёрские программы',
};
const sourceTitle = computed(() => titleMap[props.sourceType] || props.source.source || 'Источник');

/* ═══════════════ ACTIONS ═══════════════ */
const actions = computed(() => {
    switch (props.sourceType) {
        case 'online_booking': return [
            { label: '⚙️ Настроить виджет',    action: 'configure_widget' },
            { label: '📊 A/B тест формы',       action: 'ab_test' },
            { label: '📱 Мобильная оптимизация', action: 'mobile_opt' },
        ];
        case 'search': return [
            { label: '🔍 SEO-аудит',            action: 'seo_audit' },
            { label: '📝 Контент-план',          action: 'content_plan' },
            { label: '🔗 Внутренняя перелинковка', action: 'interlinking' },
        ];
        case 'news_feed': return [
            { label: '📝 Создать пост',          action: 'create_post' },
            { label: '📅 Контент-план',          action: 'content_plan' },
            { label: '🎯 Настроить таргет',      action: 'setup_targeting' },
        ];
        case 'recommendations': return [
            { label: '💎 Настроить бонусы',      action: 'configure_bonuses' },
            { label: '📤 Разослать приглашения',  action: 'send_invitations' },
            { label: '📊 Статистика программы',   action: 'program_stats' },
        ];
        case 'admin': return [
            { label: '📞 История звонков',        action: 'call_history' },
            { label: '📋 Скрипты приёма',          action: 'scripts' },
            { label: '📊 KPI администраторов',     action: 'admin_kpi' },
        ];
        case 'partners': return [
            { label: '🤝 Добавить партнёра',      action: 'add_partner' },
            { label: '📋 Условия сотрудничества',  action: 'terms' },
            { label: '📊 Отчёт по партнёрам',     action: 'partner_report' },
        ];
        default: return [];
    }
});
</script>

<template>
<div class="space-y-4">

    <!-- ═══ HEADER ═══ -->
    <div class="relative rounded-2xl overflow-hidden p-5"
         style="background:linear-gradient(135deg,var(--t-gradient-from),var(--t-gradient-via),var(--t-gradient-to))">
        <div class="absolute inset-0 opacity-10"
             style="background:repeating-linear-gradient(-30deg,transparent,transparent 12px,rgba(255,255,255,.04) 12px,rgba(255,255,255,.04) 24px)"></div>
        <div class="relative flex items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center text-3xl"
                     style="background:rgba(255,255,255,.15);backdrop-filter:blur(6px)">{{ sourceIcon }}</div>
                <div>
                    <h2 class="text-lg font-bold text-white">{{ sourceTitle }}</h2>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs text-white/70">{{ source.clients ?? source.newClients ?? 0 }} клиентов</span>
                        <span class="text-xs text-white/70">•</span>
                        <span class="text-xs font-medium"
                              :class="(source.dynamics ?? '').startsWith('+') ? 'text-emerald-300' : 'text-red-300'">{{ source.dynamics }}</span>
                    </div>
                </div>
            </div>
            <VButton size="sm" variant="outline" style="color:#fff;border-color:rgba(255,255,255,.3)" @click="$emit('close')">✕</VButton>
        </div>
    </div>

    <!-- ═══ TABS ═══ -->
    <div class="flex gap-1 flex-wrap">
        <button v-for="t in tabs" :key="t.key"
                class="px-3 py-1.5 text-xs rounded-full border transition-all"
                :style="activeTab === t.key
                    ? 'background:var(--t-primary);color:#fff;border-color:var(--t-primary)'
                    : 'background:var(--t-surface);color:var(--t-text-2);border-color:var(--t-border)'"
                @click="activeTab = t.key">{{ t.label }}</button>
    </div>

    <!-- ═══ OVERVIEW ═══ -->
    <div v-if="activeTab === 'overview'" class="space-y-4">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2">
            <div v-for="k in kpis" :key="k.label"
                 class="p-2.5 rounded-xl border text-center"
                 style="background:var(--t-bg);border-color:var(--t-border)">
                <div class="text-lg mb-0.5">{{ k.icon }}</div>
                <div class="text-[10px] mb-1" style="color:var(--t-text-3)">{{ k.label }}</div>
                <div class="text-sm font-bold" :style="`color:${k.color}`">{{ k.value }}</div>
            </div>
        </div>
    </div>

    <!-- ═══ DETAILS (conditional by sourceType) ═══ -->
    <div v-if="activeTab === 'details'" class="space-y-4">

        <!-- ONLINE BOOKING -->
        <template v-if="sourceType === 'online_booking'">
            <VCard title="🔄 Воронка онлайн-записи">
                <div class="space-y-2">
                    <div v-for="(f, i) in onlineBookingData.conversionFunnel" :key="f.step" class="flex items-center gap-3">
                        <span class="text-[10px] w-32 truncate" style="color:var(--t-text)">{{ f.step }}</span>
                        <div class="flex-1 h-5 rounded overflow-hidden" style="background:var(--t-bg)">
                            <div class="h-full rounded flex items-center justify-end pr-2"
                                 :style="`width:${(f.value / onlineBookingData.conversionFunnel[0].value) * 100}%;background:var(--t-primary);min-width:2rem`">
                                <span class="text-[9px] font-bold text-white">{{ fmt(f.value) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </VCard>
            <VCard title="🏆 Топ-услуги через онлайн-запись">
                <div class="space-y-2">
                    <div v-for="s in onlineBookingData.topServices" :key="s.name"
                         class="flex items-center gap-3 p-2 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <span class="flex-1 text-xs" style="color:var(--t-text)">{{ s.name }}</span>
                        <span class="text-[10px] font-bold" style="color:var(--t-primary)">{{ s.bookings }} записей</span>
                        <span class="text-[10px]" style="color:var(--t-text-2)">{{ fmtMoney(s.revenue) }}</span>
                    </div>
                </div>
            </VCard>
            <VCard title="⏰ Пиковые часы записи">
                <div class="grid grid-cols-4 gap-2">
                    <div v-for="h in onlineBookingData.peakHours" :key="h.hour"
                         class="p-2 rounded-lg border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                        <div class="text-[10px]" style="color:var(--t-text-3)">{{ h.hour }}</div>
                        <div class="text-sm font-bold" style="color:var(--t-primary)">{{ h.bookings }}</div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- SEARCH / SEO -->
        <template v-if="sourceType === 'search'">
            <VCard title="🔍 Топ ключевых слов">
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b" style="border-color:var(--t-border)">
                                <th class="text-left px-2 py-2" style="color:var(--t-text-3)">Ключевое слово</th>
                                <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Показы</th>
                                <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Клики</th>
                                <th class="text-right px-2 py-2" style="color:var(--t-text-3)">CTR</th>
                                <th class="text-right px-2 py-2" style="color:var(--t-text-3)">Позиция</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="kw in searchData.topKeywords" :key="kw.keyword" class="border-b" style="border-color:var(--t-border)">
                                <td class="px-2 py-2 font-medium" style="color:var(--t-text)">{{ kw.keyword }}</td>
                                <td class="text-right px-2 py-2" style="color:var(--t-text-2)">{{ fmt(kw.impressions) }}</td>
                                <td class="text-right px-2 py-2 font-bold" style="color:var(--t-text)">{{ fmt(kw.clicks) }}</td>
                                <td class="text-right px-2 py-2" style="color:var(--t-primary)">{{ kw.ctr }}%</td>
                                <td class="text-right px-2 py-2 font-bold" :style="`color:${kw.position <= 3 ? '#22c55e' : '#f59e0b'}`">{{ kw.position }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </VCard>
            <VCard title="📄 Посадочные страницы">
                <div class="space-y-2">
                    <div v-for="lp in searchData.landingPages" :key="lp.url"
                         class="flex items-center gap-3 p-2 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <span class="flex-1 text-xs font-mono truncate" style="color:var(--t-primary)">{{ lp.url }}</span>
                        <span class="text-[10px]" style="color:var(--t-text-2)">{{ fmt(lp.sessions) }} сессий</span>
                        <span class="text-[10px]" style="color:var(--t-text-3)">отказ {{ lp.bounceRate }}%</span>
                        <span class="text-[10px] font-bold" style="color:var(--t-primary)">{{ lp.bookings }} записей</span>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- NEWS FEED -->
        <template v-if="sourceType === 'news_feed'">
            <VCard title="📰 Публикации в ленте">
                <div class="space-y-2">
                    <div v-for="p in newsFeedData.posts" :key="p.id"
                         class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="flex-1 text-xs font-medium" style="color:var(--t-text)">{{ p.title }}</span>
                            <span class="text-[10px]" style="color:var(--t-text-3)">{{ p.date }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center text-[10px]">
                            <div><div style="color:var(--t-text-3)">Просмотры</div><div class="font-bold" style="color:var(--t-text)">{{ fmt(p.views) }}</div></div>
                            <div><div style="color:var(--t-text-3)">Клики</div><div class="font-bold" style="color:var(--t-text)">{{ fmt(p.clicks) }}</div></div>
                            <div><div style="color:var(--t-text-3)">Записи</div><div class="font-bold" style="color:var(--t-primary)">{{ p.bookings }}</div></div>
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- RECOMMENDATIONS -->
        <template v-if="sourceType === 'recommendations'">
            <VCard title="💬 Топ-рекомендатели">
                <div class="space-y-2">
                    <div v-for="r in recsData.topReferrers" :key="r.name"
                         class="flex items-center gap-3 p-2 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <span class="flex-1 text-xs font-medium" style="color:var(--t-text)">{{ r.name }}</span>
                        <span class="text-[10px]" style="color:var(--t-text-2)">{{ r.referrals }} рефералов</span>
                        <span class="text-[10px] font-bold" style="color:var(--t-primary)">{{ fmtMoney(r.revenue) }}</span>
                        <span class="text-[10px]" style="color:#22c55e">бонус {{ fmtMoney(r.bonus) }}</span>
                    </div>
                </div>
            </VCard>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div class="p-2.5 rounded-xl border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Всего рефералов</div>
                    <div class="text-lg font-bold" style="color:var(--t-primary)">{{ recsData.programStats.totalReferrals }}</div>
                </div>
                <div class="p-2.5 rounded-xl border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Конверсия</div>
                    <div class="text-lg font-bold" style="color:#22c55e">{{ recsData.programStats.conversionRate }}%</div>
                </div>
                <div class="p-2.5 rounded-xl border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Ср. бонус</div>
                    <div class="text-lg font-bold" style="color:var(--t-text)">{{ fmtMoney(recsData.programStats.avgBonusPerReferral) }}</div>
                </div>
                <div class="p-2.5 rounded-xl border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Выплачено бонусов</div>
                    <div class="text-lg font-bold" style="color:#f59e0b">{{ fmtMoney(recsData.programStats.totalBonusesPaid) }}</div>
                </div>
            </div>
        </template>

        <!-- ADMIN -->
        <template v-if="sourceType === 'admin'">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div class="p-2.5 rounded-xl border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Всего звонков</div>
                    <div class="text-lg font-bold" style="color:var(--t-text)">{{ adminData.callStats.totalCalls }}</div>
                </div>
                <div class="p-2.5 rounded-xl border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Принято</div>
                    <div class="text-lg font-bold" style="color:#22c55e">{{ adminData.callStats.answeredCalls }}</div>
                </div>
                <div class="p-2.5 rounded-xl border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Ср. ожидание</div>
                    <div class="text-lg font-bold" style="color:var(--t-primary)">{{ adminData.callStats.avgWaitTime }}</div>
                </div>
                <div class="p-2.5 rounded-xl border text-center" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-[10px]" style="color:var(--t-text-3)">Ср. длительность</div>
                    <div class="text-lg font-bold" style="color:var(--t-text)">{{ adminData.callStats.avgCallDuration }}</div>
                </div>
            </div>
            <VCard title="🚶 Пешеходные визиты">
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="p-2 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <div class="text-[10px]" style="color:var(--t-text-3)">Всего</div>
                        <div class="text-lg font-bold" style="color:var(--t-text)">{{ adminData.walkInStats.totalWalkIns }}</div>
                    </div>
                    <div class="p-2 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <div class="text-[10px]" style="color:var(--t-text-3)">Конвертировано</div>
                        <div class="text-lg font-bold" style="color:#22c55e">{{ adminData.walkInStats.converted }}</div>
                    </div>
                    <div class="p-2 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <div class="text-[10px]" style="color:var(--t-text-3)">Конверсия</div>
                        <div class="text-lg font-bold" style="color:var(--t-primary)">{{ adminData.walkInStats.convRate }}%</div>
                    </div>
                </div>
            </VCard>
            <VCard title="⏰ Пиковые слоты">
                <div class="space-y-2">
                    <div v-for="ts in adminData.topTimeSlots" :key="ts.slot"
                         class="flex items-center gap-3 p-2 rounded-lg border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <span class="text-xs font-medium w-24" style="color:var(--t-text)">{{ ts.slot }}</span>
                        <span class="text-[10px]" style="color:var(--t-text-2)">📞 {{ ts.calls }}</span>
                        <span class="text-[10px]" style="color:var(--t-text-2)">🚶 {{ ts.walkIns }}</span>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- PARTNERS -->
        <template v-if="sourceType === 'partners'">
            <VCard title="🤝 Партнёры">
                <div class="space-y-2">
                    <div v-for="p in partnersData.partnersList" :key="p.name"
                         class="p-3 rounded-xl border" style="background:var(--t-bg);border-color:var(--t-border)">
                        <div class="flex items-center gap-2 mb-1">
                            <VBadge :color="p.active ? 'green' : 'gray'" size="sm">{{ p.active ? 'активен' : 'пауза' }}</VBadge>
                            <span class="flex-1 text-xs font-medium" style="color:var(--t-text)">{{ p.name }}</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center text-[10px]">
                            <div><div style="color:var(--t-text-3)">Клиенты</div><div class="font-bold" style="color:var(--t-primary)">{{ p.clients }}</div></div>
                            <div><div style="color:var(--t-text-3)">Выручка</div><div class="font-bold" style="color:var(--t-text)">{{ fmtMoney(p.revenue) }}</div></div>
                            <div><div style="color:var(--t-text-3)">Комиссия</div><div class="font-bold" style="color:#f59e0b">{{ p.commission }}%</div></div>
                        </div>
                    </div>
                </div>
            </VCard>
        </template>
    </div>

    <!-- ═══ DYNAMICS ═══ -->
    <div v-if="activeTab === 'dynamics'" class="space-y-4">
        <VCard title="📈 Динамика по неделям">
            <div class="flex items-end gap-2 h-24 mb-2">
                <div v-for="w in weeklyDynamics" :key="w.week"
                     class="flex-1 rounded-t"
                     :style="`height:${(w.clients / maxDynClients) * 100}%;background:var(--t-primary)`"></div>
            </div>
            <div class="space-y-1 text-xs">
                <div v-for="w in weeklyDynamics" :key="w.week"
                     class="flex items-center justify-between p-1.5 rounded" style="background:var(--t-bg)">
                    <span style="color:var(--t-text-3)">{{ w.week }}</span>
                    <span class="font-bold" style="color:var(--t-primary)">{{ w.clients }} клиентов</span>
                    <span style="color:var(--t-text-2)">{{ fmtMoney(w.revenue) }}</span>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ ACTIONS ═══ -->
    <div v-if="activeTab === 'actions'" class="space-y-4">
        <VCard title="⚡ Доступные действия">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <button v-for="a in actions" :key="a.action"
                        class="p-4 rounded-xl border text-left transition-all hover:scale-[1.02]"
                        style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="text-sm font-medium mb-1" style="color:var(--t-text)">{{ a.label }}</div>
                </button>
            </div>
        </VCard>

        <div class="flex gap-2">
            <VButton size="sm" variant="outline" @click="$emit('export', source)">📥 Экспорт данных</VButton>
            <VButton size="sm" variant="outline">📊 Полный отчёт PDF</VButton>
        </div>
    </div>
</div>
</template>
