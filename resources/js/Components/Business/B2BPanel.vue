<script setup>
/**
 * B2BPanel — полнофункциональная B2B-панель с управлением
 * кредитной линией, оптовыми заказами, филиалами, API-ключами.
 */
import { ref } from 'vue';
import VStatCard from '../UI/VStatCard.vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VTable from '../UI/VTable.vue';
import VInput from '../UI/VInput.vue';
import VModal from '../UI/VModal.vue';

const activeTab = ref('overview');
const tabs = [
    { key: 'overview', label: 'Обзор', icon: '📊' },
    { key: 'orders', label: 'Оптовые заказы', icon: '📦', badge: 5 },
    { key: 'branches', label: 'Филиалы', icon: '🏢' },
    { key: 'credit', label: 'Кредит', icon: '💳' },
    { key: 'api', label: 'API', icon: '🔑' },
    { key: 'reports', label: 'Отчёты', icon: '📑' },
];

const showNewOrderModal = ref(false);
const showNewBranchModal = ref(false);
const showNewApiKeyModal = ref(false);

/* Demo data */
const creditData = {
    limit: 500000,
    used: 187500,
    paymentTermDays: 14,
    tier: 'gold',
    nextPaymentDate: '2026-04-22',
    nextPaymentAmount: 45000,
};

const branches = [
    { id: 1, name: 'ООО «Альфа-Центр»', inn: '7712345678', address: 'Москва, ул. Ленина 15', tier: 'gold', balance: 125000, status: 'active' },
    { id: 2, name: 'ИП Петров А.В.', inn: '771234567890', address: 'СПб, Невский 42', tier: 'silver', balance: 45000, status: 'active' },
    { id: 3, name: 'ООО «Гамма»', inn: '7798765432', address: 'Казань, ул. Баумана 8', tier: 'standard', balance: 12500, status: 'pending' },
];

const branchColumns = [
    { key: 'name', label: 'Название', sortable: true },
    { key: 'inn', label: 'ИНН' },
    { key: 'tier', label: 'Тариф', align: 'center' },
    { key: 'balance', label: 'Баланс', sortable: true, align: 'right' },
    { key: 'status', label: 'Статус', align: 'center' },
];

const bulkOrders = [
    { id: 'B-2042', customer: 'ООО «Альфа-Центр»', items: 150, total: '456 000 ₽', status: 'processing', payment: 'credit', date: '2026-04-07' },
    { id: 'B-2041', customer: 'ИП Петров А.В.', items: 45, total: '123 500 ₽', status: 'shipped', payment: 'prepaid', date: '2026-04-06' },
    { id: 'B-2040', customer: 'ООО «Гамма»', items: 300, total: '890 000 ₽', status: 'pending_approval', payment: 'credit', date: '2026-04-05' },
];

const orderColumns = [
    { key: 'id', label: '# Заказ' },
    { key: 'customer', label: 'Контрагент', sortable: true },
    { key: 'items', label: 'Позиции', align: 'center' },
    { key: 'total', label: 'Сумма', sortable: true, align: 'right' },
    { key: 'payment', label: 'Оплата', align: 'center' },
    { key: 'status', label: 'Статус', align: 'center' },
];

const apiKeys = [
    { id: 1, name: 'Интеграция 1С', key: 'b2b_sk_live_...a3f2', permissions: ['orders.read', 'orders.write', 'stock'], created: '2026-03-15', requests: 12450 },
    { id: 2, name: 'Мобильное приложение', key: 'b2b_sk_live_...7d9c', permissions: ['orders.read', 'reports'], created: '2026-02-20', requests: 3200 },
];

const tierColors = { standard: 'neutral', silver: 'info', gold: 'b2b', platinum: 'success' };
const statusColors = { active: 'success', pending: 'warning', processing: 'info', shipped: 'success', pending_approval: 'warning' };
const statusLabels = { active: 'Активен', pending: 'Ожидает', processing: 'В обработке', shipped: 'Отгружен', pending_approval: 'На одобрении' };
</script>

