<template>
  <div class="fashion-size-guide bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-xl font-bold text-gray-900">Size Guide</h3>
      <button
        @click="close"
        class="text-gray-500 hover:text-gray-700"
      >
        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
        </svg>
      </button>
    </div>

    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
      <div class="flex gap-2">
        <button
          @click="selectedGender = 'male'"
          :class="selectedGender === 'male' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700'"
          class="px-4 py-2 rounded-lg font-medium transition-colors"
        >
          Male
        </button>
        <button
          @click="selectedGender = 'female'"
          :class="selectedGender === 'female' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700'"
          class="px-4 py-2 rounded-lg font-medium transition-colors"
        >
          Female
        </button>
      </div>
    </div>

    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Measurement System</label>
      <div class="flex gap-2">
        <button
          @click="measurementSystem = 'cm'"
          :class="measurementSystem === 'cm' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700'"
          class="px-4 py-2 rounded-lg font-medium transition-colors"
        >
          CM
        </button>
        <button
          @click="measurementSystem = 'inches'"
          :class="measurementSystem === 'inches' ? 'bg-purple-600 text-white' : 'bg-gray-100 text-gray-700'"
          class="px-4 py-2 rounded-lg font-medium transition-colors"
        >
          Inches
        </button>
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-200">
            <th class="text-left py-3 px-4 font-semibold text-gray-900">Size</th>
            <th class="text-left py-3 px-4 font-semibold text-gray-900">Chest</th>
            <th class="text-left py-3 px-4 font-semibold text-gray-900">Waist</th>
            <th class="text-left py-3 px-4 font-semibold text-gray-900">Hips</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="size in filteredSizes"
            :key="size.size"
            class="border-b border-gray-100 hover:bg-gray-50"
          >
            <td class="py-3 px-4 font-medium">{{ size.size }}</td>
            <td class="py-3 px-4">{{ formatMeasurement(size.chest) }}</td>
            <td class="py-3 px-4">{{ formatMeasurement(size.waist) }}</td>
            <td class="py-3 px-4">{{ formatMeasurement(size.hips) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-6 p-4 bg-purple-50 rounded-lg">
      <h4 class="font-semibold text-purple-900 mb-2">How to Measure</h4>
      <ul class="text-sm text-purple-800 space-y-2">
        <li class="flex items-start gap-2">
          <span class="font-bold">Chest:</span>
          <span>Measure around the fullest part of your chest, keeping the tape parallel to the ground.</span>
        </li>
        <li class="flex items-start gap-2">
          <span class="font-bold">Waist:</span>
          <span>Measure around your natural waistline, typically the narrowest part of your torso.</span>
        </li>
        <li class="flex items-start gap-2">
          <span class="font-bold">Hips:</span>
          <span>Measure around the fullest part of your hips, keeping the tape parallel to the ground.</span>
        </li>
      </ul>
    </div>

    <div class="mt-6">
      <button
        @click="getRecommendation"
        :disabled="!hasMeasurements"
        class="w-full bg-gradient-to-r from-purple-600 to-pink-600 text-white py-3 rounded-lg font-semibold hover:from-purple-700 hover:to-pink-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Get Size Recommendation
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';

interface Size {
  size: string;
  chest: number;
  waist: number;
  hips: number;
}

const props = defineProps<{
  productId?: number;
}>();

const emit = defineEmits<{
  close: [];
  sizeSelected: [size: string];
}>();

const selectedGender = ref('male');
const measurementSystem = ref('cm');

const maleSizes: Size[] = [
  { size: 'XS', chest: 84, waist: 71, hips: 89 },
  { size: 'S', chest: 89, waist: 76, hips: 94 },
  { size: 'M', chest: 96, waist: 81, hips: 101 },
  { size: 'L', chest: 102, waist: 86, hips: 107 },
  { size: 'XL', chest: 107, waist: 91, hips: 112 },
  { size: 'XXL', chest: 112, waist: 96, hips: 117 },
];

const femaleSizes: Size[] = [
  { size: 'XS', chest: 78, waist: 60, hips: 84 },
  { size: 'S', chest: 84, waist: 66, hips: 90 },
  { size: 'M', chest: 90, waist: 72, hips: 96 },
  { size: 'L', chest: 96, waist: 78, hips: 102 },
  { size: 'XL', chest: 102, waist: 84, hips: 108 },
  { size: 'XXL', chest: 108, waist: 90, hips: 114 },
];

const filteredSizes = computed(() => {
  return selectedGender.value === 'male' ? maleSizes : femaleSizes;
});

const hasMeasurements = computed(() => {
  return props.productId !== undefined;
});

const formatMeasurement = (value: number): string => {
  if (measurementSystem.value === 'inches') {
    return `${Math.round(value / 2.54)}"`;
  }
  return `${value} cm`;
};

const close = () => {
  emit('close');
};

const getRecommendation = () => {
  // This would call the size recommendation service
  console.log('Get size recommendation for product:', props.productId);
};
</script>
