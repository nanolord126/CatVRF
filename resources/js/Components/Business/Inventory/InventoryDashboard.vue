<script setup>
const metrics = [
    { label: 'SKU в наличии', value: '18 240', trend: '+132', icon: '📦' },
    { label: 'Низкие остатки', value: '63', trend: '-7', icon: '📉' },
    { label: 'Активные резервы', value: '412', trend: '+15', icon: '⏳' },
    { label: 'Складов online', value: '12', trend: '100%', icon: '🏭' },
];
</script>

<template>
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
        <article v-for="item in metrics" :key="item.label" class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
            <div class="text-xs" style="color: var(--t-text-3);">{{ item.label }}</div>
            <div class="mt-2 flex items-end justify-between">
                <div class="text-xl font-bold" style="color: var(--t-text);">{{ item.value }}</div>
                <div class="text-xs font-semibold" style="color: var(--t-primary);">{{ item.trend }}</div>
            </div>
            <div class="mt-1 text-xl">{{ item.icon }}</div>
        </article>
    </section>
</template>
