<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import CarRentalDashboard from './CarRentalDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'fleet', label: 'Автопарк', icon: '🚙' },
    { key: 'rentals', label: 'Аренды', icon: '📅' },
];
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">CarRental Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Операции аренды, парк и SLA возврата.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />
        <CarRentalDashboard v-if="activeTab === 'dashboard'" />
        <VCard v-else-if="activeTab === 'fleet'" title="Автопарк">
            <div class="text-sm" style="color: var(--t-text-2);">Economy, Comfort, SUV, Premium. Контроль обслуживания и пробега.</div>
        </VCard>
        <VCard v-else title="Текущие аренды">
            <div class="text-sm" style="color: var(--t-text-2);">Продления, возвраты, претензии и штрафы.</div>
        </VCard>
    </section>
</template>
