<script setup>
/**
 * VCard — универсальная карточка с glassmorphism, hover, click,
 * loading-состоянием и слотами для полной кастомизации.
 */
defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    clickable: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    noPadding: { type: Boolean, default: false },
    glow: { type: Boolean, default: false },
    accent: { type: String, default: '' },    // top border accent color class
    flat: { type: Boolean, default: false },
});

const emit = defineEmits(['click']);
</script>

<template>
    <div
        :class="[
            'group relative rounded-2xl border border-(--t-border) bg-(--t-surface) backdrop-blur-xl',
            'transition-all duration-300 overflow-hidden',
            clickable ? 'cursor-pointer hover:-translate-y-0.5 active:scale-[0.98] hover:border-(--t-primary)/30' : '',
            glow ? 'hover:shadow-[0_0_40px_var(--t-glow)]' : '',
            flat ? '' : 'shadow-glass-sm',
            accent,
        ]"
        @click="clickable && emit('click')"
        :role="clickable ? 'button' : undefined"
        :tabindex="clickable ? 0 : undefined"
        @keydown.enter="clickable && emit('click')"
    >
        <!-- Loading overlay -->
        <div v-if="loading" class="absolute inset-0 bg-(--t-bg)/60 backdrop-blur-sm z-10 flex items-center justify-center">
            <svg class="animate-spin w-6 h-6 text-(--t-primary)" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
        </div>

        <!-- Header slot or auto-title -->
        <div v-if="$slots.header || title" :class="[noPadding ? '' : 'px-5 pt-5']">
            <slot name="header">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 v-if="title" class="text-base font-semibold text-(--t-text)">{{ title }}</h3>
                        <p v-if="subtitle" class="text-xs text-(--t-text-3) mt-0.5">{{ subtitle }}</p>
                    </div>
                    <slot name="header-action" />
                </div>
            </slot>
        </div>

        <!-- Body -->
        <div :class="[noPadding ? '' : 'p-5', title ? 'pt-3' : '']">
            <slot />
        </div>

        <!-- Footer -->
        <div v-if="$slots.footer" :class="[noPadding ? '' : 'px-5 pb-5']">
            <slot name="footer" />
        </div>

        <!-- Hover glow -->
        <div v-if="glow" class="absolute inset-0 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"
             style="background: radial-gradient(ellipse at 50% 0%, var(--t-glow) 0%, transparent 70%)" />
    </div>
</template>
