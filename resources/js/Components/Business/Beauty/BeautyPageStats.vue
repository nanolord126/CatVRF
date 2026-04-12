<script setup>
/**
 * BeautyPageStats — статистика публичных страниц (пабликов).
 * Детальная аналитика: охваты, просмотры, подписчики, вовлечённость,
 * конверсия, воронка, динамика, рейтинг постов, источники трафика,
 * демография аудитории, контент-план, A/B тесты.
 *
 * 7 табов: обзор, аудитория, контент, воронка, источники, сравнение, экспорт
 *
 * Props: pages, posts (из BeautyPublicPages)
 * Emits: export-report, open-page, open-post
 */
import { ref, computed, reactive } from 'vue';
import VCard from '../../UI/VCard.vue';
import VButton from '../../UI/VButton.vue';
import VBadge from '../../UI/VBadge.vue';
import VModal from '../../UI/VModal.vue';
import VTabs from '../../UI/VTabs.vue';
import VStatCard from '../../UI/VStatCard.vue';

const props = defineProps({
    pages: { type: Array, default: () => [] },
    posts: { type: Array, default: () => [] },
});

const emit = defineEmits(['export-report', 'open-page', 'open-post']);

/* ── Themes ── */
const themes = {
    mint:    { bg: '#f0fdf4', surface: '#ffffff', border: '#bbf7d0', primary: '#22c55e', primaryDim: '#16a34a', accent: '#10b981', text: '#1e293b', text2: '#475569', text3: '#94a3b8', glow: 'rgba(34,197,94,.18)', header: '#f0fdf4', btn: '#22c55e', btnHover: '#16a34a', cardHover: '#f0fdf4', gradientFrom: '#22c55e', gradientVia: '#10b981', gradientTo: '#059669' },
    day:     { bg: '#fffbeb', surface: '#ffffff', border: '#fde68a', primary: '#f59e0b', primaryDim: '#d97706', accent: '#eab308', text: '#1e293b', text2: '#475569', text3: '#94a3b8', glow: 'rgba(245,158,11,.18)', header: '#fffbeb', btn: '#f59e0b', btnHover: '#d97706', cardHover: '#fffbeb', gradientFrom: '#f59e0b', gradientVia: '#eab308', gradientTo: '#ca8a04' },
    night:   { bg: '#0f172a', surface: '#1e293b', border: '#334155', primary: '#818cf8', primaryDim: '#6366f1', accent: '#a78bfa', text: '#f1f5f9', text2: '#cbd5e1', text3: '#64748b', glow: 'rgba(129,140,248,.18)', header: '#0f172a', btn: '#818cf8', btnHover: '#6366f1', cardHover: '#1e293b', gradientFrom: '#818cf8', gradientVia: '#a78bfa', gradientTo: '#7c3aed' },
    sunset:  { bg: '#fff1f2', surface: '#ffffff', border: '#fecdd3', primary: '#fb7185', primaryDim: '#f43f5e', accent: '#e11d48', text: '#1e293b', text2: '#475569', text3: '#94a3b8', glow: 'rgba(251,113,133,.18)', header: '#fff1f2', btn: '#fb7185', btnHover: '#f43f5e', cardHover: '#fff1f2', gradientFrom: '#fb7185', gradientVia: '#f43f5e', gradientTo: '#e11d48' },
    lavender:{ bg: '#faf5ff', surface: '#ffffff', border: '#e9d5ff', primary: '#a855f7', primaryDim: '#9333ea', accent: '#7c3aed', text: '#1e293b', text2: '#475569', text3: '#94a3b8', glow: 'rgba(168,85,247,.18)', header: '#faf5ff', btn: '#a855f7', btnHover: '#9333ea', cardHover: '#faf5ff', gradientFrom: '#a855f7', gradientVia: '#7c3aed', gradientTo: '#6d28d9' },
};
const currentTheme = ref('mint');
const t = computed(() => themes[currentTheme.value]);

/* ── Toast ── */
const showToast = ref(false);
const toastMessage = ref('');
function toast(msg) {
    toastMessage.value = msg;
    showToast.value = true;
    setTimeout(() => { showToast.value = false; }, 3000);
}

/* ── Inner Tabs ── */
const innerTabs = [
    { key: 'overview',    label: '📊 Обзор' },
    { key: 'audience',    label: '👥 Аудитория' },
    { key: 'content',     label: '📝 Контент' },
    { key: 'funnel',      label: '🔻 Воронка' },
    { key: 'sources',     label: '🌐 Источники' },
    { key: 'compare',     label: '⚖️ Сравнение' },
    { key: 'export',      label: '📥 Экспорт' },
];
const activeInner = ref('overview');

/* ── Period selector ── */
const periods = ['7д', '14д', '30д', '90д', '365д'];
const activePeriod = ref('30д');

/* ═══════════════════════════════════════════════════
   TAB 1 — ОБЗОР (ключевые метрики)
   ═══════════════════════════════════════════════════ */
