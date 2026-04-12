<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import CollectiblesDashboard from './CollectiblesDashboard.vue';
const activeTab = ref('dashboard');
const tabs = [{ key: 'dashboard', label: 'Дашборд', icon: '📊' }, { key: 'lots', label: 'Лоты', icon: '🏷️' }, { key: 'verification', label: 'Проверка', icon: '✅' }];
</script>
<template><section class="space-y-4"><header class="flex items-center justify-between gap-3"><div><h2 class="text-xl font-bold" style="color: var(--t-text);">Collectibles Panel</h2><p class="text-sm" style="color: var(--t-text-3);">Лоты, аутентификация, споры.</p></div><VBadge text="PRODUCTION" variant="success" size="sm" dot /></header><VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" /><CollectiblesDashboard v-if="activeTab === 'dashboard'" /><VCard v-else-if="activeTab === 'lots'" title="Лоты"><div class="text-sm" style="color: var(--t-text-2);">Управление редкими товарами и витриной.</div></VCard><VCard v-else title="Проверка"><div class="text-sm" style="color: var(--t-text-2);">Происхождение, сертификаты, доверие продавца.</div></VCard></section></template>
