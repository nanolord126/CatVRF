<script setup>
/**
 * WalletFinance — полное управление кошельком, балансом, транзакциями,
 * бонусами, выплатами. Интеграция с B2C/B2B.
 */
import { ref } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VStatCard from '../UI/VStatCard.vue';
import VModal from '../UI/VModal.vue';
import VInput from '../UI/VInput.vue';

const activeTab = ref('overview');
const tabs = [
    { key: 'overview', label: 'Обзор' },
    { key: 'transactions', label: 'Транзакции', badge: 12 },
    { key: 'pnl', label: 'P&L' },
    { key: 'invoices', label: 'Счета и акты', badge: 3 },
    { key: 'bonuses', label: 'Бонусы' },
    { key: 'payouts', label: 'Выплаты' },
    { key: 'autopay', label: 'Авто-платежи' },
];

const showWithdrawModal = ref(false);
const showDepositModal = ref(false);

const pnl = ref({
    revenue: 1245000, cogs: 498000, grossProfit: 747000, grossMargin: 60.0,
    expenses: { commission: 174300, delivery: 62250, marketing: 87150, salary: 185000, other: 12400 },
    netProfit: 225900, netMargin: 18.1,
    prevNetProfit: 198500, prevRevenue: 1089000,
});

const cashFlow = ref([
    { month: 'Янв', income: 980000, expenses: 720000 },
    { month: 'Фев', income: 1050000, expenses: 780000 },
    { month: 'Мар', income: 1180000, expenses: 830000 },
    { month: 'Апр', income: 1245000, expenses: 880000 },
]);

const invoices = ref([
    { id: 'INV-2041', type: 'invoice', client: 'ООО «РестоПро»', amount: 245000, date: '2026-04-08', dueDate: '2026-04-22', status: 'pending' },
    { id: 'ACT-1893', type: 'act', client: 'ИП Козлова А.С.', amount: 87500, date: '2026-04-05', dueDate: null, status: 'signed' },
    { id: 'INV-2040', type: 'invoice', client: 'ООО «Стиль Дома»', amount: 560000, date: '2026-04-03', dueDate: '2026-04-17', status: 'overdue' },
    { id: 'ACT-1892', type: 'act', client: 'ООО «ФудСервис»', amount: 132000, date: '2026-04-01', dueDate: null, status: 'signed' },
    { id: 'INV-2039', type: 'invoice', client: 'ООО «БьютиЛаб»', amount: 195000, date: '2026-03-29', dueDate: '2026-04-12', status: 'paid' },
]);
const invoiceStatusMap = { pending: { text: 'Ожидает', variant: 'warning' }, signed: { text: 'Подписан', variant: 'success' }, overdue: { text: 'Просрочен', variant: 'error' }, paid: { text: 'Оплачен', variant: 'success' } };

const autoPayments = ref([
    { id: 1, name: 'Комиссия платформы', schedule: 'Ежедневно', amount: null, type: 'percent', value: 14, active: true, lastRun: '2026-04-08' },
    { id: 2, name: 'Выплата курьерам', schedule: 'Еженедельно (Пн)', amount: 85000, type: 'fixed', value: null, active: true, lastRun: '2026-04-07' },
    { id: 3, name: 'Аренда склада', schedule: 'Ежемесячно (1-е)', amount: 45000, type: 'fixed', value: null, active: true, lastRun: '2026-04-01' },
    { id: 4, name: 'Подписка OpenAI API', schedule: 'Ежемесячно (15-е)', amount: 12000, type: 'fixed', value: null, active: false, lastRun: '2026-03-15' },
]);

const wallet = {
    balance: 847500,
    hold: 45000,
    available: 802500,
    bonuses: 12400,
    monthlyIncome: 1245000,
    monthlyExpenses: 397500,
    pendingPayouts: 125000,
};

const transactions = [
    { id: 'TXN-9842', type: 'deposit', amount: 45600, description: 'Оплата заказа ORD-20492', date: '2026-04-08 14:35', status: 'completed' },
    { id: 'TXN-9841', type: 'commission', amount: -6384, description: 'Комиссия платформы 14%', date: '2026-04-08 14:35', status: 'completed' },
    { id: 'TXN-9840', type: 'payout', amount: -150000, description: 'Вывод на расчётный счёт', date: '2026-04-07 10:00', status: 'processing' },
    { id: 'TXN-9839', type: 'bonus', amount: 2500, description: 'Бонус за реферал @kozlova', date: '2026-04-06 18:20', status: 'completed' },
    { id: 'TXN-9838', type: 'deposit', amount: 123500, description: 'B2B заказ B-2041', date: '2026-04-06 12:00', status: 'completed' },
    { id: 'TXN-9837', type: 'refund', amount: -8900, description: 'Возврат по заказу ORD-20480', date: '2026-04-05 16:30', status: 'completed' },
];

