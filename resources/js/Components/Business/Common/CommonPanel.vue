<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import CommonDashboard from './CommonDashboard.vue';
const activeTab = ref('dashboard');
const tabs = [{ key: 'dashboard', label: 'Дашборд', icon: '📊' }, { key: 'shared', label: 'Shared', icon: '🧩' }, { key: 'ops', label: 'Ops', icon: '⚙️' }];
</script>
<template><section class="space-y-4"><header class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-bold" style="color: var(--t-text);">Common Panel</h2><p class="text-sm" style="color: var(--t-text-3);">Общие сервисы и инфраструктурные KPI.</p></div><VBadge text="PRODUCTION" variant="success" size="sm" dot /></header><VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" /><CommonDashboard v-if="activeTab === 'dashboard'" /><VCard v-else-if="activeTab === 'shared'" title="Shared Components"><div class="text-sm" style="color: var(--t-text-2);">Единые модули, контракты API, версия схем.</div></VCard><VCard v-else title="Operations"><div class="text-sm" style="color: var(--t-text-2);">Статус очередей, ошибок и стабильности.</div></VCard></section></template>
