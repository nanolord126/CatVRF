<script setup>
/**
 * BeautyReports — модуль отчётов и аналитики для B2B панели салона.
 * Сводные отчёты, KPI, сравнения периодов, экспорт PDF/Excel.
 */
import { ref, computed, reactive } from 'vue';
import VCard from '../../UI/VCard.vue';
import VButton from '../../UI/VButton.vue';
import VBadge from '../../UI/VBadge.vue';
import VModal from '../../UI/VModal.vue';
import VStatCard from '../../UI/VStatCard.vue';

const props = defineProps({
    salons: { type: Array, default: () => [] },
    masters: { type: Array, default: () => [] },
});
const emit = defineEmits(['export-report', 'open-salon', 'open-master']);

function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }
const fmtP = (n) => (n > 0 ? '+' : '') + n.toFixed(1) + '%';

/* ─── Report types ─── */
const reportTypes = [
    { key: 'summary',     label: '📊 Сводный' },
    { key: 'masters',     label: '👩‍🎨 По мастерам' },
    { key: 'services',    label: '💇‍♀️ По услугам' },
    { key: 'clients',     label: '👥 По клиентам' },
    { key: 'salons',      label: '🏪 По салонам' },
    { key: 'marketing',   label: '📢 Маркетинг' },
    { key: 'comparison',  label: '⚖️ Сравнение' },
];
const activeReport = ref('summary');

const period = ref('month');
const periods = [
    { key: 'week', label: 'Неделя' },
    { key: 'month', label: 'Месяц' },
    { key: 'quarter', label: 'Квартал' },
    { key: 'year', label: 'Год' },
];
const compareTo = ref('prev_period');

/* ─── Summary KPI ─── */
const kpis = computed(() => [
    { title: 'Выручка', value: '2 847 500 ₽', change: +12.4, icon: '💰', target: '3 000 000 ₽', progress: 94.9 },
    { title: 'Средний чек', value: '4 750 ₽', change: +3.2, icon: '🧾', target: '5 000 ₽', progress: 95.0 },
    { title: 'Конверсия записей', value: '78.3%', change: +5.1, icon: '🎯', target: '80%', progress: 97.9 },
    { title: 'Повторные визиты', value: '64.2%', change: +2.8, icon: '🔁', target: '70%', progress: 91.7 },
    { title: 'NPS', value: '72', change: +4, icon: '⭐', target: '75', progress: 96.0 },
    { title: 'Загруженность', value: '81.5%', change: +6.3, icon: '📈', target: '85%', progress: 95.9 },
    { title: 'Новые клиенты', value: '87', change: +15.1, icon: '🆕', target: '100', progress: 87.0 },
    { title: 'Churn Rate', value: '8.2%', change: -1.3, icon: '💔', target: '< 10%', progress: 100 },
]);

/* ─── Master Performance ─── */
const masterPerformance = ref([
    { name: 'Анна Иванова', role: 'Стилист-колорист', revenue: 458_000, clients: 89, avgCheck: 5_146, rating: 4.9, utilization: 92, returnRate: 78, nps: 82 },
    { name: 'Мария Смирнова', role: 'Косметолог', revenue: 378_000, clients: 63, avgCheck: 6_000, rating: 4.9, utilization: 88, returnRate: 82, nps: 85 },
    { name: 'Ольга Дмитриева', role: 'Мастер маникюра', revenue: 312_000, clients: 156, avgCheck: 2_000, rating: 4.8, utilization: 95, returnRate: 71, nps: 76 },
    { name: 'Татьяна Волкова', role: 'Парикмахер', revenue: 245_000, clients: 98, avgCheck: 2_500, rating: 4.6, utilization: 78, returnRate: 65, nps: 68 },
    { name: 'Елена Козлова', role: 'Бровист', revenue: 189_500, clients: 95, avgCheck: 1_995, rating: 4.7, utilization: 85, returnRate: 74, nps: 74 },
    { name: 'Наталья Белова', role: 'SPA-терапевт', revenue: 156_000, clients: 26, avgCheck: 6_000, rating: 4.8, utilization: 72, returnRate: 88, nps: 80 },
]);

