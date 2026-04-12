<script setup>
/**
 * BeautyLoyalty — управление программой лояльности.
 * 7 табов: обзор, тиры, правила, реферальная система, кэшбэк, VIP, аналитика.
 * 5 тем (mint/day/night/sunset/lavender) через CSS custom properties.
 */
import { ref, computed, reactive, inject } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VStatCard from '../../UI/VStatCard.vue';
import VButton from '../../UI/VButton.vue';
import VBadge from '../../UI/VBadge.vue';
import VModal from '../../UI/VModal.vue';
import VInput from '../../UI/VInput.vue';
import VTable from '../../UI/VTable.vue';

const props = defineProps({
    masters: { type: Array, default: () => [] },
    salons:  { type: Array, default: () => [] },
    clients: { type: Array, default: () => [] },
});
const emit = defineEmits([
    'open-client', 'award-bonus', 'deduct-bonus', 'export-report',
    'save-settings', 'create-tier', 'edit-tier', 'create-rule',
]);

const t = inject('theme', {
    bg: 'var(--t-bg)', surface: 'var(--t-surface)', border: 'var(--t-border)',
    primary: 'var(--t-primary)', primaryDim: 'var(--t-primary-dim)',
    accent: 'var(--t-accent)', text: 'var(--t-text)', text2: 'var(--t-text-2)',
    text3: 'var(--t-text-3)', glow: 'var(--t-glow)', header: 'var(--t-header)',
    btn: 'var(--t-btn)', btnHover: 'var(--t-btn-hover)', cardHover: 'var(--t-card-hover)',
});

/* ─── Tabs ─── */
const tabs = [
    { key: 'overview',  label: '📊 Обзор' },
    { key: 'tiers',     label: '🏅 Тиры' },
    { key: 'rules',     label: '📋 Правила' },
    { key: 'referral',  label: '🤝 Рефералы' },
    { key: 'cashback',  label: '💰 Кэшбэк' },
    { key: 'vip',       label: '👑 VIP' },
    { key: 'analytics', label: '📈 Аналитика' },
];
const activeTab = ref('overview');

/* ─── Overview stats ─── */
const loyaltyStats = ref([
    { label: 'Участников программы', value: '4 218', trend: '+12%', icon: '👥' },
    { label: 'Активных бонусов (₽)', value: '892 400', trend: '+8%', icon: '💎' },
    { label: 'Средний бонусный баланс', value: '211 ₽', trend: '+3%', icon: '💰' },
    { label: 'Конверсия рефералов', value: '34%', trend: '+5%', icon: '🤝' },
    { label: 'Возврат через программу', value: '67%', trend: '+9%', icon: '🔄' },
    { label: 'ROI программы', value: '340%', trend: '+14%', icon: '📈' },
]);

/* ─── Tiers ─── */
const tiers = ref([
    { id: 1, name: 'Бронза', minSpend: 0, maxSpend: 9999, cashbackPercent: 3, discount: 0, color: '#CD7F32', membersCount: 2840, icon: '🥉', benefits: ['Бонусы за посещения', 'День рождения +500 бонусов'] },
    { id: 2, name: 'Серебро', minSpend: 10000, maxSpend: 29999, cashbackPercent: 5, discount: 5, color: '#C0C0C0', membersCount: 980, icon: '🥈', benefits: ['Всё из Бронзы', 'Скидка 5% на все услуги', 'Приоритетная запись'] },
    { id: 3, name: 'Золото', minSpend: 30000, maxSpend: 74999, cashbackPercent: 7, discount: 10, color: '#FFD700', membersCount: 320, icon: '🥇', benefits: ['Всё из Серебра', 'Скидка 10%', 'Бесплатная парковка', 'Персональный менеджер'] },
    { id: 4, name: 'Платина', minSpend: 75000, maxSpend: null, cashbackPercent: 10, discount: 15, color: '#E5E4E2', membersCount: 78, icon: '💎', benefits: ['Всё из Золота', 'Скидка 15%', 'Бесплатные экспресс-услуги', 'Закрытые мероприятия', 'Личный стилист'] },
]);
const showAddTier = ref(false);
const newTier = reactive({ name: '', minSpend: 0, cashbackPercent: 0, discount: 0, benefits: '' });

