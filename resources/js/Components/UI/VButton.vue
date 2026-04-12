<script setup>
/**
 * VButton — кнопка с ripple-эффектом, loading и множеством вариантов.
 * Полностью кликабельна, адаптивна, с мгновенной обратной связью.
 */
import { ref } from 'vue';

const props = defineProps({
    variant: { type: String, default: 'primary' },    // primary | secondary | ghost | danger | b2b | success
    size: { type: String, default: 'md' },             // xs | sm | md | lg | xl
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    icon: { type: String, default: null },
    iconRight: { type: String, default: null },
    fullWidth: { type: Boolean, default: false },
    pill: { type: Boolean, default: false },
    badge: { type: [String, Number], default: null },
});

const emit = defineEmits(['click']);
const ripples = ref([]);

function handleClick(e) {
    if (props.disabled || props.loading) return;
    const rect = e.currentTarget.getBoundingClientRect();
    const x = e.clientX - rect.left;
    const y = e.clientY - rect.top;
    const id = Date.now();
    ripples.value.push({ id, x, y });
    setTimeout(() => { ripples.value = ripples.value.filter(r => r.id !== id); }, 700);
    emit('click', e);
}

const variantClasses = {
    primary: 'bg-(--t-btn) hover:bg-(--t-btn-hover) text-white shadow-lg shadow-(--t-glow) hover:shadow-xl hover:shadow-(--t-glow)',
    secondary: 'bg-(--t-surface) hover:bg-(--t-card-hover) text-(--t-text) border border-(--t-border)',
    ghost: 'bg-transparent hover:bg-(--t-surface) text-(--t-text-2) hover:text-(--t-text)',
    danger: 'bg-red-600/90 hover:bg-red-500 text-white shadow-lg shadow-red-500/20',
    b2b: 'bg-linear-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-black font-bold shadow-lg shadow-amber-500/25',
    success: 'bg-emerald-600/90 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-500/20',
};
const sizeClasses = {
    xs: 'px-2.5 py-1 text-xs gap-1',
    sm: 'px-3 py-1.5 text-sm gap-1.5',
    md: 'px-4 py-2 text-sm gap-2',
    lg: 'px-6 py-2.5 text-base gap-2.5',
    xl: 'px-8 py-3.5 text-lg gap-3',
};
</script>

<template>
    <button
        :class="[
            'relative overflow-hidden inline-flex items-center justify-center font-medium transition-all duration-200',
            'focus:outline-none focus-visible:ring-2 focus-visible:ring-(--t-primary) focus-visible:ring-offset-2 focus-visible:ring-offset-(--t-bg)',
            'active:scale-[0.97] select-none cursor-pointer',
            variantClasses[variant] || variantClasses.primary,
            sizeClasses[size] || sizeClasses.md,
            pill ? 'rounded-full' : 'rounded-xl',
            fullWidth ? 'w-full' : '',
            (disabled || loading) ? 'opacity-50 cursor-not-allowed pointer-events-none' : '',
        ]"
        :disabled="disabled || loading"
        @click="handleClick"
    >
        <!-- Ripple -->
        <span
            v-for="ripple in ripples"
            :key="ripple.id"
            class="absolute rounded-full bg-white/25 animate-[ripple_0.7s_ease-out] pointer-events-none"
            :style="{ left: ripple.x + 'px', top: ripple.y + 'px', width: '4px', height: '4px', transform: 'translate(-50%,-50%)' }"
        />

        <!-- Loading spinner -->
        <svg v-if="loading" class="animate-spin shrink-0" :class="size === 'xs' ? 'w-3 h-3' : size === 'sm' ? 'w-3.5 h-3.5' : 'w-4 h-4'" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
        </svg>

        <!-- Content -->
        <slot />

        <!-- Badge -->
        <span v-if="badge != null" class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 flex items-center justify-center text-[10px] font-bold rounded-full bg-red-500 text-white ring-2 ring-(--t-bg)">
            {{ badge }}
        </span>
    </button>
</template>

<style>
@keyframes ripple {
    0% { width: 4px; height: 4px; opacity: 0.6; }
    100% { width: 300px; height: 300px; opacity: 0; }
}
</style>
