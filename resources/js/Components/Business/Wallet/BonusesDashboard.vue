<script setup>
import { ref, computed } from 'vue';

const activeTab = ref('active');
const tabs = [
    { key: 'active', label: 'Активные', count: 12 },
    { key: 'expired', label: 'Истёкшие', count: 45 },
    { key: 'history', label: 'История', count: 234 },
];

const bonusRules = ref([
    { id: 1, name: 'Первый заказ', type: 'fixed', value: 500, minOrder: 0, startDate: '2026-01-01', endDate: '2026-12-31', status: 'active', usageCount: 1247 },
    { id: 2, name: 'Реферальная программа', type: 'percentage', value: 10, minOrder: 1000, startDate: '2026-01-01', endDate: '2026-12-31', status: 'active', usageCount: 856 },
    { id: 3, name: 'Праздничная скидка', type: 'percentage', value: 15, minOrder: 2000, startDate: '2026-03-01', endDate: '2026-03-08', status: 'expired', usageCount: 342 },
    { id: 4, name: 'Кэшбэк', type: 'percentage', value: 5, minOrder: 500, startDate: '2026-01-01', endDate: '2026-12-31', status: 'active', usageCount: 2341 },
]);

const bonusTransactions = ref([
    { id: 1, userId: 1001, userName: 'Иванов И.И.', type: 'award', amount: 500, reason: 'Первый заказ', createdAt: '2026-04-15 10:30' },
    { id: 2, userId: 1002, userName: 'Петров П.П.', type: 'spend', amount: 200, reason: 'Оплата заказа', createdAt: '2026-04-15 09:15' },
    { id: 3, userId: 1003, userName: 'Сидоров С.С.', type: 'award', amount: 150, reason: 'Реферальный бонус', createdAt: '2026-04-14 16:45' },
    { id: 4, userId: 1004, userName: 'Козлова К.К.', type: 'award', amount: 100, reason: 'Кэшбэк 5%', createdAt: '2026-04-14 11:20' },
]);

const filteredRules = computed(() => {
    if (activeTab.value === 'history') return [];
    return bonusRules.value.filter(r => r.status === activeTab.value);
});

const filteredTransactions = computed(() => {
    if (activeTab.value !== 'history') return [];
    return bonusTransactions.value;
});

const statusLabels = {
    active: { label: 'Активен', color: 'text-green-600', bg: 'bg-green-50' },
    expired: { label: 'Истёк', color: 'text-gray-600', bg: 'bg-gray-50' },
    paused: { label: 'Пауза', color: 'text-yellow-600', bg: 'bg-yellow-50' },
};

const typeLabels = {
    award: { label: 'Начисление', color: 'text-green-600', icon: '↑' },
    spend: { label: 'Списание', color: 'text-red-600', icon: '↓' },
};

const formatAmount = (amount) => {
    return new Intl.NumberFormat('ru-RU', { 
        style: 'currency', 
        currency: 'RUB',
        minimumFractionDigits: 0
    }).format(amount);
};
</script>

<template>
    <section class="space-y-4">
        <!-- Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
            <article class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
                <div class="text-xs" style="color: var(--t-text-3);">Активные правила</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">12</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">+2</div>
                </div>
                <div class="mt-1 text-xl">🎯</div>
            </article>
            <article class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
                <div class="text-xs" style="color: var(--t-text-3);">Начислено за месяц</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">2.4 млн ₽</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">+18%</div>
                </div>
                <div class="mt-1 text-xl">🎁</div>
            </article>
            <article class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
                <div class="text-xs" style="color: var(--t-text-3);">Использовано</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">1.8 млн ₽</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">+15%</div>
                </div>
                <div class="mt-1 text-xl">💸</div>
            </article>
            <article class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
                <div class="text-xs" style="color: var(--t-text-3);">Конверсия</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">75%</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">+3%</div>
                </div>
                <div class="mt-1 text-xl">📈</div>
            </article>
        </div>

        <!-- Tabs -->
        <div class="flex flex-wrap gap-2">
            <button 
                v-for="tab in tabs" 
                :key="tab.key" 
                class="px-3 py-1.5 rounded-xl border text-sm cursor-pointer flex items-center gap-2"
                :style="activeTab === tab.key
                    ? 'border-color: var(--t-primary); color: var(--t-primary); background: var(--t-primary-dim);'
                    : 'border-color: var(--t-border); color: var(--t-text-2); background: var(--t-surface);'"
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
                <span class="px-1.5 py-0.5 rounded-full text-xs" :style="activeTab === tab.key ? 'background: var(--t-primary); color: white;' : 'background: var(--t-border);'">
                    {{ tab.count }}
                </span>
            </button>
        </div>

        <!-- Bonus Rules Table -->
        <div v-if="activeTab !== 'history'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Название</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Тип</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Значение</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Мин. заказ</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Период</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Статус</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Использований</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="rule in filteredRules" :key="rule.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3 font-medium" style="color: var(--t-text);">{{ rule.name }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ rule.type === 'fixed' ? 'Фиксированный' : 'Процент' }}</td>
                        <td class="px-4 py-3 font-semibold" style="color: var(--t-text);">
                            {{ rule.type === 'fixed' ? formatAmount(rule.value) : rule.value + '%' }}
                        </td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ formatAmount(rule.minOrder) }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ rule.startDate }} - {{ rule.endDate }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusLabels[rule.status]">
                                {{ statusLabels[rule.status].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ rule.usageCount }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Transactions Table -->
        <div v-if="activeTab === 'history'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Пользователь</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Тип</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Сумма</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Причина</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="tx in filteredTransactions" :key="tx.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ tx.id }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ tx.userName }}</td>
                        <td class="px-4 py-3">
                            <span class="flex items-center gap-1" :class="typeLabels[tx.type].color">
                                {{ typeLabels[tx.type].icon }} {{ typeLabels[tx.type].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-semibold" :class="typeLabels[tx.type].color">
                            {{ tx.type === 'award' ? '+' : '-' }}{{ formatAmount(tx.amount) }}
                        </td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ tx.reason }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ tx.createdAt }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