function saveTier() {
    tiers.value.push({
        id: Date.now(),
        name: newTier.name,
        minSpend: newTier.minSpend,
        maxSpend: null,
        cashbackPercent: newTier.cashbackPercent,
        discount: newTier.discount,
        color: '#999',
        membersCount: 0,
        icon: '🏅',
        benefits: newTier.benefits.split(',').map(b => b.trim()),
    });
    emit('create-tier', { ...newTier });
    showAddTier.value = false;
    Object.assign(newTier, { name: '', minSpend: 0, cashbackPercent: 0, discount: 0, benefits: '' });
}

/* ─── Rules ─── */
const bonusRules = ref([
    { id: 1, name: 'Бонус за первое посещение', type: 'first_visit', amount: 500, isActive: true, usageCount: 1420 },
    { id: 2, name: 'День рождения', type: 'birthday', amount: 1000, isActive: true, usageCount: 348 },
    { id: 3, name: 'Кэшбэк за каждую услугу', type: 'cashback', amount: 0, isActive: true, usageCount: 8920, description: 'Процент зависит от тира' },
    { id: 4, name: 'Бонус за отзыв', type: 'review', amount: 200, isActive: true, usageCount: 612 },
    { id: 5, name: 'Бонус за запись онлайн', type: 'online_booking', amount: 100, isActive: true, usageCount: 3240 },
    { id: 6, name: 'Повторное посещение за 30 дней', type: 'return_visit', amount: 300, isActive: false, usageCount: 890 },
]);
const showAddRule = ref(false);
const newRule = reactive({ name: '', type: 'first_visit', amount: 0 });

const ruleTypes = [
    { key: 'first_visit', label: 'Первое посещение' },
    { key: 'birthday', label: 'День рождения' },
    { key: 'cashback', label: 'Кэшбэк' },
    { key: 'review', label: 'За отзыв' },
    { key: 'online_booking', label: 'Онлайн-запись' },
    { key: 'return_visit', label: 'Повторное посещение' },
    { key: 'referral', label: 'Реферал' },
    { key: 'custom', label: 'Кастомное' },
];

function saveRule() {
    bonusRules.value.push({ id: Date.now(), ...newRule, isActive: true, usageCount: 0 });
    emit('create-rule', { ...newRule });
    showAddRule.value = false;
    Object.assign(newRule, { name: '', type: 'first_visit', amount: 0 });
}

function toggleRule(rule) {
    rule.isActive = !rule.isActive;
}

/* ─── Referral ─── */
const referralSettings = reactive({
    inviterBonus: 500,
    inviteeBonus: 300,
    minOrderAmount: 1000,
    maxReferrals: 50,
    isActive: true,
});
const topReferrers = ref([
    { name: 'Мария К.', referrals: 23, earned: 11500, lastReferral: '08.04.2026' },
    { name: 'Елена П.', referrals: 18, earned: 9000, lastReferral: '07.04.2026' },
    { name: 'Ольга Р.', referrals: 15, earned: 7500, lastReferral: '06.04.2026' },
    { name: 'Дарья В.', referrals: 12, earned: 6000, lastReferral: '05.04.2026' },
    { name: 'Ирина М.', referrals: 9, earned: 4500, lastReferral: '04.04.2026' },
]);
const referralStats = ref({ totalReferrals: 412, activeChains: 89, avgChainLength: 2.3, totalPaid: 206000 });

/* ─── Cashback ─── */
const cashbackSettings = reactive({
    minAmount: 500,
    maxCashbackPercent: 10,
    expirationDays: 90,
    canPayWithBonus: true,
    maxBonusPayPercent: 30,
});
const cashbackHistory = ref([
    { date: '08.04.2026', client: 'Мария К.', service: 'Стрижка + укладка', amount: 3400, cashback: 238, tier: 'Золото' },
    { date: '08.04.2026', client: 'Елена П.', service: 'Маникюр гель-лак', amount: 2800, cashback: 140, tier: 'Серебро' },
    { date: '07.04.2026', client: 'Дарья В.', service: 'Окрашивание AirTouch', amount: 12000, cashback: 1200, tier: 'Платина' },
    { date: '07.04.2026', client: 'Ольга Р.', service: 'SPA-уход', amount: 5600, cashback: 280, tier: 'Серебро' },
    { date: '07.04.2026', client: 'Наталья Б.', service: 'Брови + ресницы', amount: 4200, cashback: 126, tier: 'Бронза' },
]);

