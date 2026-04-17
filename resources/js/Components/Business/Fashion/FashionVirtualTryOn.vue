<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';

interface TryOnResult {
  product_id: number;
  product_name: string;
  fit_score: number;
  ar_preview_url: string;
  embedding_similarity: number;
  in_stock: boolean;
  price: number;
}

interface TryOnResponse {
  design_id: number;
  try_on_results: TryOnResult[];
  average_fit_score: number;
}

const route = useRoute();
const loading = ref<boolean>(true);
const results = ref<TryOnResult[]>([]);
const averageFitScore = ref<number>(0);
const error = ref<string>('');
const selectedProduct = ref<TryOnResult | null>(null);

const loadTryOnResults = async () => {
  const designId = route.params.designId as string;
  const productIds = route.query.product_ids as string;

  if (!designId || !productIds) {
    error.value = 'Missing required parameters';
    loading.value = false;
    return;
  }

  try {
    const response = await axios.post<TryOnResponse>('/api/fashion/ai/virtual-try-on', {
      design_id: parseInt(designId),
      product_ids: productIds.split(',').map((id) => parseInt(id)),
    }, {
      headers: {
        'Accept': 'application/json',
      },
    });

    results.value = response.data.data.try_on_results;
    averageFitScore.value = response.data.data.average_fit_score;
    selectedProduct.value = results.value[0] || null;
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to load try-on results';
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadTryOnResults();
});

const selectProduct = (product: TryOnResult) => {
  selectedProduct.value = product;
};

const fitScoreColor = (score: number): string => {
  if (score >= 0.8) return '#059669';
  if (score >= 0.6) return '#d97706';
  return '#dc2626';
};
</script>

<template>
  <div class="fashion-virtual-try-on">
    <div class="try-on-header">
      <h2>Виртуальная примерка</h2>
      <p>AR-примерка с AI-анализом посадки</p>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Загрузка...</p>
    </div>

    <div v-else-if="error" class="error-message">
      {{ error }}
    </div>

    <div v-else class="try-on-content">
      <div class="ar-viewer">
        <div v-if="selectedProduct" class="model-container">
          <model-viewer
            :src="selectedProduct.ar_preview_url"
            :auto-rotate="true"
            :camera-controls="true"
            :shadow-intensity="0.5"
            :exposure="1.0"
            background-color="#f5f5f5"
            class="model-viewer"
          />
          <div class="fit-score-display">
            <div
              class="score-circle"
              :style="{ borderColor: fitScoreColor(selectedProduct.fit_score) }"
            >
              <span class="score-value">{{ (selectedProduct.fit_score * 100).toFixed(0) }}%</span>
            </div>
            <p>Совпадение</p>
          </div>
        </div>
        <div v-else class="no-selection">
          <p>Выберите товар для примерки</p>
        </div>
      </div>

      <div class="product-list">
        <div class="average-score">
          <h3>Среднее совпадение</h3>
          <div
            class="score-circle large"
            :style="{ borderColor: fitScoreColor(averageFitScore) }"
          >
            <span class="score-value">{{ (averageFitScore * 100).toFixed(0) }}%</span>
          </div>
        </div>

        <div class="products">
          <div
            v-for="product in results"
            :key="product.product_id"
            class="product-item"
            :class="{ active: selectedProduct?.product_id === product.product_id }"
            @click="selectProduct(product)"
          >
            <div class="product-info">
              <h4>{{ product.product_name }}</h4>
              <p class="price">${{ product.price }}</p>
              <div class="fit-score">
                <span
                  class="score-bar"
                  :style="{ width: `${product.fit_score * 100}%`, backgroundColor: fitScoreColor(product.fit_score) }"
                />
                <span class="score-text">{{ (product.fit_score * 100).toFixed(0) }}%</span>
              </div>
              <div class="embedding-similarity">
                Embedding: {{ (product.embedding_similarity * 100).toFixed(0) }}%
              </div>
              <div class="stock-status" :class="{ in_stock: product.in_stock }">
                {{ product.in_stock ? 'В наличии' : 'Нет в наличии' }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fashion-virtual-try-on {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
}

.try-on-header {
  text-align: center;
  margin-bottom: 2rem;
}

.try-on-header h2 {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}

.loading {
  text-align: center;
  padding: 4rem;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-message {
  padding: 2rem;
  background: #fee2e2;
  color: #991b1b;
  border-radius: 8px;
  text-align: center;
}

.try-on-content {
  display: grid;
  grid-template-columns: 1fr 400px;
  gap: 2rem;
}

.ar-viewer {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.model-container {
  position: relative;
  height: 600px;
  border-radius: 8px;
  overflow: hidden;
}

.model-viewer {
  width: 100%;
  height: 100%;
}

.fit-score-display {
  position: absolute;
  bottom: 2rem;
  right: 2rem;
  text-align: center;
}

.score-circle {
  width: 80px;
  height: 80px;
  border: 4px solid #059669;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.score-circle.large {
  width: 120px;
  height: 120px;
  margin: 0 auto;
}

.score-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
}

.no-selection {
  height: 600px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
}

.product-list {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  max-height: 700px;
  overflow-y: auto;
}

.average-score {
  text-align: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.products {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.product-item {
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s;
}

.product-item:hover {
  border-color: #4f46e5;
}

.product-item.active {
  border-color: #4f46e5;
  background: #e0e7ff;
}

.product-info h4 {
  margin: 0 0 0.5rem 0;
}

.price {
  font-weight: 600;
  color: #4f46e5;
  margin-bottom: 0.5rem;
}

.fit-score {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin: 0.5rem 0;
}

.score-bar {
  height: 8px;
  border-radius: 4px;
  background: #e5e7eb;
  flex: 1;
  transition: width 0.3s;
}

.score-text {
  font-size: 0.875rem;
  font-weight: 600;
  min-width: 40px;
}

.embedding-similarity {
  font-size: 0.75rem;
  color: #6b7280;
}

.stock-status {
  margin-top: 0.5rem;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 600;
  background: #fee2e2;
  color: #991b1b;
}

.stock-status.in_stock {
  background: #d1fae5;
  color: #065f46;
}

@media (max-width: 1024px) {
  .try-on-content {
    grid-template-columns: 1fr;
  }

  .model-container {
    height: 400px;
  }
}
</style>
