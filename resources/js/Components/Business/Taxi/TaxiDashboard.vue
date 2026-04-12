<script setup>
import { computed, ref } from 'vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';

const metrics = [
    { label: 'Активные поездки', value: '312', trend: '+10.2%', icon: '🚕' },
    { label: 'Средний ETA', value: '6.8 мин', trend: '-4.1%', icon: '🛰️' },
    { label: 'On-time pickup', value: '95.3%', trend: '+1.2%', icon: '⏱️' },
    { label: 'Отмены', value: '2.1%', trend: '-0.5%', icon: '❌' },
];

const liveTrips = ref([
    { id: 'TX-24918', driver: 'А. Воронов', vehicle: 'Kia Rio', zone: 'ЦАО', eta: 5, status: 'in_progress', fare: 740 },
    { id: 'TX-24921', driver: 'Е. Павлова', vehicle: 'Skoda Octavia', zone: 'СЗАО', eta: 7, status: 'pickup', fare: 520 },
    { id: 'TX-24925', driver: 'И. Миронов', vehicle: 'Toyota Camry', zone: 'ЮАО', eta: 4, status: 'in_progress', fare: 910 },
    { id: 'TX-24927', driver: 'Н. Демина', vehicle: 'VW Polo', zone: 'ВАО', eta: 9, status: 'delayed', fare: 460 },
    { id: 'TX-24928', driver: 'К. Романов', vehicle: 'Hyundai Solaris', zone: 'ЦАО', eta: 6, status: 'pickup', fare: 650 },
]);

const fleet = ref([
    { class: 'Economy', online: 152, utilization: 82, avgRideMin: 19 },
    { class: 'Comfort', online: 84, utilization: 78, avgRideMin: 23 },
    { class: 'Business', online: 31, utilization: 69, avgRideMin: 31 },
    { class: 'Van', online: 14, utilization: 58, avgRideMin: 37 },
]);

const incidents = ref([
    { id: 'INC-8811', type: 'fraud-check', severity: 'high', text: '3 отмены подряд с одного девайса', at: '10:22' },
    { id: 'INC-8814', type: 'route-deviation', severity: 'medium', text: 'Отклонение от маршрута > 1.8 км', at: '10:29' },
    { id: 'INC-8816', type: 'delay-risk', severity: 'low', text: 'Пик трафика в зоне ЮЗАО', at: '10:31' },
]);

const statusMap = {
    in_progress: { text: 'В пути', variant: 'info' },
    pickup: { text: 'Подача', variant: 'warning' },
    delayed: { text: 'Риск SLA', variant: 'danger' },
};

const severityMap = {
    low: { text: 'Low', variant: 'neutral' },
    medium: { text: 'Medium', variant: 'warning' },
    high: { text: 'High', variant: 'danger' },
};

const totalOnline = computed(() => fleet.value.reduce((acc, item) => acc + item.online, 0));
const weightedUtilization = computed(() => {
    const cap = fleet.value.reduce((acc, item) => acc + item.online, 0);
    if (!cap) return 0;
    const util = fleet.value.reduce((acc, item) => acc + item.online * item.utilization, 0);
    return Math.round(util / cap);
});
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

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <VCard title="Live Trips" subtitle="Оперативный контур поездок" class="xl:col-span-2">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b" style="border-color: var(--t-border); color: var(--t-text-3);">
                                <th class="text-left py-2 pr-3">Поездка</th>
                                <th class="text-left py-2 pr-3">Водитель</th>
                                <th class="text-left py-2 pr-3">Зона</th>
                                <th class="text-left py-2 pr-3">ETA</th>
                                <th class="text-left py-2 pr-3">Статус</th>
                                <th class="text-right py-2">Тариф</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="trip in liveTrips"
                                :key="trip.id"
                                class="border-b last:border-b-0"
                                style="border-color: var(--t-border);"
                            >
                                <td class="py-2 pr-3 font-semibold" style="color: var(--t-text);">{{ trip.id }}</td>
                                <td class="py-2 pr-3" style="color: var(--t-text-2);">
                                    {{ trip.driver }}
                                    <div class="text-xs" style="color: var(--t-text-3);">{{ trip.vehicle }}</div>
                                </td>
                                <td class="py-2 pr-3" style="color: var(--t-text-2);">{{ trip.zone }}</td>
                                <td class="py-2 pr-3" style="color: var(--t-text);">{{ trip.eta }} мин</td>
                                <td class="py-2 pr-3">
                                    <VBadge
                                        :text="statusMap[trip.status].text"
                                        :variant="statusMap[trip.status].variant"
                                        size="xs"
                                        :pulse="trip.status === 'pickup'"
                                    />
                                </td>
                                <td class="py-2 text-right font-semibold" style="color: var(--t-text);">{{ trip.fare }} ₽</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </VCard>

            <VCard title="SLA и инциденты" subtitle="Fraud / route / delay">
                <div class="space-y-2">
                    <article
                        v-for="incident in incidents"
                        :key="incident.id"
                        class="rounded-xl border p-3"
                        style="border-color: var(--t-border);"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-semibold" style="color: var(--t-text);">{{ incident.id }}</span>
                            <VBadge :text="severityMap[incident.severity].text" :variant="severityMap[incident.severity].variant" size="xs" />
                        </div>
                        <div class="mt-1 text-sm" style="color: var(--t-text-2);">{{ incident.text }}</div>
                        <div class="mt-1 text-xs" style="color: var(--t-text-3);">{{ incident.type }} · {{ incident.at }}</div>
                    </article>
                </div>
            </VCard>
        </div>

        <VCard title="Эффективность автопарка" subtitle="Utilization по классам">
            <div class="mb-3 text-sm" style="color: var(--t-text-2);">
                Онлайн: <span class="font-semibold" style="color: var(--t-text);">{{ totalOnline }}</span>
                · Средняя загрузка: <span class="font-semibold" style="color: var(--t-text);">{{ weightedUtilization }}%</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
                <article v-for="item in fleet" :key="item.class" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="text-sm font-semibold" style="color: var(--t-text);">{{ item.class }}</div>
                    <div class="mt-1 text-xs" style="color: var(--t-text-3);">Онлайн: {{ item.online }}</div>
                    <div class="mt-2 h-1.5 rounded-full overflow-hidden" style="background: var(--t-border);">
                        <div class="h-full rounded-full taxi-util-bar" :style="{ '--taxi-util': item.utilization + '%' }" />
                    </div>
                    <div class="mt-2 text-xs" style="color: var(--t-text-2);">Загрузка: {{ item.utilization }}%</div>
                    <div class="text-xs" style="color: var(--t-text-3);">Средняя поездка: {{ item.avgRideMin }} мин</div>
                </article>
            </div>
        </VCard>
    </section>
</template>

<style scoped>
.taxi-util-bar {
    inline-size: var(--taxi-util);
    background: var(--t-primary);
}
</style>
