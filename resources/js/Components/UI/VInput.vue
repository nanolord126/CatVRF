<script setup>
/**
 * VInput — текстовое поле с плавающей подписью, иконками, validation,
 * focus-гlow и полной интерактивностью.
 */
import { ref, computed } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: '' },
    placeholder: { type: String, default: '' },
    type: { type: String, default: 'text' },
    error: { type: String, default: '' },
    hint: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    prefixIcon: { type: String, default: '' },
    clearable: { type: Boolean, default: false },
    size: { type: String, default: 'md' },
});

const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'clear']);
const isFocused = ref(false);
const hasValue = computed(() => !!props.modelValue || props.modelValue === 0);

function onInput(e) {
    emit('update:modelValue', e.target.value);
}
function onClear() {
    emit('update:modelValue', '');
    emit('clear');
}
</script>

<template>
    <div class="relative">
        <!-- Label -->
        <label v-if="label" class="block mb-1.5 text-xs font-medium text-(--t-text-2)">
            {{ label }}
            <span v-if="required" class="text-red-400 ml-0.5">*</span>
        </label>

        <div
            :class="[
                'relative flex items-center rounded-xl border transition-all duration-200',
                error
                    ? 'border-red-500/60 bg-red-500/5'
                    : isFocused
                        ? 'border-(--t-primary)/60 bg-(--t-surface) shadow-[0_0_20px_var(--t-glow)]'
                        : 'border-(--t-border) bg-(--t-surface) hover:border-(--t-primary)/30',
                disabled ? 'opacity-50 cursor-not-allowed' : '',
            ]"
        >
            <!-- Prefix icon -->
            <span v-if="prefixIcon" class="pl-3 text-(--t-text-3)">
                {{ prefixIcon }}
            </span>

            <!-- Prefix slot -->
            <slot name="prefix" />

            <!-- Input -->
            <input
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :readonly="readonly"
                :required="required"
                :class="[
                    'w-full bg-transparent text-(--t-text) placeholder-(--t-text-3)',
                    'focus:outline-none',
                    size === 'sm' ? 'px-3 py-1.5 text-sm' : size === 'lg' ? 'px-4 py-3 text-base' : 'px-3 py-2 text-sm',
                    prefixIcon ? 'pl-1' : '',
                ]"
                @input="onInput"
                @focus="isFocused = true; emit('focus')"
                @blur="isFocused = false; emit('blur')"
            />

            <!-- Clear button -->
            <button
                v-if="clearable && hasValue"
                @click="onClear"
                class="pr-3 text-(--t-text-3) hover:text-(--t-text) transition-colors active:scale-90"
            >
                ✕
            </button>

            <!-- Suffix slot -->
            <slot name="suffix" />
        </div>

        <!-- Error / Hint -->
        <p v-if="error" class="mt-1 text-xs text-red-400">{{ error }}</p>
        <p v-else-if="hint" class="mt-1 text-xs text-(--t-text-3)">{{ hint }}</p>
    </div>
</template>
