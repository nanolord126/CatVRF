<script setup>
const metrics = [
    { label: 'Проектов', value: '186', trend: '+9.0%', icon: '🏗️' },
    { label: 'Смета в работе', value: '428 млн ₽', trend: '+6.4%', icon: '💼' },
    { label: 'Срок этапа', value: '12.2 дн', trend: '-0.9', icon: '📆' },
    { label: 'Отклонение бюджета', value: '2.8%', trend: '-0.3%', icon: '📉' },
];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="item in metrics" :key="item.label" class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);"><div class="text-xs" style="color: var(--t-text-3);">{{ item.label }}</div><div class="mt-2 text-xl font-bold" style="color: var(--t-text);">{{ item.value }}</div><div class="mt-1 text-xs" style="color: var(--t-primary);">{{ item.trend }} · {{ item.icon }}</div></article></section></template>
