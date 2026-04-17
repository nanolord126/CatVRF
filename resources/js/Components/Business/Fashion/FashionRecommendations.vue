<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface Recommendation {
  product_id: number;
  name: string;
  brand: string;
  price: number;
  image: string | null;
  similarity: number;
}

const recommendations = ref<Recommendation[]>([]);
const loading = ref(false);
const algorithm = ref('hybrid');
const limit = ref(20);

const loadRecommendations = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/fashion/advanced/recommendations', {
      params: { algorithm: algorithm.value, limit: limit.value },
    });
    recommendations.value = response.data.data.recommendations;
  } catch (err) {
    console.error('Failed to load recommendations');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadRecommendations();
});
</script>

<template>
  <div class="fashion-recommendations">
    <div class="header">
      <h2>Personalized Recommendations</h2>
      <select v-model="algorithm" @change="loadRecommendations" class="select-input">
        <option value="user-based">User-based</option>
        <option value="item-based">Item-based</option>
        <option value="matrix-factorization">Matrix Factorization</option>
        <option value="hybrid">Hybrid</option>
      </select>
    </div>

    <div v-if="loading" class="loading">Loading...</div>
    <div v-else class="recommendations-grid">
      <div v-for="item in recommendations" :key="item.product_id" class="recommendation-card">
        <img :src="item.image" :alt="item.name" class="product-image" />
        <div class="product-info">
          <h4>{{ item.name }}</h4>
          <p class="brand">{{ item.brand }}</p>
          <p class="price">${{ item.price }}</p>
          <p class="similarity">Match: {{ (item.similarity * 100).toFixed(0) }}%</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fashion-recommendations { padding: 1rem; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.select-input { padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; }
.recommendations-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
.recommendation-card { border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
.product-image { width: 100%; height: 200px; object-fit: cover; }
.product-info { padding: 1rem; }
.product-info h4 { margin: 0 0 0.5rem 0; }
.brand { color: #6b7280; font-size: 0.875rem; }
.price { font-weight: 600; color: #4f46e5; }
.similarity { font-size: 0.875rem; color: #059669; margin-top: 0.5rem; }
</style>
