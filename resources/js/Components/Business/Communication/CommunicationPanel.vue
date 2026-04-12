<script setup>
import { ref } from 'vue';
import CommunicationDashboard from './CommunicationDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд' },
    { key: 'mailings', label: 'Рассылки' },
    { key: 'templates', label: 'Шаблоны' },
];
</script>

<template>
    <section class="space-y-4">
        <header>
            <h2 class="text-xl font-bold" style="color: var(--t-text);">Communication Panel</h2>
            <p class="text-sm" style="color: var(--t-text-3);">Компоненты для email/push/sms контура.</p>
        </header>
        <div class="flex flex-wrap gap-2">
            <button v-for="tab in tabs" :key="tab.key" class="px-3 py-1.5 rounded-xl border text-sm cursor-pointer"
                :style="activeTab === tab.key
                    ? 'border-color: var(--t-primary); color: var(--t-primary); background: var(--t-primary-dim);'
                    : 'border-color: var(--t-border); color: var(--t-text-2); background: var(--t-surface);'"
                @click="activeTab = tab.key">
                {{ tab.label }}
            </button>
        </div>
        <CommunicationDashboard v-if="activeTab === 'dashboard'" />
        <article v-else class="rounded-2xl border p-5" style="background: var(--t-surface); border-color: var(--t-border); color: var(--t-text-2);">
            Секция «{{ tabs.find(x => x.key === activeTab)?.label }}» в реализации.
        </article>
    </section>
</template>
