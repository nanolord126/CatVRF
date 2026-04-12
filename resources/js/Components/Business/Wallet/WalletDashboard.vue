<script setup>
const metrics = [
    { label: 'Текущий баланс', value: '8 420 510 ₽', trend: '+4.3%', icon: '💰' },
    { label: 'Hold amount', value: '612 000 ₽', trend: '+1.1%', icon: '🔒' },
    { label: 'Выплаты сегодня', value: '147', trend: '+9', icon: '🏦' },
    { label: 'Ошибки операций', value: '0', trend: '-2', icon: '🛡️' },
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