/* ─── Service Performance ─── */
const servicePerformance = ref([
    { name: 'Окрашивание AirTouch', revenue: 525_000, count: 50, avgCheck: 10_500, margin: 62, growth: +18 },
    { name: 'Балаяж / мелирование', revenue: 367_000, count: 39, avgCheck: 9_410, margin: 58, growth: +12 },
    { name: 'Стрижка женская', revenue: 340_000, count: 136, avgCheck: 2_500, margin: 70, growth: +5 },
    { name: 'Маникюр гель-лак', revenue: 312_000, count: 156, avgCheck: 2_000, margin: 75, growth: +8 },
    { name: 'Уход за лицом', revenue: 288_000, count: 48, avgCheck: 6_000, margin: 55, growth: +22 },
    { name: 'Педикюр', revenue: 245_000, count: 98, avgCheck: 2_500, margin: 72, growth: +3 },
    { name: 'Оформление бровей', revenue: 189_500, count: 95, avgCheck: 1_995, margin: 80, growth: +10 },
    { name: 'SPA-программы', revenue: 156_000, count: 26, avgCheck: 6_000, margin: 50, growth: +30 },
]);

/* ─── Client metrics ─── */
const clientMetrics = computed(() => ({
    total: 1_247,
    active: 892,
    new: 87,
    returning: 645,
    vip: 124,
    sleeping: 168,
    lost: 187,
    avgLifetime: '14.2 мес.',
    avgLTV: '38 500 ₽',
    satisfactionScore: 4.7,
}));

/* ─── Salon comparison ─── */
const salonComparison = ref([
    { name: 'Beauty Lab — Тверская', revenue: 1_281_375, clients: 487, masters: 8, avgCheck: 5_200, utilization: 87, nps: 78 },
    { name: 'Beauty Lab — Арбат', revenue: 854_250, clients: 312, masters: 6, avgCheck: 4_800, utilization: 82, nps: 72 },
    { name: 'Beauty Lab — Патрики', revenue: 711_875, clients: 248, masters: 5, avgCheck: 4_300, utilization: 76, nps: 69 },
]);

/* ─── Marketing metrics ─── */
const marketingMetrics = ref([
    { channel: 'Маркетплейс CatVRF', spend: 0, revenue: 1_708_500, roi: null, leads: 312, conversion: 82 },
    { channel: 'Таргет VK', spend: 45_000, revenue: 180_000, roi: 300, leads: 67, conversion: 42 },
    { channel: 'Instagram Reels', spend: 25_000, revenue: 125_000, roi: 400, leads: 48, conversion: 38 },
    { channel: 'Яндекс Директ', spend: 60_000, revenue: 210_000, roi: 250, leads: 89, conversion: 35 },
    { channel: 'Блогеры', spend: 80_000, revenue: 240_000, roi: 200, leads: 56, conversion: 52 },
    { channel: 'Рассылки WhatsApp', spend: 5_000, revenue: 95_000, roi: 1800, leads: 120, conversion: 68 },
    { channel: 'Реферальная программа', spend: 15_000, revenue: 145_000, roi: 867, leads: 45, conversion: 75 },
]);

/* ─── Period comparison ─── */
const comparisonData = computed(() => ({
    current: { label: 'Текущий период', revenue: 2_847_500, clients: 1_247, avgCheck: 4_750, services: 599, newClients: 87 },
    previous: { label: 'Предыдущий период', revenue: 2_533_000, clients: 1_098, avgCheck: 4_603, services: 521, newClients: 75 },
}));

