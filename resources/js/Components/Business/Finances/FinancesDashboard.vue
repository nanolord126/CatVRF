<script setup>
import { ref, computed } from 'vue';

const activeTab = ref('overview');
const tabs = [
    { key: 'overview', label: 'Обзор' },
    { key: 'pnl', label: 'P&L' },
    { key: 'cashflow', label: 'Cashflow' },
    { key: 'reports', label: 'Отчёты' },
];

const metrics = ref([
    { label: 'Cashflow 30д', value: '42.8 млн ₽', trend: '+9.3%', icon: '💸' },
    { label: 'Маржа', value: '18.4%', trend: '+1.1%', icon: '📊' },
    { label: 'Payout SLA', value: '96.7%', trend: '+0.8%', icon: '🏦' },
    { label: 'Chargeback', value: '0.7%', trend: '-0.1%', icon: '🛡️' },
]);

const pnlData = ref([
    { period: '2026-01', revenue: 12500000, cost: 10250000, grossProfit: 2250000, operatingCosts: 1800000, netProfit: 450000, margin: 3.6 },
    { period: '2026-02', revenue: 13800000, cost: 11280000, grossProfit: 2520000, operatingCosts: 1950000, netProfit: 570000, margin: 4.1 },
    { period: '2026-03', revenue: 15200000, cost: 12400000, grossProfit: 2800000, operatingCosts: 2100000, netProfit: 700000, margin: 4.6 },
    { period: '2026-04', revenue: 16800000, cost: 13700000, grossProfit: 3100000, operatingCosts: 2250000, netProfit: 850000, margin: 5.1 },
]);

const cashflowData = ref([
    { period: '2026-01', inflow: 12500000, outflow: 11800000, net: 700000 },
    { period: '2026-02', inflow: 13800000, outflow: 13200000, net: 600000 },
    { period: '2026-03', inflow: 15200000, outflow: 14500000, net: 700000 },
    { period: '2026-04', inflow: 16800000, outflow: 16000000, net: 800000 },
]);

