<script setup>
import { ref, computed } from 'vue';

const activeTab = ref('balance');
const tabs = [
    { key: 'balance', label: 'Баланс' },
    { key: 'transactions', label: 'Транзакции' },
    { key: 'holds', label: 'Холды' },
];

const metrics = ref([
    { label: 'Текущий баланс', value: '8 420 510 ₽', trend: '+4.3%', icon: '💰' },
    { label: 'Hold amount', value: '612 000 ₽', trend: '+1.1%', icon: '🔒' },
    { label: 'Выплаты сегодня', value: '147', trend: '+9', icon: '🏦' },
    { label: 'Ошибки операций', value: '0', trend: '-2', icon: '🛡️' },
]);

const transactions = ref([
    { id: 1, type: 'credit', amount: 15000, description: 'Оплата заказа #1001', status: 'completed', createdAt: '2026-04-15 10:30', balanceAfter: 8420510 },
    { id: 2, type: 'debit', amount: 5000, description: 'Вывод на карту', status: 'completed', createdAt: '2026-04-15 09:15', balanceAfter: 8405510 },
    { id: 3, type: 'hold', amount: 2500, description: 'Резерв заказа #1002', status: 'active', createdAt: '2026-04-14 16:45', balanceAfter: 8403010 },
    { id: 4, type: 'credit', amount: 7500, description: 'Возврат #999', status: 'completed', createdAt: '2026-04-14 11:20', balanceAfter: 8405510 },
    { id: 5, type: 'release', amount: 2500, description: 'Снятие резерва #1000', status: 'completed', createdAt: '2026-04-13 14:00', balanceAfter: 8398010 },
]);

const holds = ref([
    { id: 1, amount: 2500, orderId: 1002, description: 'Резерв заказа #1002', createdAt: '2026-04-14 16:45', expiresAt: '2026-04-14 17:05', status: 'active' },
    { id: 2, amount: 10000, orderId: 1003, description: 'Резерв заказа #1003', createdAt: '2026-04-14 15:30', expiresAt: '2026-04-14 15:50', status: 'released' },
    { id: 3, amount: 5000, orderId: 1000, description: 'Резерв заказа #1000', createdAt: '2026-04-13 14:00', expiresAt: '2026-04-13 14:20', status: 'released' },
]);

const typeLabels = {
    credit: { label: 'Зачисление', color: 'text-green-600', icon: '↑' },
    debit: { label: 'Списание', color: 'text-red-600', icon: '↓' },
    hold: { label: 'Холд', color: 'text-yellow-600', icon: '🔒' },
    release: { label: 'Снятие холда', color: 'text-blue-600', icon: '🔓' },
};

const statusLabels = {
    completed: { label: 'Завершено', color: 'text-green-600', bg: 'bg-green-50' },
    active: { label: 'Активно', color: 'text-yellow-600', bg: 'bg-yellow-50' },
    released: { label: 'Снят', color: 'text-blue-600', bg: 'bg-blue-50' },
    failed: { label: 'Ошибка', color: 'text-red-600', bg: 'bg-red-50' },
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

        <!-- Balance Tab -->
        <div v-if="activeTab === 'balance'" class="rounded-2xl border p-5" style="background: var(--t-surface); border-color: var(--t-border);">
            <h3 class="text-lg font-semibold mb-4" style="color: var(--t-text);">Детализация баланса</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 rounded-xl" style="background: var(--t-surface-alt);">
                    <div class="text-sm" style="color: var(--t-text-2);">Доступно</div>
                    <div class="text-2xl font-bold mt-1" style="color: var(--t-text);">7 808 510 ₽</div>
                </div>
                <div class="p-4 rounded-xl" style="background: var(--t-surface-alt);">
                    <div class="text-sm" style="color: var(--t-text-2);">В резерве</div>
                    <div class="text-2xl font-bold mt-1" style="color: var(--t-primary);">612 000 ₽</div>
                </div>
            </div>
        </div>

        <!-- Transactions Tab -->
        <div v-if="activeTab === 'transactions'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Тип</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Описание</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Сумма</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Баланс после</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Статус</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="tx in transactions" :key="tx.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ tx.id }}</td>
                        <td class="px-4 py-3">
                            <span class="flex items-center gap-1" :class="typeLabels[tx.type].color">
                                {{ typeLabels[tx.type].icon }} {{ typeLabels[tx.type].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ tx.description }}</td>
                        <td class="px-4 py-3 font-semibold" :class="typeLabels[tx.type].color">
                            {{ tx.type === 'credit' || tx.type === 'release' ? '+' : '-' }}{{ formatAmount(tx.amount) }}
                        </td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ formatAmount(tx.balanceAfter) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusLabels[tx.status]">
                                {{ statusLabels[tx.status].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ tx.createdAt }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Holds Tab -->
        <div v-if="activeTab === 'holds'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Заказ</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Описание</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Сумма</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Создан</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Истекает</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="hold in holds" :key="hold.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ hold.id }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ hold.orderId }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ hold.description }}</td>
                        <td class="px-4 py-3 font-semibold" style="color: var(--t-primary);">{{ formatAmount(hold.amount) }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ hold.createdAt }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ hold.expiresAt }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusLabels[hold.status]">
                                {{ statusLabels[hold.status].label }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
