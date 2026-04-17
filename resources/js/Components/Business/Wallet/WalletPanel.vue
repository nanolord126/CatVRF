<script setup>
import { ref } from 'vue';
import WalletDashboard from './WalletDashboard.vue';
import PayoutDashboard from './PayoutDashboard.vue';
import BonusesDashboard from './BonusesDashboard.vue';
import CommissionsDashboard from './CommissionsDashboard.vue';

const activeTab = ref('dashboard');
const tabs = [
    { key: 'dashboard', label: 'Дашборд' },
    { key: 'transactions', label: 'Движения' },
    { key: 'payouts', label: 'Выплаты' },
    { key: 'bonuses', label: 'Бонусы' },
    { key: 'commissions', label: 'Комиссии' },
];
</script>

<template>
    <section class="space-y-4">
        <header>
            <h2 class="text-xl font-bold" style="color: var(--t-text);">Wallet Panel</h2>
            <p class="text-sm" style="color: var(--t-text-3);">Финансовый слой кошелька — балансы, транзакции, выплаты, бонусы, комиссии.</p>
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
        <WalletDashboard v-if="activeTab === 'dashboard'" />
        <PayoutDashboard v-else-if="activeTab === 'payouts'" />
        <BonusesDashboard v-else-if="activeTab === 'bonuses'" />
        <CommissionsDashboard v-else-if="activeTab === 'commissions'" />
        <article v-else class="rounded-2xl border p-5" style="background: var(--t-surface); border-color: var(--t-border); color: var(--t-text-2);">
            Секция «{{ tabs.find(x => x.key === activeTab)?.label }}» в реализации.
        </article>
    </section>
</template>
