<script setup>
/**
 * BeautyFinances — финансовый модуль для B2B панели салона красоты.
 * Выручка, комиссии, выплаты мастерам, расходы, прогнозы.
 */
import { ref, computed, reactive } from 'vue';
import VCard from '../../UI/VCard.vue';
import VButton from '../../UI/VButton.vue';
import VBadge from '../../UI/VBadge.vue';
import VModal from '../../UI/VModal.vue';
import VInput from '../../UI/VInput.vue';
import VStatCard from '../../UI/VStatCard.vue';

const props = defineProps({
    masters: { type: Array, default: () => [] },
    salons: { type: Array, default: () => [] },
});
const emit = defineEmits(['payout', 'export-report']);

function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }
const fmtP = (n) => (n > 0 ? '+' : '') + n.toFixed(1) + '%';

/* ─── Tabs ─── */
const tabs = [
    { key: 'overview',  label: '📊 Обзор' },
    { key: 'revenue',   label: '💰 Выручка' },
    { key: 'payouts',   label: '👩‍🎨 Выплаты мастерам' },
    { key: 'expenses',  label: '📉 Расходы' },
    { key: 'taxes',     label: '🧾 Налоги и ОФД' },
    { key: 'forecast',  label: '🔮 Прогнозы' },
];
const activeTab = ref('overview');

/* ─── Period filter ─── */
const period = ref('month');
const periods = [
    { key: 'today', label: 'Сегодня' },
    { key: 'week',  label: 'Неделя' },
    { key: 'month', label: 'Месяц' },
    { key: 'quarter', label: 'Квартал' },
    { key: 'year',  label: 'Год' },
    { key: 'custom', label: 'Период' },
];
const customRange = reactive({ from: '', to: '' });

/* ─── Overview Stats ─── */
const overviewStats = computed(() => [
    { title: 'Выручка', value: fmt(2_847_500), trend: +12.4, icon: '💰', color: 'green' },
    { title: 'Чистая прибыль', value: fmt(1_138_000), trend: +8.7, icon: '📈', color: 'blue' },
    { title: 'Средний чек', value: fmt(4_750), trend: +3.2, icon: '🧾', color: 'purple' },
    { title: 'Кол-во услуг', value: fmt(599), trend: +15.1, icon: '💇‍♀️', color: 'yellow' },
    { title: 'Возвраты', value: fmt(23_400), trend: -2.1, icon: '↩️', color: 'red' },
    { title: 'Комиссия платформы', value: fmt(398_650), trend: 0, icon: '🏷️', color: 'gray' },
]);

/* ─── Revenue Breakdown ─── */
const revenueByService = ref([
    { name: 'Окрашивание', revenue: 892_000, count: 89, avg: 10_022, share: 31.3 },
    { name: 'Стрижка', revenue: 485_000, count: 194, avg: 2_500, share: 17.0 },
    { name: 'Маникюр', revenue: 412_000, count: 206, avg: 2_000, share: 14.5 },
    { name: 'Уход за лицом', revenue: 378_000, count: 63, avg: 6_000, share: 13.3 },
    { name: 'Педикюр', revenue: 245_000, count: 98, avg: 2_500, share: 8.6 },
    { name: 'Бровист', revenue: 189_500, count: 95, avg: 1_995, share: 6.7 },
    { name: 'SPA-процедуры', revenue: 156_000, count: 26, avg: 6_000, share: 5.5 },
    { name: 'Прочее', revenue: 90_000, count: 28, avg: 3_214, share: 3.2 },
]);

const revenueByChannel = ref([
    { name: 'Маркетплейс CatVRF', revenue: 1_708_500, share: 60.0 },
    { name: 'Прямые записи', revenue: 569_500, share: 20.0 },
    { name: 'WhatsApp / Telegram', revenue: 284_750, share: 10.0 },
    { name: 'Корпоративные (B2B)', revenue: 199_325, share: 7.0 },
    { name: 'Прочие', revenue: 85_425, share: 3.0 },
]);

