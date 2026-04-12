<script setup>
import { ref } from 'vue';
import VTabs from '../../UI/VTabs.vue';
import VCard from '../../UI/VCard.vue';
import VBadge from '../../UI/VBadge.vue';
import FitnessDashboard from './FitnessDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд', icon: '📊' },
    { key: 'memberships', label: 'Абонементы', icon: '🏋️' },
    { key: 'sessions', label: 'Тренировки', icon: '📅' },
    { key: 'plans', label: 'AI-планы', icon: '🤖' },
];

const memberships = [
    { plan: 'Standard', active: 1840, churn: '3.2%' },
    { plan: 'Pro', active: 1290, churn: '2.4%' },
    { plan: 'Corporate', active: 774, churn: '1.7%' },
];

const sessions = [
    { id: 'FT-6601', trainer: 'М. Соколова', class: 'Functional', slot: '12:00', load: '92%' },
    { id: 'FT-6605', trainer: 'Р. Ильин', class: 'Crossfit', slot: '13:30', load: '88%' },
    { id: 'FT-6611', trainer: 'Н. Денисова', class: 'Yoga', slot: '15:00', load: '74%' },
];

const aiPlans = [
    { goal: 'Похудение', generated: 124, adherence: '67%' },
    { goal: 'Набор массы', generated: 96, adherence: '62%' },
    { goal: 'Выносливость', generated: 66, adherence: '71%' },
];
</script>

<template>
    <section class="space-y-4">
        <header class="flex items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold" style="color: var(--t-text);">Fitness Panel</h2>
                <p class="text-sm" style="color: var(--t-text-3);">Абонементы, классы и AI-персонализация программ.</p>
            </div>
            <VBadge text="PRODUCTION" variant="success" size="sm" dot />
        </header>

        <VTabs v-model="activeTab" :tabs="tabs" variant="segment" size="sm" />
        <FitnessDashboard v-if="activeTab === 'dashboard'" />

        <VCard v-else-if="activeTab === 'memberships'" title="Портфель абонементов">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <article v-for="item in memberships" :key="item.plan" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="font-semibold" style="color: var(--t-text);">{{ item.plan }}</div>
                    <div class="text-sm mt-1" style="color: var(--t-text-2);">Активных: {{ item.active }}</div>
                    <div class="text-xs" style="color: var(--t-text-3);">Churn: {{ item.churn }}</div>
                </article>
            </div>
        </VCard>

        <VCard v-else-if="activeTab === 'sessions'" title="Групповые тренировки">
            <div class="space-y-2">
                <article v-for="session in sessions" :key="session.id" class="rounded-xl border p-3" style="border-color: var(--t-border);">
                    <div class="font-semibold" style="color: var(--t-text);">{{ session.id }} · {{ session.class }}</div>
                    <div class="text-sm mt-1" style="color: var(--t-text-2);">{{ session.trainer }} · {{ session.slot }}</div>
                    <div class="text-xs" style="color: var(--t-text-3);">Заполняемость: {{ session.load }}</div>
                </article>
            </div>
        </VCard>

        <VCard v-else title="AI-планы тренировок">
            <div class="space-y-2">
                <article v-for="item in aiPlans" :key="item.goal" class="rounded-xl border p-3 flex items-center justify-between gap-2" style="border-color: var(--t-border);">
                    <div>
                        <div class="font-semibold" style="color: var(--t-text);">{{ item.goal }}</div>
                        <div class="text-xs" style="color: var(--t-text-3);">Планов: {{ item.generated }}</div>
                    </div>
                    <VBadge :text="item.adherence" variant="info" size="xs" />
                </article>
            </div>
        </VCard>
    </section>
</template>
