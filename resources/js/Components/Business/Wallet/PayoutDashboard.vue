<script setup>
import { ref, computed } from 'vue';

const activeTab = ref('pending');
const tabs = [
    { key: 'pending', label: 'Ожидающие', count: 23 },
    { key: 'processing', label: 'В обработке', count: 8 },
    { key: 'completed', label: 'Завершённые', count: 156 },
    { key: 'failed', label: 'Ошибка', count: 2 },
];

const payouts = ref([
    { id: 1, recipient: 'Иванов И.И.', amount: 12500, currency: 'RUB', status: 'pending', createdAt: '2026-04-15 10:30', method: 'bank_account' },
    { id: 2, recipient: 'Петров П.П.', amount: 8750, currency: 'RUB', status: 'pending', createdAt: '2026-04-15 09:15', method: 'card' },
    { id: 3, recipient: 'Сидоров С.С.', amount: 25000, currency: 'RUB', status: 'processing', createdAt: '2026-04-14 16:45', method: 'bank_account' },
    { id: 4, recipient: 'Козлова К.К.', amount: 5400, currency: 'RUB', status: 'completed', createdAt: '2026-04-14 11:20', method: 'card' },
    { id: 5, recipient: 'Николаев Н.Н.', amount: 18000, currency: 'RUB', status: 'failed', createdAt: '2026-04-13 14:00', method: 'bank_account' },
]);

const filteredPayouts = computed(() => {
    return payouts.value.filter(p => p.status === activeTab.value);
});

const statusLabels = {
    pending: { label: 'Ожидает', color: 'text-yellow-600', bg: 'bg-yellow-50' },
    processing: { label: 'В обработке', color: 'text-blue-600', bg: 'bg-blue-50' },
    completed: { label: 'Завершено', color: 'text-green-600', bg: 'bg-green-50' },
    failed: { label: 'Ошибка', color: 'text-red-600', bg: 'bg-red-50' },
};

const methodLabels = {
    bank_account: 'Банк. счёт',
    card: 'Карта',
    yoomoney: 'ЮMoney',
};

const formatAmount = (amount, currency) => {
    return new Intl.NumberFormat('ru-RU', { 
        style: 'currency', 
        currency: currency 
    }).format(amount);
};

const processPayout = (id) => {
    console.log('Process payout:', id);
};

const retryPayout = (id) => {
    console.log('Retry payout:', id);
};
</script>

<template>
    <section class="space-y-4">
        <!-- Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
            <article class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
                <div class="text-xs" style="color: var(--t-text-3);">Ожидающие выплаты</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">23</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">+5</div>
                </div>
                <div class="mt-1 text-xl">⏳</div>
            </article>
            <article class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
                <div class="text-xs" style="color: var(--t-text-3);">Сумма к выплате</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">487 500 ₽</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">+12%</div>
                </div>
                <div class="mt-1 text-xl">💰</div>
            </article>
            <article class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
                <div class="text-xs" style="color: var(--t-text-3);">Обработано сегодня</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">156</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">+23</div>
                </div>
                <div class="mt-1 text-xl">✅</div>
            </article>
            <article class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
                <div class="text-xs" style="color: var(--t-text-3);">Ошибки</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">2</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">-1</div>
                </div>
                <div class="mt-1 text-xl">❌</div>
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

        <!-- Payouts Table -->
        <div class="rounded-2xl border overflow-hidden" style="background: var(--t-surface); border-color: var(--t-border);">
            <table class="w-full text-sm">
                <thead style="background: var(--t-surface-alt);">
                    <tr>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">ID</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Получатель</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Сумма</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Метод</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Статус</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Дата</th>
                        <th class="text-left px-4 py-3 font-semibold" style="color: var(--t-text-2);">Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="payout in filteredPayouts" :key="payout.id" class="border-t" style="border-color: var(--t-border);">
                        <td class="px-4 py-3" style="color: var(--t-text);">#{{ payout.id }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text);">{{ payout.recipient }}</td>
                        <td class="px-4 py-3 font-semibold" style="color: var(--t-text);">{{ formatAmount(payout.amount, payout.currency) }}</td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ methodLabels[payout.method] }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium" :class="statusLabels[payout.status]">
                                {{ statusLabels[payout.status].label }}
                            </span>
                        </td>
                        <td class="px-4 py-3" style="color: var(--t-text-2);">{{ payout.createdAt }}</td>
                        <td class="px-4 py-3">
                            <button 
                                v-if="payout.status === 'pending'"
                                @click="processPayout(payout.id)"
                                class="px-3 py-1 rounded-lg text-xs font-medium cursor-pointer"
                                style="background: var(--t-primary); color: white;"
                            >
                                Обработать
                            </button>
                            <button 
                                v-if="payout.status === 'failed'"
                                @click="retryPayout(payout.id)"
                                class="px-3 py-1 rounded-lg text-xs font-medium cursor-pointer"
                                style="background: var(--t-primary); color: white;"
                            >
                                Повторить
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
