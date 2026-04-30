<template>
  <div :class="cardClasses">
    <div v-if="$slots.header" class="card-header">
      <slot name="header" />
    </div>
    <div class="card-body">
      <slot />
    </div>
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Props {
  variant?: 'default' | 'bordered' | 'shadow' | 'flat';
  padding?: 'none' | 'sm' | 'md' | 'lg';
  hoverable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  padding: 'md',
  hoverable: false,
});

const cardClasses = computed(() => {
  return [
    'card',
    `card-${props.variant}`,
    `card-padding-${props.padding}`,
    {
      'card-hoverable': props.hoverable,
    },
  ];
});
</script>

<style scoped>
.card {
  background-color: white;
  border-radius: 0.5rem;
  transition: all 0.2s ease;
}

.card-default {
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
}

.card-bordered {
  border: 2px solid #e5e7eb;
}

.card-shadow {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.card-flat {
  border: none;
  box-shadow: none;
  background-color: #f9fafb;
}

.card-hoverable:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.card-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.card-body {
  flex: 1;
}

.card-padding-none .card-body {
  padding: 0;
}

.card-padding-sm .card-body {
  padding: 0.75rem 1rem;
}

.card-padding-md .card-body {
  padding: 1rem 1.5rem;
}

.card-padding-lg .card-body {
  padding: 1.5rem 2rem;
}

.card-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e5e7eb;
}
</style>
