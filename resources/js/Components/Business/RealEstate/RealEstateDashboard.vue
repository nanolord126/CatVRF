<script setup>
const metrics = [
    { label: 'Активные объекты', value: '428', trend: '+5.2%', icon: '🏢' },
    { label: 'Туры/день', value: '74', trend: '+9.1%', icon: '🏘️' },
    { label: 'Конверсия в сделку', value: '6.4%', trend: '+0.8%', icon: '📈' },
    { label: 'Средний цикл', value: '19 дн', trend: '-1.3', icon: '🕒' },
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
