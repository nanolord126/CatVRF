<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import ArtDashboard from './ArtDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'catalog', label: 'Каталог', icon: '🖼️' },
    { key: 'authors', label: 'Авторы', icon: '👤' },
];
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">Art Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Каталог работ, авторы и витрина продаж.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />
        <ArtDashboard v-if="activeTab === 'dashboard'" />
        <VCard v-else-if="activeTab === 'catalog'" title="Каталог">
            <div class="text-sm" style="color: var(--t-text-2);">Живопись, цифровое искусство, скульптуры, limited editions.</div>
        </VCard>
        <VCard v-else title="Авторы">
            <div class="text-sm" style="color: var(--t-text-2);">Рейтинг авторов, оборот, повторные продажи.</div>
        </VCard>
    </section>
</template>
