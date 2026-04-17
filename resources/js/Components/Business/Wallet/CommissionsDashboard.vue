<script setup>
import { ref, computed } from 'vue';

const activeTab = ref('b2c');
const tabs = [
    { key: 'b2c', label: 'B2C', count: 12450 },
    { key: 'b2b', label: 'B2B', count: 8230 },
];

const commissionRules = ref([
    { id: 1, type: 'b2c', name: 'Стандартный B2C', rate: 14, minOrder: 0, maxOrder: null, category: 'all', status: 'active' },
    { id: 2, type: 'b2b', name: 'B2B Базовый', rate: 12, minOrder: 10000, maxOrder: 50000, category: 'all', status: 'active' },
    { id: 3, type: 'b2b', name: 'B2B Оптовый', rate: 10, minOrder: 50000, maxOrder: 200000, category: 'all', status: 'active' },
    { id: 4, type: 'b2b', name: 'B2B Крупный опт', rate: 8, minOrder: 200000, maxOrder: null, category: 'all', status: 'active' },
    { id: 5, type: 'b2c', name: 'Электроника', rate: 12, minOrder: 0, maxOrder: null, category: 'electronics', status: 'active' },
    { id: 6, type: 'b2c', name: 'Продукты', rate: 8, minOrder: 0, maxOrder: null, category: 'food', status: 'active' },
]);

const commissionTransactions = ref([
    { id: 1, orderId: 1001, sellerId: 201, sellerName: 'ООО "ТехноМир"', type: 'b2c', amount: 15000, commission: 2100, rate: 14, createdAt: '2026-04-15 10:30' },
    { id: 2, orderId: 1002, sellerId: 202, sellerName: 'ИП Петров', type: 'b2b', amount: 75000, commission: 7500, rate: 10, createdAt: '2026-04-15 09:15' },
    { id: 3, orderId: 1003, sellerId: 203, sellerName: 'ООО "ФудМаркет"', type: 'b2c', amount: 3500, commission: 280, rate: 8, createdAt: '2026-04-14 16:45' },
    { id: 4, orderId: 1004, sellerId: 204, sellerName: 'ООО "ГруппТрейд"', type: 'b2b', amount: 250000, commission: 20000, rate: 8, createdAt: '2026-04-14 11:20' },
]);

const filteredRules = computed(() => {
    return commissionRules.value.filter(r => r.type === activeTab.value);
});

const filteredTransactions = computed(() => {
    return commissionTransactions.value.filter(t => t.type === activeTab.value);
});

const totalCommission = computed(() => {
    return filteredTransactions.value.reduce((sum, t) => sum + t.commission, 0);
});

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
                <div class="text-xs" style="color: var(--t-text-3);">Комиссия ({{ activeTab.toUpperCase() }})</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">{{ formatAmount(totalCommission) }}</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">+12%</div>
                </div>
                <div class="mt-1 text-xl">💰</div>
            </article>
            <article class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
                <div class="text-xs" style="color: var(--t-text-3);">Средний rate</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">{{ activeTab === 'b2c' ? '12.5%' : '10%' }}</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">-0.5%</div>
                </div>
                <div class="mt-1 text-xl">📊</div>
            </article>
            <article class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
                <div class="text-xs" style="color: var(--t-text-3);">Транзакций</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">{{ filteredTransactions.length }}</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">+8%</div>
                </div>
                <div class="mt-1 text-xl">📈</div>
            </article>
            <article class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
                <div class="text-xs" style="color: var(--t-text-3);">Правил</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">{{ filteredRules.length }}</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">+1</div>
                </div>
                <div class="mt-1 text-xl">⚙️</div>
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

        <!-- Commission Rules -->
        <div class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
            <h3 class="text-sm font-semibold mb-3" style="color: var(--t-text);">Правила комиссии {{ activeTab.toUpperCase() }}</h3>
            <div class="space-y-2">
                <div v-for="rule in filteredRules" :key="rule.id" class="flex items-center justify-between p-3 rounded-xl" style="background: var(--t-surface-alt);">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center text-lg" style="background: var(--t-primary-dim); color: var(--t-primary);">
                            {{ rule.rate }}%
                        </div>
                        <div>
                            <div class="text-sm font-medium" style="color: var(--t-text);">{{ rule.name }}</div>
                            <div class="text-xs" style="color: var(--t-text-2);">
                                {{ formatAmount(rule.minOrder) }} - {{ rule.maxOrder ? formatAmount(rule.maxOrder) : '∞' }}
                            </div>
                        </div>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs font-medium" style="background: var(--t-primary-dim); color: var(--t-primary);">
                        {{ rule.category === 'all' ? 'Все категории' : rule.category }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Продавец</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Сумма заказа</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Rate</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Комиссия</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="tx in filteredTransactions" :key="tx.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ tx.orderId }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ tx.sellerName }}</td>
                        <td class="px-4 py-3 font-semibold" style="color: var(--t-text);">{{ formatAmount(tx.amount) }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ tx.rate }}%</td>
                        <td class="px-4 py-3 font-semibold" style="color: var(--t-primary);">{{ formatAmount(tx.commission) }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ tx.createdAt }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
