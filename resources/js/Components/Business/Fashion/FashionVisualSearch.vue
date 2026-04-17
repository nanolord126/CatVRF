<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';

interface SearchResult {
  product_id: number;
  name: string;
  brand: string;
  price: number;
  image: string | null;
  similarity: number;
}

const imageUrl = ref('');
const results = ref<SearchResult[]>([]);
const loading = ref(false);

const searchByImage = async () => {
  if (!imageUrl.value) return;
  
  loading.value = true;
  try {
    const response = await axios.post('/api/fashion/advanced/visual-search', {
      image_url: imageUrl.value,
    });
    results.value = response.data.data.results;
  } catch (err) {
    console.error('Visual search failed');
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="fashion-visual-search">
    <h2>Visual Search</h2>
    <div class="search-input">
      <input
        v-model="imageUrl"
        type="url"
        placeholder="Enter image URL..."
        class="url-input"
        @keyup.enter="searchByImage"
      />
      <button @click="searchByImage" :disabled="loading" class="search-btn">Search</button>
    </div>

    <div v-if="loading" class="loading">Searching...</div>
    <div v-else-if="results.length > 0" class="results-grid">
      <div v-for="item in results" :key="item.product_id" class="result-card">
        <img :src="item.image" :alt="item.name" class="product-image" />
        <div class="product-info">
          <h4>{{ item.name }}</h4>
          <p class="brand">{{ item.brand }}</p>
          <p class="price">${{ item.price }}</p>
          <p class="similarity">Similarity: {{ (item.similarity * 100).toFixed(0) }}%</p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fashion-visual-search { padding: 1rem; }
.search-input { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.url-input { flex: 1; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; }
.search-btn { padding: 0.5rem 1rem; background: #4f46e5; color: white; border: none; border-radius: 4px; cursor: pointer; }
.search-btn:disabled { opacity: 0.5; }
.results-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
.result-card { border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
.product-image { width: 100%; height: 200px; object-fit: cover; }
.product-info { padding: 1rem; }
.product-info h4 { margin: 0 0 0.5rem 0; }
.brand { color: #6b7280; font-size: 0.875rem; }
.price { font-weight: 600; color: #4f46e5; }
.similarity { font-size: 0.875rem; color: #059669; margin-top: 0.5rem; }
</style>
