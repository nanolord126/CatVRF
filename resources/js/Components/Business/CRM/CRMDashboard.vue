<script setup>
const metrics = [
    { label: 'Активные клиенты', value: '9 842', trend: '+3.5%', icon: '👥' },
    { label: 'Новые лиды', value: '326', trend: '+11.2%', icon: '🧲' },
    { label: 'Конверсия лидов', value: '18.4%', trend: '+1.7%', icon: '🎯' },
    { label: 'Риск churn', value: '6.2%', trend: '-0.9%', icon: '⚠️' },
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