const kpiCards = computed(() => [
    { label: 'Подписчиков всего', value: '12 480', change: '+1 230', changePercent: '+10.9%', icon: '👥', good: true },
    { label: 'Просмотров за период', value: '87 340', change: '+18 200', changePercent: '+26.3%', icon: '👁️', good: true },
    { label: 'Охват уникальных', value: '34 120', change: '+5 800', changePercent: '+20.5%', icon: '📡', good: true },
    { label: 'Вовлечённость (ER)', value: '8.4%', change: '+1.2%', changePercent: '', icon: '❤️', good: true },
    { label: 'Клики на CTA', value: '2 340', change: '+420', changePercent: '+21.9%', icon: '👆', good: true },
    { label: 'Записей через паблик', value: '324', change: '+56', changePercent: '+20.9%', icon: '📅', good: true },
    { label: 'Конверсия в запись', value: '13.8%', change: '+2.1%', changePercent: '', icon: '🎯', good: true },
    { label: 'Средний чек из паблика', value: '3 680 ₽', change: '+340', changePercent: '+10.2%', icon: '💰', good: true },
]);

const dailyMetrics = ref([
    { date: '08.04', views: 4280, followers: 42, engagement: 8.8, bookings: 18, revenue: 62400 },
    { date: '07.04', views: 3920, followers: 38, engagement: 9.1, bookings: 15, revenue: 54300 },
    { date: '06.04', views: 4100, followers: 35, engagement: 7.9, bookings: 12, revenue: 48200 },
    { date: '05.04', views: 3680, followers: 28, engagement: 8.2, bookings: 14, revenue: 51800 },
    { date: '04.04', views: 3450, followers: 31, engagement: 7.6, bookings: 11, revenue: 42100 },
    { date: '03.04', views: 3200, followers: 25, engagement: 8.5, bookings: 13, revenue: 47600 },
    { date: '02.04', views: 2980, followers: 22, engagement: 7.2, bookings: 9, revenue: 38400 },
]);
const maxViews = computed(() => Math.max(...dailyMetrics.value.map(d => d.views)));

/* ═══════════════════════════════════════════════════
   TAB 2 — АУДИТОРИЯ
   ═══════════════════════════════════════════════════ */
const audienceGender = ref([
    { label: 'Женщины', value: 78, color: '#ec4899' },
    { label: 'Мужчины', value: 18, color: '#3b82f6' },
    { label: 'Не указано', value: 4, color: '#94a3b8' },
]);
const audienceAge = ref([
    { range: '13-17', percent: 3 },
    { range: '18-24', percent: 22 },
    { range: '25-34', percent: 38 },
    { range: '35-44', percent: 24 },
    { range: '45-54', percent: 9 },
    { range: '55+', percent: 4 },
]);
const maxAgePct = computed(() => Math.max(...audienceAge.value.map(a => a.percent)));
const audienceGeo = ref([
    { city: 'Москва', percent: 34, count: 4243 },
    { city: 'Санкт-Петербург', percent: 18, count: 2246 },
    { city: 'Казань', percent: 8, count: 998 },
    { city: 'Екатеринбург', percent: 6, count: 749 },
    { city: 'Новосибирск', percent: 5, count: 624 },
    { city: 'Остальные', percent: 29, count: 3620 },
]);
const audienceActiveHours = ref([
    { hour: '08:00', activity: 12 },
    { hour: '10:00', activity: 28 },
    { hour: '12:00', activity: 42 },
    { hour: '14:00', activity: 35 },
    { hour: '16:00', activity: 48 },
    { hour: '18:00', activity: 65 },
    { hour: '20:00', activity: 78 },
    { hour: '22:00', activity: 54 },
    { hour: '00:00', activity: 18 },
]);
const maxActivity = computed(() => Math.max(...audienceActiveHours.value.map(h => h.activity)));
const followerGrowth = ref([
    { week: '10–16.03', gained: 280, lost: 42, net: 238 },
    { week: '17–23.03', gained: 310, lost: 38, net: 272 },
    { week: '24–30.03', gained: 345, lost: 51, net: 294 },
    { week: '31.03–06.04', gained: 412, lost: 46, net: 366 },
    { week: '07–08.04', gained: 190, lost: 18, net: 172 },
]);

/* ═══════════════════════════════════════════════════
   TAB 3 — КОНТЕНТ (рейтинг постов и типы)
   ═══════════════════════════════════════════════════ */
