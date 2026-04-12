<script setup>
const metrics = [
    { label: 'Активные рейсы', value: '24', trend: '+6.2%', icon: '🚚' },
    { label: 'В пути', value: '17', trend: '+3.1%', icon: '🛰️' },
    { label: 'SLA доставки', value: '96.4%', trend: '+1.4%', icon: '⏱️' },
    { label: 'Средний ETA', value: '34 мин', trend: '-4.8%', icon: '📍' },
];

const queue = [
    'Пересчёт маршрутов каждые 3 минуты',
    'Контроль отклонений от маршрута',
    'Проверка курьеров по tenant-scoping',
];
</script>

<template>
    <section class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
            <article
                v-for="item in metrics"
                :key="item.label"
                class="rounded-2xl border p-4"
                style="background: var(--t-surface); border-color: var(--t-border);"
            >
                <div class="text-xs" style="color: var(--t-text-3);">{{ item.label }}</div>
                <div class="mt-2 flex items-end justify-between">
                    <div class="text-xl font-bold" style="color: var(--t-text);">{{ item.value }}</div>
                    <div class="text-xs font-semibold" style="color: var(--t-primary);">{{ item.trend }}</div>
                </div>
                <div class="mt-1 text-xl">{{ item.icon }}</div>
            </article>
        </div>

        <article class="rounded-2xl border p-4" style="background: var(--t-surface); border-color: var(--t-border);">
            <h3 class="text-sm font-bold" style="color: var(--t-text);">Операционный фокус</h3>
            <ul class="mt-2 space-y-1.5 text-sm" style="color: var(--t-text-2);">
                <li v-for="item in queue" :key="item">• {{ item }}</li>
            </ul>
        </article>
    </section>
</template>
