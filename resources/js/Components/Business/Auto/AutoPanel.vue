<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import AutoDashboard from './AutoDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'service', label: 'СТО', icon: '🔧' },
    { key: 'parts', label: 'Запчасти', icon: '⚙️' },
    { key: 'bookings', label: 'Записи', icon: '📅', badge: 34 },
];

const stations = [
    { name: 'AutoHub Центр', bays: 12, load: 86, avgCheck: '14 200 ₽' },
    { name: 'AutoHub Север', bays: 8, load: 73, avgCheck: '11 700 ₽' },
    { name: 'AutoHub Юг', bays: 10, load: 79, avgCheck: '12 900 ₽' },
];

const parts = [
    { sku: 'BRK-4431', title: 'Колодки тормозные', stock: 84, status: 'ok' },
    { sku: 'FIL-8930', title: 'Фильтр воздушный', stock: 19, status: 'low' },
    { sku: 'OIL-5W30', title: 'Масло 5W-30', stock: 42, status: 'ok' },
    { sku: 'BAT-75AH', title: 'Аккумулятор 75Ah', stock: 7, status: 'low' },
];

const bookings = [
    { id: 'AU-9011', client: 'Кирилл П.', service: 'ТО-2', slot: '11:30', status: 'confirmed' },
    { id: 'AU-9014', client: 'ООО ТрансЛайн', service: 'Диагностика', slot: '12:10', status: 'pending' },
    { id: 'AU-9018', client: 'Мария В.', service: 'Замена колодок', slot: '13:00', status: 'confirmed' },
];

const statusMap = {
    ok: { text: 'Норма', variant: 'success' },
    low: { text: 'Low stock', variant: 'warning' },
    confirmed: { text: 'Подтверждена', variant: 'success' },
    pending: { text: 'Ожидает', variant: 'warning' },
};
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">Auto Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Сервисные зоны, запчасти и расписание СТО.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />

        <AutoDashboard v-if="activeTab === 'dashboard'" />

        <VCard v-else-if="activeTab === 'service'" title="Нагрузка по СТО">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <article v-for="station in stations" :key="station.name" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="font-semibold" style="color: var(--t-text);">{{ station.name }}</div>
                    <div class="text-sm mt-1" style="color: var(--t-text-2);">Постов: {{ station.bays }}</div>
                    <div class="text-sm" style="color: var(--t-text-2);">Средний чек: {{ station.avgCheck }}</div>
                    <div class="mt-2 h-1.5 rounded-full overflow-hidden" style="background: var(--t-border);">
                        <div class="h-full rounded-full" :style="{ inlineSize: station.load + '%', background: 'var(--t-primary)' }" />
                    </div>
                    <div class="text-xs mt-1" style="color: var(--t-text-3);">Загрузка: {{ station.load }}%</div>
                </article>
            </div>
        </VCard>

        <VCard v-else-if="activeTab === 'parts'" title="Склад запчастей">
            <div class="space-y-2">
                <article v-for="item in parts" :key="item.sku" class="rounded-xl border p-3 flex items-center justify-between gap-2" style="border-color: var(--t-border);">
                    <div>
                        <div class="font-semibold" style="color: var(--t-text);">{{ item.title }}</div>
                        <div class="text-xs" style="color: var(--t-text-3);">{{ item.sku }} · {{ item.stock }} шт</div>
                    </div>
                    <VBadge :text="statusMap[item.status].text" :variant="statusMap[item.status].variant" size="xs" />
                </article>
            </div>
        </VCard>

        <VCard v-else title="Ближайшие записи">
            <div class="space-y-2">
                <article v-for="row in bookings" :key="row.id" class="rounded-xl border p-3 flex items-center justify-between gap-2" style="border-color: var(--t-border);">
                    <div>
                        <div class="font-semibold" style="color: var(--t-text);">{{ row.id }} · {{ row.client }}</div>
                        <div class="text-xs" style="color: var(--t-text-3);">{{ row.service }} · {{ row.slot }}</div>
                    </div>
                    <VBadge :text="statusMap[row.status].text" :variant="statusMap[row.status].variant" size="xs" />
                </article>
            </div>
        </VCard>
    </section>
</template>