<template>
    <div class="space-y-6">
        <!-- B2B Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-linear-to-br from-amber-500/20 to-orange-500/20 border border-amber-500/20 flex items-center justify-center text-2xl shadow-lg shadow-amber-500/10">
                    🏢
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl lg:text-2xl font-bold text-(--t-text)">B2B Панель</h1>
                        <VBadge text="PRO" variant="b2b" size="sm" />
                    </div>
                    <p class="text-xs text-(--t-text-3)">Управление оптовыми операциями и филиалами</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <VButton variant="secondary" size="sm">📥 Импорт Excel</VButton>
                <VButton variant="b2b" size="sm" @click="showNewOrderModal = true">➕ Оптовый заказ</VButton>
            </div>
        </div>

        <!-- Tabs -->
        <VTabs :tabs="tabs" v-model="activeTab" variant="underline" />

        <!-- ===== OVERVIEW TAB ===== -->
        <template v-if="activeTab === 'overview'">
            <!-- Credit Line Banner -->
            <div class="relative overflow-hidden rounded-2xl bg-linear-to-r from-amber-900/30 via-orange-900/20 to-amber-900/30 border border-amber-500/20 p-6">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    <div>
                        <div class="text-xs text-amber-400/60 uppercase tracking-wider mb-1">Кредитный лимит</div>
                        <div class="text-2xl font-bold text-amber-100">{{ (creditData.limit/1000).toFixed(0) }}k ₽</div>
                    </div>
                    <div>
                        <div class="text-xs text-amber-400/60 uppercase tracking-wider mb-1">Использовано</div>
                        <div class="text-2xl font-bold text-orange-300">{{ (creditData.used/1000).toFixed(0) }}k ₽</div>
                        <div class="mt-1 h-1.5 rounded-full bg-amber-900/40 overflow-hidden">
                            <div class="h-full rounded-full bg-linear-to-r from-amber-400 to-orange-400" :style="{width: (creditData.used/creditData.limit*100)+'%'}" />
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-amber-400/60 uppercase tracking-wider mb-1">Отсрочка</div>
                        <div class="text-2xl font-bold text-amber-100">{{ creditData.paymentTermDays }} дней</div>
                    </div>
                    <div>
                        <div class="text-xs text-amber-400/60 uppercase tracking-wider mb-1">Следующий платёж</div>
                        <div class="text-2xl font-bold text-amber-100">{{ (creditData.nextPaymentAmount/1000).toFixed(0) }}k ₽</div>
                        <div class="text-xs text-amber-300/50 mt-1">{{ creditData.nextPaymentDate }}</div>
                    </div>
                </div>
                <div class="absolute -right-16 -bottom-16 w-48 h-48 rounded-full bg-amber-500/5 blur-3xl pointer-events-none" />
            </div>

            <!-- B2B Metrics -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <VStatCard title="Оборот B2B" value="2.4M ₽" icon="💼" :trend="22.4" color="amber" />
                <VStatCard title="Оптовые заказы" value="142" icon="📦" :trend="15.1" color="primary" />
                <VStatCard title="Филиалы" value="3" icon="🏢" color="indigo" />
                <VStatCard title="API запросы" value="15.6k" icon="🔑" :trend="8.7" color="emerald" />
            </div>
        </template>

        <!-- ===== ORDERS TAB ===== -->
        <template v-if="activeTab === 'orders'">
            <VCard title="Оптовые заказы" subtitle="Все B2B-заказы с кредитом и предоплатой">
                <template #header-action>
                    <VButton variant="b2b" size="sm" @click="showNewOrderModal = true">➕ Новый заказ</VButton>
                </template>
                <VTable :columns="orderColumns" :rows="bulkOrders">
                    <template #cell-id="{ value }">
                        <span class="font-mono text-xs font-bold text-(--t-primary) cursor-pointer hover:underline">{{ value }}</span>
                    </template>
                    <template #cell-total="{ value }">
                        <span class="font-bold text-(--t-text)">{{ value }}</span>
                    </template>
                    <template #cell-payment="{ value }">
                        <VBadge :text="value === 'credit' ? 'Кредит' : 'Предоплата'" :variant="value === 'credit' ? 'b2b' : 'neutral'" size="xs" />
                    </template>
                    <template #cell-status="{ value }">
                        <VBadge :text="statusLabels[value] || value" :variant="statusColors[value] || 'neutral'" size="xs" dot />
                    </template>
                </VTable>
            </VCard>
        </template>

        <!-- ===== BRANCHES TAB ===== -->
        <template v-if="activeTab === 'branches'">
            <VCard title="Филиалы" subtitle="Управление юридическими лицами и ИП">
                <template #header-action>
                    <VButton variant="primary" size="sm" @click="showNewBranchModal = true">➕ Добавить филиал</VButton>
                </template>
                <VTable :columns="branchColumns" :rows="branches">
                    <template #cell-name="{ value, row }">
                        <div>
                            <div class="font-medium text-(--t-text) cursor-pointer hover:text-(--t-primary) transition-colors">{{ value }}</div>
                            <div class="text-[10px] text-(--t-text-3)">{{ row.address }}</div>
                        </div>
                    </template>
                    <template #cell-tier="{ value }">
                        <VBadge :text="value.toUpperCase()" :variant="tierColors[value]" size="xs" />
                    </template>
                    <template #cell-balance="{ value }">
                        <span class="font-semibold text-emerald-400">{{ Number(value).toLocaleString('ru') }} ₽</span>
                    </template>
                    <template #cell-status="{ value }">
                        <VBadge :text="statusLabels[value] || value" :variant="statusColors[value] || 'neutral'" size="xs" dot />
                    </template>
                </VTable>
            </VCard>
        </template>

        <!-- ===== CREDIT TAB ===== -->
        <template v-if="activeTab === 'credit'">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <VCard title="💳 Кредитная линия">
                    <div class="space-y-5">
                        <div>
                            <div class="text-xs text-(--t-text-3) mb-1">Текущий лимит</div>
                            <div class="text-3xl font-bold text-(--t-text)">{{ Number(creditData.limit).toLocaleString('ru') }} ₽</div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs text-(--t-text-3) mb-1">
                                <span>Использовано</span>
                                <span>{{ (creditData.used/creditData.limit*100).toFixed(0) }}%</span>
                            </div>
                            <div class="h-3 rounded-full bg-(--t-border) overflow-hidden">
                                <div class="h-full rounded-full bg-linear-to-r from-amber-400 to-orange-400 transition-all duration-700" :style="{width:(creditData.used/creditData.limit*100)+'%'}" />
                            </div>
                            <div class="flex justify-between text-xs text-(--t-text-3) mt-1">
                                <span>{{ Number(creditData.used).toLocaleString('ru') }} ₽</span>
                                <span>{{ Number(creditData.limit).toLocaleString('ru') }} ₽</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 rounded-xl bg-(--t-card-hover)">
                                <div class="text-[10px] text-(--t-text-3)">Тариф</div>
                                <div class="text-sm font-bold text-amber-300 uppercase">{{ creditData.tier }}</div>
                            </div>
                            <div class="p-3 rounded-xl bg-(--t-card-hover)">
                                <div class="text-[10px] text-(--t-text-3)">Отсрочка</div>
                                <div class="text-sm font-bold text-(--t-text)">{{ creditData.paymentTermDays }} дней</div>
                            </div>
                        </div>
                        <VButton variant="b2b" full-width>Запросить увеличение лимита</VButton>
                    </div>
                </VCard>

                <VCard title="📅 График платежей">
                    <div class="space-y-3">
                        <div v-for="p in [{date:'2026-04-22',amount:45000,status:'upcoming'},{date:'2026-05-22',amount:67500,status:'scheduled'},{date:'2026-06-22',amount:75000,status:'scheduled'}]" :key="p.date"
                             class="flex items-center justify-between p-3 rounded-xl bg-(--t-card-hover) cursor-pointer hover:bg-(--t-primary-dim) transition-all active:scale-[0.98]"
                        >
                            <div>
                                <div class="text-sm font-medium text-(--t-text)">{{ p.date }}</div>
                                <VBadge :text="p.status === 'upcoming' ? 'Ближайший' : 'Запланирован'" :variant="p.status === 'upcoming' ? 'warning' : 'neutral'" size="xs" />
                            </div>
                            <div class="text-sm font-bold text-(--t-text)">{{ Number(p.amount).toLocaleString('ru') }} ₽</div>
                        </div>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- ===== API TAB ===== -->
        <template v-if="activeTab === 'api'">
            <VCard title="🔑 API-ключи" subtitle="Интеграция с внешними системами">
                <template #header-action>
                    <VButton variant="primary" size="sm" @click="showNewApiKeyModal = true">➕ Новый ключ</VButton>
                </template>
                <div class="space-y-3">
                    <div v-for="key in apiKeys" :key="key.id"
                         class="p-4 rounded-xl border border-(--t-border) hover:border-(--t-primary)/30 transition-all cursor-pointer active:scale-[0.99]"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-lg">🔑</span>
                                <span class="font-semibold text-sm text-(--t-text)">{{ key.name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-(--t-text-3)">{{ key.requests.toLocaleString() }} запросов</span>
                                <VButton variant="ghost" size="xs">Настроить</VButton>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 mb-2">
                            <code class="text-xs font-mono text-(--t-text-2) bg-(--t-card-hover) px-2 py-1 rounded-lg">{{ key.key }}</code>
                            <button class="text-xs text-(--t-primary) hover:underline active:scale-95 cursor-pointer">Копировать</button>
                        </div>
                        <div class="flex gap-1 flex-wrap">
                            <VBadge v-for="perm in key.permissions" :key="perm" :text="perm" variant="neutral" size="xs" />
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- ===== REPORTS TAB ===== -->
        <template v-if="activeTab === 'reports'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <VCard v-for="r in [
                    {title:'Отчёт по обороту',icon:'📊',desc:'Полный оборот по всем филиалам'},
                    {title:'Кредитная история',icon:'💳',desc:'Все кредитные операции'},
                    {title:'Отчёт по товарам',icon:'📦',desc:'Движение товаров и складов'},
                    {title:'Налоговый отчёт',icon:'📑',desc:'Для бухгалтерии и налоговой'},
                    {title:'Аналитика продаж',icon:'📈',desc:'Тренды и прогнозы'},
                    {title:'Выгрузка в 1С',icon:'🔄',desc:'Синхронизация данных'},
                ]" :key="r.title" clickable glow>
                    <div class="text-center py-2">
                        <div class="text-3xl mb-2">{{ r.icon }}</div>
                        <h3 class="text-sm font-semibold text-(--t-text) mb-1">{{ r.title }}</h3>
                        <p class="text-xs text-(--t-text-3)">{{ r.desc }}</p>
                    </div>
                </VCard>
            </div>
        </template>

        <!-- ===== MODALS ===== -->
        <VModal v-model="showNewOrderModal" title="Новый оптовый заказ" size="lg">
            <div class="space-y-4">
                <VInput label="Контрагент" placeholder="Выберите контрагента" prefix-icon="🏢" />
                <div class="grid grid-cols-2 gap-3">
                    <VInput label="Количество позиций" type="number" placeholder="0" prefix-icon="📦" />
                    <VInput label="Сумма заказа" type="number" placeholder="0 ₽" prefix-icon="💰" />
                </div>
                <VInput label="Комментарий" placeholder="Дополнительная информация" />
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showNewOrderModal = false">Отмена</VButton>
                <VButton variant="b2b">Создать заказ</VButton>
            </template>
        </VModal>

        <VModal v-model="showNewBranchModal" title="Добавить филиал" size="md">
            <div class="space-y-4">
                <VInput label="Юридическое название" placeholder="ООО «...»" required />
                <div class="grid grid-cols-2 gap-3">
                    <VInput label="ИНН" placeholder="7712345678" required />
                    <VInput label="КПП" placeholder="771201001" />
                </div>
                <VInput label="Юридический адрес" placeholder="Город, улица, дом" />
                <VInput label="Расчётный счёт" placeholder="40702810..." />
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showNewBranchModal = false">Отмена</VButton>
                <VButton variant="primary">Добавить</VButton>
            </template>
        </VModal>

        <VModal v-model="showNewApiKeyModal" title="Создать API-ключ" size="md">
            <div class="space-y-4">
                <VInput label="Название" placeholder="Интеграция с 1С" required />
                <VCard title="Разрешения" flat>
                    <div class="grid grid-cols-2 gap-2">
                        <label v-for="p in ['orders.read','orders.write','stock','reports','products.read','products.write']" :key="p"
                               class="flex items-center gap-2 p-2 rounded-lg hover:bg-(--t-card-hover) cursor-pointer transition-colors"
                        >
                            <input type="checkbox" class="rounded border-(--t-border) text-(--t-primary) focus:ring-(--t-primary)" />
                            <span class="text-xs text-(--t-text)">{{ p }}</span>
                        </label>
                    </div>
                </VCard>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showNewApiKeyModal = false">Отмена</VButton>
                <VButton variant="primary">Создать ключ</VButton>
            </template>
        </VModal>
    </div>
</template>
