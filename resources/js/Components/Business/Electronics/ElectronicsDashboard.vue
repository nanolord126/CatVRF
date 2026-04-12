<script setup>
const metrics = [
    { label: 'SKU электроники', value: '42 700', trend: '+5.9%', icon: '🔌' },
    { label: 'GMV', value: '286 млн ₽', trend: '+11.8%', icon: '💹' },
    { label: 'Warranty claims', value: '1.9%', trend: '-0.3%', icon: '🛡️' },
    { label: 'Out-of-stock', value: '4.2%', trend: '-0.6%', icon: '📦' },
];
</script>
<template><section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3"><article v-for="item in metrics" :key="item.label" class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);"><div class="text-xs" style="color: var(--t-text-3);">{{ item.label }}</div><div class="mt-2 text-xl font-bold" style="color: var(--t-text);">{{ item.value }}</div><div class="mt-1 text-xs" style="color: var(--t-primary);">{{ item.trend }} · {{ item.icon }}</div></article></section></template>
