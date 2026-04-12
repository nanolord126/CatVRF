<script setup>
/**
 * WarehouseInventory — управление складами, остатками, резервами, поставками.
 * Multi-warehouse, tenant-scoped, реал-тайм обновление.
 */
import { ref, computed } from 'vue';
import VCard from '../UI/VCard.vue';
import VButton from '../UI/VButton.vue';
import VBadge from '../UI/VBadge.vue';
import VTabs from '../UI/VTabs.vue';
import VStatCard from '../UI/VStatCard.vue';
import VModal from '../UI/VModal.vue';
import VInput from '../UI/VInput.vue';

const activeTab = ref('warehouses');
const tabs = [
    { key: 'warehouses', label: 'Склады' },
    { key: 'inventory', label: 'Остатки' },
    { key: 'movements', label: 'Движения' },
    { key: 'supplies', label: 'Поставки' },
];

const showAddWarehouse = ref(false);
const showItemDetail = ref(false);
const selectedItem = ref(null);
const search = ref('');

const warehouses = [
    { id: 1, name: 'Основной склад', address: 'Москва, ул. Промышленная 15', active: true, items: 1284, capacity: 85, lat: 55.75, lon: 37.62 },
    { id: 2, name: 'Склад-экспресс', address: 'Москва, ул. Ленина 42', active: true, items: 562, capacity: 62, lat: 55.76, lon: 37.64 },
    { id: 3, name: 'Региональный', address: 'СПб, Невский пр. 80', active: true, items: 935, capacity: 71, lat: 59.93, lon: 30.32 },
];

const inventory = [
    { id: 1, name: 'Шампунь премиум', sku: 'B-001', warehouse: 'Основной', quantity: 245, reserved: 18, available: 227, costPrice: 320, status: 'in_stock' },
    { id: 2, name: 'Маска для лица', sku: 'B-002', warehouse: 'Основной', quantity: 12, reserved: 5, available: 7, costPrice: 890, status: 'low_stock' },
    { id: 3, name: 'Сыворотка витамин C', sku: 'B-003', warehouse: 'Экспресс', quantity: 0, reserved: 0, available: 0, costPrice: 1500, status: 'out_of_stock' },
    { id: 4, name: 'Кресло барбер-класс', sku: 'F-010', warehouse: 'Региональный', quantity: 34, reserved: 2, available: 32, costPrice: 45000, status: 'in_stock' },
    { id: 5, name: 'Набор кистей PRO', sku: 'B-015', warehouse: 'Основной', quantity: 89, reserved: 10, available: 79, costPrice: 2400, status: 'in_stock' },
    { id: 6, name: 'Фен профессиональный', sku: 'B-022', warehouse: 'Экспресс', quantity: 5, reserved: 3, available: 2, costPrice: 15000, status: 'low_stock' },
];

const movements = [
    { id: 1, item: 'Шампунь премиум', type: 'in', qty: 100, source: 'Поставка #4521', time: '10 мин назад' },
    { id: 2, item: 'Маска для лица', type: 'reserve', qty: 3, source: 'Корзина #8812', time: '25 мин назад' },
    { id: 3, item: 'Набор кистей PRO', type: 'out', qty: 5, source: 'Заказ #11240', time: '1 час назад' },
    { id: 4, item: 'Фен профессиональный', type: 'release', qty: 1, source: 'Отмена корзины', time: '2 часа назад' },
    { id: 5, item: 'Кресло барбер-класс', type: 'in', qty: 10, source: 'Поставка #4518', time: '3 часа назад' },
];

const movementLabels = { in: 'Поступление', out: 'Отгрузка', reserve: 'Резерв', release: 'Снятие резерва', return: 'Возврат', adjustment: 'Корректировка' };
const movementColors = { in: 'text-emerald-400', out: 'text-rose-400', reserve: 'text-amber-400', release: 'text-blue-400', return: 'text-violet-400', adjustment: 'text-(--t-text-3)' };
const movementIcons = { in: '📥', out: '📤', reserve: '🔒', release: '🔓', return: '↩️', adjustment: '⚙️' };

const statusLabels = { in_stock: 'В наличии', low_stock: 'Мало', out_of_stock: 'Нет' };
const statusColors = { in_stock: 'success', low_stock: 'warning', out_of_stock: 'danger' };

const filteredInventory = computed(() => {
    if (!search.value) return inventory;
    const q = search.value.toLowerCase();
    return inventory.filter(i => i.name.toLowerCase().includes(q) || i.sku.toLowerCase().includes(q));
});