const revenueBySalon = ref([
    { name: 'Beauty Lab — Тверская', revenue: 1_281_375, share: 45.0, avgCheck: 5_200 },
    { name: 'Beauty Lab — Арбат', revenue: 854_250, share: 30.0, avgCheck: 4_800 },
    { name: 'Beauty Lab — Патрики', revenue: 711_875, share: 25.0, avgCheck: 4_300 },
]);

/* ─── Master Payouts ─── */
const payoutPeriod = ref('current');
const masterPayouts = ref([
    { id: 1, name: 'Анна Иванова', role: 'Стилист-колорист', revenue: 458_000, commission: 40, payout: 183_200, bonus: 12_000, total: 195_200, status: 'pending', rating: 4.9 },
    { id: 2, name: 'Ольга Дмитриева', role: 'Мастер маникюра', revenue: 312_000, commission: 45, payout: 140_400, bonus: 8_500, total: 148_900, status: 'pending', rating: 4.8 },
    { id: 3, name: 'Елена Козлова', role: 'Бровист', revenue: 189_500, commission: 40, payout: 75_800, bonus: 5_000, total: 80_800, status: 'paid', rating: 4.7 },
    { id: 4, name: 'Мария Смирнова', role: 'Косметолог', revenue: 378_000, commission: 35, payout: 132_300, bonus: 15_000, total: 147_300, status: 'pending', rating: 4.9 },
    { id: 5, name: 'Татьяна Волкова', role: 'Парикмахер', revenue: 245_000, commission: 40, payout: 98_000, bonus: 6_000, total: 104_000, status: 'paid', rating: 4.6 },
    { id: 6, name: 'Наталья Белова', role: 'SPA-терапевт', revenue: 156_000, commission: 35, payout: 54_600, bonus: 4_500, total: 59_100, status: 'pending', rating: 4.8 },
]);
const selectedPayouts = ref([]);
const showPayoutConfirm = ref(false);
const payoutTargets = ref([]);

const totalPayable = computed(() => masterPayouts.value.filter(p => p.status === 'pending').reduce((s, p) => s + p.total, 0));

function selectAllPayouts(e) {
    selectedPayouts.value = e.target.checked
        ? masterPayouts.value.filter(p => p.status === 'pending').map(p => p.id)
        : [];
}
function togglePayout(id) {
    const idx = selectedPayouts.value.indexOf(id);
    if (idx >= 0) selectedPayouts.value.splice(idx, 1);
    else selectedPayouts.value.push(id);
}
function initPayout(targets) {
    payoutTargets.value = targets || selectedPayouts.value.map(id => masterPayouts.value.find(p => p.id === id)).filter(Boolean);
    showPayoutConfirm.value = true;
}
function confirmPayout() {
    payoutTargets.value.forEach(p => {
        const master = masterPayouts.value.find(m => m.id === p.id);
        if (master) master.status = 'paid';
    });
    emit('payout', { masters: payoutTargets.value.map(p => p.id), total: payoutTargets.value.reduce((s, p) => s + p.total, 0) });
    selectedPayouts.value = [];
    showPayoutConfirm.value = false;
}

