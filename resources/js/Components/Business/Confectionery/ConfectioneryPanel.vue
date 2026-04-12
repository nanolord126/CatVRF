<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import ConfectioneryDashboard from './ConfectioneryDashboard.vue';
const activeTab = ref('dashboard');
const tabs = [{ key: 'dashboard', label: 'Дашборд', icon: '📊' }, { key: 'production', label: 'Производство', icon: '🏭' }, { key: 'orders', label: 'Заказы', icon: '🧾' }];
</script>
<template><section class="space-y-4"><header class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-bold" style="color: var(--t-text);">Confectionery Panel</h2><p class="text-sm" style="color: var(--t-text-3);">Кондитерское производство и выдача заказов.</p></div><VBadge text="PRODUCTION" variant="success" size="sm" dot /></header><VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" /><ConfectioneryDashboard v-if="activeTab === 'dashboard'" /><VCard v-else-if="activeTab === 'production'" title="Производство"><div class="text-sm" style="color: var(--t-text-2);">Печи, смены, загрузка, контроль качества.</div></VCard><VCard v-else title="Заказы"><div class="text-sm" style="color: var(--t-text-2);">Кастомные торты, сроки, доставка.</div></VCard></section></template>
