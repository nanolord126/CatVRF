<script setup lang="ts">
/**
 * CatVRF 2026 — CriticalModal
 * Красная критическая модалка: удаление, опасные действия
 * Обязательный ввод «УДАЛИТЬ» для подтверждения
 */
import { ref, computed } from 'vue';
import { ExclamationTriangleIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = withDefaults(defineProps<{
    title: string;
    message: string;
    confirmText?: string;
    confirmWord?: string;
    cancelText?: string;
    isLoading?: boolean;
}>(), {
    confirmText: 'Удалить',
    confirmWord: 'УДАЛИТЬ',
    cancelText: 'Отмена',
    isLoading: false,
});

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();

const inputValue = ref('');

const canConfirm = computed(() => inputValue.value === props.confirmWord);

function onConfirm(): void {
    if (canConfirm.value && !props.isLoading) {
        emit('confirm');
    }
}

function onCancel(): void {
    inputValue.value = '';
    emit('cancel');
}
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop (red tint) -->
            <div
                class="absolute inset-0 bg-black/60"
                @click="onCancel"
            />

            <!-- Modal card -->
            <div
                class="relative z-10 w-full max-w-md rounded-2xl border-2 border-(--t-danger)/30 bg-(--t-surface) p-6 shadow-2xl"
                role="alertdialog"
                aria-modal="true"
            >
                <!-- Close button -->
                <button
                    class="absolute right-4 top-4 rounded-lg p-1 text-(--t-text-secondary) hover:bg-(--t-surface-hover)"
                    @click="onCancel"
                >
                    <XMarkIcon class="size-5" />
                </button>

                <!-- Icon -->
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-(--t-danger)/10">
                    <ExclamationTriangleIcon class="size-8 text-(--t-danger)" />
                </div>

                <!-- Title -->
                <h2 class="mb-2 text-center text-lg font-bold text-(--t-text)">
                    {{ title }}
                </h2>

                <!-- Message -->
                <p class="mb-6 text-center text-sm text-(--t-text-secondary)">
                    {{ message }}
                </p>

                <!-- Confirmation input -->
                <div class="mb-6">
                    <label class="mb-1 block text-sm font-medium text-(--t-text)">
                        Введите <span class="font-bold text-(--t-danger)">{{ confirmWord }}</span> для подтверждения
                    </label>
                    <input
                        v-model="inputValue"
                        type="text"
                        :placeholder="confirmWord"
                        class="w-full rounded-xl border border-(--t-danger)/30 bg-(--t-surface-secondary) px-4 py-2.5 text-sm text-(--t-text) placeholder:text-(--t-text-secondary) focus:border-(--t-danger) focus:outline-none focus:ring-2 focus:ring-(--t-danger)/20"
                        @keydown.enter="onConfirm"
                    />
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button
                        class="flex-1 rounded-xl border border-(--t-border) bg-(--t-surface-secondary) px-4 py-2.5 text-sm font-medium text-(--t-text) hover:bg-(--t-surface-hover)"
                        @click="onCancel"
                    >
                        {{ cancelText }}
                    </button>
                    <button
                        class="flex-1 rounded-xl px-4 py-2.5 text-sm font-bold text-white transition-all"
                        :class="canConfirm && !isLoading
                            ? 'bg-(--t-danger) hover:bg-(--t-danger)/90 cursor-pointer'
                            : 'bg-(--t-danger)/40 cursor-not-allowed'"
                        :disabled="!canConfirm || isLoading"
                        @click="onConfirm"
                    >
                        <span v-if="isLoading" class="flex items-center justify-center gap-2">
                            <span class="size-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                            Удаление…
                        </span>
                        <span v-else>{{ confirmText }}</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
