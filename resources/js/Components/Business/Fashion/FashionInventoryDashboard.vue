<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

const productId = ref<number | null>(null);
const forecast = ref<any>(null);
const reorderList = ref<any[]>([]);
const outOfStockStats = ref<any>(null);
const loading = ref(false);

const loadForecast = async () => {
  if (!productId.value) return;
  
  loading.value = true;
  try {
    const response = await axios.post('/api/fashion/advanced/inventory/forecast', {
      product_id: productId.value,
      days_ahead: 30,
    });
    forecast.value = response.data.data;
  } catch (err) {
    console.error('Failed to load forecast');
  } finally {
    loading.value = false;
  }
};

const loadReorderRecommendations = async () => {
  try {
    const response = await axios.get('/api/fashion/advanced/inventory/reorder');
    reorderList.value = response.data.data.products_requiring_reorder;
  } catch (err) {
    console.error('Failed to load reorder recommendations');
  }
};

const loadOutOfStockStats = async () => {
  try {
    const response = await axios.get('/api/fashion/advanced/inventory/out-of-stock');
    outOfStockStats.value = response.data.data;
  } catch (err) {
    console.error('Failed to load out-of-stock stats');
  }
};

onMounted(() => {
  loadReorderRecommendations();
  loadOutOfStockStats();
});
</script>

<template>
  <div class="fashion-inventory-dashboard">
    <h2>Inventory Dashboard</h2>

    <div class="forecast-section">
      <h3>Demand Forecast</h3>
      <div class="input-group">
        <input v-model.number="productId" type="number" placeholder="Product ID" class="input-field" />
        <button @click="loadForecast" :disabled="loading" class="btn-primary">Forecast</button>
      </div>

      <div v-if="loading" class="loading">Loading...</div>
      <div v-else-if="forecast" class="forecast-result">
        <div class="stat">
          <span class="label">Current Stock:</span>
          <span class="value">{{ forecast.current_stock }}</span>
        </div>
        <div class="stat">
          <span class="label">Total Forecast Demand:</span>
          <span class="value">{{ forecast.total_forecast_demand }}</span>
        </div>
        <div class="stat">
          <span class="label">Stockout Date:</span>
          <span class="value">{{ forecast.stockout_date || 'N/A' }}</span>
        </div>
        <div v-if="forecast.reorder_recommendation.should_reorder" class="alert">
          <strong>Reorder Recommended:</strong> {{ forecast.reorder_recommendation.quantity }} units
        </div>
      </div>
    </div>

    <div class="stats-section">
      <div class="stat-card">
        <h3>Out-of-Stock Stats</h3>
        <div v-if="outOfStockStats">
          <div class="stat">
            <span class="label">Total Events:</span>
            <span class="value">{{ outOfStockStats.total_events }}</span>
          </div>
          <div class="stat">
            <span class="label">Lost Sales:</span>
            <span class="value">{{ outOfStockStats.total_lost_sales }}</span>
          </div>
        </div>
      </div>
    </div>

    <div class="reorder-section">
      <h3>Reorder Recommendations</h3>
      <div v-if="reorderList.length > 0" class="reorder-list">
        <div v-for="item in reorderList" :key="item.product_id" class="reorder-item">
          <span class="product-name">{{ item.name }}</span>
          <span class="stock">Stock: {{ item.current_stock }}</span>
          <span class="reorder-qty">Reorder: {{ item.recommended_order_quantity }}</span>
        </div>
      </div>
      <div v-else class="empty-state">No reorder recommendations</div>
    </div>
  </div>
</template>

<style scoped>
.fashion-inventory-dashboard { padding: 1rem; }
.input-group { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.input-field { padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; }
.btn-primary { padding: 0.5rem 1rem; background: #4f46e5; color: white; border: none; border-radius: 4px; cursor: pointer; }
.btn-primary:disabled { opacity: 0.5; }
.forecast-section, .stats-section, .reorder-section { margin-bottom: 2rem; }
.forecast-result { padding: 1rem; background: #f9fafb; border-radius: 8px; }
.stat { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
.label { color: #6b7280; }
.value { font-weight: 600; }
.alert { padding: 0.75rem; background: #fef3c7; border: 1px solid #fcd34d; border-radius: 4px; margin-top: 1rem; }
.stat-card { padding: 1rem; background: #f9fafb; border-radius: 8px; }
.reorder-list { display: flex; flex-direction: column; gap: 0.5rem; }
.reorder-item { display: flex; justify-content: space-between; padding: 0.75rem; background: #f9fafb; border-radius: 4px; }
.product-name { flex: 1; font-weight: 600; }
.reorder-qty { color: #4f46e5; font-weight: 600; }
.empty-state { padding: 1rem; color: #6b7280; text-align: center; }
</style>