/* ─── Export ─── */
function exportReport(format) {
    const reportName = reportTypes.find(r => r.key === activeReport.value)?.label || 'Отчёт';
    if (format === 'csv') {
        let csv = `Отчёт: ${reportName}\nПериод: ${period.value}\n\n`;
        if (activeReport.value === 'summary') {
            csv += 'KPI;Значение;Изменение;Цель;Прогресс\n';
            kpis.value.forEach(k => { csv += `${k.title};${k.value};${fmtP(k.change)};${k.target};${k.progress}%\n`; });
        } else if (activeReport.value === 'masters') {
            csv += 'Мастер;Роль;Выручка;Клиентов;Ср. чек;Рейтинг;Загрузка;Возврат;NPS\n';
            masterPerformance.value.forEach(m => {
                csv += `${m.name};${m.role};${m.revenue};${m.clients};${m.avgCheck};${m.rating};${m.utilization}%;${m.returnRate}%;${m.nps}\n`;
            });
        } else if (activeReport.value === 'services') {
            csv += 'Услуга;Выручка;Кол-во;Ср. чек;Маржа;Рост\n';
            servicePerformance.value.forEach(s => {
                csv += `${s.name};${s.revenue};${s.count};${s.avgCheck};${s.margin}%;${fmtP(s.growth)}\n`;
            });
        } else if (activeReport.value === 'marketing') {
            csv += 'Канал;Расход;Выручка;ROI;Лидов;Конверсия\n';
            marketingMetrics.value.forEach(m => {
                csv += `${m.channel};${m.spend};${m.revenue};${m.roi ?? 'N/A'};${m.leads};${m.conversion}%\n`;
            });
        }
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
        downloadBlob(blob, `report-${activeReport.value}-${period.value}.csv`);
    } else if (format === 'pdf') {
        let text = `═══ ${reportName} ═══\nПериод: ${period.value}\nДата: ${new Date().toLocaleDateString('ru-RU')}\n\n`;
        if (activeReport.value === 'summary') {
            kpis.value.forEach(k => { text += `${k.icon} ${k.title}: ${k.value} (${fmtP(k.change)}) цель: ${k.target}\n`; });
        }
        const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
        downloadBlob(blob, `report-${activeReport.value}-${period.value}.txt`);
    }
    emit('export-report', { type: activeReport.value, period: period.value, format });
}

function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

/* ─── Scheduled reports ─── */
const scheduledReports = ref([
    { id: 1, name: 'Еженедельный сводный', frequency: 'weekly', nextDate: '14.04.2026', email: 'owner@salon.ru', active: true },
    { id: 2, name: 'Ежемесячный по мастерам', frequency: 'monthly', nextDate: '01.05.2026', email: 'owner@salon.ru', active: true },
    { id: 3, name: 'Квартальный маркетинг', frequency: 'quarterly', nextDate: '01.07.2026', email: 'marketing@salon.ru', active: false },
]);
const showScheduleModal = ref(false);
const newSchedule = reactive({ name: '', frequency: 'weekly', email: '', type: 'summary' });

function addScheduledReport() {
    if (!newSchedule.name || !newSchedule.email) return;
    scheduledReports.value.push({
        id: Date.now(),
        ...newSchedule,
        nextDate: '—',
        active: true,
    });
    Object.assign(newSchedule, { name: '', frequency: 'weekly', email: '', type: 'summary' });
    showScheduleModal.value = false;
}

function toggleScheduleActive(report) {
    report.active = !report.active;
}

function deleteScheduledReport(id) {
    scheduledReports.value = scheduledReports.value.filter(r => r.id !== id);
}
</script>