const typeIcons = { deposit: '📥', commission: '💸', payout: '📤', bonus: '🎁', refund: '↩️', withdrawal: '🏦', hold: '🔒', release_hold: '🔓' };
const typeLabels = { deposit: 'Пополнение', commission: 'Комиссия', payout: 'Вывод', bonus: 'Бонус', refund: 'Возврат', withdrawal: 'Списание', hold: 'Холд', release_hold: 'Разблокировка' };
const typeColors = { deposit: 'text-emerald-400', commission: 'text-rose-400', payout: 'text-orange-400', bonus: 'text-amber-400', refund: 'text-rose-400', withdrawal: 'text-rose-400' };
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl lg:text-2xl font-bold text-(--t-text)">💳 Кошелёк</h1>
                <p class="text-xs text-(--t-text-3)">Баланс, транзакции, бонусы и выплаты</p>
            </div>
            <div class="flex items-center gap-2">
                <VButton variant="secondary" size="sm" @click="showDepositModal = true">📥 Пополнить</VButton>
                <VButton variant="primary" size="sm" @click="showWithdrawModal = true">📤 Вывести</VButton>
            </div>
        </div>

        <!-- Balance Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Main Balance -->
            <div class="md:col-span-2 relative overflow-hidden rounded-2xl bg-linear-to-br from-(--t-primary)/10 via-(--t-surface) to-(--t-accent)/5 border border-(--t-primary)/15 p-6">
                <div class="relative z-10">
                    <div class="text-xs text-(--t-text-3) uppercase tracking-wider mb-1">Текущий баланс</div>
                    <div class="text-4xl font-bold text-(--t-text) mb-4">{{ Number(wallet.balance).toLocaleString('ru') }}<span class="text-lg text-(--t-text-3) ml-1">₽</span></div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <div class="text-[10px] text-(--t-text-3)">Доступно</div>
                            <div class="text-sm font-bold text-emerald-400">{{ Number(wallet.available).toLocaleString('ru') }} ₽</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-(--t-text-3)">Холд</div>
                            <div class="text-sm font-bold text-amber-400">{{ Number(wallet.hold).toLocaleString('ru') }} ₽</div>
                        </div>
                        <div>
                            <div class="text-[10px] text-(--t-text-3)">На выводе</div>
                            <div class="text-sm font-bold text-orange-400">{{ Number(wallet.pendingPayouts).toLocaleString('ru') }} ₽</div>
                        </div>
                    </div>
                </div>
                <div class="absolute -right-20 -top-20 w-64 h-64 rounded-full bg-(--t-primary)/5 blur-3xl pointer-events-none" />
            </div>

            <!-- Bonuses -->
            <div class="relative overflow-hidden rounded-2xl bg-linear-to-br from-amber-500/10 to-orange-500/5 border border-amber-500/15 p-6 flex flex-col justify-between">
                <div>
                    <div class="text-xs text-amber-400/60 uppercase tracking-wider mb-1">Бонусы</div>
                    <div class="text-3xl font-bold text-amber-200">{{ Number(wallet.bonuses).toLocaleString('ru') }}<span class="text-lg text-amber-400/40 ml-1">₽</span></div>
                </div>
                <VButton variant="b2b" size="sm" full-width class="mt-4">Потратить бонусы</VButton>
            </div>
        </div>

        <!-- Revenue Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <VStatCard title="Доход за месяц" :value="`${(wallet.monthlyIncome/1000).toFixed(0)}k ₽`" icon="📈" :trend="18.5" color="emerald" clickable />
            <VStatCard title="Расходы за месяц" :value="`${(wallet.monthlyExpenses/1000).toFixed(0)}k ₽`" icon="📉" :trend="-5.2" color="rose" clickable />
            <VStatCard title="Комиссия платформы" value="14%" icon="💸" color="amber" />
            <VStatCard title="Бонус за рефералы" value="+2 500 ₽" icon="🎁" :trend="12" color="indigo" clickable />
        </div>

        <!-- Tabs -->
        <VTabs :tabs="tabs" v-model="activeTab" variant="underline" />

        <!-- Transactions -->
        <template v-if="activeTab === 'overview' || activeTab === 'transactions'">
            <VCard title="История транзакций" subtitle="Все операции по кошельку">
                <div class="space-y-2">
                    <div v-for="txn in transactions" :key="txn.id"
                         class="flex items-center gap-3 p-3 rounded-xl hover:bg-(--t-card-hover) transition-all cursor-pointer active:scale-[0.99] group"
                    >
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0" :class="txn.amount > 0 ? 'bg-emerald-500/10' : 'bg-rose-500/10'">
                            {{ typeIcons[txn.type] || '💰' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-(--t-text) truncate">{{ txn.description }}</span>
                                <VBadge v-if="txn.status === 'processing'" text="В обработке" variant="warning" size="xs" dot />
                            </div>
                            <div class="flex items-center gap-2 text-[10px] text-(--t-text-3)">
                                <span class="font-mono">{{ txn.id }}</span>
                                <span>•</span>
                                <span>{{ txn.date }}</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-bold" :class="typeColors[txn.type] || 'text-(--t-text)'">
                                {{ txn.amount > 0 ? '+' : '' }}{{ Number(txn.amount).toLocaleString('ru') }} ₽
                            </div>
                            <div class="text-[10px] text-(--t-text-3)">{{ typeLabels[txn.type] }}</div>
                        </div>
                    </div>
                </div>
                <template #footer>
                    <VButton variant="ghost" size="sm" full-width>Показать все транзакции →</VButton>
                </template>
            </VCard>
        </template>

        <!-- Bonuses Tab -->
        <template v-if="activeTab === 'bonuses'">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <VCard title="🎁 Бонусная программа">
                    <div class="space-y-4">
                        <div v-for="rule in [
                            {icon:'🤝',title:'Реферальный бонус',desc:'500 ₽ за каждого приведённого друга',amount:'+500 ₽'},
                            {icon:'📦',title:'Бонус за оборот',desc:'1% от оборота > 100 000 ₽/мес',amount:'+1%'},
                            {icon:'⭐',title:'Бонус за отзывы',desc:'100 ₽ за каждый отзыв с фото',amount:'+100 ₽'},
                            {icon:'🔄',title:'Кешбэк покупки',desc:'До 5% кешбэка на все покупки',amount:'до 5%'},
                        ]" :key="rule.title" class="flex items-center gap-3 p-3 rounded-xl border border-(--t-border) hover:border-amber-500/20 transition-all cursor-pointer active:scale-[0.98]">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 flex items-center justify-center text-xl shrink-0">{{ rule.icon }}</div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-(--t-text)">{{ rule.title }}</div>
                                <div class="text-xs text-(--t-text-3)">{{ rule.desc }}</div>
                            </div>
                            <div class="text-sm font-bold text-amber-400 shrink-0">{{ rule.amount }}</div>
                        </div>
                    </div>
                </VCard>

                <VCard title="📊 История бонусов">
                    <div class="space-y-3">
                        <div v-for="b in [
                            {type:'referral',amount:500,desc:'Реферал @kozlova',date:'2026-04-06'},
                            {type:'turnover',amount:2000,desc:'Оборот > 100k',date:'2026-04-01'},
                            {type:'promo',amount:1000,desc:'Промоакция «Весна 2026»',date:'2026-03-28'},
                        ]" :key="b.date" class="flex items-center justify-between p-3 rounded-xl hover:bg-(--t-card-hover) transition-colors cursor-pointer active:scale-[0.99]">
                            <div>
                                <div class="text-sm text-(--t-text)">{{ b.desc }}</div>
                                <div class="text-[10px] text-(--t-text-3)">{{ b.date }}</div>
                            </div>
                            <span class="text-sm font-bold text-amber-400">+{{ b.amount }} ₽</span>
                        </div>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- Payouts Tab -->
        <template v-if="activeTab === 'payouts'">
            <VCard title="📤 Выплаты" subtitle="История выводов и настройки">
                <div class="space-y-3">
                    <div v-for="p in [
                        {id:'PAY-412',amount:150000,bank:'Тинькофф ****5678',status:'processing',date:'2026-04-07 10:00'},
                        {id:'PAY-411',amount:200000,bank:'Тинькофф ****5678',status:'completed',date:'2026-04-01 09:00'},
                        {id:'PAY-410',amount:175000,bank:'Сбербанк ****1234',status:'completed',date:'2026-03-25 09:00'},
                    ]" :key="p.id" class="flex items-center gap-3 p-4 rounded-xl border border-(--t-border) hover:border-(--t-primary)/20 transition-all cursor-pointer active:scale-[0.99]">
                        <div class="w-10 h-10 rounded-xl bg-(--t-primary-dim) flex items-center justify-center text-lg shrink-0">📤</div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-(--t-text)">Вывод на {{ p.bank }}</div>
                            <div class="flex items-center gap-2 text-[10px] text-(--t-text-3)">
                                <span class="font-mono">{{ p.id }}</span>
                                <span>•</span>
                                <span>{{ p.date }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-(--t-text)">-{{ Number(p.amount).toLocaleString('ru') }} ₽</div>
                            <VBadge :text="p.status === 'processing' ? 'В обработке' : 'Завершён'" :variant="p.status === 'processing' ? 'warning' : 'success'" size="xs" />
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- P&L Tab -->
        <template v-if="activeTab === 'pnl'">
            <div class="space-y-4">
                <!-- Summary -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                    <VStatCard title="Выручка" :value="`${(pnl.revenue/1000).toFixed(0)}k ₽`" icon="💰" :trend="((pnl.revenue - pnl.prevRevenue) / pnl.prevRevenue * 100).toFixed(1)" color="emerald" />
                    <VStatCard title="Валовая прибыль" :value="`${(pnl.grossProfit/1000).toFixed(0)}k ₽`" icon="📊" color="sky" />
                    <VStatCard title="Чистая прибыль" :value="`${(pnl.netProfit/1000).toFixed(0)}k ₽`" icon="🏆" :trend="((pnl.netProfit - pnl.prevNetProfit) / pnl.prevNetProfit * 100).toFixed(1)" color="primary" />
                    <VStatCard title="Маржинальность" :value="`${pnl.netMargin}%`" icon="📈" color="violet" />
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Expenses Breakdown -->
                    <VCard title="💸 Структура расходов">
                        <div class="space-y-3">
                            <div v-for="(value, key) in pnl.expenses" :key="key" class="space-y-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-(--t-text-2)">
                                        {{ {commission:'Комиссия платформы',delivery:'Доставка',marketing:'Маркетинг',salary:'Зарплаты',other:'Прочее'}[key] }}
                                    </span>
                                    <span class="font-bold text-(--t-text)">{{ (value/1000).toFixed(0) }}k ₽</span>
                                </div>
                                <div class="h-2 rounded-full bg-(--t-border) overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500" :class="{
                                        'bg-rose-500': key==='commission',
                                        'bg-sky-500': key==='delivery',
                                        'bg-violet-500': key==='marketing',
                                        'bg-amber-500': key==='salary',
                                        'bg-gray-500': key==='other',
                                    }" :style="{ width: (value / pnl.revenue * 100) + '%' }" />
                                </div>
                            </div>
                        </div>
                    </VCard>

                    <!-- Cash Flow Chart -->
                    <VCard title="📉 Движение денежных средств">
                        <div class="space-y-3">
                            <div v-for="m in cashFlow" :key="m.month" class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-medium text-(--t-text)">{{ m.month }}</span>
                                    <span class="text-emerald-400 font-bold">+{{ (m.income/1000).toFixed(0) }}k</span>
                                </div>
                                <div class="relative h-3 rounded-full bg-(--t-border) overflow-hidden">
                                    <div class="absolute inset-y-0 left-0 rounded-full bg-emerald-500/60" :style="{ width: (m.income / 1500000 * 100) + '%' }" />
                                    <div class="absolute inset-y-0 left-0 rounded-full bg-rose-500/60" :style="{ width: (m.expenses / 1500000 * 100) + '%' }" />
                                </div>
                                <div class="flex justify-between text-[10px] text-(--t-text-3)">
                                    <span>Расход: {{ (m.expenses/1000).toFixed(0) }}k</span>
                                    <span class="text-emerald-400">Прибыль: {{ ((m.income - m.expenses)/1000).toFixed(0) }}k</span>
                                </div>
                            </div>
                        </div>
                    </VCard>
                </div>
            </div>
        </template>

        <!-- Invoices & Acts Tab -->
        <template v-if="activeTab === 'invoices'">
            <VCard title="📄 Счета и закрывающие документы">
                <template #header-action>
                    <div class="flex gap-2">
                        <VButton variant="ghost" size="xs">📥 Скачать все</VButton>
                        <VButton variant="primary" size="xs">+ Создать счёт</VButton>
                    </div>
                </template>
                <div class="space-y-2">
                    <div v-for="inv in invoices" :key="inv.id"
                         class="flex items-center gap-3 p-4 rounded-xl border border-(--t-border) hover:border-(--t-primary)/20 transition-all cursor-pointer active:scale-[0.99]"
                    >
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0"
                             :class="inv.type === 'invoice' ? 'bg-sky-500/10' : 'bg-emerald-500/10'">
                            {{ inv.type === 'invoice' ? '📋' : '✅' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-(--t-text) truncate">{{ inv.client }}</span>
                                <VBadge :text="invoiceStatusMap[inv.status]?.text" :variant="invoiceStatusMap[inv.status]?.variant" size="xs" />
                            </div>
                            <div class="flex items-center gap-2 text-[10px] text-(--t-text-3)">
                                <span class="font-mono">{{ inv.id }}</span>
                                <span>•</span>
                                <span>{{ inv.date }}</span>
                                <template v-if="inv.dueDate">
                                    <span>•</span>
                                    <span :class="inv.status === 'overdue' ? 'text-rose-400' : ''">Оплата до {{ inv.dueDate }}</span>
                                </template>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-sm font-bold text-(--t-text)">{{ Number(inv.amount).toLocaleString('ru') }} ₽</div>
                            <div class="text-[10px] text-(--t-text-3)">{{ inv.type === 'invoice' ? 'Счёт' : 'Акт' }}</div>
                        </div>
                        <div class="flex gap-1 shrink-0">
                            <button class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-(--t-card-hover) transition-colors text-sm">📥</button>
                            <button class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-(--t-card-hover) transition-colors text-sm">🖨️</button>
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- Auto-Payments Tab -->
        <template v-if="activeTab === 'autopay'">
            <VCard title="🔄 Автоматические платежи" subtitle="Регулярные списания и подписки">
                <template #header-action>
                    <VButton variant="primary" size="xs">+ Добавить</VButton>
                </template>
                <div class="space-y-2">
                    <div v-for="ap in autoPayments" :key="ap.id"
                         :class="['flex items-center gap-3 p-4 rounded-xl border transition-all',
                                  ap.active ? 'border-(--t-border) hover:border-(--t-primary)/20' : 'border-(--t-border)/50 opacity-50']"
                    >
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0"
                             :class="ap.active ? 'bg-(--t-primary-dim)' : 'bg-(--t-card-hover)'">
                            {{ ap.type === 'percent' ? '📊' : '💳' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-(--t-text)">{{ ap.name }}</div>
                            <div class="text-[10px] text-(--t-text-3)">
                                {{ ap.schedule }} • Последний: {{ ap.lastRun }}
                            </div>
                        </div>
                        <div class="text-right shrink-0 mr-2">
                            <div class="text-sm font-bold text-(--t-text)">
                                {{ ap.type === 'percent' ? ap.value + '%' : Number(ap.amount).toLocaleString('ru') + ' ₽' }}
                            </div>
                        </div>
                        <button
                            class="relative w-11 h-6 rounded-full transition-colors shrink-0"
                            :class="ap.active ? 'bg-emerald-500' : 'bg-(--t-border)'"
                            @click="ap.active = !ap.active"
                        >
                            <div class="absolute top-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform"
                                 :class="ap.active ? 'translate-x-5.5' : 'translate-x-0.5'" />
                        </button>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- Withdraw Modal -->
        <VModal v-model="showWithdrawModal" title="Вывод средств" size="md">
            <div class="space-y-4">
                <div class="p-4 rounded-xl bg-(--t-card-hover) text-center">
                    <div class="text-xs text-(--t-text-3)">Доступно для вывода</div>
                    <div class="text-2xl font-bold text-emerald-400">{{ Number(wallet.available).toLocaleString('ru') }} ₽</div>
                </div>
                <VInput label="Сумма вывода" type="number" placeholder="0 ₽" required />
                <VInput label="Расчётный счёт" placeholder="Тинькофф ****5678" readonly />
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showWithdrawModal = false">Отмена</VButton>
                <VButton variant="primary">Вывести</VButton>
            </template>
        </VModal>

        <!-- Deposit Modal -->
        <VModal v-model="showDepositModal" title="Пополнение баланса" size="md">
            <div class="space-y-4">
                <VInput label="Сумма пополнения" type="number" placeholder="0 ₽" required />
                <div class="grid grid-cols-3 gap-2">
                    <button v-for="amount in [10000, 50000, 100000]" :key="amount"
                            class="p-2 rounded-xl border border-(--t-border) text-sm font-medium text-(--t-text) hover:border-(--t-primary) hover:bg-(--t-primary-dim) transition-all cursor-pointer active:scale-95"
                    >
                        {{ (amount/1000).toFixed(0) }}k ₽
                    </button>
                </div>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showDepositModal = false">Отмена</VButton>
                <VButton variant="primary">Пополнить</VButton>
            </template>
        </VModal>
    </div>
</template>
