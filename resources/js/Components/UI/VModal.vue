<script setup>
/**
 * VModal — модальное окно с backdrop-blur, анимацией, focus trap.
 */
import { watch, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: '' },
    size: { type: String, default: 'md' },     // sm | md | lg | xl | full
    closable: { type: Boolean, default: true },
    persistent: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue', 'close']);
const modalRef = ref(null);

function close() {
    if (!props.closable) return;
    emit('update:modelValue', false);
    emit('close');
}

function onBackdropClick() {
    if (props.persistent) {
        modalRef.value?.classList.add('animate-shake');
        setTimeout(() => modalRef.value?.classList.remove('animate-shake'), 400);
        return;
    }
    close();
}

function onKeydown(e) {
    if (e.key === 'Escape' && props.closable) close();
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => document.removeEventListener('keydown', onKeydown));

watch(() => props.modelValue, (v) => {
    document.body.style.overflow = v ? 'hidden' : '';
});

const sizeClasses = {
    sm: 'max-w-sm',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-4xl',
    full: 'max-w-[95vw] max-h-[95vh]',
};
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="modelValue"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/60 backdrop-blur-sm"
                    @click="onBackdropClick"
                />

                <!-- Dialog -->
                <div
                    ref="modalRef"
                    :class="[
                        'relative w-full rounded-2xl border border-(--t-border) bg-(--t-bg)/95 backdrop-blur-2xl shadow-2xl',
                        'transform transition-all duration-300',
                        sizeClasses[size] || sizeClasses.md,
                    ]"
                >
                    <!-- Header -->
                    <div v-if="title || $slots.header || closable" class="flex items-center justify-between px-6 pt-5 pb-3">
                        <slot name="header">
                            <h2 class="text-lg font-bold text-(--t-text)">{{ title }}</h2>
                        </slot>
                        <button
                            v-if="closable"
                            @click="close"
                            class="p-1.5 rounded-lg hover:bg-(--t-surface) text-(--t-text-3) hover:text-(--t-text) transition-colors active:scale-90"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-6 pb-4 max-h-[70vh] overflow-y-auto">
                        <slot />
                    </div>

                    <!-- Footer -->
                    <div v-if="$slots.footer" class="px-6 pb-5 pt-2 flex items-center justify-end gap-3 border-t border-(--t-border)">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style>
.modal-enter-active, .modal-leave-active { transition: all 0.25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
.modal-enter-from > div:last-child, .modal-leave-to > div:last-child { transform: scale(0.95) translateY(10px); }
@keyframes shake { 0%,100%{transform:translateX(0)} 25%{transform:translateX(-4px)} 75%{transform:translateX(4px)} }
.animate-shake { animation: shake 0.3s ease; }
</style>
