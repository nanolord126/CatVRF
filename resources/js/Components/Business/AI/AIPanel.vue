<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import AIDashboard from './AIDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'constructors', label: 'Конструкторы', icon: '🧠' },
    { key: 'quotas', label: 'Квоты', icon: '📦' },
];
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">AI Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Мониторинг AI-конструкторов и SLA.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />
        <AIDashboard v-if="activeTab === 'dashboard'" />
        <VCard v-else-if="activeTab === 'constructors'" title="Вертикальные конструкторы">
            <div class="text-sm" style="color: var(--t-text-2);">Beauty, Fashion, Food, Furniture, Travel, Medical, Auto, Hotels.</div>
        </VCard>
        <VCard v-else title="Квоты и лимиты">
            <div class="text-sm" style="color: var(--t-text-2);">Контроль расходов, лимитов tenant и rate-limit heavy inference.</div>
        </VCard>
    </section>
</template>