const topPosts = ref([
    { id: 1, title: 'AirTouch — тренд весны 🌸', page: 'Анна С.', type: 'video', views: 5800, likes: 456, comments: 42, shares: 28, er: 9.1, bookings: 18 },
    { id: 2, title: 'Конкурс подписчиков 🎁', page: 'Бьюти Лайф', type: 'promo', views: 4900, likes: 380, comments: 94, shares: 67, er: 11.0, bookings: 0 },
    { id: 3, title: 'Nail Art космос 🌌', page: 'Ольга Д.', type: 'before-after', views: 4200, likes: 312, comments: 29, shares: 15, er: 8.5, bookings: 12 },
    { id: 4, title: 'Скидка 20% на маникюр', page: 'Гламур', type: 'promo', views: 4200, likes: 189, comments: 7, shares: 34, er: 5.5, bookings: 42 },
    { id: 5, title: 'Новая коллекция весна 💐', page: 'Бьюти Лайф', type: 'text+photo', views: 3400, likes: 234, comments: 18, shares: 12, er: 7.8, bookings: 8 },
    { id: 6, title: 'Мой путь в nail-art 💅', page: 'Ольга Д.', type: 'text+photo', views: 2900, likes: 312, comments: 29, shares: 15, er: 12.3, bookings: 5 },
]);
const contentByType = ref([
    { type: 'Фото + текст', count: 48, avgViews: 3200, avgER: 7.4, avgBookings: 4.2 },
    { type: 'Видео', count: 22, avgViews: 5100, avgER: 9.8, avgBookings: 8.1 },
    { type: 'До / После', count: 18, avgViews: 4400, avgER: 10.2, avgBookings: 6.5 },
    { type: 'Акция / Промо', count: 14, avgViews: 4000, avgER: 6.1, avgBookings: 12.3 },
    { type: 'Reels / Шортс', count: 12, avgViews: 6200, avgER: 11.5, avgBookings: 3.8 },
    { type: 'Карусель', count: 8, avgViews: 3800, avgER: 8.9, avgBookings: 5.1 },
    { type: 'Опрос', count: 6, avgViews: 2400, avgER: 14.2, avgBookings: 1.0 },
]);
const bestPublishTimes = ref([
    { day: 'Понедельник', time: '18:00–19:00', avgER: 9.8 },
    { day: 'Вторник', time: '12:00–13:00', avgER: 8.4 },
    { day: 'Среда', time: '20:00–21:00', avgER: 10.1 },
    { day: 'Четверг', time: '18:00–19:00', avgER: 9.2 },
    { day: 'Пятница', time: '16:00–17:00', avgER: 8.8 },
    { day: 'Суббота', time: '11:00–12:00', avgER: 11.3 },
    { day: 'Воскресенье', time: '19:00–20:00', avgER: 10.5 },
]);

/* ═══════════════════════════════════════════════════
   TAB 4 — ВОРОНКА (путь от просмотра до записи)
   ═══════════════════════════════════════════════════ */
const funnelSteps = ref([
    { step: '👁️ Просмотр страницы', count: 87340, percent: 100, drop: 0 },
    { step: '📖 Просмотр поста / портфолио', count: 42600, percent: 48.8, drop: 51.2 },
    { step: '❤️ Лайк / Сохранение', count: 18200, percent: 20.8, drop: 57.3 },
    { step: '👤 Переход в профиль мастера', count: 8400, percent: 9.6, drop: 53.8 },
    { step: '📅 Открытие формы записи', count: 4120, percent: 4.7, drop: 51.0 },
    { step: '✅ Подтверждённая запись', count: 2340, percent: 2.7, drop: 43.2 },
    { step: '💰 Оплаченная услуга', count: 1890, percent: 2.2, drop: 19.2 },
]);
const maxFunnelCount = computed(() => funnelSteps.value[0]?.count || 1);
const funnelConversion = computed(() => {
    const first = funnelSteps.value[0]?.count || 1;
    const last = funnelSteps.value[funnelSteps.value.length - 1]?.count || 0;
    return ((last / first) * 100).toFixed(1);
});

const funnelByPage = ref([
    { page: 'Бьюти Лайф', views: 32400, bookings: 124, conversion: 0.38, revenue: 456200 },
    { page: 'Анна С.', views: 24100, bookings: 98, conversion: 0.41, revenue: 342100 },
    { page: 'Гламур', views: 18200, bookings: 62, conversion: 0.34, revenue: 218400 },
    { page: 'Ольга Д.', views: 9800, bookings: 34, conversion: 0.35, revenue: 124800 },
    { page: 'НейлАрт', views: 2100, bookings: 6, conversion: 0.29, revenue: 18600 },
]);

/* ═══════════════════════════════════════════════════
   TAB 5 — ИСТОЧНИКИ ТРАФИКА
   ═══════════════════════════════════════════════════ */
const trafficSources = ref([
    { source: 'Поиск (Yandex + Google)', visits: 28400, percent: 32.5, bookings: 86, conversion: 0.30, color: '#3b82f6' },
    { source: 'Прямые переходы', visits: 18200, percent: 20.8, bookings: 124, conversion: 0.68, color: '#22c55e' },
    { source: 'Instagram', visits: 14800, percent: 16.9, bookings: 42, conversion: 0.28, color: '#e11d48' },
    { source: 'VK', visits: 10200, percent: 11.7, bookings: 28, conversion: 0.27, color: '#2563eb' },
    { source: 'Telegram', visits: 6800, percent: 7.8, bookings: 22, conversion: 0.32, color: '#0ea5e9' },
    { source: 'Реферальные ссылки', visits: 4200, percent: 4.8, bookings: 14, conversion: 0.33, color: '#a855f7' },
    { source: 'Виджеты на сайтах', visits: 2800, percent: 3.2, bookings: 6, conversion: 0.21, color: '#f59e0b' },
    { source: 'QR-коды', visits: 2040, percent: 2.3, bookings: 2, conversion: 0.10, color: '#64748b' },
]);
const maxSourceVisits = computed(() => Math.max(...trafficSources.value.map(s => s.visits)));

