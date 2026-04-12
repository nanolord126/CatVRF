<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import ElectronicsDashboard from './ElectronicsDashboard.vue';
const activeTab = ref('dashboard');
const tabs = [{ key: 'dashboard', label: 'Дашборд', icon: '📊' }, { key: 'catalog', label: 'Каталог', icon: '📱' }, { key: 'service', label: 'Гарантии', icon: '🛠️' }];
</script>
<template><section class="space-y-4"><header class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-bold" style="color: var(--t-text);">Electronics Panel</h2><p class="text-sm" style="color: var(--t-text-3);">Каталог техники, гарантия и сервисные кейсы.</p></div><VBadge text="PRODUCTION" variant="success" size="sm" dot /></header><VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" /><ElectronicsDashboard v-if="activeTab === 'dashboard'" /><VCard v-else-if="activeTab === 'catalog'" title="Каталог"><div class="text-sm" style="color: var(--t-text-2);">Смартфоны, ноутбуки, аксессуары, периферия.</div></VCard><VCard v-else title="Гарантии"><div class="text-sm" style="color: var(--t-text-2);">RMA, обращения, сроки ремонта.</div></VCard></section></template>
