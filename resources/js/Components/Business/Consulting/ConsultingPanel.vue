<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import ConsultingDashboard from './ConsultingDashboard.vue';
const activeTab = ref('dashboard');
const tabs = [{ key: 'dashboard', label: 'Дашборд', icon: '📊' }, { key: 'pipeline', label: 'Pipeline', icon: '🔻' }, { key: 'delivery', label: 'Delivery', icon: '📦' }];
</script>
<template><section class="space-y-4"><header class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-bold" style="color: var(--t-text);">Consulting Panel</h2><p class="text-sm" style="color: var(--t-text-3);">Продажи консалтинга и delivery-контур.</p></div><VBadge text="PRODUCTION" variant="success" size="sm" dot /></header><VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" /><ConsultingDashboard v-if="activeTab === 'dashboard'" /><VCard v-else-if="activeTab === 'pipeline'" title="Pipeline"><div class="text-sm" style="color: var(--t-text-2);">Лиды, пресейл, вероятность сделки.</div></VCard><VCard v-else title="Delivery"><div class="text-sm" style="color: var(--t-text-2);">Этапы проектов, SLA и profitability.</div></VCard></section></template>