<template>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold" style="color:var(--t-text)">📊 Отчёты</h2>
            <p class="text-sm mt-1" style="color:var(--t-text-2)">Аналитика, KPI и сравнения</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <div class="flex rounded-lg border overflow-hidden" style="border-color:var(--t-border)">
                <button v-for="p in periods" :key="p.key" @click="period = p.key"
                        class="px-3 py-1.5 text-xs font-medium transition-all"
                        :style="period === p.key ? 'background:var(--t-primary);color:#fff' : 'background:var(--t-surface);color:var(--t-text-2)'">
                    {{ p.label }}
                </button>
            </div>
            <VButton size="sm" variant="outline" @click="exportReport('csv')">📥 CSV</VButton>
            <VButton size="sm" variant="outline" @click="exportReport('pdf')">📄 PDF</VButton>
            <VButton size="sm" variant="outline" @click="showScheduleModal = true">⏰ Расписание</VButton>
        </div>
    </div>

    <!-- Report type tabs -->
    <div class="flex gap-1 overflow-x-auto">
        <button v-for="rt in reportTypes" :key="rt.key" @click="activeReport = rt.key"
                class="px-4 py-2 rounded-xl text-sm font-medium whitespace-nowrap transition-all"
                :style="activeReport === rt.key ? 'background:var(--t-primary);color:#fff' : 'background:var(--t-surface);color:var(--t-text-2)'">
            {{ rt.label }}
        </button>
    </div>

    <!-- Summary Report -->
    <template v-if="activeReport === 'summary'">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <VCard v-for="kpi in kpis" :key="kpi.title" class="p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xl">{{ kpi.icon }}</span>
                    <span class="text-xs font-medium" :style="`color:${kpi.change > 0 ? '#22c55e' : kpi.change < 0 ? '#ef4444' : 'var(--t-text-3)'}`">
                        {{ fmtP(kpi.change) }}
                    </span>
                </div>
                <div class="text-lg font-bold" style="color:var(--t-text)">{{ kpi.value }}</div>
                <div class="text-xs mt-1" style="color:var(--t-text-3)">{{ kpi.title }}</div>
                <div class="mt-2 rounded-full h-1.5 overflow-hidden" style="background:var(--t-bg)">
                    <div class="h-full rounded-full transition-all duration-700" :style="`background:var(--t-primary);width:${Math.min(100, kpi.progress)}%`"></div>
                </div>
                <div class="flex justify-between text-[10px] mt-1" style="color:var(--t-text-3)">
                    <span>{{ kpi.progress.toFixed(0) }}%</span>
                    <span>Цель: {{ kpi.target }}</span>
                </div>
            </VCard>
        </div>
    </template>

    <!-- Masters Report -->
    <template v-if="activeReport === 'masters'">
        <VCard class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" style="border-color:var(--t-border)">
                        <th class="py-3 px-3 text-left" style="color:var(--t-text-3)">Мастер</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Выручка</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Клиентов</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Ср. чек</th>
                        <th class="py-3 px-3 text-center" style="color:var(--t-text-3)">⭐</th>
                        <th class="py-3 px-3 text-center" style="color:var(--t-text-3)">Загрузка</th>
                        <th class="py-3 px-3 text-center" style="color:var(--t-text-3)">Возврат</th>
                        <th class="py-3 px-3 text-center" style="color:var(--t-text-3)">NPS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="mp in masterPerformance" :key="mp.name" class="border-b" style="border-color:var(--t-border)">
                        <td class="py-3 px-3">
                            <div class="font-medium" style="color:var(--t-text)">{{ mp.name }}</div>
                            <div class="text-xs" style="color:var(--t-text-3)">{{ mp.role }}</div>
                        </td>
                        <td class="py-3 px-3 text-right font-bold" style="color:var(--t-primary)">{{ fmt(mp.revenue) }} ₽</td>
                        <td class="py-3 px-3 text-right" style="color:var(--t-text)">{{ mp.clients }}</td>
                        <td class="py-3 px-3 text-right" style="color:var(--t-text)">{{ fmt(mp.avgCheck) }} ₽</td>
                        <td class="py-3 px-3 text-center" style="color:var(--t-text)">{{ mp.rating }}</td>
                        <td class="py-3 px-3 text-center"><VBadge :color="mp.utilization >= 85 ? 'green' : mp.utilization >= 70 ? 'yellow' : 'red'" size="sm">{{ mp.utilization }}%</VBadge></td>
                        <td class="py-3 px-3 text-center"><VBadge :color="mp.returnRate >= 70 ? 'green' : 'yellow'" size="sm">{{ mp.returnRate }}%</VBadge></td>
                        <td class="py-3 px-3 text-center"><VBadge :color="mp.nps >= 75 ? 'green' : mp.nps >= 60 ? 'yellow' : 'red'" size="sm">{{ mp.nps }}</VBadge></td>
                    </tr>
                </tbody>
            </table>
        </VCard>
    </template>

    <!-- Services Report -->
    <template v-if="activeReport === 'services'">
        <VCard class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" style="border-color:var(--t-border)">
                        <th class="py-3 px-3 text-left" style="color:var(--t-text-3)">Услуга</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Выручка</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Кол-во</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Ср. чек</th>
                        <th class="py-3 px-3 text-center" style="color:var(--t-text-3)">Маржа</th>
                        <th class="py-3 px-3 text-center" style="color:var(--t-text-3)">Рост</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="sp in servicePerformance" :key="sp.name" class="border-b" style="border-color:var(--t-border)">
                        <td class="py-3 px-3 font-medium" style="color:var(--t-text)">{{ sp.name }}</td>
                        <td class="py-3 px-3 text-right font-bold" style="color:var(--t-primary)">{{ fmt(sp.revenue) }} ₽</td>
                        <td class="py-3 px-3 text-right" style="color:var(--t-text)">{{ sp.count }}</td>
                        <td class="py-3 px-3 text-right" style="color:var(--t-text)">{{ fmt(sp.avgCheck) }} ₽</td>
                        <td class="py-3 px-3 text-center"><VBadge :color="sp.margin >= 60 ? 'green' : 'yellow'" size="sm">{{ sp.margin }}%</VBadge></td>
                        <td class="py-3 px-3 text-center">
                            <span class="text-sm font-medium" :style="`color:${sp.growth >= 0 ? '#22c55e' : '#ef4444'}`">{{ sp.growth > 0 ? '↗' : '↘' }} {{ fmtP(sp.growth) }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </VCard>
    </template>

    <!-- Clients Report -->
    <template v-if="activeReport === 'clients'">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <VCard class="p-4 text-center">
                <div class="text-2xl mb-1">👥</div>
                <div class="text-xl font-bold" style="color:var(--t-text)">{{ fmt(clientMetrics.total) }}</div>
                <div class="text-xs" style="color:var(--t-text-3)">Всего клиентов</div>
            </VCard>
            <VCard class="p-4 text-center">
                <div class="text-2xl mb-1">✅</div>
                <div class="text-xl font-bold" style="color:#22c55e">{{ fmt(clientMetrics.active) }}</div>
                <div class="text-xs" style="color:var(--t-text-3)">Активных</div>
            </VCard>
            <VCard class="p-4 text-center">
                <div class="text-2xl mb-1">🆕</div>
                <div class="text-xl font-bold" style="color:var(--t-primary)">{{ clientMetrics.new }}</div>
                <div class="text-xs" style="color:var(--t-text-3)">Новых за период</div>
            </VCard>
            <VCard class="p-4 text-center">
                <div class="text-2xl mb-1">👑</div>
                <div class="text-xl font-bold" style="color:#a855f7">{{ clientMetrics.vip }}</div>
                <div class="text-xs" style="color:var(--t-text-3)">VIP</div>
            </VCard>
            <VCard class="p-4 text-center">
                <div class="text-2xl mb-1">🔁</div>
                <div class="text-xl font-bold" style="color:var(--t-text)">{{ fmt(clientMetrics.returning) }}</div>
                <div class="text-xs" style="color:var(--t-text-3)">Повторных</div>
            </VCard>
            <VCard class="p-4 text-center">
                <div class="text-2xl mb-1">😴</div>
                <div class="text-xl font-bold" style="color:#eab308">{{ clientMetrics.sleeping }}</div>
                <div class="text-xs" style="color:var(--t-text-3)">Спящих</div>
            </VCard>
            <VCard class="p-4 text-center">
                <div class="text-2xl mb-1">⏱</div>
                <div class="text-xl font-bold" style="color:var(--t-text)">{{ clientMetrics.avgLifetime }}</div>
                <div class="text-xs" style="color:var(--t-text-3)">Ср. срок жизни</div>
            </VCard>
            <VCard class="p-4 text-center">
                <div class="text-2xl mb-1">💎</div>
                <div class="text-xl font-bold" style="color:var(--t-primary)">{{ clientMetrics.avgLTV }}</div>
                <div class="text-xs" style="color:var(--t-text-3)">Ср. LTV</div>
            </VCard>
        </div>
    </template>

    <!-- Salons Comparison -->
    <template v-if="activeReport === 'salons'">
        <VCard class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" style="border-color:var(--t-border)">
                        <th class="py-3 px-3 text-left" style="color:var(--t-text-3)">Салон</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Выручка</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Клиентов</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Мастеров</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Ср. чек</th>
                        <th class="py-3 px-3 text-center" style="color:var(--t-text-3)">Загрузка</th>
                        <th class="py-3 px-3 text-center" style="color:var(--t-text-3)">NPS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="sl in salonComparison" :key="sl.name" class="border-b cursor-pointer transition-all" style="border-color:var(--t-border)"
                        @mouseenter="$event.target.style.background = 'var(--t-bg)'" @mouseleave="$event.target.style.background = ''">
                        <td class="py-3 px-3 font-medium" style="color:var(--t-text)">🏪 {{ sl.name }}</td>
                        <td class="py-3 px-3 text-right font-bold" style="color:var(--t-primary)">{{ fmt(sl.revenue) }} ₽</td>
                        <td class="py-3 px-3 text-right" style="color:var(--t-text)">{{ sl.clients }}</td>
                        <td class="py-3 px-3 text-right" style="color:var(--t-text)">{{ sl.masters }}</td>
                        <td class="py-3 px-3 text-right" style="color:var(--t-text)">{{ fmt(sl.avgCheck) }} ₽</td>
                        <td class="py-3 px-3 text-center"><VBadge :color="sl.utilization >= 85 ? 'green' : 'yellow'" size="sm">{{ sl.utilization }}%</VBadge></td>
                        <td class="py-3 px-3 text-center"><VBadge :color="sl.nps >= 75 ? 'green' : 'yellow'" size="sm">{{ sl.nps }}</VBadge></td>
                    </tr>
                </tbody>
            </table>
        </VCard>
    </template>

    <!-- Marketing Report -->
    <template v-if="activeReport === 'marketing'">
        <VCard class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" style="border-color:var(--t-border)">
                        <th class="py-3 px-3 text-left" style="color:var(--t-text-3)">Канал</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Расход</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Выручка</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">ROI</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Лидов</th>
                        <th class="py-3 px-3 text-center" style="color:var(--t-text-3)">Конверсия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="mm in marketingMetrics" :key="mm.channel" class="border-b" style="border-color:var(--t-border)">
                        <td class="py-3 px-3 font-medium" style="color:var(--t-text)">{{ mm.channel }}</td>
                        <td class="py-3 px-3 text-right" style="color:var(--t-text-2)">{{ mm.spend ? fmt(mm.spend) + ' ₽' : '—' }}</td>
                        <td class="py-3 px-3 text-right font-bold" style="color:var(--t-primary)">{{ fmt(mm.revenue) }} ₽</td>
                        <td class="py-3 px-3 text-right">
                            <VBadge v-if="mm.roi" :color="mm.roi >= 300 ? 'green' : mm.roi >= 100 ? 'yellow' : 'red'" size="sm">{{ mm.roi }}%</VBadge>
                            <span v-else class="text-xs" style="color:var(--t-text-3)">∞</span>
                        </td>
                        <td class="py-3 px-3 text-right" style="color:var(--t-text)">{{ mm.leads }}</td>
                        <td class="py-3 px-3 text-center"><VBadge :color="mm.conversion >= 50 ? 'green' : mm.conversion >= 30 ? 'yellow' : 'red'" size="sm">{{ mm.conversion }}%</VBadge></td>
                    </tr>
                </tbody>
            </table>
        </VCard>
    </template>

    <!-- Comparison Report -->
    <template v-if="activeReport === 'comparison'">
        <VCard class="p-6">
            <h3 class="font-bold mb-4" style="color:var(--t-text)">⚖️ Сравнение периодов</h3>
            <div class="grid md:grid-cols-5 gap-4">
                <div v-for="metric in ['revenue', 'clients', 'avgCheck', 'services', 'newClients']" :key="metric" class="p-4 rounded-xl text-center" style="background:var(--t-bg)">
                    <div class="text-xs font-medium mb-2" style="color:var(--t-text-3)">{{ { revenue: 'Выручка', clients: 'Клиенты', avgCheck: 'Ср. чек', services: 'Услуги', newClients: 'Новые' }[metric] }}</div>
                    <div class="font-bold text-lg" style="color:var(--t-primary)">{{ typeof comparisonData.current[metric] === 'number' ? fmt(comparisonData.current[metric]) : comparisonData.current[metric] }}</div>
                    <div class="text-xs mt-1" style="color:var(--t-text-3)">было: {{ typeof comparisonData.previous[metric] === 'number' ? fmt(comparisonData.previous[metric]) : comparisonData.previous[metric] }}</div>
                    <div class="text-sm font-medium mt-1" :style="`color:${comparisonData.current[metric] >= comparisonData.previous[metric] ? '#22c55e' : '#ef4444'}`">
                        {{ comparisonData.current[metric] >= comparisonData.previous[metric] ? '↗' : '↘' }}
                        {{ fmtP(((comparisonData.current[metric] - comparisonData.previous[metric]) / comparisonData.previous[metric]) * 100) }}
                    </div>
                </div>
            </div>
        </VCard>
    </template>

    <!-- Scheduled Reports -->
    <VCard class="p-6 mt-4">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold" style="color:var(--t-text)">⏰ Запланированные отчёты</h3>
            <VButton size="sm" @click="showScheduleModal = true">➕ Добавить</VButton>
        </div>
        <div class="space-y-2">
            <div v-for="sr in scheduledReports" :key="sr.id" class="flex items-center justify-between p-3 rounded-lg" style="background:var(--t-bg)">
                <div>
                    <div class="font-medium text-sm" style="color:var(--t-text)">{{ sr.name }}</div>
                    <div class="text-xs" style="color:var(--t-text-3)">{{ sr.frequency === 'weekly' ? 'Еженедельно' : sr.frequency === 'monthly' ? 'Ежемесячно' : 'Ежеквартально' }} · {{ sr.email }} · След.: {{ sr.nextDate }}</div>
                </div>
                <div class="flex items-center gap-2">
                    <VBadge :color="sr.active ? 'green' : 'gray'" size="sm" class="cursor-pointer" @click="toggleScheduleActive(sr)">{{ sr.active ? '✅ Активен' : '⏸️ Пауза' }}</VBadge>
                    <button @click="deleteScheduledReport(sr.id)" class="text-red-400 hover:text-red-500 text-sm">🗑️</button>
                </div>
            </div>
        </div>
    </VCard>
</div>

<!-- Schedule Modal -->
<VModal :show="showScheduleModal" @close="showScheduleModal = false" title="⏰ Новый запланированный отчёт">
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Название</label>
            <input v-model="newSchedule.name" type="text" class="w-full rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)" placeholder="Название отчёта" />
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Тип отчёта</label>
                <select v-model="newSchedule.type" class="w-full rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)">
                    <option v-for="rt in reportTypes" :key="rt.key" :value="rt.key">{{ rt.label }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Частота</label>
                <select v-model="newSchedule.frequency" class="w-full rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)">
                    <option value="weekly">Еженедельно</option>
                    <option value="monthly">Ежемесячно</option>
                    <option value="quarterly">Ежеквартально</option>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Email</label>
            <input v-model="newSchedule.email" type="email" class="w-full rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)" placeholder="email@example.com" />
        </div>
        <div class="flex justify-end gap-3">
            <VButton variant="outline" @click="showScheduleModal = false">Отмена</VButton>
            <VButton @click="addScheduledReport" :disabled="!newSchedule.name || !newSchedule.email">➕ Создать</VButton>
        </div>
    </div>
</VModal>
</template>
