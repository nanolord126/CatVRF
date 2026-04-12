<script setup>
const metrics = [
    { label: 'Доставки сегодня', value: '1 604', trend: '+9.9%', icon: '🚚' },
    { label: 'On-time rate', value: '94.7%', trend: '+1.2%', icon: '🕒' },
    { label: 'Среднее время', value: '41 мин', trend: '-3.6%', icon: '📍' },
    { label: 'Cancelled', value: '1.1%', trend: '-0.2%', icon: '❌' },
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
