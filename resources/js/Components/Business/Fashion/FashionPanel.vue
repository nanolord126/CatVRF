<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import FashionDashboard from './FashionDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'collections', label: 'Коллекции', icon: '👗' },
    { key: 'tryon', label: 'AR-примерка', icon: '🪞' },
    { key: 'returns', label: 'Возвраты', icon: '↩️' },
];

const collections = [
    { name: 'Spring Capsule 2026', sellThrough: '68%', stock: 1220 },
    { name: 'Office Essentials', sellThrough: '61%', stock: 940 },
    { name: 'Weekend Denim', sellThrough: '72%', stock: 760 },
];

const tryOn = [
    { segment: 'New users', sessions: 2140, conversion: '11.2%' },
    { segment: 'Returning', sessions: 3180, conversion: '16.8%' },
    { segment: 'B2B buyers', sessions: 420, conversion: '22.4%' },
];

const returns = [
    { reason: 'Размер', share: '43%' },
    { reason: 'Ожидание/факт', share: '28%' },
    { reason: 'Качество', share: '17%' },
    { reason: 'Другое', share: '12%' },
];
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">Fashion Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Управление коллекциями, AR-воронкой и возвратами.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />
        <FashionDashboard v-if="activeTab === 'dashboard'" />

        <VCard v-else-if="activeTab === 'collections'" title="Коллекции">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <article v-for="item in collections" :key="item.name" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="font-semibold" style="color: var(--t-text);">{{ item.name }}</div>
                    <div class="text-sm mt-1" style="color: var(--t-text-2);">Sell-through: {{ item.sellThrough }}</div>
                    <div class="text-xs" style="color: var(--t-text-3);">Остаток: {{ item.stock }}</div>
                </article>
            </div>
        </VCard>

        <VCard v-else-if="activeTab === 'tryon'" title="AR Try-on performance">
            <div class="space-y-2">
                <article v-for="item in tryOn" :key="item.segment" class="rounded-xl border p-3 flex items-center justify-between gap-2" style="border-color: var(--t-border);">
                    <div>
                        <div class="font-semibold" style="color: var(--t-text);">{{ item.segment }}</div>
                        <div class="text-xs" style="color: var(--t-text-3);">Сессий: {{ item.sessions }}</div>
                    </div>
                    <VBadge :text="item.conversion" variant="info" size="xs" />
                </article>
            </div>
        </VCard>

        <VCard v-else title="Причины возвратов">
            <div class="space-y-2">
                <article v-for="item in returns" :key="item.reason" class="rounded-xl border p-3 flex items-center justify-between gap-2" style="border-color: var(--t-border);">
                    <span class="font-semibold" style="color: var(--t-text);">{{ item.reason }}</span>
                    <VBadge :text="item.share" variant="warning" size="xs" />
                </article>
            </div>
        </VCard>
    </section>
</template>
