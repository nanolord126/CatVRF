<script setup lang="ts">
/**
 * CatVRF 2026 — SkeletonLoader
 * Универсальный skeleton-placeholder с анимацией shimmer
 * Варианты: text, avatar, card, table, list
 */
withDefaults(defineProps<{
    variant?: 'text' | 'avatar' | 'card' | 'table' | 'list';
    lines?: number;
    width?: string;
    height?: string;
    rounded?: string;
}>(), {
    variant: 'text',
    lines: 3,
    width: '100%',
    height: 'auto',
    rounded: 'rounded-lg',
});
</script>

<template>
    <!-- Text skeleton -->
    <div v-if="variant === 'text'" class="flex flex-col gap-2" :style="{ width }">
        <div
            v-for="i in lines"
            :key="i"
            class="animate-pulse bg-(--t-surface-secondary)"
            :class="rounded"
            :style="{
                height: '0.875rem',
                width: i === lines ? '60%' : '100%',
            }"
        />
    </div>

    <!-- Avatar skeleton -->
    <div
        v-else-if="variant === 'avatar'"
        class="animate-pulse rounded-full bg-(--t-surface-secondary)"
        :style="{ width: width === '100%' ? '3rem' : width, height: height === 'auto' ? '3rem' : height }"
    />

    <!-- Card skeleton -->
    <div
        v-else-if="variant === 'card'"
        class="animate-pulse overflow-hidden border border-(--t-border) bg-(--t-surface)"
        :class="rounded"
        :style="{ width, height: height === 'auto' ? '12rem' : height }"
    >
        <div class="h-1/2 bg-(--t-surface-secondary)" />
        <div class="flex flex-col gap-2 p-4">
            <div class="h-4 w-3/4 rounded bg-(--t-surface-secondary)" />
            <div class="h-3 w-1/2 rounded bg-(--t-surface-secondary)" />
            <div class="h-3 w-full rounded bg-(--t-surface-secondary)" />
        </div>
    </div>

    <!-- Table skeleton -->
    <div v-else-if="variant === 'table'" class="flex flex-col gap-1" :style="{ width }">
        <div
            v-for="i in lines"
            :key="i"
            class="flex gap-4"
        >
            <div class="h-4 flex-1 animate-pulse rounded bg-(--t-surface-secondary)" />
            <div class="h-4 flex-1 animate-pulse rounded bg-(--t-surface-secondary)" />
            <div class="h-4 w-24 animate-pulse rounded bg-(--t-surface-secondary)" />
        </div>
    </div>

    <!-- List skeleton -->
    <div v-else-if="variant === 'list'" class="flex flex-col gap-3" :style="{ width }">
        <div
            v-for="i in lines"
            :key="i"
            class="flex items-center gap-3"
        >
            <div class="size-10 shrink-0 animate-pulse rounded-full bg-(--t-surface-secondary)" />
            <div class="flex flex-1 flex-col gap-1.5">
                <div class="h-3.5 w-3/4 animate-pulse rounded bg-(--t-surface-secondary)" />
                <div class="h-3 w-1/2 animate-pulse rounded bg-(--t-surface-secondary)" />
            </div>
        </div>
    </div>
</template>
