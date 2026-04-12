<script setup>
const metrics = [
    { label: 'Заказы кондитерки', value: '1 124', trend: '+7.3%', icon: '🧁' },
    { label: 'Кастом-торты', value: '214', trend: '+11.0%', icon: '🎂' },
    { label: 'Срок производства', value: '9.4ч', trend: '-0.6ч', icon: '⏳' },
    { label: 'Брак', value: '0.8%', trend: '-0.2%', icon: '✅' },
];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="item in metrics" :key="item.label" class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);"><div class="text-xs" style="color: var(--t-text-3);">{{ item.label }}</div><div class="mt-2 text-xl font-bold" style="color: var(--t-text);">{{ item.value }}</div><div class="mt-1 text-xs" style="color: var(--t-primary);">{{ item.trend }} · {{ item.icon }}</div></article></section></template>
