<script setup>
const metrics = [
    { label: 'Проверок за сутки', value: '92 441', trend: '+14.2%', icon: '🛡️' },
    { label: 'Block decision', value: '0.83%', trend: '+0.1%', icon: '⛔' },
    { label: 'Review decision', value: '2.14%', trend: '-0.2%', icon: '🔎' },
    { label: 'Model version', value: '2026-04-09-v2', trend: 'stable', icon: '🧠' },
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
