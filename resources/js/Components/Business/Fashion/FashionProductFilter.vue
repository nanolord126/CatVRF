<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

interface Filter {
  categories: string[];
  price_min?: number;
  price_max?: number;
  brands: string[];
  colors: string[];
  sizes: string[];
  materials: string[];
  styles: string[];
  seasons: string[];
  target_audiences: string[];
  in_stock_only: boolean;
  on_sale: boolean;
  new_arrivals: boolean;
}

interface AvailableFilters {
  categories: Array<{id: number; name: string; slug: string; product_count: number}>;
  price_ranges: Array<{min: number; max: number | null; label: string}>;
  brands: string[];
  colors: string[];
  sizes: string[];
  materials: string[];
  styles: string[];
  seasons: Array<{value: string; label: string}>;
  target_audiences: Array<{value: string; label: string}>;
}

const filters = ref<Filter>({
  categories: [],
  brands: [],
  colors: [],
  sizes: [],
  materials: [],
  styles: [],
  seasons: [],
  target_audiences: [],
  in_stock_only: false,
  on_sale: false,
  new_arrivals: false,
});

const availableFilters = ref<AvailableFilters | null>(null);
const loading = ref(false);
const products = ref([]);
const pagination = ref({ current_page: 1, total: 0, last_page: 1 });
const sortBy = ref('newest');
const showMobileFilters = ref(false);

const activeFilterCount = computed(() => {
  let count = 0;
  if (filters.value.categories.length) count++;
  if (filters.value.brands.length) count++;
  if (filters.value.colors.length) count++;
  if (filters.value.sizes.length) count++;
  if (filters.value.materials.length) count++;
  if (filters.value.styles.length) count++;
  if (filters.value.seasons.length) count++;
  if (filters.value.target_audiences.length) count++;
  if (filters.value.in_stock_only) count++;
  if (filters.value.on_sale) count++;
  if (filters.value.new_arrivals) count++;
  return count;
});

const loadAvailableFilters = async () => {
  try {
    const response = await axios.get('/api/fashion/filters/available');
    availableFilters.value = response.data.data;
  } catch (err) {
    console.error('Failed to load filters');
  }
};

const applyFilters = async () => {
  loading.value = true;
  try {
    const response = await axios.post('/api/fashion/products/filter', {
      filters: filters.value,
      sort_by: sortBy.value,
      sort_order: 'desc',
      page: pagination.value.current_page,
      per_page: 20,
    });
    products.value = response.data.data.products;
    pagination.value = response.data.data.pagination;
  } catch (err) {
    console.error('Failed to apply filters');
  } finally {
    loading.value = false;
  }
};

const resetFilters = () => {
  filters.value = {
    categories: [],
    brands: [],
    colors: [],
    sizes: [],
    materials: [],
    styles: [],
    seasons: [],
    target_audiences: [],
    in_stock_only: false,
    on_sale: false,
    new_arrivals: false,
  };
  pagination.value.current_page = 1;
  applyFilters();
};

const toggleFilter = (type: keyof Filter, value: string) => {
  const arr = filters.value[type] as string[];
  const index = arr.indexOf(value);
  if (index === -1) {
    arr.push(value);
  } else {
    arr.splice(index, 1);
  }
  pagination.value.current_page = 1;
  applyFilters();
};

const savePreferences = async () => {
  try {
    await axios.post('/api/fashion/filters/preferences', { filters: filters.value });
  } catch (err) {
    console.error('Failed to save preferences');
  }
};

onMounted(() => {
  loadAvailableFilters();
  applyFilters();
});
</script>

