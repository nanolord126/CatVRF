<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import AnalyticsDashboard from './AnalyticsDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'funnels', label: 'Воронки', icon: '🔻' },
    { key: 'segments', label: 'Сегменты', icon: '👥' },
];
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">Analytics Vertical Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Tenant-aware аналитика по вертикалям и каналам.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />
        <AnalyticsDashboard v-if="activeTab === 'dashboard'" />
        <VCard v-else-if="activeTab === 'funnels'" title="Воронки">
            <div class="text-sm" style="color: var(--t-text-2);">Просмотр → взаимодействие → заказ → оплата.</div>
        </VCard>
        <VCard v-else title="Сегментация">
            <div class="text-sm" style="color: var(--t-text-2);">New, returning, B2B, high-LTV, churn-risk.</div>
        </VCard>
    </section>
</template>