/* ─── Expenses ─── */
const expenses = ref([
    { id: 1, date: '08.04.2026', category: 'Аренда', description: 'Помещение — Тверская', amount: 350_000, recurring: true },
    { id: 2, date: '07.04.2026', category: 'Материалы', description: 'Краски Redken, кератин', amount: 87_500, recurring: false },
    { id: 3, date: '06.04.2026', category: 'Зарплата', description: 'Администраторы (3 чел.)', amount: 135_000, recurring: true },
    { id: 4, date: '05.04.2026', category: 'Реклама', description: 'Таргетированная реклама VK', amount: 45_000, recurring: false },
    { id: 5, date: '04.04.2026', category: 'Коммунальные', description: 'Электричество, вода, интернет', amount: 28_000, recurring: true },
    { id: 6, date: '03.04.2026', category: 'Оборудование', description: 'Лампа UV + кресло массажное', amount: 67_000, recurring: false },
    { id: 7, date: '02.04.2026', category: 'Подписки', description: 'CRM, бухгалтерия, аналитика', amount: 12_500, recurring: true },
    { id: 8, date: '01.04.2026', category: 'Хозяйственные', description: 'Полотенца, одноразовые материалы', amount: 15_000, recurring: false },
]);
const showAddExpense = ref(false);
const newExpense = reactive({
    category: 'Материалы',
    description: '',
    amount: 0,
    recurring: false,
});
const expenseCategories = ['Аренда', 'Материалы', 'Зарплата', 'Реклама', 'Коммунальные', 'Оборудование', 'Подписки', 'Хозяйственные', 'Обучение', 'Прочее'];
const totalExpenses = computed(() => expenses.value.reduce((s, e) => s + e.amount, 0));

function addExpense() {
    if (!newExpense.description || !newExpense.amount) return;
    expenses.value.unshift({
        id: Date.now(),
        date: new Date().toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' }),
        ...newExpense,
    });
    Object.assign(newExpense, { category: 'Материалы', description: '', amount: 0, recurring: false });
    showAddExpense.value = false;
}

function deleteExpense(id) {
    expenses.value = expenses.value.filter(e => e.id !== id);
}

/* ─── Taxes & OFD ─── */
const taxInfo = reactive({
    regime: 'УСН 6%',
    ofdProvider: 'Атол Онлайн',
    ofdStatus: 'connected',
    taxPeriodRevenue: 2_847_500,
    estimatedTax: 170_850,
    paidTax: 85_425,
    nextPaymentDate: '25.04.2026',
    receiptsIssued: 599,
    receiptErrors: 2,
});

/* ─── Forecast ─── */
const forecastData = ref([
    { month: 'Апрель', predicted: 2_920_000, actual: null, growth: 2.5 },
    { month: 'Май', predicted: 3_150_000, actual: null, growth: 7.9 },
    { month: 'Июнь', predicted: 2_680_000, actual: null, growth: -14.9 },
    { month: 'Июль', predicted: 2_450_000, actual: null, growth: -8.6 },
    { month: 'Август', predicted: 2_780_000, actual: null, growth: 13.5 },
    { month: 'Сентябрь', predicted: 3_320_000, actual: null, growth: 19.4 },
]);

