<script setup>
import { ref, computed } from 'vue';

const activeTab = ref('transactions');
const tabs = [
    { key: 'transactions', label: 'Транзакции' },
    { key: 'providers', label: 'Провайдеры' },
    { key: 'webhooks', label: 'Webhooks' },
];

const metrics = ref([
    { label: 'Инициации платежей', value: '1 248', trend: '+8.1%', icon: '💳' },
    { label: 'Успешные capture', value: '1 192', trend: '+7.6%', icon: '✅' },
    { label: 'Refund rate', value: '1.8%', trend: '-0.4%', icon: '↩️' },
    { label: 'Pending webhook', value: '6', trend: '-2', icon: '📨' },
]);

const transactions = ref([
    { id: 1, orderId: 1001, amount: 15000, currency: 'RUB', provider: 'tinkoff', status: 'captured', idempotencyKey: 'uuid-001', createdAt: '2026-04-15 10:30' },
    { id: 2, orderId: 1002, amount: 8500, currency: 'RUB', provider: 'tochka', status: 'pending', idempotencyKey: 'uuid-002', createdAt: '2026-04-15 09:15' },
    { id: 3, orderId: 1003, amount: 25000, currency: 'RUB', provider: 'sber', status: 'captured', idempotencyKey: 'uuid-003', createdAt: '2026-04-14 16:45' },
    { id: 4, orderId: 1004, amount: 5400, currency: 'RUB', provider: 'sbp', status: 'refunded', idempotencyKey: 'uuid-004', createdAt: '2026-04-14 11:20' },
    { id: 5, orderId: 1005, amount: 12000, currency: 'RUB', provider: 'tinkoff', status: 'failed', idempotencyKey: 'uuid-005', createdAt: '2026-04-13 14:00' },
]);

const providers = ref([
    { id: 'tinkoff', name: 'Тинькофф', transactions: 542, successRate: 98.5, avgAmount: 12500, status: 'active' },
    { id: 'tochka', name: 'Точка', transactions: 312, successRate: 97.2, avgAmount: 8750, status: 'active' },
    { id: 'sber', name: 'Сбер', transactions: 284, successRate: 99.1, avgAmount: 18000, status: 'active' },
    { id: 'sbp', name: 'СБП', transactions: 110, successRate: 95.8, avgAmount: 5400, status: 'active' },
]);

const webhooks = ref([
    { id: 1, provider: 'tinkoff', eventType: 'payment.captured', attempts: 3, status: 'delivered', lastAttemptAt: '2026-04-15 10:31' },
    { id: 2, provider: 'tochka', eventType: 'payment.pending', attempts: 1, status: 'pending', lastAttemptAt: '2026-04-15 09:16' },
    { id: 3, provider: 'sber', eventType: 'payment.captured', attempts: 2, status: 'delivered', lastAttemptAt: '2026-04-14 16:46' },
    { id: 4, provider: 'tinkoff', eventType: 'payment.failed', attempts: 5, status: 'failed', lastAttemptAt: '2026-04-13 14:05' },
]);

const statusLabels = {
    captured: { label: 'Захвачен', color: 'text-green-600', bg: 'bg-green-50' },
    pending: { label: 'В обработке', color: 'text-yellow-600', bg: 'bg-yellow-50' },
    refunded: { label: 'Возврат', color: 'text-blue-600', bg: 'bg-blue-50' },
    failed: { label: 'Ошибка', color: 'text-red-600', bg: 'bg-red-50' },
    delivered: { label: 'Доставлен', color: 'text-green-600', bg: 'bg-green-50' },
    pending_webhook: { label: 'Ожидает', color: 'text-yellow-600', bg: 'bg-yellow-50' },
};

const providerLabels = {
    tinkoff: 'Тинькофф',
    tochka: 'Точка',
    sber: 'Сбер',
    sbp: 'СБП',
};

const formatAmount = (amount, currency) => {
    return new Intl.NumberFormat('ru-RU', { 
        style: 'currency', 
        currency: currency,
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

        <!-- Transactions Tab -->
        <div v-if="activeTab === 'transactions'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Заказ</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Провайдер</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Сумма</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Статус</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Idempotency Key</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Дата</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="tx in transactions" :key="tx.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ tx.id }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ tx.orderId }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ providerLabels[tx.provider] }}</td>
                        <td class="px-4 py-3 font-semibold" style="color: var(--t-text);">{{ formatAmount(tx.amount, tx.currency) }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusLabels[tx.status]">
                                {{ statusLabels[tx.status].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ tx.idempotencyKey }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ tx.createdAt }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Providers Tab -->
        <div v-if="activeTab === 'providers'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Провайдер</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Транзакций</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Успешность</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Средний чек</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="provider in providers" :key="provider.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3 font-medium" style="color: var(--t-text);">{{ provider.name }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ provider.transactions }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <div class="w-20 h-2 rounded-full" style="background: var(--t-border);">
                                    <div class="h-full rounded-full" style="background: var(--t-primary); width: {{ provider.successRate }}%;"></div>
                                </div>
                                <span class="text-xs" style="color: var(--t-text-2);">{{ provider.successRate }}%</span>
                            </div>
                        </td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ formatAmount(provider.avgAmount, 'RUB') }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" style="background: var(--t-primary-dim); color: var(--t-primary);">
                                {{ provider.status === 'active' ? 'Активен' : 'Отключен' }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Webhooks Tab -->
        <div v-if="activeTab === 'webhooks'" class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Провайдер</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Тип события</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Попыток</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Статус</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Последняя попытка</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="webhook in webhooks" :key="webhook.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ webhook.id }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ providerLabels[webhook.provider] }}</td>
                        <td class="px-4 py-3 font-mono text-xs" style="color: var(--t-text-2);">{{ webhook.eventType }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ webhook.attempts }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="webhook.status === 'delivered' ? statusLabels.captured : webhook.status === 'failed' ? statusLabels.failed : statusLabels.pending">
                                {{ webhook.status === 'delivered' ? 'Доставлен' : webhook.status === 'failed' ? 'Ошибка' : 'Ожидает' }}
                            </span>
                        </td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ webhook.lastAttemptAt }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
