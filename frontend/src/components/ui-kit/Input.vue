<template>
  <div class="input-wrapper">
    <label v-if="label" :for="inputId" class="input-label">
      {{ label }}
      <span v-if="required" class="required-mark">*</span>
    </label>
    <div class="input-container">
      <input
        :id="inputId"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :min="min"
        :max="max"
        :step="step"
        :class="inputClasses"
        @input="handleInput"
        @blur="handleBlur"
        @focus="handleFocus"
      />
      <span v-if="prefix" class="input-prefix">{{ prefix }}</span>
      <span v-if="suffix" class="input-suffix">{{ suffix }}</span>
    </div>
    <span v-if="error" class="input-error">{{ error }}</span>
    <span v-if="hint && !error" class="input-hint">{{ hint }}</span>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';

interface Props {
  modelValue: string | number;
  type?: 'text' | 'email' | 'password' | 'number' | 'tel' | 'url';
  label?: string;
  placeholder?: string;
  error?: string;
  hint?: string;
  disabled?: boolean;
  readonly?: boolean;
  required?: boolean;
  prefix?: string;
  suffix?: string;
  min?: number | string;
  max?: number | string;
  step?: number | string;
}

const props = withDefaults(defineProps<Props>(), {
  type: 'text',
  disabled: false,
  readonly: false,
  required: false,
});

const emit = defineEmits<{
  'update:modelValue': [value: string | number];
  blur: [event: FocusEvent];
  focus: [event: FocusEvent];
}>();

const inputId = ref(`input-${Math.random().toString(36).substr(2, 9)}`);

const inputClasses = computed(() => {
  return [
    'input',
    {
      'input-error': props.error,
      'input-disabled': props.disabled,
      'input-readonly': props.readonly,
      'input-has-prefix': props.prefix,
      'input-has-suffix': props.suffix,
    },
  ];
});

function handleInput(event: Event) {
  const target = event.target as HTMLInputElement;
  emit('update:modelValue', target.value);
}

function handleBlur(event: FocusEvent) {
  emit('blur', event);
}

function handleFocus(event: FocusEvent) {
  emit('focus', event);
}
</script>

<style scoped>
.input-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.input-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.required-mark {
  color: #ef4444;
  margin-left: 0.125rem;
}

.input-container {
  position: relative;
  display: flex;
  align-items: center;
}

.input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 1rem;
  transition: all 0.2s ease;
  font-family: inherit;
}

.input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.input-error {
  border-color: #ef4444;
}

.input-error:focus {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.input-disabled {
  background-color: #f3f4f6;
  cursor: not-allowed;
  opacity: 0.6;
}

.input-readonly {
  background-color: #f9fafb;
  cursor: default;
}

.input-has-prefix {
  padding-left: 2.5rem;
}

.input-has-suffix {
  padding-right: 2.5rem;
}

.input-prefix,
.input-suffix {
  position: absolute;
  color: #6b7280;
  font-size: 0.875rem;
  pointer-events: none;
}

.input-prefix {
  left: 0.75rem;
}

.input-suffix {
  right: 0.75rem;
}

.input-error-text {
  font-size: 0.75rem;
  color: #ef4444;
}

.input-hint {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
