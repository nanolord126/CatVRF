<script setup>
const metrics = [
    { label: 'AI-запросов/сутки', value: '24 820', trend: '+18%', icon: '🤖' },
    { label: 'Успешность', value: '99.2%', trend: '+0.3%', icon: '✅' },
    { label: 'Средний latency', value: '1.4с', trend: '-0.2с', icon: '⚡' },
    { label: 'Квота', value: '78%', trend: '+4%', icon: '📊' },
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
