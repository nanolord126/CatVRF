<script setup>
/**
 * VBadge — бейджик со статусом, анимацией пульса для live, и кликабельностью.
 */
defineProps({
    text: { type: [String, Number], default: '' },
    variant: { type: String, default: 'default' },
    // default | success | warning | danger | info | b2b | live | neutral
    size: { type: String, default: 'sm' },          // xs | sm | md
    dot: { type: Boolean, default: false },
    pulse: { type: Boolean, default: false },
    clickable: { type: Boolean, default: false },
    removable: { type: Boolean, default: false },
});

const emit = defineEmits(['click', 'remove']);

const variantClasses = {
    default: 'bg-(--t-primary-dim) text-(--t-primary) ring-(--t-primary)/20',
    success: 'bg-emerald-500/15 text-emerald-400 ring-emerald-500/20',
    warning: 'bg-amber-500/15 text-amber-400 ring-amber-500/20',
    danger: 'bg-red-500/15 text-red-400 ring-red-500/20',
    info: 'bg-sky-500/15 text-sky-400 ring-sky-500/20',
    b2b: 'bg-linear-to-r from-amber-500/20 to-orange-500/20 text-amber-300 ring-amber-500/25 font-semibold',
    live: 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/20',
    neutral: 'bg-(--t-surface) text-(--t-text-2) ring-(--t-border)',
};

const sizeClasses = {
    xs: 'px-1.5 py-0.5 text-[10px]',
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-2.5 py-1 text-sm',
};

const dotVariant = {
    default: 'bg-(--t-primary)',
    success: 'bg-emerald-400',
    warning: 'bg-amber-400',
    danger: 'bg-red-400',
    info: 'bg-sky-400',
    b2b: 'bg-amber-400',
    live: 'bg-emerald-400',
    neutral: 'bg-(--t-text-3)',
};
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1 rounded-full ring-1 ring-inset font-medium transition-all duration-200',
            variantClasses[variant] || variantClasses.default,
            sizeClasses[size] || sizeClasses.sm,
            clickable ? 'cursor-pointer hover:brightness-110 active:scale-95' : '',
        ]"
        @click="clickable && emit('click')"
        :role="clickable ? 'button' : undefined"
    >
        <!-- Dot -->
        <span v-if="dot || pulse" class="relative flex h-2 w-2">
            <span v-if="pulse" :class="['animate-ping absolute inline-flex h-full w-full rounded-full opacity-50', dotVariant[variant]]" />
            <span :class="['relative inline-flex rounded-full h-2 w-2', dotVariant[variant]]" />
        </span>

        <slot>{{ text }}</slot>

        <!-- Remove button -->
        <button
            v-if="removable"
            @click.stop="emit('remove')"
            class="ml-0.5 -mr-0.5 p-0.5 rounded-full hover:bg-white/10 transition-colors active:scale-90"
        >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </span>
</template>
