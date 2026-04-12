<script setup lang="ts">
/**
 * CatVRF 2026 — TaxiDriverBonusPenalty
 * Бонусы и штрафы водителя: список, тип, сумма, причина
 */
import { computed } from 'vue';
import type { TaxiBonusPenalty } from '@/types/taxi';
import { GiftIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps<{
    items: TaxiBonusPenalty[];
    isLoading?: boolean;
}>();

const sortedItems = computed(() =>
    [...props.items].sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()),
);

const totalBonuses = computed(() =>
    props.items.filter(i => i.type === 'bonus').reduce((sum, i) => sum + i.amount, 0),
);

const totalPenalties = computed(() =>
    props.items.filter(i => i.type === 'penalty').reduce((sum, i) => sum + i.amount, 0),
);

function formatMoney(amount: number): string {
    return amount.toLocaleString('ru-RU') + ' ₽';
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('ru-RU', {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <div class="rounded-2xl border border-(--t-border) bg-(--t-surface) p-5">
        <h3 class="mb-4 text-lg font-bold text-(--t-text)">Бонусы и штрафы</h3>

        <!-- Summary -->
        <div class="mb-4 grid grid-cols-2 gap-3">
            <div class="rounded-xl bg-emerald-50 p-3 text-center dark:bg-emerald-950/40">
                <GiftIcon class="mx-auto mb-1 size-6 text-emerald-600 dark:text-emerald-400" />
                <p class="text-lg font-bold text-emerald-700 dark:text-emerald-300">+{{ formatMoney(totalBonuses) }}</p>
                <p class="text-xs text-emerald-600 dark:text-emerald-400">Бонусы</p>
            </div>
            <div class="rounded-xl bg-red-50 p-3 text-center dark:bg-red-950/40">
                <ExclamationTriangleIcon class="mx-auto mb-1 size-6 text-red-600 dark:text-red-400" />
                <p class="text-lg font-bold text-red-700 dark:text-red-300">−{{ formatMoney(totalPenalties) }}</p>
                <p class="text-xs text-red-600 dark:text-red-400">Штрафы</p>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="space-y-2">
            <div v-for="i in 3" :key="i" class="h-16 animate-pulse rounded-xl bg-(--t-surface-secondary)" />
        </div>

        <!-- Empty -->
        <div
            v-else-if="sortedItems.length === 0"
            class="py-8 text-center text-sm text-(--t-text-secondary)"
        >
            Нет бонусов или штрафов
        </div>

        <!-- List -->
        <div v-else class="space-y-2">
            <div
                v-for="item in sortedItems"
                :key="item.id"
                class="flex items-start gap-3 rounded-xl bg-(--t-surface-secondary) p-3"
            >
                <div
                    class="flex size-9 shrink-0 items-center justify-center rounded-full"
                    :class="item.type === 'bonus' ? 'bg-emerald-100 dark:bg-emerald-900/40' : 'bg-red-100 dark:bg-red-900/40'"
                >
                    <component
                        :is="item.type === 'bonus' ? GiftIcon : ExclamationTriangleIcon"
                        class="size-5"
                        :class="item.type === 'bonus' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
                    />
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-(--t-text)">{{ item.reason }}</p>
                    <p v-if="item.description" class="mt-0.5 text-xs text-(--t-text-secondary)">
                        {{ item.description }}
                    </p>
                    <p class="mt-1 text-xs text-(--t-text-secondary)">
                        {{ formatDate(item.created_at) }} · {{ item.created_by }}
                    </p>
                </div>
                <span
                    class="shrink-0 text-sm font-bold"
                    :class="item.type === 'bonus' ? 'text-emerald-600' : 'text-red-500'"
                >
                    {{ item.type === 'bonus' ? '+' : '−' }}{{ formatMoney(item.amount) }}
                </span>
            </div>
        </div>
    </div>
</template>
