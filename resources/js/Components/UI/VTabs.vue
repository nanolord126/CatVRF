<script setup>
/**
 * VTabs — табы с анимацией активного индикатора, кликабельные,
 * с иконками и бейджами.
 */
import { ref, watch, nextTick, onMounted } from 'vue';

const props = defineProps({
    tabs: { type: Array, required: true },
    // [{ key: 'overview', label: 'Обзор', icon: '📊', badge: 3 }]
    modelValue: { type: String, default: '' },
    variant: { type: String, default: 'underline' },  // underline | pills | segment
    size: { type: String, default: 'md' },
});

const emit = defineEmits(['update:modelValue']);
const containerRef = ref(null);
const indicatorStyle = ref({});

function select(key) {
    emit('update:modelValue', key);
}

async function updateIndicator() {
    await nextTick();
    if (!containerRef.value) return;
    const active = containerRef.value.querySelector('[data-active="true"]');
    if (!active) return;
    indicatorStyle.value = {
        left: active.offsetLeft + 'px',
        width: active.offsetWidth + 'px',
    };
}

watch(() => props.modelValue, updateIndicator);
onMounted(updateIndicator);

const variantContainer = {
    underline: 'border-b border-(--t-border)',
    pills: 'bg-(--t-surface) rounded-xl p-1',
    segment: 'bg-(--t-surface) rounded-xl p-1 border border-(--t-border)',
};

const variantTab = {
    underline: 'pb-3 px-1',
    pills: 'px-3 py-1.5 rounded-lg',
    segment: 'px-3 py-1.5 rounded-lg flex-1 text-center',
};

const activeTab = {
    underline: 'text-(--t-primary)',
    pills: 'bg-(--t-primary-dim) text-(--t-primary)',
    segment: 'bg-(--t-card-hover) text-(--t-text) shadow-sm',
};

const inactiveTab = {
    underline: 'text-(--t-text-3) hover:text-(--t-text)',
    pills: 'text-(--t-text-3) hover:text-(--t-text) hover:bg-(--t-card-hover)',
    segment: 'text-(--t-text-3) hover:text-(--t-text)',
};
</script>

<template>
    <div ref="containerRef" :class="['relative flex gap-1', variantContainer[variant]]">
        <!-- Underline indicator -->
        <div
            v-if="variant === 'underline'"
            class="absolute bottom-0 h-0.5 bg-(--t-primary) rounded-full transition-all duration-300 ease-out"
            :style="indicatorStyle"
        />

        <button
            v-for="tab in tabs"
            :key="tab.key"
            :data-active="modelValue === tab.key"
            :class="[
                'relative inline-flex items-center gap-1.5 font-medium transition-all duration-200',
                'focus:outline-none focus-visible:ring-2 focus-visible:ring-(--t-primary) focus-visible:ring-offset-1',
                'active:scale-[0.97] select-none cursor-pointer',
                size === 'sm' ? 'text-xs' : size === 'lg' ? 'text-base' : 'text-sm',
                variantTab[variant],
                modelValue === tab.key ? activeTab[variant] : inactiveTab[variant],
            ]"
            @click="select(tab.key)"
        >
            <span v-if="tab.icon" class="text-sm">{{ tab.icon }}</span>
            <span>{{ tab.label }}</span>
            <span
                v-if="tab.badge != null"
                class="ml-1 min-w-[16px] h-4 px-1 flex items-center justify-center text-[10px] font-bold rounded-full bg-red-500/80 text-white"
            >
                {{ tab.badge }}
            </span>
        </button>
    </div>
</template>
