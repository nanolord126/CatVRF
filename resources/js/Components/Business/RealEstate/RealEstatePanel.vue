<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import RealEstateDashboard from './RealEstateDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'listings', label: 'Объекты', icon: '🏢' },
    { key: 'tours', label: 'Показы', icon: '🏘️' },
    { key: 'deals', label: 'Сделки', icon: '🤝' },
];

const listings = [
    { id: 'RE-1021', title: 'ЖК River View, 78м²', price: '24.8 млн ₽', status: 'active' },
    { id: 'RE-1034', title: 'Лофт Таганка, 54м²', price: '18.2 млн ₽', status: 'active' },
    { id: 'RE-1062', title: 'БЦ Савеловский, офис 210м²', price: '57.5 млн ₽', status: 'review' },
];

const tours = [
    { client: 'Семья Климовых', property: 'River View', at: '12:30', agent: 'О. Петрова' },
    { client: 'ООО Меркурий', property: 'БЦ Савеловский', at: '14:00', agent: 'И. Тарасов' },
    { client: 'Дарья П.', property: 'Лофт Таганка', at: '16:15', agent: 'А. Ефимов' },
];

const deals = [
    { id: 'DL-771', stage: 'Due diligence', amount: '32.4 млн ₽', sla: '2 дн' },
    { id: 'DL-775', stage: 'Ипотека', amount: '12.1 млн ₽', sla: '4 дн' },
    { id: 'DL-778', stage: 'Регистрация', amount: '8.9 млн ₽', sla: '1 дн' },
];

const listingStatus = {
    active: { text: 'В продаже', variant: 'success' },
    review: { text: 'Проверка', variant: 'warning' },
};
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">RealEstate Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Управление объектами, показами и воронкой сделок.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />
        <RealEstateDashboard v-if="activeTab === 'dashboard'" />

        <VCard v-else-if="activeTab === 'listings'" title="Каталог объектов">
            <div class="space-y-2">
                <article v-for="row in listings" :key="row.id" class="rounded-xl border p-3 flex items-center justify-between gap-2" style="border-color: var(--t-border);">
                    <div>
                        <div class="font-semibold" style="color: var(--t-text);">{{ row.title }}</div>
                        <div class="text-xs" style="color: var(--t-text-3);">{{ row.id }} · {{ row.price }}</div>
                    </div>
                    <VBadge :text="listingStatus[row.status].text" :variant="listingStatus[row.status].variant" size="xs" />
                </article>
            </div>
        </VCard>

        <VCard v-else-if="activeTab === 'tours'" title="График показов">
            <div class="space-y-2">
                <article v-for="tour in tours" :key="tour.client + tour.at" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="font-semibold" style="color: var(--t-text);">{{ tour.at }} · {{ tour.property }}</div>
                    <div class="text-sm mt-1" style="color: var(--t-text-2);">{{ tour.client }}</div>
                    <div class="text-xs" style="color: var(--t-text-3);">Агент: {{ tour.agent }}</div>
                </article>
            </div>
        </VCard>

        <VCard v-else title="Сделки в работе">
            <div class="space-y-2">
                <article v-for="deal in deals" :key="deal.id" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="font-semibold" style="color: var(--t-text);">{{ deal.id }} · {{ deal.amount }}</div>
                    <div class="text-sm mt-1" style="color: var(--t-text-2);">Этап: {{ deal.stage }}</div>
                    <div class="text-xs" style="color: var(--t-text-3);">SLA: {{ deal.sla }}</div>
                </article>
            </div>
        </VCard>
    </section>
</template>
