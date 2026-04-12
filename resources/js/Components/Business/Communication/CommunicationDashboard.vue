<script setup>
const metrics = [
    { label: 'Отправлено сообщений', value: '42 180', trend: '+12.4%', icon: '💬' },
    { label: 'Open rate', value: '38.2%', trend: '+2.1%', icon: '📨' },
    { label: 'Push delivery', value: '97.9%', trend: '+0.5%', icon: '📲' },
    { label: 'Rate-limit incidents', value: '3', trend: '-4', icon: '🧯' },
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
