<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import FoodDashboard from './FoodDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'menu', label: 'Меню', icon: '🍽️' },
    { key: 'kitchen', label: 'Кухня', icon: '👨‍🍳' },
    { key: 'delivery', label: 'Доставка', icon: '🛵' },
];

const topDishes = [
    { name: 'Поке с лососем', orders: 186, margin: '34%' },
    { name: 'Том-ям', orders: 162, margin: '29%' },
    { name: 'Паста карбонара', orders: 149, margin: '31%' },
];

const kitchenQueue = [
    { id: 'FD-7741', prep: '15 мин', station: 'Горячий цех', status: 'in-progress' },
    { id: 'FD-7743', prep: '8 мин', station: 'Холодный цех', status: 'ready-soon' },
    { id: 'FD-7746', prep: '19 мин', station: 'Горячий цех', status: 'in-progress' },
];

const courierLoad = [
    { zone: 'Центр', rides: 122, avgEta: '24 мин' },
    { zone: 'Север', rides: 96, avgEta: '29 мин' },
    { zone: 'Юг', rides: 104, avgEta: '27 мин' },
];

const statusMap = {
    'in-progress': { text: 'Готовится', variant: 'info' },
    'ready-soon': { text: 'Скоро готово', variant: 'warning' },
};
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">Food Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Меню, кухня, курьеры и контроль SLA доставки.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />

        <FoodDashboard v-if="activeTab === 'dashboard'" />

        <VCard v-else-if="activeTab === 'menu'" title="Топ блюд">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <article v-for="dish in topDishes" :key="dish.name" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="font-semibold" style="color: var(--t-text);">{{ dish.name }}</div>
                    <div class="text-sm mt-1" style="color: var(--t-text-2);">Заказов: {{ dish.orders }}</div>
                    <div class="text-xs" style="color: var(--t-text-3);">Маржинальность: {{ dish.margin }}</div>
                </article>
            </div>
        </VCard>

        <VCard v-else-if="activeTab === 'kitchen'" title="Очередь кухни">
            <div class="space-y-2">
                <article v-for="row in kitchenQueue" :key="row.id" class="rounded-xl border p-3 flex items-center justify-between gap-2" style="border-color: var(--t-border);">
                    <div>
                        <div class="font-semibold" style="color: var(--t-text);">{{ row.id }}</div>
                        <div class="text-xs" style="color: var(--t-text-3);">{{ row.station }} · {{ row.prep }}</div>
                    </div>
                    <VBadge :text="statusMap[row.status].text" :variant="statusMap[row.status].variant" size="xs" />
                </article>
            </div>
        </VCard>

        <VCard v-else title="Нагрузка доставки">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <article v-for="zone in courierLoad" :key="zone.zone" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="font-semibold" style="color: var(--t-text);">{{ zone.zone }}</div>
                    <div class="text-sm mt-1" style="color: var(--t-text-2);">Рейсов: {{ zone.rides }}</div>
                    <div class="text-xs" style="color: var(--t-text-3);">ETA: {{ zone.avgEta }}</div>
                </article>
            </div>
        </VCard>
    </section>
</template>
