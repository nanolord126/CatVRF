<script setup>
const metrics = [
    { label: 'Активных аренд', value: '482', trend: '+7.1%', icon: '🚗' },
    { label: 'Загрузка парка', value: '84%', trend: '+1.6%', icon: '📈' },
    { label: 'Средний срок', value: '2.7 дня', trend: '+0.2', icon: '🕒' },
    { label: 'Late returns', value: '3.2%', trend: '-0.4%', icon: '⚠️' },
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