const utmCampaigns = ref([
    { campaign: 'spring_promo_2026', source: 'instagram', visits: 4200, bookings: 18, cpa: 280 },
    { campaign: 'nail_art_contest', source: 'vk', visits: 3100, bookings: 12, cpa: 0 },
    { campaign: 'airtouch_video', source: 'telegram', visits: 2800, bookings: 15, cpa: 180 },
    { campaign: 'beauty_blog_ref', source: 'referral', visits: 1400, bookings: 8, cpa: 350 },
    { campaign: 'qr_storefront', source: 'qr', visits: 800, bookings: 2, cpa: 0 },
]);

/* ═══════════════════════════════════════════════════
   TAB 6 — СРАВНЕНИЕ СТРАНИЦ
   ═══════════════════════════════════════════════════ */
const comparePages = ref([
    { name: 'Бьюти Лайф', followers: 4280, views30d: 32400, posts30d: 12, er: 9.2, bookings30d: 124, revenue30d: 456200, growth: '+12%' },
    { name: 'Анна С.', followers: 3120, views30d: 24100, posts30d: 10, er: 11.4, bookings30d: 98, revenue30d: 342100, growth: '+18%' },
    { name: 'Гламур', followers: 2890, views30d: 18200, posts30d: 8, er: 7.8, bookings30d: 62, revenue30d: 218400, growth: '+8%' },
    { name: 'Ольга Д.', followers: 1480, views30d: 9800, posts30d: 6, er: 12.1, bookings30d: 34, revenue30d: 124800, growth: '+22%' },
    { name: 'НейлАрт', followers: 510, views30d: 2100, posts30d: 2, er: 6.3, bookings30d: 6, revenue30d: 18600, growth: '+45%' },
    { name: 'Кристина Л.', followers: 200, views30d: 680, posts30d: 0, er: 0, bookings30d: 0, revenue30d: 0, growth: 'new' },
]);
const compareSortKey = ref('revenue30d');
const sortedCompare = computed(() => {
    return [...comparePages.value].sort((a, b) => {
        const va = typeof a[compareSortKey.value] === 'string' ? 0 : a[compareSortKey.value];
        const vb = typeof b[compareSortKey.value] === 'string' ? 0 : b[compareSortKey.value];
        return vb - va;
    });
});

/* ═══════════════════════════════════════════════════
   TAB 7 — ЭКСПОРТ
   ═══════════════════════════════════════════════════ */
