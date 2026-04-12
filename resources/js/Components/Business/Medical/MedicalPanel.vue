<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import MedicalDashboard from './MedicalDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'appointments', label: 'Приёмы', icon: '🩺' },
    { key: 'triage', label: 'AI Triage', icon: '🤖' },
    { key: 'quality', label: 'Качество', icon: '🛡️' },
];

const appointments = [
    { id: 'MD-8201', doctor: 'Е. Мартынова', slot: '11:20', profile: 'Терапия', status: 'confirmed' },
    { id: 'MD-8204', doctor: 'А. Рязанцев', slot: '11:40', profile: 'Кардио', status: 'confirmed' },
    { id: 'MD-8208', doctor: 'И. Блинова', slot: '12:10', profile: 'Эндокринология', status: 'pending' },
];

const triageQueue = [
    { id: 'TR-301', risk: 'medium', eta: '4 мин', category: 'Острое состояние' },
    { id: 'TR-302', risk: 'low', eta: '2 мин', category: 'Плановый осмотр' },
    { id: 'TR-303', risk: 'high', eta: '1 мин', category: 'Кардио жалобы' },
];

const quality = [
    { metric: 'NPS', value: '72', trend: '+3' },
    { metric: 'Повторные обращения 30д', value: '14.2%', trend: '-0.8%' },
    { metric: 'Отклонения протоколов', value: '0.6%', trend: '-0.1%' },
];

const map = {
    confirmed: { text: 'Подтвержден', variant: 'success' },
    pending: { text: 'Ожидает', variant: 'warning' },
    low: { text: 'Low', variant: 'neutral' },
    medium: { text: 'Medium', variant: 'warning' },
    high: { text: 'High', variant: 'danger' },
};
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">Medical Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Триаж, поток пациентов и quality-контроль.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />
        <MedicalDashboard v-if="activeTab === 'dashboard'" />

        <VCard v-else-if="activeTab === 'appointments'" title="Ближайшие приёмы">
            <div class="space-y-2">
                <article v-for="row in appointments" :key="row.id" class="rounded-xl border p-3 flex items-center justify-between gap-2" style="border-color: var(--t-border);">
                    <div>
                        <div class="font-semibold" style="color: var(--t-text);">{{ row.id }} · {{ row.slot }}</div>
                        <div class="text-sm mt-1" style="color: var(--t-text-2);">{{ row.doctor }} · {{ row.profile }}</div>
                    </div>
                    <VBadge :text="map[row.status].text" :variant="map[row.status].variant" size="xs" />
                </article>
            </div>
        </VCard>

        <VCard v-else-if="activeTab === 'triage'" title="AI triage очередь">
            <div class="space-y-2">
                <article v-for="item in triageQueue" :key="item.id" class="rounded-xl border p-3 flex items-center justify-between gap-2" style="border-color: var(--t-border);">
                    <div>
                        <div class="font-semibold" style="color: var(--t-text);">{{ item.id }} · {{ item.category }}</div>
                        <div class="text-xs" style="color: var(--t-text-3);">Время оценки: {{ item.eta }}</div>
                    </div>
                    <VBadge :text="map[item.risk].text" :variant="map[item.risk].variant" size="xs" />
                </article>
            </div>
        </VCard>

        <VCard v-else title="Quality metrics">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <article v-for="row in quality" :key="row.metric" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="text-xs" style="color: var(--t-text-3);">{{ row.metric }}</div>
                    <div class="mt-1 text-xl font-bold" style="color: var(--t-text);">{{ row.value }}</div>
                    <div class="text-xs" style="color: var(--t-primary);">{{ row.trend }}</div>
                </article>
            </div>
        </VCard>
    </section>
</template>
