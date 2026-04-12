<script setup>
const metrics = [
    { label: 'Продажи коллекций', value: '2 140', trend: '+9.2%', icon: '👗' },
    { label: 'AR-примерки', value: '5 320', trend: '+16.4%', icon: '🪞' },
    { label: 'Sell-through', value: '63%', trend: '+2.7%', icon: '📦' },
    { label: 'Возвраты', value: '7.8%', trend: '-1.1%', icon: '↩️' },
];
</script>

<template>
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
        <article v-for="item in metrics" :key="item.label" class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
            <div class="text-xs" style="color: var(--t-text-3);">{{ item.label }}</div>
            <div class="mt-2 text-xl font-bold" style="color: var(--t-text);">{{ item.value }}</div>
            <div class="mt-1 text-xs" style="color: var(--t-primary);">{{ item.trend }} · {{ item.icon }}</div>
        </article>
    </section>
</template>
