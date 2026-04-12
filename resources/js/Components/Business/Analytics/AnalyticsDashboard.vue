<script setup>
const metrics = [
    { label: 'GMV 30д', value: '124.6 млн ₽', trend: '+12.1%', icon: '💹' },
    { label: 'Конверсия', value: '4.9%', trend: '+0.4%', icon: '🎯' },
    { label: 'ARPU', value: '3 240 ₽', trend: '+7.2%', icon: '📊' },
    { label: 'Новые пользователи', value: '18 440', trend: '+10.8%', icon: '👤' },
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
