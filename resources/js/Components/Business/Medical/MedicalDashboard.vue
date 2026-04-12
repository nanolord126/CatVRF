<script setup>
const metrics = [
    { label: 'Приёмов сегодня', value: '267', trend: '+6.6%', icon: '🩺' },
    { label: 'Среднее ожидание', value: '11 мин', trend: '-2.3%', icon: '⏳' },
    { label: 'NPS', value: '72', trend: '+3', icon: '⭐' },
    { label: 'Повторные визиты', value: '58%', trend: '+1.9%', icon: '🔁' },
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
