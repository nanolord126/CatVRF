<script setup>
const metrics = [
    { label: 'Активных работ', value: '6 214', trend: '+7.9%', icon: '🎨' },
    { label: 'Комиссии платформы', value: '12.4%', trend: 'stable', icon: '💰' },
    { label: 'Конверсия карточек', value: '5.1%', trend: '+0.6%', icon: '🖼️' },
    { label: 'Повторные покупки', value: '28%', trend: '+1.1%', icon: '🔁' },
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
