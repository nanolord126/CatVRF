<template>
  <div class="onboarding-progress">
    <div class="mb-4">
      <div class="flex justify-between items-center mb-2">
        <h3 class="font-semibold text-gray-800">{{ title }}</h3>
        <span class="text-sm text-gray-600">{{ percentage }}%</span>
      </div>
      
      <!-- Progress bar -->
      <div class="w-full bg-gray-200 rounded-full h-3">
        <div
          class="h-3 rounded-full transition-all duration-500 ease-out"
          :class="progressBarColor"
          :style="{ width: `${percentage}%` }"
        />
      </div>
    </div>

    <!-- Steps indicator -->
    <div class="flex justify-between mt-6">
      <div
        v-for="(step, index) in steps"
        :key="index"
        class="flex flex-col items-center"
        :class="{ 'opacity-50': index > currentStep }"
      >
        <div
          class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold mb-2"
          :class="getStepClass(index)"
        >
          <span v-if="index < currentStep">✓</span>
          <span v-else>{{ index + 1 }}</span>
        </div>
        <span class="text-xs text-center max-w-[80px]">{{ step.label }}</span>
      </div>
    </div>

    <!-- Current step info -->
    <div v-if="currentStepInfo" class="mt-6 p-4 bg-gray-50 rounded-lg">
      <p class="text-sm text-gray-600">{{ currentStepInfo.description }}</p>
    </div>

    <!-- Estimated time -->
    <div v-if="estimatedTime" class="mt-4 text-sm text-gray-500">
      Ожидаемое время: {{ estimatedTime }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

interface Step {
  label: string;
  description: string;
}

interface Props {
  steps: Step[];
  currentStep: number;
  title?: string;
  estimatedTime?: string;
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Прогресс регистрации',
  estimatedTime: '',
});

const percentage = computed(() => {
  if (props.steps.length === 0) return 0;
  return Math.round((props.currentStep / props.steps.length) * 100);
});

const currentStepInfo = computed(() => {
  if (props.currentStep >= props.steps.length) return null;
  return props.steps[props.currentStep];
});

const progressBarColor = computed(() => {
  if (percentage.value < 33) return 'bg-yellow-500';
  if (percentage.value < 66) return 'bg-blue-500';
  return 'bg-green-500';
});

const getStepClass = (index: number) => {
  if (index < props.currentStep) {
    return 'bg-green-500 text-white';
  }
  if (index === props.currentStep) {
    return 'bg-blue-500 text-white ring-4 ring-blue-200';
  }
  return 'bg-gray-300 text-gray-600';
};
</script>

<style scoped>
.onboarding-progress {
  @apply w-full;
}
</style>
