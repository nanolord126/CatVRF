<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import HotelsDashboard from './HotelsDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'rooms', label: 'Номера', icon: '🛏️' },
    { key: 'bookings', label: 'Бронирования', icon: '📅' },
    { key: 'service', label: 'Сервис', icon: '🧾' },
];

const rooms = [
    { code: 'DLX-1204', type: 'Deluxe', status: 'occupied', adr: '10 800 ₽' },
    { code: 'STD-0411', type: 'Standard', status: 'cleaning', adr: '6 300 ₽' },
    { code: 'STE-2002', type: 'Suite', status: 'available', adr: '18 500 ₽' },
];

const bookings = [
    { id: 'HT-4001', guest: 'Вера К.', nights: 3, channel: 'Direct', status: 'confirmed' },
    { id: 'HT-4004', guest: 'ООО БизнесТур', nights: 12, channel: 'B2B', status: 'confirmed' },
    { id: 'HT-4008', guest: 'Михаил Т.', nights: 2, channel: 'OTA', status: 'pending' },
];

const service = [
    { metric: 'Housekeeping SLA', value: '97.2%' },
    { metric: 'Check-in < 7 мин', value: '91.8%' },
    { metric: 'Guest CSAT', value: '4.8 / 5' },
];

const map = {
    occupied: { text: 'Занят', variant: 'danger' },
    cleaning: { text: 'Уборка', variant: 'warning' },
    available: { text: 'Свободен', variant: 'success' },
    confirmed: { text: 'Подтв.', variant: 'success' },
    pending: { text: 'Ожидает', variant: 'warning' },
};
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">Hotels Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Операции отеля: загрузка номеров, booking и сервис.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />
        <HotelsDashboard v-if="activeTab === 'dashboard'" />

        <VCard v-else-if="activeTab === 'rooms'" title="Номерной фонд">
            <div class="space-y-2">
                <article v-for="room in rooms" :key="room.code" class="rounded-xl border p-3 flex items-center justify-between gap-2" style="border-color: var(--t-border);">
                    <div>
                        <div class="font-semibold" style="color: var(--t-text);">{{ room.code }} · {{ room.type }}</div>
                        <div class="text-xs" style="color: var(--t-text-3);">ADR: {{ room.adr }}</div>
                    </div>
                    <VBadge :text="map[room.status].text" :variant="map[room.status].variant" size="xs" />
                </article>
            </div>
        </VCard>

        <VCard v-else-if="activeTab === 'bookings'" title="Ближайшие заезды">
            <div class="space-y-2">
                <article v-for="item in bookings" :key="item.id" class="rounded-xl border p-3 flex items-center justify-between gap-2" style="border-color: var(--t-border);">
                    <div>
                        <div class="font-semibold" style="color: var(--t-text);">{{ item.id }} · {{ item.guest }}</div>
                        <div class="text-xs" style="color: var(--t-text-3);">{{ item.nights }} ночи · {{ item.channel }}</div>
                    </div>
                    <VBadge :text="map[item.status].text" :variant="map[item.status].variant" size="xs" />
                </article>
            </div>
        </VCard>

        <VCard v-else title="Сервисные KPI">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <article v-for="kpi in service" :key="kpi.metric" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="text-xs" style="color: var(--t-text-3);">{{ kpi.metric }}</div>
                    <div class="mt-1 text-xl font-bold" style="color: var(--t-text);">{{ kpi.value }}</div>
                </article>
            </div>
        </VCard>
    </section>
</template>
