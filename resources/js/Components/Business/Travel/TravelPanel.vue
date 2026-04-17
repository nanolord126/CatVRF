<script setup lang="ts">
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import TravelDashboard from './TravelDashboard.vue';

interface Tab {
  key: string;
  label: string;
  icon: string;
}

interface Package {
  name: string;
  sales: number;
  margin: string;
}

interface Booking {
  id: string;
  client: string;
  route: string;
  status: 'confirmed' | 'ticketing' | 'pending' | 'cancelled';
}

interface Event {
  type: string;
  text: string;
  severity: 'warning' | 'danger' | 'info' | 'success';
}

interface StatusMap {
  [key: string]: {
    text: string;
    variant: 'success' | 'warning' | 'danger' | 'info';
  };
}

const activeTab = ref<string>('dashboard');
const tabs: Tab[] = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'packages', label: 'Пакеты', icon: '🧳' },
    { key: 'bookings', label: 'Бронирования', icon: '✈️' },
    { key: 'alerts', label: 'События', icon: '⚠️' },
];

const packages: Package[] = [
    { name: 'Japan Spring 9d', sales: 44, margin: '22%' },
    { name: 'Istanbul City Break', sales: 71, margin: '18%' },
    { name: 'Dubai Premium 6d', sales: 36, margin: '27%' },
];

const bookings: Booking[] = [
    { id: 'TV-9021', client: 'Антон С.', route: 'MOW → HND', status: 'confirmed' },
    { id: 'TV-9024', client: 'ООО Альянс', route: 'MOW → IST', status: 'ticketing' },
    { id: 'TV-9028', client: 'Лидия К.', route: 'MOW → DXB', status: 'confirmed' },
];

const events: Event[] = [
    { type: 'flight-delay', text: 'TK420 задержан на 1ч 35м', severity: 'warning' },
    { type: 'hotel-overbook', text: 'Hotel Pearl: риск overbooking', severity: 'danger' },
    { type: 'visa-check', text: 'Пакет JP-883 требует проверку виз', severity: 'info' },
];

const map: StatusMap = {
    confirmed: { text: 'Подтверждено', variant: 'success' },
    ticketing: { text: 'Билетирование', variant: 'warning' },
    warning: { text: 'Warning', variant: 'warning' },
    danger: { text: 'Critical', variant: 'danger' },
    info: { text: 'Info', variant: 'info' },
};
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">Travel Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Пакеты, билеты, отели и мониторинг travel-событий.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />
        <TravelDashboard v-if="activeTab === 'dashboard'" />

        <VCard v-else-if="activeTab === 'packages'" title="Турпакеты">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <article v-for="item in packages" :key="item.name" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="font-semibold" style="color: var(--t-text);">{{ item.name }}</div>
                    <div class="text-sm mt-1" style="color: var(--t-text-2);">Продано: {{ item.sales }}</div>
                    <div class="text-xs" style="color: var(--t-text-3);">Маржа: {{ item.margin }}</div>
                </article>
            </div>
        </VCard>

        <VCard v-else-if="activeTab === 'bookings'" title="Текущие бронирования">
            <div class="space-y-2">
                <article v-for="row in bookings" :key="row.id" class="rounded-xl border p-3 flex items-center justify-between gap-2" style="border-color: var(--t-border);">
                    <div>
                        <div class="font-semibold" style="color: var(--t-text);">{{ row.id }} · {{ row.client }}</div>
                        <div class="text-xs" style="color: var(--t-text-3);">{{ row.route }}</div>
                    </div>
                    <VBadge :text="map[row.status].text" :variant="map[row.status].variant" size="xs" />
                </article>
            </div>
        </VCard>

        <VCard v-else title="Операционные события">
            <div class="space-y-2">
                <article v-for="event in events" :key="event.type + event.text" class="rounded-xl border p-3 flex items-center justify-between gap-2" style="border-color: var(--t-border);">
                    <div>
                        <div class="font-semibold" style="color: var(--t-text);">{{ event.type }}</div>
                        <div class="text-xs" style="color: var(--t-text-3);">{{ event.text }}</div>
                    </div>
                    <VBadge :text="map[event.severity].text" :variant="map[event.severity].variant" size="xs" />
                </article>
            </div>
        </VCard>
    </section>
</template>
