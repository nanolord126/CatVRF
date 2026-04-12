<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import ConstructionAndRepairDashboard from './ConstructionAndRepairDashboard.vue';
const activeTab = ref('dashboard');
const tabs = [{ key: 'dashboard', label: 'Дашборд', icon: '📊' }, { key: 'projects', label: 'Проекты', icon: '🏗️' }, { key: 'budget', label: 'Бюджет', icon: '💰' }];
</script>
<template><section class="space-y-4"><header class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-bold" style="color: var(--t-text);">ConstructionAndRepair Panel</h2><p class="text-sm" style="color: var(--t-text-3);">Проекты ремонта, сметы и контроль этапов.</p></div><VBadge text="PRODUCTION" variant="success" size="sm" dot /></header><VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" /><ConstructionAndRepairDashboard v-if="activeTab === 'dashboard'" /><VCard v-else-if="activeTab === 'projects'" title="Проекты"><div class="text-sm" style="color: var(--t-text-2);">Этапы, бригады, сроки и риск-сигналы.</div></VCard><VCard v-else title="Бюджеты"><div class="text-sm" style="color: var(--t-text-2);">План/факт, перерасход, экономия.</div></VCard></section></template>