function openItem(item) {
    selectedItem.value = item;
    showItemDetail.value = true;
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl lg:text-2xl font-bold text-(--t-text)">🏭 Склады и инвентарь</h1>
                <p class="text-xs text-(--t-text-3)">Управление остатками, резервами и поставками</p>
            </div>
            <VButton variant="primary" size="sm" @click="showAddWarehouse = true">➕ Новый склад</VButton>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <VStatCard title="Склады" value="3" icon="🏭" color="primary" clickable />
            <VStatCard title="Позиций" value="2 781" icon="📦" :trend="12.3" color="indigo" clickable />
            <VStatCard title="Резервы" value="38" icon="🔒" color="amber" clickable />
            <VStatCard title="Low stock" value="2" icon="⚠️" color="rose" clickable />
        </div>

        <!-- Tabs -->
        <VTabs :tabs="tabs" v-model="activeTab" variant="underline" />

        <!-- Warehouses -->
        <template v-if="activeTab === 'warehouses'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="wh in warehouses" :key="wh.id"
                     class="rounded-xl border border-(--t-border) hover:border-(--t-primary)/20 bg-(--t-surface) p-4 transition-all cursor-pointer hover:shadow-lg active:scale-[0.98]"
                >
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <div class="text-sm font-bold text-(--t-text)">{{ wh.name }}</div>
                            <div class="text-[10px] text-(--t-text-3)">{{ wh.address }}</div>
                        </div>
                        <VBadge :text="wh.active ? 'Активен' : 'Неактивен'" :variant="wh.active ? 'success' : 'neutral'" size="xs" />
                    </div>

                    <!-- Capacity bar -->
                    <div class="mb-3">
                        <div class="flex justify-between text-[10px] mb-1">
                            <span class="text-(--t-text-3)">Заполненность</span>
                            <span :class="wh.capacity > 80 ? 'text-amber-400' : 'text-emerald-400'">{{ wh.capacity }}%</span>
                        </div>
                        <div class="h-2 rounded-full bg-(--t-border) overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500"
                                 :class="wh.capacity > 80 ? 'bg-linear-to-r from-amber-500 to-amber-300' : 'bg-linear-to-r from-emerald-500 to-emerald-300'"
                                 :style="{width: wh.capacity + '%'}"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="p-2 rounded-lg bg-(--t-card-hover) text-center">
                            <div class="text-sm font-bold text-(--t-text)">{{ wh.items }}</div>
                            <div class="text-[9px] text-(--t-text-3)">Позиций</div>
                        </div>
                        <div class="p-2 rounded-lg bg-(--t-card-hover) text-center cursor-pointer hover:bg-(--t-primary-dim) transition-colors">
                            <div class="text-sm font-bold text-(--t-primary)">📋</div>
                            <div class="text-[9px] text-(--t-text-3)">Инвентаризация</div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Inventory -->
        <template v-if="activeTab === 'inventory'">
            <div class="mb-4">
                <VInput v-model="search" placeholder="Поиск по названию или SKU..." clearable prefix-icon="🔍" size="sm" />
            </div>

            <div class="space-y-2">
                <div v-for="item in filteredInventory" :key="item.id"
                     class="flex items-center gap-3 p-3 rounded-xl border border-(--t-border) hover:border-(--t-primary)/20 bg-(--t-surface) transition-all cursor-pointer hover:shadow-md active:scale-[0.99]"
                     @click="openItem(item)"
                >
                    <div class="w-10 h-10 rounded-lg bg-(--t-card-hover) flex items-center justify-center text-lg"
                         :class="item.status === 'out_of_stock' ? 'grayscale opacity-50' : ''"
                    >📦</div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-(--t-text) truncate">{{ item.name }}</div>
                        <div class="text-[10px] text-(--t-text-3)">SKU: {{ item.sku }} • {{ item.warehouse }}</div>
                    </div>
                    <div class="text-right mr-2">
                        <div class="text-sm font-bold text-(--t-text)">{{ item.available }}</div>
                        <div class="text-[9px] text-(--t-text-3)">
                            <span v-if="item.reserved" class="text-amber-400">🔒 {{ item.reserved }}</span>
                        </div>
                    </div>
                    <VBadge :text="statusLabels[item.status]" :variant="statusColors[item.status]" size="xs" />
                </div>
            </div>
        </template>

        <!-- Stock Movements -->
        <template v-if="activeTab === 'movements'">
            <VCard title="📋 Последние движения" subtitle="Все операции с остатками">
                <div class="space-y-2">
                    <div v-for="mv in movements" :key="mv.id"
                         class="flex items-center gap-3 p-3 rounded-xl hover:bg-(--t-card-hover) transition-colors cursor-pointer"
                    >
                        <div class="w-8 h-8 rounded-lg bg-(--t-card-hover) flex items-center justify-center text-sm">{{ movementIcons[mv.type] }}</div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-(--t-text)">{{ mv.item }}</span>
                                <span class="text-xs font-medium" :class="movementColors[mv.type]">
                                    {{ mv.type === 'in' || mv.type === 'release' ? '+' : '-' }}{{ mv.qty }}
                                </span>
                            </div>
                            <div class="text-[10px] text-(--t-text-3)">{{ movementLabels[mv.type] }} • {{ mv.source }}</div>
                        </div>
                        <div class="text-[10px] text-(--t-text-3) whitespace-nowrap">{{ mv.time }}</div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- Supplies -->
        <template v-if="activeTab === 'supplies'">
            <VCard title="🚛 Ожидаемые поставки">
                <div class="space-y-3">
                    <div v-for="n in 3" :key="n"
                         class="p-3 rounded-xl border border-(--t-border) hover:border-(--t-primary)/20 transition-all cursor-pointer"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <div class="text-sm font-medium text-(--t-text)">Поставка #{{ 4520 + n }}</div>
                                <div class="text-[10px] text-(--t-text-3)">Поставщик: {{ ['BeautyPro LLC', 'FurniWorld', 'HealthLine'][n-1] }}</div>
                            </div>
                            <VBadge :text="['В пути','Ожидается','Подтверждена'][n-1]"
                                    :variant="['info','warning','success'][n-1]" size="xs" />
                        </div>
                        <div class="flex items-center gap-4 text-xs text-(--t-text-3)">
                            <span>📦 {{ [48, 120, 35][n-1] }} позиций</span>
                            <span>💰 {{ ['86 400', '340 000', '52 500'][n-1] }} ₽</span>
                            <span>📅 {{ ['12.04', '15.04', '18.04'][n-1] }}.2026</span>
                        </div>
                    </div>
                </div>
            </VCard>
        </template>

        <!-- Add Warehouse Modal -->
        <VModal v-model="showAddWarehouse" title="Добавить склад" size="md">
            <div class="space-y-4">
                <VInput label="Название склада" placeholder="Основной склад" required />
                <VInput label="Адрес" placeholder="Москва, ул. Промышленная 15" required />
                <div class="grid grid-cols-2 gap-3">
                    <VInput label="Широта" type="number" placeholder="55.75" />
                    <VInput label="Долгота" type="number" placeholder="37.62" />
                </div>
            </div>
            <template #footer>
                <VButton variant="secondary" @click="showAddWarehouse = false">Отмена</VButton>
                <VButton variant="primary">Создать</VButton>
            </template>
        </VModal>

        <!-- Item Detail Modal -->
        <VModal v-model="showItemDetail" :title="selectedItem?.name" size="md">
            <template v-if="selectedItem">
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="p-3 rounded-xl bg-(--t-card-hover) text-center">
                            <div class="text-[10px] text-(--t-text-3)">Всего</div>
                            <div class="text-lg font-bold text-(--t-text)">{{ selectedItem.quantity }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-(--t-card-hover) text-center">
                            <div class="text-[10px] text-(--t-text-3)">Резерв</div>
                            <div class="text-lg font-bold text-amber-400">{{ selectedItem.reserved }}</div>
                        </div>
                        <div class="p-3 rounded-xl bg-(--t-card-hover) text-center">
                            <div class="text-[10px] text-(--t-text-3)">Доступно</div>
                            <div class="text-lg font-bold text-emerald-400">{{ selectedItem.available }}</div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-(--t-card-hover)">
                        <span class="text-xs text-(--t-text-3)">Себестоимость:</span>
                        <span class="text-sm font-bold text-(--t-text)">{{ Number(selectedItem.costPrice).toLocaleString('ru') }} ₽</span>
                    </div>
                    <div class="flex items-center justify-between p-3 rounded-xl bg-(--t-card-hover)">
                        <span class="text-xs text-(--t-text-3)">Склад:</span>
                        <span class="text-sm text-(--t-text)">{{ selectedItem.warehouse }}</span>
                    </div>
                </div>
            </template>
            <template #footer>
                <VButton variant="secondary" @click="showItemDetail = false">Закрыть</VButton>
                <VButton variant="primary">📥 Поступление</VButton>
            </template>
        </VModal>
    </div>
</template>