/* ─── VIP ─── */
const vipClients = ref([
    { id: 1, name: 'Анастасия Волкова', tier: 'Платина', totalSpend: 182000, visits: 48, bonus: 12400, lastVisit: '08.04.2026', favMaster: 'Анна С.', phone: '+7 999 123-45-67' },
    { id: 2, name: 'Виктория Смирнова', tier: 'Платина', totalSpend: 156000, visits: 41, bonus: 8900, lastVisit: '07.04.2026', favMaster: 'Ольга Д.', phone: '+7 999 234-56-78' },
    { id: 3, name: 'Екатерина Иванова', tier: 'Золото', totalSpend: 98000, visits: 34, bonus: 6200, lastVisit: '06.04.2026', favMaster: 'Анна С.', phone: '+7 999 345-67-89' },
    { id: 4, name: 'Светлана Петрова', tier: 'Золото', totalSpend: 74000, visits: 28, bonus: 4100, lastVisit: '05.04.2026', favMaster: 'Светлана Р.', phone: '+7 999 456-78-90' },
]);
const vipPerks = ref([
    { name: 'Закрытые мероприятия', description: 'Приглашения на beauty-вечера и мастер-классы', isActive: true },
    { name: 'Ранний доступ', description: 'Первые в очереди на новые услуги и продукцию', isActive: true },
    { name: 'Персональный стилист', description: 'Индивидуальный подбор образа и консультации', isActive: true },
    { name: 'Бесплатный трансфер', description: 'Такси до салона за счёт компании (Платина)', isActive: false },
]);

/* ─── Analytics ─── */
const monthlyLoyaltyData = ref([
    { month: 'Янв', newMembers: 180, bonusesEarned: 42000, bonusesSpent: 31000, retention: 72 },
    { month: 'Фев', newMembers: 210, bonusesEarned: 48000, bonusesSpent: 35000, retention: 74 },
    { month: 'Мар', newMembers: 290, bonusesEarned: 62000, bonusesSpent: 44000, retention: 78 },
    { month: 'Апр', newMembers: 340, bonusesEarned: 78000, bonusesSpent: 52000, retention: 82 },
]);
const tierDistribution = computed(() => {
    const total = tiers.value.reduce((s, t) => s + t.membersCount, 0);
    return tiers.value.map(t => ({ ...t, percent: total ? Math.round((t.membersCount / total) * 100) : 0 }));
});

/* ─── Toast ─── */
const showToast = ref(false);
const toastMessage = ref('');
function toast(msg) { toastMessage.value = msg; showToast.value = true; setTimeout(() => { showToast.value = false; }, 3000); }

