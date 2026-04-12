<script setup>
const metrics = [
    { label: 'Событий в работе', value: '126', trend: '+9.1%', icon: '🎉' },
    { label: 'Средний чек', value: '184 000 ₽', trend: '+6.3%', icon: '💼' },
    { label: 'On-time delivery', value: '95.8%', trend: '+1.2%', icon: '⏱️' },
    { label: 'Повторные клиенты', value: '38%', trend: '+2.0%', icon: '🔁' },
];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="item in metrics" :key="item.label" class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);"><div class="text-xs" style="color: var(--t-text-3);">{{ item.label }}</div><div class="mt-2 text-xl font-bold" style="color: var(--t-text);">{{ item.value }}</div><div class="mt-1 text-xs" style="color: var(--t-primary);">{{ item.trend }} · {{ item.icon }}</div></article></section></template>
