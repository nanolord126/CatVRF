<script setup lang="ts">
/**
 * CatVRF 2026 — TaxiDriverFinance
 * Финансовая сводка водителя: заработок, комиссия, бонусы, штрафы, чистая выплата
 */
import type { TaxiFinanceSummary } from '@/types/taxi';
import {
    BanknotesIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    GiftIcon,
    ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps<{
    summary: TaxiFinanceSummary;
    isLoading?: boolean;
}>();

function formatMoney(amount: number): string {
    return amount.toLocaleString('ru-RU') + ' ₽';
}

interface FinanceItem {
    label: string;
    value: number;
    icon: typeof BanknotesIcon;
    color: string;
    isNegative?: boolean;
}

const items: FinanceItem[] = [
    { label: 'Общий заработок', value: props.summary.total_earnings, icon: BanknotesIcon, color: 'text-emerald-600' },
    { label: 'Комиссия платформы', value: props.summary.total_commission, icon: ArrowTrendingDownIcon, color: 'text-red-500', isNegative: true },
    { label: 'Бонусы', value: props.summary.total_bonuses, icon: GiftIcon, color: 'text-blue-500' },
    { label: 'Штрафы', value: props.summary.total_penalties, icon: ExclamationTriangleIcon, color: 'text-amber-500', isNegative: true },
];
</script>

<template>
    <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) p-5">
        <h3 class="mb-4 text-lg font-bold text-(--t-text)">
            Финансы · {{ summary.period }}
        </h3>

        <!-- Loading state -->
        <div v-if="isLoading" class="space-y-3">
            <div v-for="i in 4" :key="i" class="h-12 animate-pulse rounded-xl bg-(--t-surface-secondary)" />
        </div>

        <template v-else>
            <!-- Finance rows -->
            <div class="mb-4 space-y-2">
                <div
                    v-for="item in items"
                    :key="item.label"
                    class="flex items-center justify-between rounded-xl bg-(--t-surface-secondary) px-4 py-3"
                >
                    <div class="flex items-center gap-3">
                        <component :is="item.icon" class="size-5" :class="item.color" />
                        <span class="text-sm text-(--t-text)">{{ item.label }}</span>
                    </div>
                    <span
                        class="text-sm font-semibold"
                        :class="item.isNegative ? 'text-red-500' : 'text-(--t-text)'"
                    >
                        {{ item.isNegative ? '−' : '' }}{{ formatMoney(item.value) }}
                    </span>
                </div>
            </div>

            <!-- Net payout -->
            <div class="flex items-center justify-between rounded-xl bg-(--t-primary)/10 px-4 py-3">
                <div class="flex items-center gap-3">
                    <ArrowTrendingUpIcon class="size-5 text-(--t-primary)" />
                    <span class="text-sm font-semibold text-(--t-text)">Чистая выплата</span>
                </div>
                <span class="text-lg font-bold text-(--t-primary)">
                    {{ formatMoney(summary.net_payout) }}
                </span>
            </div>

            <!-- Stats row -->
            <div class="mt-4 grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-(--t-surface-secondary) p-3 text-center">
                    <p class="text-2xl font-bold text-(--t-text)">{{ summary.rides_count }}</p>
                    <p class="text-xs text-(--t-text-secondary)">Поездок</p>
                </div>
                <div class="rounded-xl bg-(--t-surface-secondary) p-3 text-center">
                    <p class="text-2xl font-bold text-(--t-text)">{{ summary.average_rating.toFixed(1) }}</p>
                    <p class="text-xs text-(--t-text-secondary)">Ср. рейтинг</p>
                </div>
            </div>
        </template>
    </div>
</template>
