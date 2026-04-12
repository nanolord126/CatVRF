<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import BooksAndLiteratureDashboard from './BooksAndLiteratureDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'catalog', label: 'Каталог', icon: '📚' },
    { key: 'publishers', label: 'Издательства', icon: '🏛️' },
];
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">BooksAndLiterature Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Книги, издательства и тематические подборки.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />
        <BooksAndLiteratureDashboard v-if="activeTab === 'dashboard'" />
        <VCard v-else-if="activeTab === 'catalog'" title="Каталог">
            <div class="text-sm" style="color: var(--t-text-2);">Художественная, нон-фикшн, учебная, комиксы, коллекционные издания.</div>
        </VCard>
        <VCard v-else title="Издательства">
            <div class="text-sm" style="color: var(--t-text-2);">Оборот, оборачиваемость, возвраты и бэклист.</div>
        </VCard>
    </section>
</template>
