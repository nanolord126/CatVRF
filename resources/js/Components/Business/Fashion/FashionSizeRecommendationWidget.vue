<template>
  <div class="fashion-size-widget bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-xl font-bold text-gray-900 mb-4">Size Recommendation</h3>

    <div v-if="loading" class="text-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
      <p class="text-gray-500 mt-2">Calculating ideal size...</p>
    </div>

    <div v-else-if="sizeData">
      <div class="text-center mb-6">
        <div class="text-5xl font-bold text-indigo-600">{{ sizeData.recommended_size }}</div>
        <div class="text-sm text-gray-500 mt-1">Recommended Size</div>
        <div class="flex items-center justify-center gap-2 mt-2">
          <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
            {{ Math.round(sizeData.confidence * 100) }}% Confidence
          </div>
        </div>
      </div>

      <div v-if="sizeData.brand_runs" class="bg-blue-50 rounded-lg p-4 mb-4">
        <div class="flex items-center gap-2">
          <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
          </svg>
          <span class="text-sm text-blue-800">{{ sizeData.brand_runs.recommendation }}</span>
        </div>
      </div>

      <div v-if="sizeData.alternative_sizes && sizeData.alternative_sizes.length > 0" class="mb-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-2">Alternative Sizes</h4>
        <div class="space-y-2">
          <div
            v-for="alt in sizeData.alternative_sizes"
            :key="alt.size"
            class="flex items-center justify-between bg-gray-50 rounded-lg p-3"
          >
            <div>
              <span class="font-semibold">{{ alt.size }}</span>
              <span class="text-sm text-gray-500 ml-2">{{ alt.recommendation }}</span>
            </div>
            <span class="text-xs text-gray-400">{{ alt.fit_note }}</span>
          </div>
        </div>
      </div>

      <div v-if="sizeData.tolerance" class="mb-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-2">Size Tolerance</h4>
        <div class="grid grid-cols-3 gap-2 text-xs">
          <div class="bg-gray-50 rounded p-2">
            <div class="font-medium">Chest</div>
            <div class="text-gray-600">±{{ sizeData.tolerance.chest }}cm</div>
          </div>
          <div class="bg-gray-50 rounded p-2">
            <div class="font-medium">Waist</div>
            <div class="text-gray-600">±{{ sizeData.tolerance.waist }}cm</div>
          </div>
          <div class="bg-gray-50 rounded p-2">
            <div class="font-medium">Hips</div>
            <div class="text-gray-600">±{{ sizeData.tolerance.hips }}cm</div>
          </div>
        </div>
      </div>

      <div class="flex gap-2">
        <button
          @click="provideFeedback('perfect')"
          class="flex-1 bg-green-600 text-white py-2 rounded-lg font-medium hover:bg-green-700 transition-colors"
        >
          Fits Perfectly
        </button>
        <button
          @click="provideFeedback('too_small')"
          class="flex-1 bg-orange-600 text-white py-2 rounded-lg font-medium hover:bg-orange-700 transition-colors"
        >
          Too Small
        </button>
        <button
          @click="provideFeedback('too_large')"
          class="flex-1 bg-red-600 text-white py-2 rounded-lg font-medium hover:bg-red-700 transition-colors"
        >
          Too Large
        </button>
      </div>
    </div>

    <div v-else class="text-center py-8">
      <p class="text-gray-500">Unable to calculate size</p>
      <button
        @click="calculateSize"
        class="mt-4 bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition-colors"
      >
        Try Again
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';

const props = defineProps<{
  productId: number;
}>();

const loading = ref(false);
const sizeData = ref<any>(null);

const calculateSize = async () => {
  loading.value = true;
  try {
    const response = await fetch(`/api/fashion/ml/size-recommendation/${props.productId}`);
    const data = await response.json();
    sizeData.value = data;
  } catch (error) {
    console.error('Failed to calculate size:', error);
  } finally {
    loading.value = false;
  }
};

const provideFeedback = async (feedback: string) => {
  try {
    await fetch(`/api/fashion/ml/size-feedback/${props.productId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ feedback }),
    });
    alert('Thank you for your feedback!');
  } catch (error) {
    console.error('Failed to submit feedback:', error);
  }
};

onMounted(() => {
  calculateSize();
});
</script>
