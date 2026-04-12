<script setup>
const metrics = [
    { label: 'Бронирований', value: '842', trend: '+11.1%', icon: '✈️' },
    { label: 'Средний пакет', value: '68 900 ₽', trend: '+4.3%', icon: '🧳' },
    { label: 'Подтверждение', value: '97.1%', trend: '+0.6%', icon: '✅' },
    { label: 'Отмены', value: '3.4%', trend: '-0.7%', icon: '🚫' },
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
