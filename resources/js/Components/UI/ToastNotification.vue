<script setup lang="ts">
/**
 * CatVRF 2026 — ToastNotification
 * Контейнер тостов: success, error, warning, info
 * Появление: slide-in сверху + fade. Исчезание: fade + slide-out вправо.
 */
import { computed } from 'vue';
import { useUI } from '@/stores/core/useUI';
import {
    CheckCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XCircleIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import type { Toast, ToastType } from '@/types/ui';

const ui = useUI();

const toasts = computed(() => ui.toasts);

const iconMap: Record<ToastType, typeof CheckCircleIcon> = {
    success: CheckCircleIcon,
    error: XCircleIcon,
    warning: ExclamationTriangleIcon,
    info: InformationCircleIcon,
};

const colorMap: Record<ToastType, string> = {
    success: 'border-emerald-500/30 bg-emerald-50 dark:bg-emerald-950/40',
    error: 'border-red-500/30 bg-red-50 dark:bg-red-950/40',
    warning: 'border-amber-500/30 bg-amber-50 dark:bg-amber-950/40',
    info: 'border-blue-500/30 bg-blue-50 dark:bg-blue-950/40',
};

const iconColorMap: Record<ToastType, string> = {
    success: 'text-emerald-600 dark:text-emerald-400',
    error: 'text-red-600 dark:text-red-400',
    warning: 'text-amber-600 dark:text-amber-400',
    info: 'text-blue-600 dark:text-blue-400',
};

function dismiss(id: string): void {
    ui.removeToast(id);
}
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed right-4 top-4 z-100 flex max-w-sm flex-col gap-2"
            aria-live="polite"
        >
            <TransitionGroup
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="-translate-y-4 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="translate-x-0 opacity-100"
                leave-to-class="translate-x-full opacity-0"
                move-class="transition-all duration-200"
            >
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex items-start gap-3 rounded-xl border p-4 shadow-lg"
                    :class="colorMap[toast.type]"
                    role="alert"
                >
                    <component
                        :is="iconMap[toast.type]"
                        class="size-6 shrink-0"
                        :class="iconColorMap[toast.type]"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-(--t-text)">{{ toast.title }}</p>
                        <p class="mt-0.5 text-sm text-(--t-text-secondary)">{{ toast.message }}</p>
                    </div>
                    <button
                        class="shrink-0 rounded-lg p-1 text-(--t-text-secondary) hover:bg-black/5"
                        @click="dismiss(toast.id)"
                    >
                        <XMarkIcon class="size-4" />
                    </button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
