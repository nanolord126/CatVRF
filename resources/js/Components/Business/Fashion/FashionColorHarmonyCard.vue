<template>
  <div class="fashion-color-harmony-card bg-gradient-to-br from-pink-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-xl font-bold">Color Harmony</h3>
        <p class="text-sm text-pink-100">Based on your recent beauty services</p>
      </div>
      <button
        @click="refresh"
        class="bg-white/20 hover:bg-white/30 p-2 rounded-lg transition-colors"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
        </svg>
      </button>
    </div>

    <div v-if="recommendations.length > 0" class="space-y-3">
      <div
        v-for="rec in recommendations"
        :key="rec.product.id"
        class="bg-white/10 rounded-lg p-3 backdrop-blur-sm"
      >
        <div class="flex items-center gap-3">
          <img
            :src="rec.product.image_url"
            :alt="rec.product.name"
            class="w-16 h-16 object-cover rounded-lg"
          >
          <div class="flex-1">
            <p class="font-semibold">{{ rec.product.name }}</p>
            <p class="text-sm text-pink-100">{{ rec.reason }}</p>
            <p class="text-xs text-pink-200 mt-1">{{ rec.product.price_b2c }} ₽</p>
          </div>
          <div class="text-right">
            <div class="text-xs text-pink-200">{{ rec.source }}</div>
            <div class="font-bold">{{ Math.round(rec.relevance_score * 100) }}%</div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-8">
      <p class="text-pink-100">No color harmony recommendations yet</p>
      <p class="text-xs text-pink-200 mt-2">Complete beauty services to get personalized recommendations</p>
    </div>

    <div v-if="harmonyTypes.length > 0" class="mt-4">
      <div class="text-xs text-pink-200 mb-2">Harmony Types Used:</div>
      <div class="flex flex-wrap gap-1">
        <span
          v-for="type in harmonyTypes"
          :key="type"
          class="bg-white/20 px-2 py-1 rounded text-xs capitalize"
        >
          {{ type }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

interface Product {
  id: number;
  name: string;
  image_url: string;
  price_b2c: number;
}

interface Recommendation {
  product: Product;
  source: string;
  reason: string;
  relevance_score: number;
  harmony_type: string;
}

const recommendations = ref<Recommendation[]>([]);

const harmonyTypes = computed(() => {
  return [...new Set(recommendations.value.map(r => r.harmony_type))];
});

const refresh = async () => {
  try {
    const response = await fetch('/api/fashion/ml/color-harmony');
    const data = await response.json();
    recommendations.value = data.recommendations || [];
  } catch (error) {
    console.error('Failed to load color harmony recommendations:', error);
  }
};

onMounted(() => {
  refresh();
});
</script>