/* ─── Export ─── */
function exportCSV(data, filename) {
    if (!data.length) return;
    const keys = Object.keys(data[0]);
    const csv = [keys.join(';'), ...data.map(r => keys.map(k => String(r[k] ?? '')).join(';'))].join('\r\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = `${filename}_${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
    toast(`Экспорт «${filename}» завершён`);
    emit('export-report', { filename, format: 'csv' });
}

function fmt(n) { return new Intl.NumberFormat('ru-RU').format(n); }
</script>

<template>
<div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <h1 class="text-xl font-bold" style="color:var(--t-text)">🎁 Программа лояльности</h1>
        <div class="flex gap-2">
            <VButton size="sm" variant="outline" @click="exportCSV(cashbackHistory, 'loyalty_cashback')">📥 Экспорт</VButton>
            <VButton size="sm" @click="showAddRule = true">➕ Новое правило</VButton>
        </div>
    </div>

    <VTabs :tabs="tabs" v-model="activeTab" />

    <!-- ═══ OVERVIEW ═══ -->
    <div v-if="activeTab === 'overview'" class="space-y-4">
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <VStatCard v-for="s in loyaltyStats" :key="s.label" :label="s.label" :value="s.value" :trend="s.trend" :icon="s.icon" />
        </div>
        <div class="grid lg:grid-cols-2 gap-4">
            <VCard title="🏅 Распределение по тирам">
                <div class="space-y-3">
                    <div v-for="td in tierDistribution" :key="td.id" class="flex items-center gap-3">
                        <span class="text-xl">{{ td.icon }}</span>
                        <div class="flex-1">
                            <div class="flex justify-between text-sm mb-1">
                                <span style="color:var(--t-text)">{{ td.name }}</span>
                                <span style="color:var(--t-text-2)">{{ fmt(td.membersCount) }} ({{ td.percent }}%)</span>
                            </div>
                            <div class="w-full h-2 rounded-full" style="background:var(--t-bg)">
                                <div class="h-full rounded-full transition-all" :style="{ width: td.percent + '%', background: td.color }"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </VCard>
            <VCard title="🤝 Топ рефереров">
                <div class="space-y-2">
                    <div v-for="(r, i) in topReferrers" :key="r.name" class="flex items-center gap-3 p-2 rounded-lg cursor-pointer hover:shadow transition-shadow" style="background:var(--t-bg)" @click="emit('open-client', r)">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold" style="background:var(--t-primary-dim);color:var(--t-primary)">{{ i + 1 }}</span>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-sm truncate" style="color:var(--t-text)">{{ r.name }}</div>
                            <div class="text-xs" style="color:var(--t-text-3)">{{ r.referrals }} рефералов</div>
                        </div>
                        <span class="font-bold text-sm" style="color:var(--t-primary)">{{ fmt(r.earned) }} ₽</span>
                    </div>
                </div>
            </VCard>
        </div>
    </div>

    <!-- ═══ TIERS ═══ -->
    <div v-if="activeTab === 'tiers'" class="space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold" style="color:var(--t-text)">Уровни лояльности</h2>
            <VButton size="sm" @click="showAddTier = true">➕ Новый тир</VButton>
        </div>
        <div class="grid md:grid-cols-2 gap-4">
            <VCard v-for="tier in tiers" :key="tier.id">
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-2xl">{{ tier.icon }}</span>
                        <div>
                            <h3 class="font-bold" style="color:var(--t-text)">{{ tier.name }}</h3>
                            <div class="text-xs" style="color:var(--t-text-3)">от {{ fmt(tier.minSpend) }} ₽{{ tier.maxSpend ? ' до ' + fmt(tier.maxSpend) + ' ₽' : '+' }}</div>
                        </div>
                    </div>
                    <VBadge color="blue" size="sm">{{ fmt(tier.membersCount) }} чел.</VBadge>
                </div>
                <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                    <div class="p-2 rounded-lg text-center" style="background:var(--t-bg)">
                        <div class="font-bold" style="color:var(--t-primary)">{{ tier.cashbackPercent }}%</div>
                        <div class="text-xs" style="color:var(--t-text-3)">Кэшбэк</div>
                    </div>
                    <div class="p-2 rounded-lg text-center" style="background:var(--t-bg)">
                        <div class="font-bold" style="color:var(--t-primary)">{{ tier.discount }}%</div>
                        <div class="text-xs" style="color:var(--t-text-3)">Скидка</div>
                    </div>
                </div>
                <div class="space-y-1">
                    <div v-for="b in tier.benefits" :key="b" class="text-xs flex items-center gap-1" style="color:var(--t-text-2)">
                        <span class="text-green-400">✓</span> {{ b }}
                    </div>
                </div>
                <div class="flex gap-2 mt-3">
                    <VButton size="sm" variant="outline" class="flex-1" @click="emit('edit-tier', tier)">✏️ Изменить</VButton>
                </div>
            </VCard>
        </div>
    </div>

    <!-- ═══ RULES ═══ -->
    <div v-if="activeTab === 'rules'" class="space-y-4">
        <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold" style="color:var(--t-text)">Бонусные правила</h2>
            <VButton size="sm" @click="showAddRule = true">➕ Новое правило</VButton>
        </div>
        <div class="space-y-2">
            <div v-for="rule in bonusRules" :key="rule.id"
                 class="p-4 rounded-xl border flex items-center gap-4 flex-wrap"
                 style="background:var(--t-surface);border-color:var(--t-border)">
                <div class="flex-1 min-w-[200px]">
                    <div class="font-bold text-sm" style="color:var(--t-text)">{{ rule.name }}</div>
                    <div class="text-xs mt-1" style="color:var(--t-text-3)">Тип: {{ ruleTypes.find(rt => rt.key === rule.type)?.label || rule.type }}</div>
                    <div v-if="rule.description" class="text-xs mt-1" style="color:var(--t-text-2)">{{ rule.description }}</div>
                </div>
                <div class="text-right">
                    <div v-if="rule.amount" class="font-bold" style="color:var(--t-primary)">+{{ fmt(rule.amount) }} ₽</div>
                    <div class="text-xs" style="color:var(--t-text-3)">Использований: {{ fmt(rule.usageCount) }}</div>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" :checked="rule.isActive" @change="toggleRule(rule)" class="w-5 h-5 accent-(--t-primary)">
                    <span class="text-xs" :style="{ color: rule.isActive ? 'var(--t-primary)' : 'var(--t-text-3)' }">{{ rule.isActive ? 'Активно' : 'Выкл.' }}</span>
                </label>
            </div>
        </div>
    </div>

    <!-- ═══ REFERRAL ═══ -->
    <div v-if="activeTab === 'referral'" class="space-y-4">
        <div class="grid lg:grid-cols-2 gap-4">
            <VCard title="⚙️ Настройки реферальной программы">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color:var(--t-text)">Программа активна</span>
                        <input type="checkbox" v-model="referralSettings.isActive" class="w-5 h-5 accent-(--t-primary)">
                    </div>
                    <VInput label="Бонус приглашающему (₽)" v-model.number="referralSettings.inviterBonus" type="number" />
                    <VInput label="Бонус приглашённому (₽)" v-model.number="referralSettings.inviteeBonus" type="number" />
                    <VInput label="Мин. сумма первого заказа (₽)" v-model.number="referralSettings.minOrderAmount" type="number" />
                    <VInput label="Макс. рефералов на 1 клиента" v-model.number="referralSettings.maxReferrals" type="number" />
                    <VButton @click="emit('save-settings', { type: 'referral', data: { ...referralSettings } }); toast('Настройки рефералов сохранены')">💾 Сохранить</VButton>
                </div>
            </VCard>
            <VCard title="📊 Статистика рефералов">
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 rounded-lg text-center" style="background:var(--t-bg)">
                        <div class="text-xl font-bold" style="color:var(--t-primary)">{{ fmt(referralStats.totalReferrals) }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">Всего рефералов</div>
                    </div>
                    <div class="p-3 rounded-lg text-center" style="background:var(--t-bg)">
                        <div class="text-xl font-bold" style="color:var(--t-primary)">{{ referralStats.activeChains }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">Активных цепочек</div>
                    </div>
                    <div class="p-3 rounded-lg text-center" style="background:var(--t-bg)">
                        <div class="text-xl font-bold" style="color:var(--t-primary)">{{ referralStats.avgChainLength }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">Сред. длина цепочки</div>
                    </div>
                    <div class="p-3 rounded-lg text-center" style="background:var(--t-bg)">
                        <div class="text-xl font-bold" style="color:var(--t-primary)">{{ fmt(referralStats.totalPaid) }} ₽</div>
                        <div class="text-xs" style="color:var(--t-text-3)">Всего выплачено</div>
                    </div>
                </div>
            </VCard>
        </div>
        <VCard title="🏆 Топ рефереров">
            <div class="space-y-2">
                <div v-for="(r, i) in topReferrers" :key="r.name" class="flex items-center gap-3 p-3 rounded-lg" style="background:var(--t-bg)">
                    <span class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold" :style="`background:var(--t-primary-dim);color:var(--t-primary)`">{{ i + 1 }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium text-sm" style="color:var(--t-text)">{{ r.name }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">Последний реферал: {{ r.lastReferral }}</div>
                    </div>
                    <div class="text-center">
                        <div class="font-bold text-sm" style="color:var(--t-primary)">{{ r.referrals }}</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">рефералов</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-sm" style="color:var(--t-primary)">{{ fmt(r.earned) }} ₽</div>
                        <div class="text-[10px]" style="color:var(--t-text-3)">заработано</div>
                    </div>
                    <VButton size="sm" variant="outline" @click="emit('open-client', r)">👤</VButton>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ CASHBACK ═══ -->
    <div v-if="activeTab === 'cashback'" class="space-y-4">
        <div class="grid lg:grid-cols-2 gap-4">
            <VCard title="⚙️ Настройки кэшбэка">
                <div class="space-y-4">
                    <VInput label="Мин. сумма для кэшбэка (₽)" v-model.number="cashbackSettings.minAmount" type="number" />
                    <VInput label="Макс. кэшбэк (%)" v-model.number="cashbackSettings.maxCashbackPercent" type="number" />
                    <VInput label="Срок действия бонусов (дней)" v-model.number="cashbackSettings.expirationDays" type="number" />
                    <div class="flex items-center justify-between">
                        <span class="text-sm" style="color:var(--t-text)">Оплата бонусами</span>
                        <input type="checkbox" v-model="cashbackSettings.canPayWithBonus" class="w-5 h-5 accent-(--t-primary)">
                    </div>
                    <VInput v-if="cashbackSettings.canPayWithBonus" label="Макс. доля бонусов в оплате (%)" v-model.number="cashbackSettings.maxBonusPayPercent" type="number" />
                    <VButton @click="emit('save-settings', { type: 'cashback', data: { ...cashbackSettings } }); toast('Настройки кэшбэка сохранены')">💾 Сохранить</VButton>
                </div>
            </VCard>
            <VCard title="📊 Кэшбэк по тирам">
                <div class="space-y-3">
                    <div v-for="tier in tiers" :key="tier.id" class="flex items-center gap-3">
                        <span class="text-xl">{{ tier.icon }}</span>
                        <span class="flex-1 text-sm font-medium" style="color:var(--t-text)">{{ tier.name }}</span>
                        <span class="font-bold" style="color:var(--t-primary)">{{ tier.cashbackPercent }}%</span>
                    </div>
                </div>
            </VCard>
        </div>
        <VCard title="📋 Последние начисления кэшбэка">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="border-bottom:2px solid var(--t-border)">
                            <th class="text-left p-2" style="color:var(--t-text-3)">Дата</th>
                            <th class="text-left p-2" style="color:var(--t-text-3)">Клиент</th>
                            <th class="text-left p-2" style="color:var(--t-text-3)">Услуга</th>
                            <th class="text-right p-2" style="color:var(--t-text-3)">Сумма</th>
                            <th class="text-right p-2" style="color:var(--t-text-3)">Кэшбэк</th>
                            <th class="text-center p-2" style="color:var(--t-text-3)">Тир</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="h in cashbackHistory" :key="h.date + h.client" class="transition-colors" :style="{ borderBottom: '1px solid var(--t-border)' }" @mouseenter="$event.target.style.background = t.cardHover" @mouseleave="$event.target.style.background = ''">
                            <td class="p-2 font-mono text-xs" style="color:var(--t-text-3)">{{ h.date }}</td>
                            <td class="p-2 font-medium cursor-pointer" style="color:var(--t-text)" @click="emit('open-client', h)">{{ h.client }}</td>
                            <td class="p-2" style="color:var(--t-text-2)">{{ h.service }}</td>
                            <td class="p-2 text-right font-bold" style="color:var(--t-text)">{{ fmt(h.amount) }} ₽</td>
                            <td class="p-2 text-right font-bold" style="color:var(--t-primary)">+{{ fmt(h.cashback) }} ₽</td>
                            <td class="p-2 text-center"><VBadge color="blue" size="sm">{{ h.tier }}</VBadge></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>
    </div>

    <!-- ═══ VIP ═══ -->
    <div v-if="activeTab === 'vip'" class="space-y-4">
        <VCard title="👑 VIP-привилегии">
            <div class="grid md:grid-cols-2 gap-3">
                <div v-for="perk in vipPerks" :key="perk.name" class="p-3 rounded-lg border flex items-center gap-3" style="background:var(--t-bg);border-color:var(--t-border)">
                    <div class="flex-1">
                        <div class="font-medium text-sm" style="color:var(--t-text)">{{ perk.name }}</div>
                        <div class="text-xs mt-1" style="color:var(--t-text-3)">{{ perk.description }}</div>
                    </div>
                    <input type="checkbox" v-model="perk.isActive" class="w-5 h-5 accent-(--t-primary)">
                </div>
            </div>
        </VCard>
        <VCard title="💎 VIP-клиенты">
            <div class="space-y-3">
                <div v-for="vc in vipClients" :key="vc.id" class="p-4 rounded-xl border flex items-center gap-4 flex-wrap cursor-pointer hover:shadow-lg transition-shadow" style="background:var(--t-surface);border-color:var(--t-border)" @click="emit('open-client', vc)">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold" style="background:var(--t-primary-dim);color:var(--t-primary)">{{ vc.name.charAt(0) }}</div>
                    <div class="flex-1 min-w-[180px]">
                        <div class="font-bold" style="color:var(--t-text)">{{ vc.name }}</div>
                        <div class="text-xs" style="color:var(--t-text-3)">{{ vc.phone }} · Мастер: {{ vc.favMaster }}</div>
                    </div>
                    <div class="grid grid-cols-4 gap-3 text-center text-xs">
                        <div><div class="font-bold" style="color:var(--t-primary)">{{ fmt(vc.totalSpend) }} ₽</div>Потрачено</div>
                        <div><div class="font-bold" style="color:var(--t-primary)">{{ vc.visits }}</div>Визитов</div>
                        <div><div class="font-bold" style="color:var(--t-primary)">{{ fmt(vc.bonus) }} ₽</div>Бонусы</div>
                        <div><div class="font-bold" style="color:var(--t-text-2)">{{ vc.lastVisit }}</div>Последний</div>
                    </div>
                    <VBadge :color="vc.tier === 'Платина' ? 'purple' : 'yellow'" size="sm">{{ vc.tier }}</VBadge>
                </div>
            </div>
        </VCard>
    </div>

    <!-- ═══ ANALYTICS ═══ -->
    <div v-if="activeTab === 'analytics'" class="space-y-4">
        <VCard title="📈 Динамика программы лояльности">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr style="border-bottom:2px solid var(--t-border)">
                            <th class="text-left p-2" style="color:var(--t-text-3)">Месяц</th>
                            <th class="text-right p-2" style="color:var(--t-text-3)">Новых участников</th>
                            <th class="text-right p-2" style="color:var(--t-text-3)">Бонусов начислено</th>
                            <th class="text-right p-2" style="color:var(--t-text-3)">Бонусов потрачено</th>
                            <th class="text-right p-2" style="color:var(--t-text-3)">Retention (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="d in monthlyLoyaltyData" :key="d.month" :style="{ borderBottom: '1px solid var(--t-border)' }">
                            <td class="p-2 font-medium" style="color:var(--t-text)">{{ d.month }}</td>
                            <td class="p-2 text-right" style="color:var(--t-text-2)">+{{ d.newMembers }}</td>
                            <td class="p-2 text-right font-bold" style="color:var(--t-primary)">{{ fmt(d.bonusesEarned) }} ₽</td>
                            <td class="p-2 text-right" style="color:var(--t-text-2)">{{ fmt(d.bonusesSpent) }} ₽</td>
                            <td class="p-2 text-right font-bold" style="color:var(--t-primary)">{{ d.retention }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>
        <div class="grid lg:grid-cols-2 gap-4">
            <VCard title="📊 Бонусы: начислено vs потрачено">
                <div class="space-y-2">
                    <div v-for="d in monthlyLoyaltyData" :key="d.month" class="space-y-1">
                        <div class="text-xs font-medium" style="color:var(--t-text-2)">{{ d.month }}</div>
                        <div class="flex gap-1 items-center">
                            <div class="h-3 rounded-full" :style="{ width: (d.bonusesEarned / 1000) + 'px', background: 'var(--t-primary)' }"></div>
                            <span class="text-[10px]" style="color:var(--t-text-3)">{{ fmt(d.bonusesEarned) }}</span>
                        </div>
                        <div class="flex gap-1 items-center">
                            <div class="h-3 rounded-full" :style="{ width: (d.bonusesSpent / 1000) + 'px', background: 'var(--t-accent)' }"></div>
                            <span class="text-[10px]" style="color:var(--t-text-3)">{{ fmt(d.bonusesSpent) }}</span>
                        </div>
                    </div>
                </div>
            </VCard>
            <VCard title="🔄 Retention по месяцам">
                <div class="space-y-3">
                    <div v-for="d in monthlyLoyaltyData" :key="d.month" class="space-y-1">
                        <div class="flex justify-between text-xs">
                            <span style="color:var(--t-text-2)">{{ d.month }}</span>
                            <span class="font-bold" style="color:var(--t-primary)">{{ d.retention }}%</span>
                        </div>
                        <div class="w-full h-2 rounded-full" style="background:var(--t-bg)">
                            <div class="h-full rounded-full transition-all" :style="{ width: d.retention + '%', background: 'var(--t-primary)' }"></div>
                        </div>
                    </div>
                </div>
            </VCard>
        </div>
        <div class="flex justify-end">
            <VButton variant="outline" @click="exportCSV(monthlyLoyaltyData, 'loyalty_analytics')">📥 Экспорт аналитики</VButton>
        </div>
    </div>

    <!-- ═══ MODALS ═══ -->
    <VModal :show="showAddTier" @close="showAddTier = false" title="🏅 Новый тир лояльности">
        <div class="space-y-4">
            <VInput label="Название" v-model="newTier.name" placeholder="Бронза, Серебро..." />
            <VInput label="Мин. сумма покупок (₽)" v-model.number="newTier.minSpend" type="number" />
            <div class="grid grid-cols-2 gap-4">
                <VInput label="Кэшбэк (%)" v-model.number="newTier.cashbackPercent" type="number" />
                <VInput label="Скидка (%)" v-model.number="newTier.discount" type="number" />
            </div>
            <VInput label="Привилегии (через запятую)" v-model="newTier.benefits" placeholder="Бонус за визит, Скидка 5%..." />
            <div class="flex justify-end gap-3">
                <VButton variant="outline" @click="showAddTier = false">Отмена</VButton>
                <VButton @click="saveTier">✅ Создать</VButton>
            </div>
        </div>
    </VModal>

    <VModal :show="showAddRule" @close="showAddRule = false" title="📋 Новое бонусное правило">
        <div class="space-y-4">
            <VInput label="Название" v-model="newRule.name" placeholder="Бонус за..." />
            <div>
                <label class="block text-sm font-medium mb-1" style="color:var(--t-text)">Тип правила</label>
                <select v-model="newRule.type" class="w-full rounded-lg px-3 py-2 border text-sm" style="background:var(--t-surface);border-color:var(--t-border);color:var(--t-text)">
                    <option v-for="rt in ruleTypes" :key="rt.key" :value="rt.key">{{ rt.label }}</option>
                </select>
            </div>
            <VInput label="Сумма бонуса (₽)" v-model.number="newRule.amount" type="number" />
            <div class="flex justify-end gap-3">
                <VButton variant="outline" @click="showAddRule = false">Отмена</VButton>
                <VButton @click="saveRule">✅ Создать</VButton>
            </div>
        </div>
    </VModal>
</div>

<!-- Toast -->
<Teleport to="body">
    <Transition name="fade">
        <div v-if="showToast" class="fixed bottom-6 right-6 z-[9999] px-5 py-3 rounded-xl shadow-2xl text-sm font-medium" style="background:var(--t-primary);color:#fff">
            {{ toastMessage }}
        </div>
    </Transition>
</Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .3s, transform .3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(12px); }
</style>