<template>
  <div class="fashion-product-filter">
    <div class="filter-header">
      <h2>Фильтры</h2>
      <button class="mobile-toggle" @click="showMobileFilters = !showMobileFilters">
        Фильтры ({{ activeFilterCount }})
      </button>
    </div>

    <div class="filter-container" :class="{ 'mobile-open': showMobileFilters }">
      <div class="filter-sidebar">
        <div class="filter-group" v-if="availableFilters?.categories">
          <h3>Категории</h3>
          <label v-for="cat in availableFilters.categories" :key="cat.id" class="checkbox-label">
            <input type="checkbox" :value="cat.name" v-model="filters.categories" @change="applyFilters" />
            {{ cat.name }} ({{ cat.product_count }})
          </label>
        </div>

        <div class="filter-group" v-if="availableFilters?.price_ranges">
          <h3>Цена</h3>
          <select v-model="filters.price_min" @change="applyFilters" class="select-input">
            <option :value="undefined">От</option>
            <option v-for="range in availableFilters.price_ranges" :key="range.min" :value="range.min">
              {{ range.label }}
            </option>
          </select>
          <select v-model="filters.price_max" @change="applyFilters" class="select-input">
            <option :value="undefined">До</option>
            <option v-for="range in availableFilters.price_ranges" :key="range.max" :value="range.max">
              {{ range.label }}
            </option>
          </select>
        </div>

        <div class="filter-group" v-if="availableFilters?.brands">
          <h3>Бренды</h3>
          <label v-for="brand in availableFilters.brands.slice(0, 10)" :key="brand" class="checkbox-label">
            <input type="checkbox" :value="brand" v-model="filters.brands" @change="applyFilters" />
            {{ brand }}
          </label>
        </div>

        <div class="filter-group">
          <h3>Дополнительно</h3>
          <label class="checkbox-label">
            <input type="checkbox" v-model="filters.in_stock_only" @change="applyFilters" />
            В наличии
          </label>
          <label class="checkbox-label">
            <input type="checkbox" v-model="filters.on_sale" @change="applyFilters" />
            Со скидкой
          </label>
          <label class="checkbox-label">
            <input type="checkbox" v-model="filters.new_arrivals" @change="applyFilters" />
            Новинки
          </label>
        </div>

        <div class="filter-actions">
          <button class="reset-btn" @click="resetFilters">Сбросить</button>
          <button class="save-btn" @click="savePreferences">Сохранить</button>
        </div>
      </div>

      <div class="filter-results">
        <div class="results-header">
          <select v-model="sortBy" @change="applyFilters" class="sort-select">
            <option value="newest">Новые</option>
            <option value="price">Цена</option>
            <option value="popular">Популярные</option>
            <option value="rating">Рейтинг</option>
          </select>
          <span class="results-count">{{ pagination.total }} товаров</span>
        </div>

        <div v-if="loading" class="loading">Загрузка...</div>
        <div v-else class="products-grid">
          <div v-for="product in products" :key="product.id" class="product-card">
            <img :src="product.image" :alt="product.name" class="product-image" />
            <div class="product-info">
              <h4>{{ product.name }}</h4>
              <p class="price">${{ product.price_b2c }}</p>
              <div class="fit-score" v-if="product.fit_score">
                Совпадение: {{ (product.fit_score * 100).toFixed(0) }}%
              </div>
            </div>
          </div>
        </div>

        <div class="pagination" v-if="pagination.last_page > 1">
          <button :disabled="pagination.current_page === 1" @click="pagination.current_page--; applyFilters()">
            Назад
          </button>
          <span>{{ pagination.current_page }} / {{ pagination.last_page }}</span>
          <button :disabled="pagination.current_page === pagination.last_page" @click="pagination.current_page++; applyFilters()">
            Вперед
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fashion-product-filter { padding: 1rem; }
.filter-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.mobile-toggle { display: none; padding: 0.5rem 1rem; background: #4f46e5; color: white; border: none; border-radius: 4px; }
.filter-container { display: grid; grid-template-columns: 250px 1fr; gap: 2rem; }
.filter-sidebar { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); }
.filter-group { margin-bottom: 1.5rem; }
.filter-group h3 { margin: 0 0 0.75rem 0; font-size: 1rem; }
.checkbox-label { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; cursor: pointer; }
.select-input { width: 100%; padding: 0.5rem; margin-bottom: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; }
.filter-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
.reset-btn, .save-btn { flex: 1; padding: 0.5rem; border: none; border-radius: 4px; cursor: pointer; }
.reset-btn { background: #e5e7eb; }
.save-btn { background: #4f46e5; color: white; }
.results-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.sort-select { padding: 0.5rem; border: 1px solid #e5e7eb; border-radius: 4px; }
.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
.product-card { border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
.product-image { width: 100%; height: 200px; object-fit: cover; }
.product-info { padding: 1rem; }
.product-info h4 { margin: 0 0 0.5rem 0; }
.price { font-weight: 600; color: #4f46e5; }
.fit-score { font-size: 0.875rem; color: #059669; margin-top: 0.5rem; }
.pagination { display: flex; justify-content: center; align-items: center; gap: 1rem; margin-top: 2rem; }
.pagination button { padding: 0.5rem 1rem; border: 1px solid #e5e7eb; background: white; border-radius: 4px; cursor: pointer; }
.pagination button:disabled { opacity: 0.5; cursor: not-allowed; }
@media (max-width: 768px) {
  .mobile-toggle { display: block; }
  .filter-sidebar { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 100; overflow-y: auto; }
  .filter-sidebar.mobile-open { display: block; }
  .filter-container { grid-template-columns: 1fr; }
}
</style>