/* ─── Export ─── */
function exportFinanceReport(format) {
    const data = {
        period: period.value,
        overview: overviewStats.value,
        revenueByService: revenueByService.value,
        payouts: masterPayouts.value,
        expenses: expenses.value,
        format,
    };
    if (format === 'csv') {
        let csv = 'Категория;Сумма\n';
        overviewStats.value.forEach(s => { csv += `${s.title};${s.value}\n`; });
        csv += '\nУслуга;Выручка;Количество;Средний чек;Доля\n';
        revenueByService.value.forEach(r => { csv += `${r.name};${r.revenue};${r.count};${r.avg};${r.share}%\n`; });
        csv += '\nМастер;Выручка;%;Выплата;Бонус;Итого;Статус\n';
        masterPayouts.value.forEach(m => { csv += `${m.name};${m.revenue};${m.commission}%;${m.payout};${m.bonus};${m.total};${m.status}\n`; });
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' });
        downloadBlob(blob, `finance-report-${period.value}.csv`);
    } else if (format === 'pdf') {
        const text = `ФИНАНСОВЫЙ ОТЧЁТ — ${period.value}\n${'='.repeat(50)}\n\n` +
            overviewStats.value.map(s => `${s.title}: ${s.value} (${fmtP(s.trend)})`).join('\n') +
            `\n\n--- ВЫРУЧКА ПО УСЛУГАМ ---\n` +
            revenueByService.value.map(r => `${r.name}: ${fmt(r.revenue)} ₽ (${r.share}%)`).join('\n') +
            `\n\n--- РАСХОДЫ ---\n` +
            expenses.value.map(e => `${e.date} ${e.category}: ${e.description} — ${fmt(e.amount)} ₽`).join('\n') +
            `\n\nИтого расходов: ${fmt(totalExpenses.value)} ₽\n`;
        const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
        downloadBlob(blob, `finance-report-${period.value}.txt`);
    }
    emit('export-report', data);
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
</script>

<template>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold" style="color:var(--t-text)">💰 Финансы</h2>
            <p class="text-sm mt-1" style="color:var(--t-text-2)">Выручка, выплаты мастерам, расходы и прогнозы</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <div class="flex rounded-lg border overflow-hidden" style="border-color:var(--t-border)">
                <button v-for="p in periods" :key="p.key" @click="period = p.key"
                        class="px-3 py-1.5 text-xs font-medium transition-all"
                        :style="period === p.key ? 'background:var(--t-primary);color:#fff' : 'background:var(--t-surface);color:var(--t-text-2)'">
                    {{ p.label }}
                </button>
            </div>
            <VButton size="sm" variant="outline" @click="exportFinanceReport('csv')">📥 CSV</VButton>
            <VButton size="sm" variant="outline" @click="exportFinanceReport('pdf')">📄 PDF</VButton>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 overflow-x-auto">
        <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key"
                class="px-4 py-2 rounded-xl text-sm font-medium whitespace-nowrap transition-all"
                :style="activeTab === tab.key ? 'background:var(--t-primary);color:#fff' : 'background:var(--t-surface);color:var(--t-text-2)'">
            {{ tab.label }}
        </button>
    </div>

    <!-- Overview Tab -->
    <template v-if="activeTab === 'overview'">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <VCard v-for="stat in overviewStats" :key="stat.title" class="p-4 text-center">
                <div class="text-2xl mb-1">{{ stat.icon }}</div>
                <div class="text-lg font-bold" style="color:var(--t-text)">{{ stat.value }} ₽</div>
                <div class="text-xs mt-1" style="color:var(--t-text-3)">{{ stat.title }}</div>
                <div class="text-xs mt-1 font-medium" :style="`color:${stat.trend > 0 ? '#22c55e' : stat.trend < 0 ? '#ef4444' : 'var(--t-text-3)'}`">
                    {{ stat.trend > 0 ? '↗' : stat.trend < 0 ? '↘' : '→' }} {{ fmtP(stat.trend) }}
                </div>
            </VCard>
        </div>

        <!-- Revenue bar chart (visual) -->
        <VCard class="p-6">
            <h3 class="text-lg font-bold mb-4" style="color:var(--t-text)">📊 Выручка по услугам</h3>
            <div class="space-y-3">
                <div v-for="svc in revenueByService" :key="svc.name" class="flex items-center gap-3">
                    <div class="w-32 text-sm truncate" style="color:var(--t-text)">{{ svc.name }}</div>
                    <div class="flex-1 rounded-full h-6 overflow-hidden" style="background:var(--t-bg)">
                        <div class="h-full rounded-full flex items-center px-2 text-xs text-white font-medium transition-all duration-500"
                             :style="`background:var(--t-primary);width:${svc.share}%`">
                            {{ svc.share }}%
                        </div>
                    </div>
                    <div class="w-24 text-right text-sm font-medium" style="color:var(--t-text)">{{ fmt(svc.revenue) }} ₽</div>
                </div>
            </div>
        </VCard>

        <!-- Revenue by channel & salon -->
        <div class="grid md:grid-cols-2 gap-6">
            <VCard class="p-6">
                <h3 class="font-bold mb-4" style="color:var(--t-text)">📡 По каналам</h3>
                <div class="space-y-3">
                    <div v-for="ch in revenueByChannel" :key="ch.name" class="flex justify-between items-center">
                        <span class="text-sm" style="color:var(--t-text)">{{ ch.name }}</span>
                        <div class="flex items-center gap-2">
                            <VBadge :color="ch.share > 20 ? 'green' : 'gray'" size="sm">{{ ch.share }}%</VBadge>
                            <span class="text-sm font-medium w-28 text-right" style="color:var(--t-text)">{{ fmt(ch.revenue) }} ₽</span>
                        </div>
                    </div>
                </div>
            </VCard>
            <VCard class="p-6">
                <h3 class="font-bold mb-4" style="color:var(--t-text)">🏪 По салонам</h3>
                <div class="space-y-3">
                    <div v-for="sl in revenueBySalon" :key="sl.name" class="flex justify-between items-center">
                        <span class="text-sm" style="color:var(--t-text)">{{ sl.name }}</span>
                        <div class="flex items-center gap-2">
                            <VBadge color="blue" size="sm">{{ sl.share }}%</VBadge>
                            <span class="text-sm font-medium w-28 text-right" style="color:var(--t-text)">{{ fmt(sl.revenue) }} ₽</span>
                        </div>
                    </div>
                </div>
            </VCard>
        </div>
    </template>

    <!-- Revenue Tab -->
    <template v-if="activeTab === 'revenue'">
        <VCard class="p-6">
            <h3 class="font-bold mb-4" style="color:var(--t-text)">💰 Детальная выручка по услугам</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b" style="border-color:var(--t-border)">
                            <th class="text-left py-3 px-2" style="color:var(--t-text-3)">Услуга</th>
                            <th class="text-right py-3 px-2" style="color:var(--t-text-3)">Выручка</th>
                            <th class="text-right py-3 px-2" style="color:var(--t-text-3)">Кол-во</th>
                            <th class="text-right py-3 px-2" style="color:var(--t-text-3)">Ср. чек</th>
                            <th class="text-right py-3 px-2" style="color:var(--t-text-3)">Доля</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="svc in revenueByService" :key="svc.name" class="border-b" style="border-color:var(--t-border)">
                            <td class="py-3 px-2 font-medium" style="color:var(--t-text)">{{ svc.name }}</td>
                            <td class="py-3 px-2 text-right font-bold" style="color:var(--t-primary)">{{ fmt(svc.revenue) }} ₽</td>
                            <td class="py-3 px-2 text-right" style="color:var(--t-text)">{{ svc.count }}</td>
                            <td class="py-3 px-2 text-right" style="color:var(--t-text)">{{ fmt(svc.avg) }} ₽</td>
                            <td class="py-3 px-2 text-right"><VBadge color="blue" size="sm">{{ svc.share }}%</VBadge></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="font-bold">
                            <td class="py-3 px-2" style="color:var(--t-text)">Итого</td>
                            <td class="py-3 px-2 text-right" style="color:var(--t-primary)">{{ fmt(revenueByService.reduce((s,r) => s + r.revenue, 0)) }} ₽</td>
                            <td class="py-3 px-2 text-right" style="color:var(--t-text)">{{ revenueByService.reduce((s,r) => s + r.count, 0) }}</td>
                            <td class="py-3 px-2 text-right" style="color:var(--t-text)">—</td>
                            <td class="py-3 px-2 text-right"><VBadge color="green" size="sm">100%</VBadge></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </VCard>
    </template>

    <!-- Payouts Tab -->
    <template v-if="activeTab === 'payouts'">
        <div class="flex items-center justify-between mb-2">
            <div class="text-sm" style="color:var(--t-text-2)">К выплате: <strong style="color:var(--t-primary)">{{ fmt(totalPayable) }} ₽</strong></div>
            <div class="flex gap-2">
                <VButton size="sm" variant="outline" :disabled="selectedPayouts.length === 0" @click="initPayout(null)">
                    💸 Выплатить выбранным ({{ selectedPayouts.length }})
                </VButton>
                <VButton size="sm" @click="initPayout(masterPayouts.filter(p => p.status === 'pending'))">
                    💸 Выплатить всем
                </VButton>
            </div>
        </div>
        <VCard class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" style="border-color:var(--t-border)">
                        <th class="py-3 px-3 text-left"><input type="checkbox" @change="selectAllPayouts" /></th>
                        <th class="py-3 px-3 text-left" style="color:var(--t-text-3)">Мастер</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Выручка</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">%</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Выплата</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Бонус</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Итого</th>
                        <th class="py-3 px-3 text-center" style="color:var(--t-text-3)">Статус</th>
                        <th class="py-3 px-3 text-center" style="color:var(--t-text-3)">Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="mp in masterPayouts" :key="mp.id" class="border-b transition-all"
                        style="border-color:var(--t-border)"
                        :class="{ 'opacity-50': mp.status === 'paid' }">
                        <td class="py-3 px-3"><input type="checkbox" :checked="selectedPayouts.includes(mp.id)" @change="togglePayout(mp.id)" :disabled="mp.status === 'paid'" /></td>
                        <td class="py-3 px-3">
                            <div class="font-medium" style="color:var(--t-text)">{{ mp.name }}</div>
                            <div class="text-xs" style="color:var(--t-text-3)">{{ mp.role }} · ⭐ {{ mp.rating }}</div>
                        </td>
                        <td class="py-3 px-3 text-right" style="color:var(--t-text)">{{ fmt(mp.revenue) }} ₽</td>
                        <td class="py-3 px-3 text-right" style="color:var(--t-text-2)">{{ mp.commission }}%</td>
                        <td class="py-3 px-3 text-right" style="color:var(--t-text)">{{ fmt(mp.payout) }} ₽</td>
                        <td class="py-3 px-3 text-right" style="color:var(--t-accent)">+{{ fmt(mp.bonus) }} ₽</td>
                        <td class="py-3 px-3 text-right font-bold" style="color:var(--t-primary)">{{ fmt(mp.total) }} ₽</td>
                        <td class="py-3 px-3 text-center">
                            <VBadge :color="mp.status === 'paid' ? 'green' : 'yellow'" size="sm">{{ mp.status === 'paid' ? '✅ Выплачено' : '⏳ Ожидает' }}</VBadge>
                        </td>
                        <td class="py-3 px-3 text-center">
                            <VButton v-if="mp.status === 'pending'" size="sm" @click="initPayout([mp])">💸</VButton>
                        </td>
                    </tr>
                </tbody>
            </table>
        </VCard>
    </template>

    <!-- Expenses Tab -->
    <template v-if="activeTab === 'expenses'">
        <div class="flex items-center justify-between mb-2">
            <div class="text-sm" style="color:var(--t-text-2)">Итого расходов: <strong style="color:var(--t-text)">{{ fmt(totalExpenses) }} ₽</strong></div>
            <VButton size="sm" @click="showAddExpense = true">➕ Добавить расход</VButton>
        </div>
        <VCard class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b" style="border-color:var(--t-border)">
                        <th class="py-3 px-3 text-left" style="color:var(--t-text-3)">Дата</th>
                        <th class="py-3 px-3 text-left" style="color:var(--t-text-3)">Категория</th>
                        <th class="py-3 px-3 text-left" style="color:var(--t-text-3)">Описание</th>
                        <th class="py-3 px-3 text-right" style="color:var(--t-text-3)">Сумма</th>
                        <th class="py-3 px-3 text-center" style="color:var(--t-text-3)">Тип</th>
                        <th class="py-3 px-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="exp in expenses" :key="exp.id" class="border-b" style="border-color:var(--t-border)">
                        <td class="py-3 px-3 text-sm" style="color:var(--t-text-2)">{{ exp.date }}</td>
                        <td class="py-3 px-3"><VBadge color="blue" size="sm">{{ exp.category }}</VBadge></td>
                        <td class="py-3 px-3" style="color:var(--t-text)">{{ exp.description }}</td>
                        <td class="py-3 px-3 text-right font-medium" style="color:#ef4444">−{{ fmt(exp.amount) }} ₽</td>
                        <td class="py-3 px-3 text-center"><VBadge :color="exp.recurring ? 'purple' : 'gray'" size="sm">{{ exp.recurring ? '🔄 Пост.' : '📌 Разовый' }}</VBadge></td>
                        <td class="py-3 px-3 text-center"><button @click="deleteExpense(exp.id)" class="text-red-400 hover:text-red-500 text-sm">🗑️</button></td>
                    </tr>
                </tbody>
            </table>
        </VCard>
    </template>

    <!-- Taxes Tab -->
    <template v-if="activeTab === 'taxes'">
        <div class="grid md:grid-cols-2 gap-6">
            <VCard class="p-6">
                <h3 class="font-bold mb-4" style="color:var(--t-text)">🧾 Налоговый режим</h3>
                <div class="space-y-3">
                    <div class="flex justify-between"><span style="color:var(--t-text-2)">Режим</span><span class="font-medium" style="color:var(--t-text)">{{ taxInfo.regime }}</span></div>
                    <div class="flex justify-between"><span style="color:var(--t-text-2)">Выручка за период</span><span class="font-medium" style="color:var(--t-text)">{{ fmt(taxInfo.taxPeriodRevenue) }} ₽</span></div>
                    <div class="flex justify-between"><span style="color:var(--t-text-2)">Расчётный налог</span><span class="font-bold" style="color:#ef4444">{{ fmt(taxInfo.estimatedTax) }} ₽</span></div>
                    <div class="flex justify-between"><span style="color:var(--t-text-2)">Уплачено</span><span class="font-medium" style="color:#22c55e">{{ fmt(taxInfo.paidTax) }} ₽</span></div>
                    <div class="flex justify-between"><span style="color:var(--t-text-2)">Осталось</span><span class="font-bold" style="color:var(--t-primary)">{{ fmt(taxInfo.estimatedTax - taxInfo.paidTax) }} ₽</span></div>
                    <div class="flex justify-between"><span style="color:var(--t-text-2)">Следующий платёж</span><span class="font-medium" style="color:var(--t-text)">{{ taxInfo.nextPaymentDate }}</span></div>
                </div>
            </VCard>
            <VCard class="p-6">
                <h3 class="font-bold mb-4" style="color:var(--t-text)">🖨️ ОФД</h3>
                <div class="space-y-3">
                    <div class="flex justify-between"><span style="color:var(--t-text-2)">Провайдер</span><span class="font-medium" style="color:var(--t-text)">{{ taxInfo.ofdProvider }}</span></div>
                    <div class="flex justify-between">
                        <span style="color:var(--t-text-2)">Статус</span>
                        <VBadge :color="taxInfo.ofdStatus === 'connected' ? 'green' : 'red'" size="sm">{{ taxInfo.ofdStatus === 'connected' ? '✅ Подключено' : '❌ Отключено' }}</VBadge>
                    </div>
                    <div class="flex justify-between"><span style="color:var(--t-text-2)">Чеков выдано</span><span class="font-medium" style="color:var(--t-text)">{{ taxInfo.receiptsIssued }}</span></div>
                    <div class="flex justify-between"><span style="color:var(--t-text-2)">Ошибок</span><span class="font-medium" :style="`color:${taxInfo.receiptErrors > 0 ? '#ef4444' : '#22c55e'}`">{{ taxInfo.receiptErrors }}</span></div>
                </div>
            </VCard>
        </div>
    </template>

    <!-- Forecast Tab -->
    <template v-if="activeTab === 'forecast'">
        <VCard class="p-6">
            <h3 class="font-bold mb-4" style="color:var(--t-text)">🔮 Прогноз выручки (AI)</h3>
            <p class="text-sm mb-6" style="color:var(--t-text-2)">Прогноз на основе исторических данных, сезонности и текущих трендов</p>
            <div class="space-y-3">
                <div v-for="fc in forecastData" :key="fc.month" class="flex items-center gap-4 p-3 rounded-xl" style="background:var(--t-bg)">
                    <div class="w-24 font-medium text-sm" style="color:var(--t-text)">{{ fc.month }}</div>
                    <div class="flex-1 rounded-full h-8 overflow-hidden" style="background:var(--t-surface)">
                        <div class="h-full rounded-full flex items-center px-3 text-xs text-white font-medium"
                             :style="`background:${fc.growth >= 0 ? 'var(--t-primary)' : '#ef4444'};width:${Math.min(100, (fc.predicted / 3_500_000) * 100)}%`">
                            {{ fmt(fc.predicted) }} ₽
                        </div>
                    </div>
                    <div class="w-20 text-right text-sm font-medium" :style="`color:${fc.growth >= 0 ? '#22c55e' : '#ef4444'}`">
                        {{ fc.growth > 0 ? '↗' : '↘' }} {{ fmtP(fc.growth) }}
                    </div>
                </div>
            </div>
        </VCard>
    </template>
</div>

<!-- Payout Confirm Modal -->
<VModal :show="showPayoutConfirm" @close="showPayoutConfirm = false" title="💸 Подтверждение выплаты">
    <div class="space-y-4">
        <div class="space-y-2">
            <div v-for="pt in payoutTargets" :key="pt.id" class="flex justify-between items-center p-3 rounded-lg" style="background:var(--t-bg)">
                <div>
                    <div class="font-medium text-sm" style="color:var(--t-text)">{{ pt.name }}</div>
                    <div class="text-xs" style="color:var(--t-text-3)">{{ pt.role }}</div>
                </div>
                <div class="font-bold" style="color:var(--t-primary)">{{ fmt(pt.total) }} ₽</div>
            </div>
        </div>
        <div class="flex justify-between items-center p-3 rounded-lg border" style="border-color:var(--t-primary)">
            <span class="font-bold" style="color:var(--t-text)">Итого к выплате:</span>
            <span class="text-xl font-bold" style="color:var(--t-primary)">{{ fmt(payoutTargets.reduce((s, p) => s + p.total, 0)) }} ₽</span>
        </div>
        <div class="flex justify-end gap-3">
            <VButton variant="outline" @click="showPayoutConfirm = false">Отмена</VButton>
            <VButton @click="confirmPayout">✅ Подтвердить выплату</VButton>
        </div>
    </div>
</VModal>

<!-- Add Expense Modal -->
<VModal :show="showAddExpense" @close="showAddExpense = false" title="➕ Новый расход">
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Категория</label>
            <select v-model="newExpense.category" class="w-full rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)">
                <option v-for="cat in expenseCategories" :key="cat" :value="cat">{{ cat }}</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Описание</label>
            <input v-model="newExpense.description" type="text" class="w-full rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)" placeholder="Описание расхода" />
        </div>
        <div>
            <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Сумма (₽)</label>
            <input v-model.number="newExpense.amount" type="number" min="0" class="w-full rounded-lg px-3 py-2 text-sm border" style="background:var(--t-bg);border-color:var(--t-border);color:var(--t-text)" />
        </div>
        <label class="flex items-center gap-2">
            <input v-model="newExpense.recurring" type="checkbox" />
            <span class="text-sm" style="color:var(--t-text)">Постоянный расход (ежемесячный)</span>
        </label>
        <div class="flex justify-end gap-3">
            <VButton variant="outline" @click="showAddExpense = false">Отмена</VButton>
            <VButton @click="addExpense" :disabled="!newExpense.description || !newExpense.amount">➕ Добавить</VButton>
        </div>
    </div>
</VModal>

</template>
