<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import AdvertisingDashboard from './AdvertisingDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'campaigns', label: 'Кампании', icon: '📣' },
    { key: 'segments', label: 'Сегменты', icon: '👥' },
];

const campaigns = [
    { name: 'Spring Boost', budget: '1 200 000 ₽', status: 'active' },
    { name: 'Retarget 30d', budget: '480 000 ₽', status: 'active' },
    { name: 'B2B Prospecting', budget: '640 000 ₽', status: 'review' },
];

const map = {
    active: { text: 'Активна', variant: 'success' },
    review: { text: 'Проверка', variant: 'warning' },
};
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">Advertising Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Управление рекламой и окупаемостью.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />
        <AdvertisingDashboard v-if="activeTab === 'dashboard'" />

        <VCard v-else-if="activeTab === 'campaigns'" title="Кампании">
            <div class="space-y-2">
                <article v-for="item in campaigns" :key="item.name" class="rounded-xl border p-3 flex items-center justify-between" style="border-color: var(--t-border);">
                    <div>
                        <div class="font-semibold" style="color: var(--t-text);">{{ item.name }}</div>
                        <div class="text-xs" style="color: var(--t-text-3);">{{ item.budget }}</div>
                    </div>
                    <VBadge :text="map[item.status].text" :variant="map[item.status].variant" size="xs" />
                </article>
            </div>
        </VCard>

        <VCard v-else title="Сегменты аудитории">
            <div class="text-sm" style="color: var(--t-text-2);">Новые, returning, B2B, high-LTV, risk-churn.</div>
        </VCard>
    </section>
</template>
