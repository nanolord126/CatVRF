<template>
  <div class="theme-switcher">
    <button
      class="theme-button"
      :class="{ active: modelValue === 'light' }"
      @click="$emit('update:modelValue', 'light'); $emit('change', 'light')"
    >
      <Sun :size="18" />
    </button>
    <button
      class="theme-button"
      :class="{ active: modelValue === 'dark' }"
      @click="$emit('update:modelValue', 'dark'); $emit('change', 'dark')"
    >
      <Moon :size="18" />
    </button>
    <button
      class="theme-button"
      :class="{ active: modelValue === 'auto' }"
      @click="$emit('update:modelValue', 'auto'); $emit('change', 'auto')"
    >
      <Monitor :size="18" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { Sun, Moon, Monitor } from 'lucide-vue-next';

defineProps<{
  modelValue: string;
}>();

defineEmits<{
  'update:modelValue': [value: string];
  change: [value: string];
}>();
</script>

<style scoped>
.theme-switcher {
  display: flex;
  gap: 0.25rem;
  padding: 0.25rem;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 0.5rem;
}

.theme-button {
  padding: 0.5rem;
  background: transparent;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
  color: var(--text-secondary);
  transition: all 0.2s;
}

.theme-button:hover {
  color: var(--text-primary);
}

.theme-button.active {
  background: var(--primary-color);
  color: white;
}
</style>
