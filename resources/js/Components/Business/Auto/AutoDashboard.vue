<script setup>
const metrics = [
    { label: 'Записей на СТО', value: '184', trend: '+7.1%', icon: '🔧' },
    { label: 'Средний чек', value: '12 400 ₽', trend: '+2.9%', icon: '💳' },
    { label: 'Доступность слотов', value: '92%', trend: '+1.5%', icon: '📅' },
    { label: 'Возвраты', value: '1.2%', trend: '-0.3%', icon: '↩️' },
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
