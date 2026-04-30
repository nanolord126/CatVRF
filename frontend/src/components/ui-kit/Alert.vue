<template>
  <div :class="alertClasses">
    <div class="alert-icon">
      <component :is="iconComponent" />
    </div>
    <div class="alert-content">
      <h4 v-if="title" class="alert-title">{{ title }}</h4>
      <p class="alert-message">
        <slot />
      </p>
    </div>
    <button v-if="dismissible" class="alert-close" @click="close" aria-label="Close">
      <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor">
        <path
          fill-rule="evenodd"
          d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
          clip-rule="evenodd"
        />
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  variant?: 'info' | 'success' | 'warning' | 'error';
  title?: string;
  dismissible?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'info',
  dismissible: false,
});

const emit = defineEmits<{
  close: [];
}>();

const alertClasses = computed(() => {
  return [
    'alert',
    `alert-${props.variant}`,
  ];
});

const iconComponent = computed(() => {
  const icons = {
    info: 'InfoIcon',
    success: 'SuccessIcon',
    warning: 'WarningIcon',
    error: 'ErrorIcon',
  };
  return icons[props.variant];
});

function close() {
  emit('close');
}
</script>

<script lang="ts">
const InfoIcon = {
  template: `
    <svg viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
    </svg>
  `,
};

const SuccessIcon = {
  template: `
    <svg viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
    </svg>
  `,
};

const WarningIcon = {
  template: `
    <svg viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
    </svg>
  `,
};

const ErrorIcon = {
  template: `
    <svg viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
    </svg>
  `,
};

export default {
  components: {
    InfoIcon,
    SuccessIcon,
    WarningIcon,
    ErrorIcon,
  },
};
</script>

<style scoped>
.alert {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: 0.375rem;
  position: relative;
}

.alert-info {
  background-color: #eff6ff;
  border: 1px solid #dbeafe;
  color: #1e40af;
}

.alert-success {
  background-color: #f0fdf4;
  border: 1px solid #dcfce7;
  color: #166534;
}

.alert-warning {
  background-color: #fffbeb;
  border: 1px solid #fef3c7;
  color: #92400e;
}

.alert-error {
  background-color: #fef2f2;
  border: 1px solid #fee2e2;
  color: #991b1b;
}

.alert-icon {
  flex-shrink: 0;
  width: 1.25rem;
  height: 1.25rem;
}

.alert-content {
  flex: 1;
}

.alert-title {
  font-size: 0.875rem;
  font-weight: 600;
  margin: 0 0 0.25rem 0;
}

.alert-message {
  font-size: 0.875rem;
  margin: 0;
}

.alert-close {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.25rem;
  height: 1.25rem;
  border: none;
  background-color: transparent;
  cursor: pointer;
  color: inherit;
  opacity: 0.6;
  transition: opacity 0.2s ease;
  padding: 0;
}

.alert-close:hover {
  opacity: 1;
}
</style>
