<script setup>
const metrics = [
    { label: 'Единиц контента', value: '48 240', trend: '+5.2%', icon: '📝' },
    { label: 'Просмотры', value: '3.2 млн', trend: '+12.3%', icon: '👁️' },
    { label: 'ER', value: '6.1%', trend: '+0.7%', icon: '❤️' },
    { label: 'Публикации/день', value: '214', trend: '+9%', icon: '🚀' },
];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="item in metrics" :key="item.label" class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);"><div class="text-xs" style="color: var(--t-text-3);">{{ item.label }}</div><div class="mt-2 text-xl font-bold" style="color: var(--t-text);">{{ item.value }}</div><div class="mt-1 text-xs" style="color: var(--t-primary);">{{ item.trend }} · {{ item.icon }}</div></article></section></template>
