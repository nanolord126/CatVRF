<template>
  <div class="branch-switcher">
    <select
      :value="currentBranch?.id || ''"
      class="branch-select"
      @change="$emit('change', branches.find(b => b.id === $event.target.value))"
    >
      <option v-for="branch in branches" :key="branch.id" :value="branch.id">
        {{ branch.name }}
      </option>
    </select>
    <MapPin :size="16" class="branch-icon" />
  </div>
</template>

<script setup lang="ts">
import { MapPin } from 'lucide-vue-next';

defineProps<{
  branches: any[];
  currentBranch: any;
}>();

defineEmits<{
  change: [branch: any];
}>();
</script>

<style scoped>
.branch-switcher {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 0.5rem;
}

.branch-select {
  border: none;
  background: transparent;
  color: var(--text-primary);
  font-size: 0.875rem;
  cursor: pointer;
  outline: none;
}

.branch-icon {
  color: var(--text-secondary);
}
</style>
