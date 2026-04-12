<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import ContentDashboard from './ContentDashboard.vue';
const activeTab = ref('dashboard');
const tabs = [{ key: 'dashboard', label: 'Дашборд', icon: '📊' }, { key: 'calendar', label: 'Контент-план', icon: '🗓️' }, { key: 'performance', label: 'Перформанс', icon: '📈' }];
</script>
<template><section class="space-y-4"><header class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-bold" style="color: var(--t-text);">Content Panel</h2><p class="text-sm" style="color: var(--t-text-3);">Контент-план, публикации, качество охвата.</p></div><VBadge text="PRODUCTION" variant="success" size="sm" dot /></header><VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" /><ContentDashboard v-if="activeTab === 'dashboard'" /><VCard v-else-if="activeTab === 'calendar'" title="Контент-план"><div class="text-sm" style="color: var(--t-text-2);">Слоты, каналы, ответственные редакторы.</div></VCard><VCard v-else title="Перформанс"><div class="text-sm" style="color: var(--t-text-2);">Вовлеченность, retention, вклад в конверсию.</div></VCard></section></template>
