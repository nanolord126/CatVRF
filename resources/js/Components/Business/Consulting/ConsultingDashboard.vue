<script setup>
const metrics = [
    { label: 'Активных кейсов', value: '304', trend: '+6.8%', icon: '🧠' },
    { label: 'Billable utilization', value: '82%', trend: '+1.4%', icon: '📈' },
    { label: 'NPS клиентов', value: '74', trend: '+3', icon: '⭐' },
    { label: 'Средний контракт', value: '1.42 млн ₽', trend: '+5.1%', icon: '💼' },
];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="item in metrics" :key="item.label" class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);"><div class="text-xs" style="color: var(--t-text-3);">{{ item.label }}</div><div class="mt-2 text-xl font-bold" style="color: var(--t-text);">{{ item.value }}</div><div class="mt-1 text-xs" style="color: var(--t-primary);">{{ item.trend }} · {{ item.icon }}</div></article></section></template>