const exportFormats = ref([
    { key: 'csv', label: '📊 CSV', desc: 'Таблица метрик для Excel / Google Sheets' },
    { key: 'pdf', label: '📄 PDF', desc: 'Отформатированный отчёт с графиками' },
    { key: 'json', label: '🔧 JSON', desc: 'Структурированные данные для интеграции' },
]);
const exportSections = ref([
    { key: 'overview', label: 'Общая статистика', checked: true },
    { key: 'audience', label: 'Аудитория', checked: true },
    { key: 'content', label: 'Контент-аналитика', checked: true },
    { key: 'funnel', label: 'Воронка', checked: true },
    { key: 'sources', label: 'Источники трафика', checked: true },
    { key: 'compare', label: 'Сравнение страниц', checked: false },
]);
function exportReport(format) {
    const sections = exportSections.value.filter(s => s.checked).map(s => s.key);
    const data = { format, sections, period: activePeriod.value, generatedAt: new Date().toISOString() };
    if (format === 'csv') {
        const csv = ['Метрика;Значение;Изменение']
            .concat(kpiCards.value.map(k => `${k.label};${k.value};${k.change}`))
            .join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = `beauty_page_stats_${Date.now()}.csv`; a.click();
        URL.revokeObjectURL(url);
    } else if (format === 'json') {
        const json = JSON.stringify(data, null, 2);
        const blob = new Blob([json], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = `beauty_page_stats_${Date.now()}.json`; a.click();
        URL.revokeObjectURL(url);
    }
    emit('export-report', data);
    toast(`Отчёт экспортирован (${format.toUpperCase()})`);
}
</script>

<template>
<section class="p-6 min-h-screen transition-colors duration-300" :style="{ background: t.bg, color: t.text }">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-2">📈 Статистика страниц</h1>
            <p class="text-sm mt-1" :style="{ color: t.text2 }">Аналитика пабликов, воронки, аудитория и контент</p>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex gap-1 p-1 rounded-lg" :style="{ background: t.surface, border: `1px solid ${t.border}` }">
                <button v-for="p in periods" :key="p" class="px-3 py-1 rounded-md text-xs font-medium transition-all"
                    :style="{ background: activePeriod === p ? t.primary : 'transparent', color: activePeriod === p ? '#fff' : t.text2 }"
                    @click="activePeriod = p">{{ p }}</button>
            </div>
            <VButton v-for="th in Object.keys(themes)" :key="th" size="xs"
                :style="{ background: currentTheme === th ? t.primary : t.surface, color: currentTheme === th ? '#fff' : t.text, border: `1px solid ${t.border}` }"
                @click="currentTheme = th">{{ th }}</VButton>
        </div>
    </div>

    <!-- Tabs -->
    <VTabs :tabs="innerTabs" :active="activeInner" @update:active="activeInner = $event" class="mb-6" />

    <!-- ═══ TAB 1: ОБЗОР ═══ -->
    <div v-if="activeInner === 'overview'">
        <!-- KPI Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <VCard v-for="kpi in kpiCards" :key="kpi.label" :style="{ background: t.surface, border: `1px solid ${t.border}` }">
                <div class="p-4 text-center">
                    <div class="text-2xl mb-1">{{ kpi.icon }}</div>
                    <div class="text-2xl font-bold">{{ kpi.value }}</div>
                    <div class="text-xs" :style="{ color: t.text3 }">{{ kpi.label }}</div>
                    <div class="text-xs font-semibold mt-1" :style="{ color: kpi.good ? '#22c55e' : '#ef4444' }">{{ kpi.change }} {{ kpi.changePercent }}</div>
                </div>
            </VCard>
        </div>

        <!-- Daily Chart (table-based) -->
        <VCard class="mb-6" :style="{ background: t.surface, border: `1px solid ${t.border}` }">
            <template #header><h3 class="font-semibold">📅 Динамика по дням (последние 7 дней)</h3></template>
            <div class="p-4">
                <div class="space-y-2">
                    <div v-for="d in dailyMetrics" :key="d.date" class="flex items-center gap-3">
                        <span class="w-14 text-sm font-medium">{{ d.date }}</span>
                        <div class="flex-1 h-6 rounded-full overflow-hidden relative" :style="{ background: t.bg }">
                            <div class="h-full rounded-full transition-all" :style="{ width: (d.views / maxViews * 100) + '%', background: `linear-gradient(90deg, ${t.gradientFrom}, ${t.gradientTo})` }"></div>
                            <span class="absolute inset-0 flex items-center justify-center text-xs font-bold" :style="{ color: t.text }">{{ d.views.toLocaleString('ru-RU') }} просм.</span>
                        </div>
                        <div class="text-xs whitespace-nowrap" :style="{ color: t.text2 }">
                            +{{ d.followers }}👥 · {{ d.engagement }}%❤️ · {{ d.bookings }}📅 · {{ (d.revenue / 1000).toFixed(0) }}K₽
                        </div>
                    </div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ TAB 2: АУДИТОРИЯ ═══ -->
    <div v-if="activeInner === 'audience'">
        <div class="grid gap-6 md:grid-cols-2 mb-6">
            <!-- Gender -->
            <VCard :style="{ background: t.surface, border: `1px solid ${t.border}` }">
                <template #header><h3 class="font-semibold">👤 Пол аудитории</h3></template>
                <div class="p-4 space-y-3">
                    <div v-for="g in audienceGender" :key="g.label" class="flex items-center gap-3">
                        <span class="w-24 text-sm">{{ g.label }}</span>
                        <div class="flex-1 h-5 rounded-full overflow-hidden" :style="{ background: t.bg }">
                            <div class="h-full rounded-full" :style="{ width: g.value + '%', background: g.color }"></div>
                        </div>
                        <span class="text-sm font-bold w-10 text-right">{{ g.value }}%</span>
                    </div>
                </div>
            </VCard>

            <!-- Age -->
            <VCard :style="{ background: t.surface, border: `1px solid ${t.border}` }">
                <template #header><h3 class="font-semibold">🎂 Возраст</h3></template>
                <div class="p-4 space-y-2">
                    <div v-for="a in audienceAge" :key="a.range" class="flex items-center gap-3">
                        <span class="w-14 text-sm">{{ a.range }}</span>
                        <div class="flex-1 h-5 rounded-full overflow-hidden" :style="{ background: t.bg }">
                            <div class="h-full rounded-full" :style="{ width: (a.percent / maxAgePct * 100) + '%', background: t.primary }"></div>
                        </div>
                        <span class="text-sm font-bold w-10 text-right">{{ a.percent }}%</span>
                    </div>
                </div>
            </VCard>
        </div>

        <div class="grid gap-6 md:grid-cols-2 mb-6">
            <!-- Geography -->
            <VCard :style="{ background: t.surface, border: `1px solid ${t.border}` }">
                <template #header><h3 class="font-semibold">📍 География</h3></template>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr :style="{ borderBottom: `2px solid ${t.border}` }">
                                <th class="text-left py-2 px-3">Город</th>
                                <th class="text-right py-2 px-3">%</th>
                                <th class="text-right py-2 px-3">Подп.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="g in audienceGeo" :key="g.city" :style="{ borderBottom: `1px solid ${t.border}` }">
                                <td class="py-2 px-3">{{ g.city }}</td>
                                <td class="py-2 px-3 text-right font-semibold">{{ g.percent }}%</td>
                                <td class="py-2 px-3 text-right" :style="{ color: t.text2 }">{{ g.count.toLocaleString('ru-RU') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </VCard>

            <!-- Active Hours -->
            <VCard :style="{ background: t.surface, border: `1px solid ${t.border}` }">
                <template #header><h3 class="font-semibold">⏰ Активность по часам</h3></template>
                <div class="p-4 space-y-2">
                    <div v-for="h in audienceActiveHours" :key="h.hour" class="flex items-center gap-3">
                        <span class="w-12 text-xs font-mono">{{ h.hour }}</span>
                        <div class="flex-1 h-4 rounded-full overflow-hidden" :style="{ background: t.bg }">
                            <div class="h-full rounded-full" :style="{ width: (h.activity / maxActivity * 100) + '%', background: h.activity > 60 ? t.primary : h.activity > 30 ? t.accent : t.text3 }"></div>
                        </div>
                        <span class="text-xs w-8 text-right">{{ h.activity }}%</span>
                    </div>
                </div>
            </VCard>
        </div>

        <!-- Follower Growth -->
        <VCard :style="{ background: t.surface, border: `1px solid ${t.border}` }">
            <template #header><h3 class="font-semibold">📈 Динамика подписчиков</h3></template>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr :style="{ borderBottom: `2px solid ${t.border}` }">
                            <th class="text-left py-2 px-3">Неделя</th>
                            <th class="text-right py-2 px-3 text-green-600">Пришло</th>
                            <th class="text-right py-2 px-3 text-red-500">Ушло</th>
                            <th class="text-right py-2 px-3 font-bold">Нетто</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="fg in followerGrowth" :key="fg.week" :style="{ borderBottom: `1px solid ${t.border}` }">
                            <td class="py-2 px-3">{{ fg.week }}</td>
                            <td class="py-2 px-3 text-right text-green-600">+{{ fg.gained }}</td>
                            <td class="py-2 px-3 text-right text-red-500">-{{ fg.lost }}</td>
                            <td class="py-2 px-3 text-right font-bold" :style="{ color: t.primary }">+{{ fg.net }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>
    </div>

    <!-- ═══ TAB 3: КОНТЕНТ ═══ -->
    <div v-if="activeInner === 'content'">
        <!-- Top Posts -->
        <VCard class="mb-6" :style="{ background: t.surface, border: `1px solid ${t.border}` }">
            <template #header><h3 class="font-semibold">🏆 Топ постов по вовлечённости</h3></template>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr :style="{ borderBottom: `2px solid ${t.border}` }">
                            <th class="text-left py-2 px-3">Пост</th>
                            <th class="text-left py-2 px-3">Страница</th>
                            <th class="text-center py-2 px-3">Тип</th>
                            <th class="text-right py-2 px-3">👁️</th>
                            <th class="text-right py-2 px-3">❤️</th>
                            <th class="text-right py-2 px-3">💬</th>
                            <th class="text-right py-2 px-3">🔁</th>
                            <th class="text-right py-2 px-3">ER%</th>
                            <th class="text-right py-2 px-3">📅</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(p, idx) in topPosts" :key="p.id" :style="{ borderBottom: `1px solid ${t.border}` }" class="transition-colors cursor-pointer" @mouseenter="$event.target.style.background = t.cardHover" @mouseleave="$event.target.style.background = 'transparent'" @click="emit('open-post', p.id)">
                            <td class="py-2 px-3"><span class="font-bold mr-2" :style="{ color: idx < 3 ? t.primary : t.text3 }">{{ idx + 1 }}.</span>{{ p.title }}</td>
                            <td class="py-2 px-3" :style="{ color: t.text2 }">{{ p.page }}</td>
                            <td class="py-2 px-3 text-center"><VBadge>{{ p.type }}</VBadge></td>
                            <td class="py-2 px-3 text-right">{{ p.views.toLocaleString('ru-RU') }}</td>
                            <td class="py-2 px-3 text-right">{{ p.likes }}</td>
                            <td class="py-2 px-3 text-right">{{ p.comments }}</td>
                            <td class="py-2 px-3 text-right">{{ p.shares }}</td>
                            <td class="py-2 px-3 text-right font-bold" :style="{ color: p.er > 10 ? '#22c55e' : p.er > 7 ? t.primary : t.text2 }">{{ p.er }}%</td>
                            <td class="py-2 px-3 text-right">{{ p.bookings }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>

        <!-- Content by Type -->
        <VCard class="mb-6" :style="{ background: t.surface, border: `1px solid ${t.border}` }">
            <template #header><h3 class="font-semibold">📊 Эффективность по типу контента</h3></template>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr :style="{ borderBottom: `2px solid ${t.border}` }">
                            <th class="text-left py-2 px-3">Тип</th>
                            <th class="text-right py-2 px-3">Кол-во</th>
                            <th class="text-right py-2 px-3">Ср. просм.</th>
                            <th class="text-right py-2 px-3">Ср. ER</th>
                            <th class="text-right py-2 px-3">Ср. записей</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="ct in contentByType" :key="ct.type" :style="{ borderBottom: `1px solid ${t.border}` }">
                            <td class="py-2 px-3 font-medium">{{ ct.type }}</td>
                            <td class="py-2 px-3 text-right">{{ ct.count }}</td>
                            <td class="py-2 px-3 text-right">{{ ct.avgViews.toLocaleString('ru-RU') }}</td>
                            <td class="py-2 px-3 text-right font-semibold" :style="{ color: ct.avgER > 10 ? '#22c55e' : t.text }">{{ ct.avgER }}%</td>
                            <td class="py-2 px-3 text-right">{{ ct.avgBookings }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>

        <!-- Best Publish Times -->
        <VCard :style="{ background: t.surface, border: `1px solid ${t.border}` }">
            <template #header><h3 class="font-semibold">⏰ Лучшее время для публикации</h3></template>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 p-4">
                <div v-for="bp in bestPublishTimes" :key="bp.day" class="text-center p-3 rounded-xl" :style="{ background: t.bg, border: `1px solid ${t.border}` }">
                    <div class="font-semibold text-sm">{{ bp.day }}</div>
                    <div class="text-lg font-bold mt-1" :style="{ color: t.primary }">{{ bp.time }}</div>
                    <div class="text-xs mt-1" :style="{ color: t.text3 }">ER {{ bp.avgER }}%</div>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ TAB 4: ВОРОНКА ═══ -->
    <div v-if="activeInner === 'funnel'">
        <!-- Overall Funnel -->
        <VCard class="mb-6" :style="{ background: t.surface, border: `1px solid ${t.border}` }">
            <template #header>
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold">🔻 Воронка: просмотр → запись → оплата</h3>
                    <VBadge :variant="Number(funnelConversion) > 2 ? 'success' : 'warning'">Общая конверсия: {{ funnelConversion }}%</VBadge>
                </div>
            </template>
            <div class="p-4 space-y-3">
                <div v-for="(step, idx) in funnelSteps" :key="step.step" class="flex items-center gap-4">
                    <span class="text-sm w-60 shrink-0">{{ step.step }}</span>
                    <div class="flex-1 relative">
                        <div class="h-10 rounded-lg overflow-hidden" :style="{ background: t.bg }">
                            <div class="h-full rounded-lg flex items-center justify-center transition-all"
                                :style="{ width: (step.count / maxFunnelCount * 100) + '%', background: `linear-gradient(90deg, ${t.gradientFrom}, ${t.gradientTo})`, opacity: 1 - idx * 0.08 }">
                                <span class="text-xs font-bold text-white drop-shadow">{{ step.count.toLocaleString('ru-RU') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right w-20">
                        <div class="text-sm font-bold">{{ step.percent }}%</div>
                        <div v-if="step.drop" class="text-xs text-red-500">-{{ step.drop }}%</div>
                    </div>
                </div>
            </div>
        </VCard>

        <!-- Funnel by Page -->
        <VCard :style="{ background: t.surface, border: `1px solid ${t.border}` }">
            <template #header><h3 class="font-semibold">📄 Воронка по страницам</h3></template>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr :style="{ borderBottom: `2px solid ${t.border}` }">
                            <th class="text-left py-2 px-3">Страница</th>
                            <th class="text-right py-2 px-3">Просмотры</th>
                            <th class="text-right py-2 px-3">Записи</th>
                            <th class="text-right py-2 px-3">Конверсия</th>
                            <th class="text-right py-2 px-3">Выручка</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="fp in funnelByPage" :key="fp.page" :style="{ borderBottom: `1px solid ${t.border}` }">
                            <td class="py-2 px-3 font-medium">{{ fp.page }}</td>
                            <td class="py-2 px-3 text-right">{{ fp.views.toLocaleString('ru-RU') }}</td>
                            <td class="py-2 px-3 text-right font-semibold">{{ fp.bookings }}</td>
                            <td class="py-2 px-3 text-right" :style="{ color: fp.conversion > 0.35 ? '#22c55e' : t.text2 }">{{ (fp.conversion * 100).toFixed(1) }}%</td>
                            <td class="py-2 px-3 text-right font-bold">{{ fp.revenue.toLocaleString('ru-RU') }} ₽</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>
    </div>

    <!-- ═══ TAB 5: ИСТОЧНИКИ ═══ -->
    <div v-if="activeInner === 'sources'">
        <!-- Traffic Sources -->
        <VCard class="mb-6" :style="{ background: t.surface, border: `1px solid ${t.border}` }">
            <template #header><h3 class="font-semibold">🌐 Источники трафика</h3></template>
            <div class="p-4 space-y-3">
                <div v-for="s in trafficSources" :key="s.source" class="flex items-center gap-3">
                    <span class="w-48 text-sm shrink-0">{{ s.source }}</span>
                    <div class="flex-1 h-6 rounded-full overflow-hidden" :style="{ background: t.bg }">
                        <div class="h-full rounded-full" :style="{ width: (s.visits / maxSourceVisits * 100) + '%', background: s.color }"></div>
                    </div>
                    <div class="text-xs w-40 flex gap-2 justify-end">
                        <span>{{ s.visits.toLocaleString('ru-RU') }} визитов</span>
                        <span class="font-bold">{{ s.percent }}%</span>
                    </div>
                </div>
            </div>
        </VCard>

        <!-- UTM Campaigns -->
        <VCard :style="{ background: t.surface, border: `1px solid ${t.border}` }">
            <template #header><h3 class="font-semibold">🏷️ UTM-кампании</h3></template>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr :style="{ borderBottom: `2px solid ${t.border}` }">
                            <th class="text-left py-2 px-3">Кампания</th>
                            <th class="text-left py-2 px-3">Источник</th>
                            <th class="text-right py-2 px-3">Визиты</th>
                            <th class="text-right py-2 px-3">Записи</th>
                            <th class="text-right py-2 px-3">CPA ₽</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="utm in utmCampaigns" :key="utm.campaign" :style="{ borderBottom: `1px solid ${t.border}` }">
                            <td class="py-2 px-3 font-mono text-xs">{{ utm.campaign }}</td>
                            <td class="py-2 px-3"><VBadge>{{ utm.source }}</VBadge></td>
                            <td class="py-2 px-3 text-right">{{ utm.visits.toLocaleString('ru-RU') }}</td>
                            <td class="py-2 px-3 text-right font-semibold">{{ utm.bookings }}</td>
                            <td class="py-2 px-3 text-right" :style="{ color: utm.cpa === 0 ? '#22c55e' : utm.cpa > 300 ? '#ef4444' : t.text }">{{ utm.cpa === 0 ? 'Бесплатно' : utm.cpa + ' ₽' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>
    </div>

    <!-- ═══ TAB 6: СРАВНЕНИЕ ═══ -->
    <div v-if="activeInner === 'compare'">
        <div class="flex gap-2 mb-4 flex-wrap">
            <span class="text-sm self-center" :style="{ color: t.text2 }">Сортировка:</span>
            <VButton v-for="sk in [{k:'revenue30d',l:'💰 Выручка'},{k:'followers',l:'👥 Подписчики'},{k:'views30d',l:'👁️ Просмотры'},{k:'er',l:'❤️ ER'},{k:'bookings30d',l:'📅 Записи'}]" :key="sk.k" size="sm"
                :style="{ background: compareSortKey === sk.k ? t.primary : t.surface, color: compareSortKey === sk.k ? '#fff' : t.text, border: `1px solid ${t.border}` }"
                @click="compareSortKey = sk.k">{{ sk.l }}</VButton>
        </div>

        <VCard :style="{ background: t.surface, border: `1px solid ${t.border}` }">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr :style="{ borderBottom: `2px solid ${t.border}` }">
                            <th class="text-left py-2 px-3">#</th>
                            <th class="text-left py-2 px-3">Страница</th>
                            <th class="text-right py-2 px-3">Подписч.</th>
                            <th class="text-right py-2 px-3">Просмотры</th>
                            <th class="text-right py-2 px-3">Постов</th>
                            <th class="text-right py-2 px-3">ER%</th>
                            <th class="text-right py-2 px-3">Записи</th>
                            <th class="text-right py-2 px-3">Выручка</th>
                            <th class="text-right py-2 px-3">Рост</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(cp, idx) in sortedCompare" :key="cp.name" :style="{ borderBottom: `1px solid ${t.border}` }" class="transition-colors" @mouseenter="$event.target.style.background = t.cardHover" @mouseleave="$event.target.style.background = 'transparent'">
                            <td class="py-2 px-3 font-bold" :style="{ color: idx < 3 ? t.primary : t.text3 }">{{ idx + 1 }}</td>
                            <td class="py-2 px-3 font-medium">{{ cp.name }}</td>
                            <td class="py-2 px-3 text-right">{{ cp.followers.toLocaleString('ru-RU') }}</td>
                            <td class="py-2 px-3 text-right">{{ cp.views30d.toLocaleString('ru-RU') }}</td>
                            <td class="py-2 px-3 text-right">{{ cp.posts30d }}</td>
                            <td class="py-2 px-3 text-right font-semibold" :style="{ color: cp.er > 10 ? '#22c55e' : t.text }">{{ cp.er }}%</td>
                            <td class="py-2 px-3 text-right">{{ cp.bookings30d }}</td>
                            <td class="py-2 px-3 text-right font-bold">{{ cp.revenue30d.toLocaleString('ru-RU') }} ₽</td>
                            <td class="py-2 px-3 text-right" :style="{ color: '#22c55e' }">{{ cp.growth }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>
    </div>

    <!-- ═══ TAB 7: ЭКСПОРТ ═══ -->
    <div v-if="activeInner === 'export'">
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Format Selection -->
            <VCard :style="{ background: t.surface, border: `1px solid ${t.border}` }">
                <template #header><h3 class="font-semibold">📥 Формат экспорта</h3></template>
                <div class="p-4 space-y-3">
                    <div v-for="ef in exportFormats" :key="ef.key" class="p-4 rounded-xl cursor-pointer transition-all" :style="{ background: t.bg, border: `1px solid ${t.border}` }" @click="exportReport(ef.key)">
                        <h4 class="font-semibold">{{ ef.label }}</h4>
                        <p class="text-xs mt-1" :style="{ color: t.text3 }">{{ ef.desc }}</p>
                    </div>
                </div>
            </VCard>

            <!-- Section Selection -->
            <VCard :style="{ background: t.surface, border: `1px solid ${t.border}` }">
                <template #header><h3 class="font-semibold">📋 Разделы отчёта</h3></template>
                <div class="p-4 space-y-3">
                    <label v-for="es in exportSections" :key="es.key" class="flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors" :style="{ background: es.checked ? t.glow : t.bg }">
                        <input type="checkbox" v-model="es.checked" class="w-4 h-4" />
                        <span class="text-sm">{{ es.label }}</span>
                    </label>
                </div>
                <div class="px-4 pb-4">
                    <p class="text-xs" :style="{ color: t.text3 }">Период: {{ activePeriod }} · Выбрано {{ exportSections.filter(s => s.checked).length }} из {{ exportSections.length }} разделов</p>
                </div>
            </VCard>
        </div>
    </div>

    <!-- Toast -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="showToast" class="fixed bottom-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-medium" :style="{ background: t.primary, color: '#fff' }">{{ toastMessage }}</div>
        </Transition>
    </Teleport>
</section>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