const formatAmount = (amount) => {
    return new Intl.NumberFormat('ru-RU', { 
        style: 'currency', 
        currency: 'RUB',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
};

const formatCompact = (amount) => {
    if (amount >= 1000000) return (amount / 1000000).toFixed(1) + ' млн ₽';
    if (amount >= 1000) return (amount / 1000).toFixed(1) + ' тыс ₽';
    return amount + ' ₽';
};
</script>

<template>
    <section class="space-y-4">
        <!-- Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
            <article v-for="item in metrics" :key="item.label" class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
                <div class="text-xs" style="color: var(--t-text-3);">{{ item.label }}</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">{{ item.value }}</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">{{ item.trend }}</div>
                </div>
                <div class="mt-1 text-xl">{{ item.icon }}</div>
            </article>
        </div>

        <!-- Tabs -->
        <div class="flex flex-wrap gap-2">
            <button 
                v-for="tab in tabs" 
                :key="tab.key" 
                class="px-3 py-1.5 rounded-xl border text-sm cursor-pointer"
                :style="activeTab === tab.key
                    ? 'border-color: var(--t-primary); color: var(--t-primary); background: var(--t-primary-dim);'
                    : 'border-color: var(--t-border); color: var(--t-text-2); background: var(--t-surface);'"
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
            </button>
        </div>

        <!-- Overview Tab -->
        <div v-if="activeTab === 'overview'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-2xl border p-5" style="background: var(--t-surface); border-color: var(--t-border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--t-text);">Ключевые показатели</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm" style="color: var(--t-text-2);">Выручка (30д)</span>
                        <span class="font-semibold" style="color: var(--t-text);">58.3 млн ₽</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm" style="color: var(--t-text-2);">Себестоимость</span>
                        <span class="font-semibold" style="color: var(--t-text);">47.6 млн ₽</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm" style="color: var(--t-text-2);">Валовая прибыль</span>
                        <span class="font-semibold" style="color: var(--t-primary);">10.7 млн ₽</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm" style="color: var(--t-text-2);">Операционные расходы</span>
                        <span class="font-semibold" style="color: var(--t-text);">8.1 млн ₽</span>
                    </div>
                    <div class="border-t pt-3" style="border-color: var(--t-border);">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium" style="color: var(--t-text);">Чистая прибыль</span>
                            <span class="font-bold text-lg" style="color: var(--t-primary);">2.6 млн ₽</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border p-5" style="background: var(--t-surface); border-color: var(--t-border);">
                <h3 class="text-lg font-semibold mb-4" style="color: var(--t-text);">Динамика маржи</h3>
                <div class="space-y-3">
                    <div v-for="item in pnlData" :key="item.period" class="flex items-center gap-3">
                        <span class="text-xs w-16" style="color: var(--t-text-2);">{{ item.period }}</span>
                        <div class="flex-1 h-6 rounded-lg" style="background: var(--t-surface-alt);">
                            <div class="h-full rounded-lg flex items-center justify-end pr-2 text-xs font-medium" 
                                 style="background: var(--t-primary); color: white; width: {{ item.margin * 20 }}%;">
                                {{ item.margin }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- P&L Tab -->
        <div v-if="activeTab === 'pnl'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Период</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Выручка</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Себестоимость</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Вал. прибыль</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Оп. расходы</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Чист. прибыль</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Маржа</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in pnlData" :key="item.period" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3 font-medium" style="color: var(--t-text);">{{ item.period }}</td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-text);">{{ formatCompact(item.revenue) }}</td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-text-2);">{{ formatCompact(item.cost) }}</td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-primary);">{{ formatCompact(item.grossProfit) }}</td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-text-2);">{{ formatCompact(item.operatingCosts) }}</td>
                        <td class="px-4 py-3 text-right font-semibold" style="color: var(--t-primary);">{{ formatCompact(item.netProfit) }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" style="background: var(--t-primary-dim); color: var(--t-primary);">
                                {{ item.margin }}%
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Cashflow Tab -->
        <div v-if="activeTab === 'cashflow'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Период</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Приток</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Отток</th>
                        <th class="text-right px-4 py-3 font-semibold" style="color: var(--t-text-2);">Чистый поток</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in cashflowData" :key="item.period" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3 font-medium" style="color: var(--t-text);">{{ item.period }}</td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-primary);">{{ formatCompact(item.inflow) }}</td>
                        <td class="px-4 py-3 text-right" style="color: var(--t-text-2);">{{ formatCompact(item.outflow) }}</td>
                        <td class="px-4 py-3 text-right font-semibold" :style="item.net >= 0 ? 'color: var(--t-primary);' : 'color: #ef4444;'">
                            {{ item.net >= 0 ? '+' : '' }}{{ formatCompact(item.net) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Reports Tab -->
        <div v-if="activeTab === 'reports'" class="rounded-2xl border p-5" style="background: var(--t-surface); border-color: var(--t-border);">
            <h3 class="text-lg font-semibold mb-4" style="color: var(--t-text);">Финансовые отчёты</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl border cursor-pointer hover:border-opacity-100 transition-all" style="background: var(--t-surface-alt); border-color: var(--t-border);">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-2xl">📊</span>
                        <div>
                            <div class="font-semibold" style="color: var(--t-text);">P&L Report</div>
                            <div class="text-xs" style="color: var(--t-text-2);">Отчёт о прибылях и убытках</div>
                        </div>
                    </div>
                    <div class="text-xs" style="color: var(--t-text-3);">Последнее обновление: 2026-04-15</div>
                </div>
                <div class="p-4 rounded-xl border cursor-pointer hover:border-opacity-100 transition-all" style="background: var(--t-surface-alt); border-color: var(--t-border);">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-2xl">💰</span>
                        <div>
                            <div class="font-semibold" style="color: var(--t-text);">Cashflow Statement</div>
                            <div class="text-xs" style="color: var(--t-text-2);">Движение денежных средств</div>
                        </div>
                    </div>
                    <div class="text-xs" style="color: var(--t-text-3);">Последнее обновление: 2026-04-15</div>
                </div>
                <div class="p-4 rounded-xl border cursor-pointer hover:border-opacity-100 transition-all" style="background: var(--t-surface-alt); border-color: var(--t-border);">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-2xl">🏦</span>
                        <div>
                            <div class="font-semibold" style="color: var(--t-text);">Payout Report</div>
                            <div class="text-xs" style="color: var(--t-text-2);">Отчёт по выплатам</div>
                        </div>
                    </div>
                    <div class="text-xs" style="color: var(--t-text-3);">Последнее обновление: 2026-04-15</div>
                </div>
                <div class="p-4 rounded-xl border cursor-pointer hover:border-opacity-100 transition-all" style="background: var(--t-surface-alt); border-color: var(--t-border);">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-2xl">🎁</span>
                        <div>
                            <div class="font-semibold" style="color: var(--t-text);">Bonus Report</div>
                            <div class="text-xs" style="color: var(--t-text-2);">Отчёт по бонусам</div>
                        </div>
                    </div>
                    <div class="text-xs" style="color: var(--t-text-3);">Последнее обновление: 2026-04-15</div>
                </div>
            </div>
        </div>
    </section>
</template>
