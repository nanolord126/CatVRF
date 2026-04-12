<script setup>
const metrics = [
    { label: 'Инициации платежей', value: '1 248', trend: '+8.1%', icon: '💳' },
    { label: 'Успешные capture', value: '1 192', trend: '+7.6%', icon: '✅' },
    { label: 'Refund rate', value: '1.8%', trend: '-0.4%', icon: '↩️' },
    { label: 'Pending webhook', value: '6', trend: '-2', icon: '📨' },
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
