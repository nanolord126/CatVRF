<script setup>
const metrics = [
    { label: 'Лотов в продаже', value: '12 084', trend: '+4.8%', icon: '🧿' },
    { label: 'AOV', value: '8 920 ₽', trend: '+2.3%', icon: '💳' },
    { label: 'Проверка подлинности', value: '98.4%', trend: '+0.4%', icon: '🔍' },
    { label: 'Споры', value: '0.9%', trend: '-0.1%', icon: '🛡️' },
];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="item in metrics" :key="item.label" class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);"><div class="text-xs" style="color: var(--t-text-3);">{{ item.label }}</div><div class="mt-2 text-xl font-bold" style="color: var(--t-text);">{{ item.value }}</div><div class="mt-1 text-xs" style="color: var(--t-primary);">{{ item.trend }} · {{ item.icon }}</div></article></section></template>
