<script setup>
/**
 * OrdersManagement — полное управление заказами (B2C + B2B).
 * Фильтры, поиск, статусы, действия, экспорт.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VTable from '../UI/VTable.vue';
import VInput from '../UI/VInput.vue';
import VModal from '../UI/VModal.vue';
import VStatCard from '../UI/VStatCard.vue';

const activeTab = ref('all');
const tabs = [
    { key: 'all', label: 'Все', badge: 48 },
    { key: 'new', label: 'Новые', badge: 7 },
    { key: 'processing', label: 'В работе', badge: 12 },
    { key: 'shipping', label: 'Доставка', badge: 5 },
    { key: 'completed', label: 'Завершённые' },
    { key: 'cancelled', label: 'Отменённые' },
    { key: 'returns', label: 'Возвраты', badge: 3 },
];

const returns = ref([
    { id: 'RET-301', orderId: 'ORD-20488', customer: 'Козлова Анна', reason: 'Не подошёл размер', amount: 8900, status: 'pending', date: '2026-04-08' },
    { id: 'RET-300', orderId: 'ORD-20480', customer: 'Иванов Алексей', reason: 'Товар повреждён', amount: 4500, status: 'approved', date: '2026-04-07' },
    { id: 'RET-299', orderId: 'ORD-20475', customer: 'Петрова Мария', reason: 'Передумал', amount: 3200, status: 'rejected', date: '2026-04-06' },
]);
const returnStatusMap = {
    pending: { label: 'На рассмотрении', variant: 'warning' },
    approved: { label: 'Одобрен', variant: 'success' },
    rejected: { label: 'Отклонён', variant: 'danger' },
    refunded: { label: 'Возвращено', variant: 'info' },
};

const searchQuery = ref('');
const selectedOrders = ref([]);
const showOrderDetail = ref(false);
const selectedOrder = ref(null);

const orders = ref([
    { id: 'ORD-20492', customer: 'Иванов Алексей', email: 'ivanov@mail.ru', items: 3, total: 12800, status: 'new', type: 'b2c', payment: 'paid', date: '2026-04-08 14:32', vertical: 'beauty' },
    { id: 'ORD-20491', customer: 'ООО «Альфа-Центр»', email: 'alpha@corp.ru', items: 150, total: 456000, status: 'processing', type: 'b2b', payment: 'credit', date: '2026-04-08 12:15', vertical: 'furniture' },
    { id: 'ORD-20490', customer: 'Петрова Мария', email: 'petrova@yandex.ru', items: 1, total: 3200, status: 'shipping', type: 'b2c', payment: 'paid', date: '2026-04-08 10:44', vertical: 'food' },
    { id: 'ORD-20489', customer: 'ИП Сидоров', email: 'sidorov@biz.ru', items: 45, total: 123500, status: 'processing', type: 'b2b', payment: 'prepaid', date: '2026-04-07 18:20', vertical: 'fashion' },
    { id: 'ORD-20488', customer: 'Козлова Анна', email: 'kozlova@gmail.com', items: 2, total: 8900, status: 'completed', type: 'b2c', payment: 'paid', date: '2026-04-07 16:05', vertical: 'beauty' },
    { id: 'ORD-20487', customer: 'Волков Дмитрий', email: 'volkov@mail.ru', items: 5, total: 45600, status: 'new', type: 'b2c', payment: 'pending', date: '2026-04-07 14:30', vertical: 'travel' },
    { id: 'ORD-20486', customer: 'ООО «Гамма»', email: 'gamma@corp.ru', items: 300, total: 890000, status: 'new', type: 'b2b', payment: 'credit', date: '2026-04-07 11:00', vertical: 'furniture' },
]);

const filteredOrders = computed(() => {
    let filtered = orders.value;
    if (activeTab.value !== 'all') {
        filtered = filtered.filter(o => o.status === activeTab.value);
    }
    if (searchQuery.value) {
        const q = searchQuery.value.toLowerCase();
        filtered = filtered.filter(o => o.id.toLowerCase().includes(q) || o.customer.toLowerCase().includes(q));
    }
    return filtered;
});

const columns = [
    { key: 'id', label: '# Заказ', sortable: true },
    { key: 'customer', label: 'Клиент', sortable: true },
    { key: 'items', label: 'Позиции', align: 'center' },
    { key: 'total', label: 'Сумма', sortable: true, align: 'right' },
    { key: 'type', label: 'Тип', align: 'center' },
    { key: 'payment', label: 'Оплата', align: 'center' },
    { key: 'status', label: 'Статус', align: 'center' },
    { key: 'actions', label: '', align: 'center' },
];

const statusMap = {
    new: { label: 'Новый', variant: 'warning', dot: true },
    processing: { label: 'В работе', variant: 'info', dot: true },
    shipping: { label: 'Доставка', variant: 'info' },
    completed: { label: 'Завершён', variant: 'success' },
    cancelled: { label: 'Отменён', variant: 'danger' },
};

const paymentMap = {
    paid: { label: 'Оплачен', variant: 'success' },
    pending: { label: 'Ожидает', variant: 'warning' },
    credit: { label: 'Кредит', variant: 'b2b' },
    prepaid: { label: 'Предоплата', variant: 'neutral' },
};

const verticalIcons = {
    beauty: '💄', furniture: '🛋️', food: '🍕', fashion: '👗', travel: '✈️',
    hotel: '🏨', auto: '🚗', fitness: '💪', realestate: '🏠',
};

function openOrder(order) {
    selectedOrder.value = order;
    showOrderDetail.value = true;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl lg:text-2xl font-bold text-(--t-text)">Заказы</h1>
                <p class="text-xs text-(--t-text-3)">Управление всеми B2C и B2B заказами</p>
            </div>
            <div class="flex items-center gap-2">
                <VButton variant="secondary" size="sm">📥 Экспорт</VButton>
                <VButton variant="primary" size="sm">➕ Создать заказ</VButton>
            </div>
        </div>

        <!-- Batch Actions Bar -->
        <div v-if="selectedOrders.length > 0" class="flex items-center gap-3 p-3 rounded-xl bg-(--t-primary-dim) border border-(--t-primary)/20">
            <span class="text-sm font-semibold text-(--t-primary)">Выбрано: {{ selectedOrders.length }}</span>
            <div class="flex items-center gap-2 ml-auto">
                <VButton variant="ghost" size="xs">📦 Объединить</VButton>
                <VButton variant="ghost" size="xs">🖨️ Печать</VButton>
                <VButton variant="ghost" size="xs">📤 Экспорт</VButton>
                <VButton variant="danger" size="xs">❌ Отменить</VButton>
            </div>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <VStatCard title="Всего заказов" value="48" icon="📦" :trend="12.5" color="primary" clickable />
            <VStatCard title="Новые" value="7" icon="🆕" :trend="8.2" color="amber" clickable />
            <VStatCard title="Общая сумма" value="1.54M ₽" icon="💰" :trend="18.6" color="emerald" clickable />
            <VStatCard title="Средний чек" value="32.1k ₽" icon="🧾" :trend="4.1" color="indigo" clickable />
        </div>

        <!-- Tabs & Search -->
        <div class="flex flex-col sm:flex-row sm:items-end gap-4">
            <div class="flex-1">
                <VTabs :tabs="tabs" v-model="activeTab" variant="pills" size="sm" />
            </div>
            <div class="w-full sm:w-64">
                <VInput v-model="searchQuery" placeholder="Поиск по номеру или клиенту..." prefix-icon="🔍" clearable size="sm" />
            </div>
        </div>

        <!-- Orders Table -->
        <VCard no-padding>
            <VTable :columns="columns" :rows="filteredOrders" clickable-rows @row-click="openOrder" striped>
                <template #cell-id="{ value }">
                    <span class="font-mono text-xs font-bold text-(--t-primary) cursor-pointer hover:underline">{{ value }}</span>
                </template>
                <template #cell-customer="{ value, row }">
                    <div class="flex items-center gap-2">
                        <span class="text-sm">{{ verticalIcons[row.vertical] || '📦' }}</span>
                        <div>
                            <div class="text-sm font-medium text-(--t-text)">{{ value }}</div>
                            <div class="text-[10px] text-(--t-text-3)">{{ row.email }}</div>
                        </div>
                    </div>
                </template>
                <template #cell-total="{ value }">
                    <span class="font-bold text-(--t-text)">{{ Number(value).toLocaleString('ru') }} ₽</span>
                </template>
                <template #cell-type="{ value }">
                    <VBadge :text="value.toUpperCase()" :variant="value === 'b2b' ? 'b2b' : 'neutral'" size="xs" />
                </template>
                <template #cell-payment="{ value }">
                    <VBadge :text="paymentMap[value]?.label || value" :variant="paymentMap[value]?.variant || 'neutral'" size="xs" />
                </template>
                <template #cell-status="{ value }">
                    <VBadge :text="statusMap[value]?.label || value" :variant="statusMap[value]?.variant || 'neutral'" :dot="statusMap[value]?.dot" size="xs" />
                </template>
                <template #cell-actions="{ row }">
                    <div class="flex items-center gap-1 justify-center">
                        <button class="p-1.5 rounded-lg text-xs hover:bg-(--t-card-hover) cursor-pointer transition-colors active:scale-90" title="Подробнее" @click.stop="openOrder(row)">👁️</button>
                        <button class="p-1.5 rounded-lg text-xs hover:bg-(--t-card-hover) cursor-pointer transition-colors active:scale-90" title="Печать">🖨️</button>
                    </div>
                </template>
            </VTable>
        </VCard>

        <!-- Returns Tab -->
        <template v-if="activeTab === 'returns'">
            <VCard title="🔄 Возвраты и диспуты" subtitle="Управление возвратами и претензиями">
                <div class="space-y-3">
                    <div v-for="ret in returns" :key="ret.id"
                         class="p-4 rounded-xl border border-(--t-border) hover:bg-(--t-card-hover) transition-all cursor-pointer"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-xs font-bold text-(--t-primary)">{{ ret.id }}</span>
                                <span class="text-[10px] text-(--t-text-3)">→ {{ ret.orderId }}</span>
                            </div>
                            <VBadge :text="returnStatusMap[ret.status]?.label" :variant="returnStatusMap[ret.status]?.variant" size="xs" />
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm text-(--t-text)">{{ ret.customer }}</div>
                                <div class="text-xs text-(--t-text-3)">{{ ret.reason }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-sm font-bold text-(--t-text)">{{ ret.amount.toLocaleString('ru') }} ₽</div>
                                <div class="text-[10px] text-(--t-text-3)">{{ ret.date }}</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mt-3">
                            <VButton v-if="ret.status === 'pending'" variant="primary" size="xs">✅ Одобрить</VButton>
                            <VButton v-if="ret.status === 'pending'" variant="danger" size="xs">❌ Отклонить</VButton>
                            <VButton variant="ghost" size="xs">💬 Комментарий</VButton>
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- Order Detail Modal -->
        <VModal v-model="showOrderDetail" :title="selectedOrder ? `Заказ ${selectedOrder.id}` : 'Заказ'" size="lg">
            <template v-if="selectedOrder">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-3 rounded-xl bg-(--t-card-hover)">
                            <div class="text-[10px] text-(--t-text-3) uppercase tracking-wider">Клиент</div>
                            <div class="text-sm font-semibold text-(--t-text) mt-1">{{ selectedOrder.customer }}</div>
                            <div class="text-xs text-(--t-text-3)">{{ selectedOrder.email }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-(--t-card-hover)">
                            <div class="text-[10px] text-(--t-text-3) uppercase tracking-wider">Сумма</div>
                            <div class="text-xl font-bold text-(--t-text) mt-1">{{ Number(selectedOrder.total).toLocaleString('ru') }} ₽</div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <VBadge :text="statusMap[selectedOrder.status]?.label" :variant="statusMap[selectedOrder.status]?.variant" dot />
                        <VBadge :text="selectedOrder.type.toUpperCase()" :variant="selectedOrder.type === 'b2b' ? 'b2b' : 'neutral'" />
                        <VBadge :text="paymentMap[selectedOrder.payment]?.label" :variant="paymentMap[selectedOrder.payment]?.variant" />
                    </div>

                    <!-- Timeline -->
                    <VCard title="История заказа" flat>
                        <div class="space-y-3">
                            <div v-for="(step, i) in [{time:'14:32',text:'Заказ создан',icon:'📝'},{time:'14:33',text:'Fraud-check пройден',icon:'🛡️'},{time:'14:35',text:'Оплата подтверждена',icon:'✅'},{time:'14:40',text:'Товар зарезервирован на складе',icon:'📦'},{time:'15:00',text:'Передан в обработку',icon:'⚙️'},{time:'15:20',text:'Назначен курьер',icon:'🚴'},{time:'15:45',text:'Курьер забрал заказ',icon:'📬'}]" :key="i"
                                 class="flex items-start gap-3"
                            >
                                <div class="w-8 h-8 rounded-full bg-(--t-primary-dim) flex items-center justify-center text-sm shrink-0">{{ step.icon }}</div>
                                <div>
                                    <div class="text-sm text-(--t-text)">{{ step.text }}</div>
                                    <div class="text-[10px] text-(--t-text-3)">{{ step.time }}</div>
                                </div>
                            </div>
                        </div>
                    </VCard>
                </div>
            </template>
            <template #footer>
                <VButton variant="secondary" @click="showOrderDetail = false">Закрыть</VButton>
                <VButton variant="danger" size="sm">Отменить</VButton>
                <VButton variant="primary">Обработать</VButton>
            </template>
        </VModal>
    </div>
</template>
