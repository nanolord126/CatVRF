<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import TaxiDashboard from './TaxiDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'drivers', label: 'Водители', icon: '👤', badge: 8 },
    { key: 'orders', label: 'Заказы', icon: '🚕', badge: 24 },
    { key: 'fraud', label: 'Fraud/SLA', icon: '🛡️' },
];

const drivers = [
    { name: 'А. Воронов', class: 'Comfort', rating: 4.94, online: true, acceptance: 96 },
    { name: 'Е. Павлова', class: 'Economy', rating: 4.89, online: true, acceptance: 94 },
    { name: 'И. Миронов', class: 'Business', rating: 4.98, online: true, acceptance: 97 },
    { name: 'Н. Демина', class: 'Economy', rating: 4.71, online: false, acceptance: 88 },
];

const queueOrders = [
    { id: 'OR-77341', from: 'Тверская 14', to: 'Ленинградский пр-т 22', class: 'Comfort', eta: 7, surge: '1.2x' },
    { id: 'OR-77342', from: 'Арбат 3', to: 'Москва-Сити', class: 'Business', eta: 5, surge: '1.4x' },
    { id: 'OR-77344', from: 'Профсоюзная 77', to: 'Павелецкая', class: 'Economy', eta: 8, surge: '1.0x' },
];

const fraudSla = [
    { key: 'Подозрительные отмены', value: '14', status: 'warning' },
    { key: 'Route deviation > 1км', value: '9', status: 'danger' },
    { key: 'SLA pickup breach', value: '2.6%', status: 'warning' },
    { key: 'Критические блокировки', value: '1', status: 'danger' },
];
</script>

<template>
    <section class="space-y-4">
        <header class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">Taxi Control Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Реал-тайм управление поездками, водителями и SLA.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />

        <TaxiDashboard v-if="activeTab === 'dashboard'" />

        <VCard v-else-if="activeTab === 'drivers'" title="Водители в линии" subtitle="Контроль качества и acceptance rate">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <article v-for="driver in drivers" :key="driver.name" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="flex items-center justify-between gap-2">
                        <div class="font-semibold" style="color: var(--t-text);">{{ driver.name }}</div>
                        <VBadge :text="driver.online ? 'Online' : 'Offline'" :variant="driver.online ? 'success' : 'neutral'" size="xs" :pulse="driver.online" />
                    </div>
                    <div class="mt-2 text-sm" style="color: var(--t-text-2);">
                        Класс: {{ driver.class }} · Рейтинг: {{ driver.rating }}
                    </div>
                    <div class="text-xs mt-1" style="color: var(--t-text-3);">Acceptance: {{ driver.acceptance }}%</div>
                </article>
            </div>
        </VCard>

        <VCard v-else-if="activeTab === 'orders'" title="Очередь заказов" subtitle="Входящие поездки и динамический тариф">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b" style="border-color: var(--t-border); color: var(--t-text-3);">
                            <th class="text-left py-2 pr-3">ID</th>
                            <th class="text-left py-2 pr-3">Маршрут</th>
                            <th class="text-left py-2 pr-3">Класс</th>
                            <th class="text-left py-2 pr-3">ETA</th>
                            <th class="text-right py-2">Surge</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in queueOrders" :key="order.id" class="border-b last:border-b-0" style="border-color: var(--t-border);">
                            <td class="py-2 pr-3 font-semibold" style="color: var(--t-text);">{{ order.id }}</td>
                            <td class="py-2 pr-3" style="color: var(--t-text-2);">{{ order.from }} → {{ order.to }}</td>
                            <td class="py-2 pr-3" style="color: var(--t-text-2);">{{ order.class }}</td>
                            <td class="py-2 pr-3" style="color: var(--t-text);">{{ order.eta }} мин</td>
                            <td class="py-2 text-right font-semibold" style="color: var(--t-primary);">{{ order.surge }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </VCard>

        <VCard v-else title="Fraud + SLA мониторинг" subtitle="Риск-сигналы и сервисные отклонения">
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
                <article v-for="item in fraudSla" :key="item.key" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="text-xs" style="color: var(--t-text-3);">{{ item.key }}</div>
                    <div class="mt-1 text-lg font-bold" style="color: var(--t-text);">{{ item.value }}</div>
                    <VBadge class="mt-2" :text="item.status" :variant="item.status === 'danger' ? 'danger' : 'warning'" size="xs" />
                </article>
            </div>
        </VCard>
    </section>
</template>
