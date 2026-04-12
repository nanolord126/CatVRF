<script setup lang="ts">
/**
 * CatVRF 2026 — BottomSheet
 * Мобильный bottom sheet с snap-точками: peek (30%), half (60%), full (100%)
 * Поддержка свайпа и drag
 */
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { useUI } from '@/stores/core/useUI';

const props = withDefaults(defineProps<{
    modelValue?: boolean;
    snapPoints?: number[];
    initialSnap?: number;
    maxHeight?: string;
}>(), {
    modelValue: false,
    snapPoints: () => [0.3, 0.6, 1],
    initialSnap: 1,
    maxHeight: '90dvh',
});

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
    close: [];
}>();

const ui = useUI();
const sheetRef = ref<HTMLElement | null>(null);
const currentSnapIndex = ref(props.initialSnap);
const isDragging = ref(false);
const startY = ref(0);
const startHeight = ref(0);

const currentHeight = computed(() => {
    const snap = props.snapPoints[currentSnapIndex.value] ?? 0.6;
    return `${snap * 100}dvh`;
});

const isOpen = computed(() => props.modelValue);

function close(): void {
    emit('update:modelValue', false);
    emit('close');
}

function onBackdropClick(): void {
    close();
}

/* ── Touch/Mouse Drag ───────────────────────────────────────────── */
function onDragStart(event: TouchEvent | MouseEvent): void {
    isDragging.value = true;
    const clientY = 'touches' in event ? event.touches[0].clientY : event.clientY;
    startY.value = clientY;
    startHeight.value = sheetRef.value?.offsetHeight ?? 0;
}

function onDragMove(event: TouchEvent | MouseEvent): void {
    if (!isDragging.value) return;
    const clientY = 'touches' in event ? event.touches[0].clientY : event.clientY;
    const diff = startY.value - clientY;
    const newHeight = startHeight.value + diff;
    const maxH = window.innerHeight * 0.9;

    if (sheetRef.value) {
        const clamped = Math.max(0, Math.min(newHeight, maxH));
        sheetRef.value.style.height = `${clamped}px`;
    }
}

function onDragEnd(): void {
    if (!isDragging.value) return;
    isDragging.value = false;

    const currentH = sheetRef.value?.offsetHeight ?? 0;
    const windowH = window.innerHeight;
    const ratio = currentH / windowH;

    // Snap to nearest point
    let closest = 0;
    let minDist = Infinity;
    for (let i = 0; i < props.snapPoints.length; i++) {
        const dist = Math.abs(props.snapPoints[i] - ratio);
        if (dist < minDist) {
            minDist = dist;
            closest = i;
        }
    }

    // If dragged below minimum snap, close
    if (ratio < props.snapPoints[0] * 0.5) {
        close();
        return;
    }

    currentSnapIndex.value = closest;
    if (sheetRef.value) {
        sheetRef.value.style.height = '';
    }
}

onMounted(() => {
    document.addEventListener('mousemove', onDragMove);
    document.addEventListener('mouseup', onDragEnd);
    document.addEventListener('touchmove', onDragMove, { passive: false });
    document.addEventListener('touchend', onDragEnd);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousemove', onDragMove);
    document.removeEventListener('mouseup', onDragEnd);
    document.removeEventListener('touchmove', onDragMove);
    document.removeEventListener('touchend', onDragEnd);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/50"
                    @click="onBackdropClick"
                />

                <!-- Sheet -->
                <Transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="translate-y-full"
                    enter-to-class="translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="translate-y-0"
                    leave-to-class="translate-y-full"
                >
                    <div
                        v-if="isOpen"
                        ref="sheetRef"
                        class="absolute inset-x-0 bottom-0 flex flex-col rounded-t-2xl bg-(--t-surface) shadow-2xl transition-[height] duration-200"
                        :style="{ height: currentHeight, maxHeight: maxHeight }"
                    >
                        <!-- Drag handle -->
                        <div
                            class="flex shrink-0 cursor-grab items-center justify-center pb-2 pt-3 active:cursor-grabbing"
                            @mousedown="onDragStart"
                            @touchstart="onDragStart"
                        >
                            <div class="h-1 w-10 rounded-full bg-(--t-text-secondary)/30" />
                        </div>

                        <!-- Content -->
                        <div class="flex-1 overflow-y-auto px-4 pb-8">
                            <slot />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
