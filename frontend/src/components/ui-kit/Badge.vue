<template>
  <span :class="badgeClasses">
    <slot />
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  variant?: 'default' | 'primary' | 'secondary' | 'success' | 'warning' | 'danger' | 'info';
  size?: 'sm' | 'md' | 'lg';
  rounded?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size: 'md',
  rounded: false,
});

const badgeClasses = computed(() => {
  return [
    'badge',
    `badge-${props.variant}`,
    `badge-${props.size}`,
    {
      'badge-rounded': props.rounded,
    },
  ];
});
</script>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.625rem;
  font-size: 0.75rem;
  font-weight: 500;
  line-height: 1;
  white-space: nowrap;
}

.badge-sm {
  padding: 0.125rem 0.375rem;
  font-size: 0.6875rem;
}

.badge-md {
  padding: 0.25rem 0.625rem;
  font-size: 0.75rem;
}

.badge-lg {
  padding: 0.375rem 0.875rem;
  font-size: 0.875rem;
}

.badge-rounded {
  border-radius: 9999px;
}

.badge:not(.badge-rounded) {
  border-radius: 0.25rem;
}

/* Variants */
.badge-default {
  background-color: #f3f4f6;
  color: #374151;
}

.badge-primary {
  background-color: #dbeafe;
  color: #1e40af;
}

.badge-secondary {
  background-color: #e2e8f0;
  color: #475569;
}

.badge-success {
  background-color: #dcfce7;
  color: #166534;
}

.badge-warning {
  background-color: #fef3c7;
  color: #92400e;
}

.badge-danger {
  background-color: #fee2e2;
  color: #991b1b;
}

.badge-info {
  background-color: #e0f2fe;
  color: #075985;
}
</style>
