<script setup>
/**
 * VStatCard — карточка метрики с glassmorphism, hover-анимацией,
 * тренд-индикатором и кликабельностью.
 */
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    subtitle: { type: String, default: '' },
    icon: { type: String, default: '📊' },
    trend: { type: Number, default: null },       // +5.2 / -3.1
    trendLabel: { type: String, default: '' },
    color: { type: String, default: 'primary' },  // primary | amber | emerald | rose | indigo
    clickable: { type: Boolean, default: true },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);

const trendColor = computed(() => {
    if (props.trend === null) return '';
    return props.trend >= 0 ? 'text-emerald-400' : 'text-rose-400';
});

const trendIcon = computed(() => {
    if (props.trend === null) return '';
    return props.trend >= 0 ? '↑' : '↓';
});

const colorBorder = {
    primary: 'hover:border-(--t-primary)/40',
    amber: 'hover:border-amber-400/40',
    emerald: 'hover:border-emerald-400/40',
    rose: 'hover:border-rose-400/40',
    indigo: 'hover:border-indigo-400/40',
};

const colorGlow = {
    primary: 'hover:shadow-[0_0_40px_var(--t-glow)]',
    amber: 'hover:shadow-[0_0_40px_rgba(245,158,11,0.12)]',
    emerald: 'hover:shadow-[0_0_40px_rgba(16,185,129,0.12)]',
    rose: 'hover:shadow-[0_0_40px_rgba(244,63,94,0.12)]',
    indigo: 'hover:shadow-[0_0_40px_rgba(99,102,241,0.12)]',
};
</script>

<template>
    <div
        :class="[
            'group relative rounded-2xl border border-(--t-border) bg-(--t-surface)',
            'backdrop-blur-xl p-5 transition-all duration-300',
            colorBorder[color], colorGlow[color],
            'hover:-translate-y-0.5',
            clickable ? 'cursor-pointer active:scale-[0.98]' : '',
        ]"
        @click="clickable && emit('click')"
        role="button"
        :tabindex="clickable ? 0 : -1"
        @keydown.enter="clickable && emit('click')"
    >
        <!-- Loading skeleton -->
        <template v-if="loading">
            <div class="animate-pulse space-y-3">
                <div class="h-4 w-20 bg-(--t-border) rounded" />
                <div class="h-8 w-32 bg-(--t-border) rounded" />
                <div class="h-3 w-24 bg-(--t-border) rounded" />
            </div>
        </template>

        <template v-else>
            <!-- Header -->
            <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-medium text-(--t-text-2) group-hover:text-(--t-text) transition-colors">
                    {{ title }}
                </span>
                <span class="text-xl transition-transform group-hover:scale-110 group-hover:rotate-6 duration-300">
                    {{ icon }}
                </span>
            </div>

            <!-- Value -->
            <div class="text-2xl lg:text-3xl font-bold text-(--t-text) tracking-tight mb-1">
                {{ value }}
            </div>

            <!-- Footer: trend + subtitle -->
            <div class="flex items-center gap-2 text-xs mt-1">
                <span v-if="trend !== null" :class="[trendColor, 'font-semibold flex items-center gap-0.5']">
                    {{ trendIcon }} {{ Math.abs(trend) }}%
                </span>
                <span v-if="trendLabel" class="text-(--t-text-3)">{{ trendLabel }}</span>
                <span v-else-if="subtitle" class="text-(--t-text-3)">{{ subtitle }}</span>
            </div>
        </template>

        <!-- Hover glow -->
        <div class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"
             style="background: radial-gradient(ellipse at 50% 0%, var(--t-glow) 0%, transparent 70%)" />
    </div>
</template>
