<script setup>
/**
 * B2BOrders — Wholesale orders list with filtering, status tracking,
 * bulk actions, and order management.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VInput from '../UI/VInput.vue';
import VTable from '../UI/VTable.vue';

const activeTab = ref('all');
const searchQuery = ref('');
const dateRange = ref('30days');
const selectedOrders = ref([]);

const tabs = [
    { id: 'all', label: 'Все', count: 24 },
    { id: 'pending', label: 'Ожидают', count: 3 },
    { id: 'processing', label: 'В обработке', count: 5 },
    { id: 'shipped', label: 'Отгружены', count: 8 },
    { id: 'completed', label: 'Выполнены', count: 6 },
    { id: 'cancelled', label: 'Отменены', count: 2 },
];

const dateRanges = [
    { id: '7days', label: '7 дней' },
    { id: '30days', label: '30 дней' },
    { id: '90days', label: '90 дней' },
    { id: '365days', label: '1 год' },
    { id: 'all', label: 'За всё время' },
];

const orders = [
    {
        id: 'B-2042',
        company: 'ООО «Альфа-Центр»',
        companyInn: '7712345678',
        items: 15,
        total: 435000,
        status: 'shipped',
        payment: 'credit',
        paymentStatus: 'pending',
        createdAt: '2026-04-15',
        shippedAt: '2026-04-17',
        deliveryDate: '2026-04-20',
        trackingNumber: 'TRK123456789',
    },
    {
        id: 'B-2041',
        company: 'ИП Петров А.В.',
        companyInn: '771234567890',
        items: 25,
        total: 125000,
        status: 'processing',
        payment: 'prepaid',
        paymentStatus: 'paid',
        createdAt: '2026-04-14',
        deliveryDate: '2026-04-22',
    },
    {
        id: 'B-2040',
        company: 'ООО «Гамма»',
        companyInn: '7798765432',
        items: 150,
        total: 240000,
        status: 'pending',
        payment: 'credit',
        paymentStatus: 'pending',
        createdAt: '2026-04-13',
    },
    {
        id: 'B-2039',
        company: 'ООО «Альфа-Центр»',
        companyInn: '7712345678',
        items: 50,
        total: 89000,
        status: 'completed',
        payment: 'credit',
        paymentStatus: 'paid',
        createdAt: '2026-04-10',
        completedAt: '2026-04-12',
    },
    {
        id: 'B-2038',
        company: 'ИП Петров А.В.',
        companyInn: '771234567890',
        items: 100,
        total: 180000,
        status: 'cancelled',
        payment: 'prepaid',
        paymentStatus: 'refunded',
        createdAt: '2026-04-08',
        cancelledAt: '2026-04-09',
        cancelReason: 'По просьбе клиента',
    },
];

const columns = [
    { key: 'id', label: '№ Заказа' },
    { key: 'company', label: 'Компания', sortable: true },
    { key: 'items', label: 'Поз.', align: 'center' },
    { key: 'total', label: 'Сумма', sortable: true, align: 'right' },
    { key: 'status', label: 'Статус', align: 'center' },
    { key: 'payment', label: 'Оплата', align: 'center' },
    { key: 'date', label: 'Дата', sortable: true },
];

const statusColors = {
    pending: { bg: 'rgba(251,191,36,0.12)', text: '#fbbf24', border: 'rgba(251,191,36,0.3)' },
    processing: { bg: 'rgba(96,165,250,0.12)', text: '#60a5fa', border: 'rgba(96,165,250,0.3)' },
    shipped: { bg: 'rgba(168,85,247,0.12)', text: '#a855f7', border: 'rgba(168,85,247,0.3)' },
    completed: { bg: 'rgba(52,211,153,0.12)', text: '#34d399', border: 'rgba(52,211,153,0.3)' },
    cancelled: { bg: 'rgba(248,113,113,0.12)', text: '#f87171', border: 'rgba(248,113,113,0.3)' },
};

const statusLabels = {
    pending: 'Ожидает',
    processing: 'В обработке',
    shipped: 'Отгружен',
    completed: 'Выполнен',
    cancelled: 'Отменён',
};

const paymentStatusColors = {
    pending: 'warning',
    paid: 'success',
    refunded: 'danger',
};

const paymentStatusLabels = {
    pending: 'Ожидает',
    paid: 'Оплачен',
    refunded: 'Возвращён',
};

const filteredOrders = computed(() => {
    let result = [...orders];

    // Tab filter
    if (activeTab.value !== 'all') {
        result = result.filter(o => o.status === activeTab.value);
    }

    // Search filter
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(o => 
            o.id.toLowerCase().includes(query) || 
            o.company.toLowerCase().includes(query) ||
            o.companyInn.includes(query)
        );
    }

    return result;
});

const totalVolume = computed(() => filteredOrders.value.reduce((sum, o) => sum + o.total, 0));
const selectedCount = computed(() => selectedOrders.value.length);
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-(--t-text)">📦 B2B Заказы</h1>
                <p class="text-sm text-(--t-text-3) mt-1">Управление оптовыми заказами</p>
            </div>
            <div class="flex items-center gap-2">
                <VButton variant="secondary" size="sm">📥 Экспорт</VButton>
                <VButton variant="b2b" size="sm">➕ Новый заказ</VButton>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <VCard flat>
                <div class="text-center p-3">
                    <p class="text-2xl font-bold text-(--t-text)">{{ orders.length }}</p>
                    <p class="text-xs text-(--t-text-3)">Всего заказов</p>
                </div>
            </VCard>
            <VCard flat>
                <div class="text-center p-3">
                    <p class="text-2xl font-bold text-amber-400">{{ orders.filter(o => o.status === 'pending').length }}</p>
                    <p class="text-xs text-(--t-text-3)">Ожидают</p>
                </div>
            </VCard>
            <VCard flat>
                <div class="text-center p-3">
                    <p class="text-2xl font-bold text-blue-400">{{ orders.filter(o => o.status === 'processing').length }}</p>
                    <p class="text-xs text-(--t-text-3)">В обработке</p>
                </div>
            </VCard>
            <VCard flat>
                <div class="text-center p-3">
                    <p class="text-2xl font-bold text-emerald-400">{{ (totalVolume / 1000000).toFixed(1) }}M ₽</p>
                    <p class="text-xs text-(--t-text-3)">Оборот</p>
                </div>
            </VCard>
        </div>

        <!-- Filters -->
        <VCard flat>
            <div class="flex flex-col lg:flex-row gap-4">
                <VInput 
                    v-model="searchQuery" 
                    placeholder="Поиск по номеру, компании или ИНН..." 
                    prefix-icon="🔍"
                    class="flex-1"
                />
                <select 
                    v-model="dateRange"
                    class="px-4 py-2 rounded-xl bg-(--t-surface) border border-(--t-border) text-(--t-text) text-sm focus:outline-none focus:border-(--t-primary)"
                >
                    <option v-for="range in dateRanges" :key="range.id" :value="range.id">
                        {{ range.label }}
                    </option>
                </select>
            </div>
        </VCard>

        <!-- Tabs -->
        <div class="flex gap-1 p-1 rounded-xl border" style="background: var(--t-surface); border-color: var(--t-border);">
            <button 
                v-for="tab in tabs" 
                :key="tab.id"
                @click="activeTab = tab.id"
                class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition-all duration-300 active:scale-95 cursor-pointer"
                :style="activeTab === tab.id
                    ? { background: 'var(--t-primary-dim)', color: 'var(--t-text)', boxShadow: '0 0 8px var(--t-glow)' }
                    : { color: 'var(--t-text-3)' }"
            >
                {{ tab.label }}
                <span v-if="tab.count" class="ml-1 opacity-60">{{ tab.count }}</span>
            </button>
        </div>

        <!-- Orders Table -->
        <VCard>
            <template #header-action>
                <div v-if="selectedCount > 0" class="flex items-center gap-2">
                    <span class="text-sm text-(--t-text-3)">Выбрано: {{ selectedCount }}</span>
                    <VButton variant="danger" size="xs">Удалить</VButton>
                    <VButton variant="secondary" size="xs">Экспорт</VButton>
                </div>
            </template>

            <VTable :columns="columns" :rows="filteredOrders">
                <template #cell-id="{ value }">
                    <span class="font-mono text-sm font-bold text-(--t-primary) cursor-pointer hover:underline">
                        {{ value }}
                    </span>
                </template>
                <template #cell-company="{ value, row }">
                    <div>
                        <div class="font-medium text-(--t-text)">{{ value }}</div>
                        <div class="text-[10px] text-(--t-text-3)">ИНН: {{ row.companyInn }}</div>
                    </div>
                </template>
                <template #cell-total="{ value }">
                    <span class="font-bold text-(--t-text)">{{ value.toLocaleString() }} ₽</span>
                </template>
                <template #cell-status="{ value }">
                    <VBadge 
                        :text="statusLabels[value]" 
                        :variant="value === 'pending' ? 'warning' : value === 'processing' ? 'info' : value === 'shipped' ? 'b2b' : value === 'completed' ? 'success' : 'danger'" 
                        size="xs" 
                        dot 
                    />
                </template>
                <template #cell-payment="{ row }">
                    <div class="flex flex-col gap-1">
                        <VBadge 
                            :text="row.payment === 'credit' ? 'Кредит' : 'Предоплата'" 
                            :variant="row.payment === 'credit' ? 'b2b' : 'neutral'" 
                            size="xs" 
                        />
                        <VBadge 
                            :text="paymentStatusLabels[row.paymentStatus]" 
                            :variant="paymentStatusColors[row.paymentStatus]" 
                            size="xs" 
                        />
                    </div>
                </template>
                <template #cell-date="{ value, row }">
                    <div class="text-sm text-(--t-text)">{{ value }}</div>
                    <div v-if="row.deliveryDate && row.status !== 'completed'" class="text-[10px] text-(--t-text-3)">
                        Доставка: {{ row.deliveryDate }}
                    </div>
                </template>
            </VTable>
        </VCard>

        <!-- Empty State -->
        <div v-if="filteredOrders.length === 0" class="text-center py-16">
            <p class="text-5xl mb-4">📭</p>
            <p class="text-(--t-text-2) text-lg">Заказы не найдены</p>
            <p class="text-(--t-text-3) text-sm mt-2">Измените фильтры или создайте новый заказ</p>
            <VButton variant="b2b" size="md" class="mt-6">Создать заказ</VButton>
        </div>
    </div>
</template>
