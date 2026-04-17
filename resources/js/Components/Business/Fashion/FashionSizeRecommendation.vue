<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';

const productId = ref<number | null>(null);
const measurements = ref({
  height: null as number | null,
  weight: null as number | null,
  chest: null as number | null,
  waist: null as number | null,
  hips: null as number | null,
});
const recommendation = ref<any>(null);
const loading = ref(false);

const getRecommendation = async () => {
  if (!productId.value) return;
  
  loading.value = true;
  try {
    const response = await axios.post('/api/fashion/advanced/size/recommend', {
      product_id: productId.value,
      measurements: Object.fromEntries(
        Object.entries(measurements.value).filter(([_, v]) => v !== null)
      ),
    });
    recommendation.value = response.data.data;
  } catch (err) {
    console.error('Failed to get size recommendation');
  } finally {
    loading.value = false;
  }
};

const saveProfile = async () => {
  try {
    await axios.post('/api/fashion/advanced/size/profile', {
      measurements: measurements.value,
    });
    alert('Profile saved');
  } catch (err) {
    console.error('Failed to save profile');
  }
};
</script>

<template>
  <div class="fashion-size-recommendation">
    <h2>Size Recommendation</h2>
    
    <div class="form-grid">
      <div class="form-group">
        <label>Product ID</label>
        <input v-model.number="productId" type="number" class="input-field" />
      </div>
      <div class="form-group">
        <label>Height (cm)</label>
        <input v-model.number="measurements.height" type="number" class="input-field" />
      </div>
      <div class="form-group">
        <label>Weight (kg)</label>
        <input v-model.number="measurements.weight" type="number" class="input-field" />
      </div>
      <div class="form-group">
        <label>Chest (cm)</label>
        <input v-model.number="measurements.chest" type="number" class="input-field" />
      </div>
      <div class="form-group">
        <label>Waist (cm)</label>
        <input v-model.number="measurements.waist" type="number" class="input-field" />
      </div>
      <div class="form-group">
        <label>Hips (cm)</label>
        <input v-model.number="measurements.hips" type="number" class="input-field" />
      </div>
    </div>

    <div class="actions">
      <button @click="saveProfile" class="btn-secondary">Save Profile</button>
      <button @click="getRecommendation" :disabled="loading" class="btn-primary">
        Get Recommendation
      </button>
    </div>

    <div v-if="loading" class="loading">Loading...</div>
    <div v-else-if="recommendation" class="result">
      <h3>Recommended Size: {{ recommendation.recommended_size }}</h3>
      <p>Confidence: {{ (recommendation.confidence * 100).toFixed(0) }}%</p>
      <p>{{ recommendation.reason }}</p>
      <div v-if="recommendation.alternative_sizes.length > 0">
        <h4>Alternative Sizes</h4>
        <div class="alternatives">
          <span v-for="size in recommendation.alternative_sizes" :key="size" class="size-tag">
            {{ size }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fashion-size-recommendation { padding: 1rem; }
.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.25rem; font-size: 0.875rem; }
.input-field { width: 100%; padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; }
.actions { display: flex; gap: 0.5rem; margin-bottom: 1rem; }
.btn-primary, .btn-secondary { padding: 0.5rem 1rem; border: none; border-radius: 4px; cursor: pointer; }
.btn-primary { background: #4f46e5; color: white; }
.btn-primary:disabled { opacity: 0.5; }
.btn-secondary { background: #e5e7eb; }
.result { padding: 1rem; background: #f9fafb; border-radius: 8px; }
.alternatives { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
.size-tag { padding: 0.25rem 0.5rem; background: #e0e7ff; border-radius: 4px; }
</style>
