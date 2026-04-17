<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';

interface Property {
  id: number;
  uuid: string;
  title: string;
  address: string;
  price: number;
  type: string;
  area_sqm: number;
  photos: string[];
  features: {
    ai_virtual_tour_url?: string;
    ar_viewing_url?: string;
    dynamic_pricing_enabled?: boolean;
  };
  dynamic_price?: number;
  is_flash_discount?: boolean;
}

interface FilterOptions {
  type?: string;
  minPrice?: number;
  maxPrice?: number;
  minArea?: number;
  maxArea?: number;
  hasVirtualTour?: boolean;
  hasAR?: boolean;
}

const props = defineProps<{
  initialProperties?: Property[];
}>();

const emit = defineEmits<{
  (e: 'select-property', property: Property): void;
  (e: 'filter-change', filters: FilterOptions): void;
}>();

const properties = ref<Property[]>(props.initialProperties || []);
const loading = ref(false);
const filters = ref<FilterOptions>({
  type: '',
  minPrice: 0,
  maxPrice: 0,
  minArea: 0,
  maxArea: 0,
  hasVirtualTour: false,
  hasAR: false,
});

const propertyTypes = [
  { value: '', label: 'Все типы' },
  { value: 'apartment', label: 'Квартира' },
  { value: 'house', label: 'Дом' },
  { value: 'studio', label: 'Студия' },
  { value: 'commercial', label: 'Коммерческое' },
];

const filteredProperties = computed(() => {
  return properties.value.filter(property => {
    if (filters.value.type && property.type !== filters.value.type) return false;
    if (filters.value.minPrice && property.price < filters.value.minPrice) return false;
    if (filters.value.maxPrice && property.price > filters.value.maxPrice) return false;
    if (filters.value.minArea && property.area_sqm < filters.value.minArea) return false;
    if (filters.value.maxArea && property.area_sqm > filters.value.maxArea) return false;
    if (filters.value.hasVirtualTour && !property.features.ai_virtual_tour_url) return false;
    if (filters.value.hasAR && !property.features.ar_viewing_url) return false;
    return true;
  });
});

const sortedProperties = computed(() => {
  return [...filteredProperties.value].sort((a, b) => {
    const priceA = a.dynamic_price || a.price;
    const priceB = b.dynamic_price || b.price;
    return priceA - priceB;
  });
});

const loadProperties = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/v1/real-estate/properties?' + new URLSearchParams({
      type: filters.value.type || '',
      min_price: filters.value.minPrice?.toString() || '',
      max_price: filters.value.maxPrice?.toString() || '',
      min_area: filters.value.minArea?.toString() || '',
      max_area: filters.value.maxArea?.toString() || '',
      has_virtual_tour: filters.value.hasVirtualTour ? '1' : '',
      has_ar: filters.value.hasAR ? '1' : '',
    }));
    const data = await response.json();
    properties.value = data.properties || [];
  } catch (error) {
    console.error('Failed to load properties:', error);
  } finally {
    loading.value = false;
  }
};

const loadDynamicPrices = async () => {
  for (const property of properties.value) {
    if (property.features.dynamic_pricing_enabled) {
      try {
        const response = await fetch(`/api/v1/real-estate/transactions/properties/${property.id}/pricing`);
        const data = await response.json();
        if (data.success) {
          property.dynamic_price = data.pricing.final_price;
          property.is_flash_discount = data.pricing.is_flash_discount;
        }
      } catch (error) {
        console.error(`Failed to load dynamic price for property ${property.id}:`, error);
      }
    }
  }
};

const selectProperty = (property: Property) => {
  emit('select-property', property);
};

const resetFilters = () => {
  filters.value = {
    type: '',
    minPrice: 0,
    maxPrice: 0,
    minArea: 0,
    maxArea: 0,
    hasVirtualTour: false,
    hasAR: false,
  };
};

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
  }).format(price);
};

const formatArea = (area: number): string => {
  return `${area} м²`;
};

watch(filters, () => {
  loadProperties();
}, { deep: true });

onMounted(() => {
  loadProperties();
  setTimeout(loadDynamicPrices, 1000);
});
</script>

<template>
  <div class="property-grid">
    <div class="filters">
      <h2>Фильтры</h2>
      <div class="filter-group">
        <label>Тип объекта:</label>
        <select v-model="filters.type">
          <option v-for="type in propertyTypes" :key="type.value" :value="type.value">
            {{ type.label }}
          </option>
        </select>
      </div>
      <div class="filter-group">
        <label>Мин. цена (₽):</label>
        <input v-model.number="filters.minPrice" type="number" min="0" step="100000" />
      </div>
      <div class="filter-group">
        <label>Макс. цена (₽):</label>
        <input v-model.number="filters.maxPrice" type="number" min="0" step="100000" />
      </div>
      <div class="filter-group">
        <label>Мин. площадь (м²):</label>
        <input v-model.number="filters.minArea" type="number" min="0" step="5" />
      </div>
      <div class="filter-group">
        <label>Макс. площадь (м²):</label>
        <input v-model.number="filters.maxArea" type="number" min="0" step="5" />
      </div>
      <div class="filter-group checkbox">
        <label>
          <input v-model="filters.hasVirtualTour" type="checkbox" />
          С виртуальным туром
        </label>
      </div>
      <div class="filter-group checkbox">
        <label>
          <input v-model="filters.hasAR" type="checkbox" />
          С AR-просмотром
        </label>
      </div>
      <button @click="resetFilters" class="reset-button">Сбросить</button>
    </div>

    <div v-if="loading" class="loading">Загрузка...</div>

    <div v-else-if="sortedProperties.length === 0" class="empty">
      <p>Нет объектов, соответствующих выбранным фильтрам</p>
    </div>

    <div v-else class="grid">
      <div
        v-for="property in sortedProperties"
        :key="property.id"
        class="property-card"
        @click="selectProperty(property)"
      >
        <div class="property-image">
          <img v-if="property.photos[0]" :src="property.photos[0]" :alt="property.title" />
          <div v-else class="placeholder">Нет фото</div>
          <div v-if="property.is_flash_discount" class="flash-badge">Flash скидка</div>
        </div>
        <div class="property-info">
          <h3 class="property-title">{{ property.title }}</h3>
          <p class="property-address">{{ property.address }}</p>
          <div class="property-meta">
            <span class="meta-item">{{ formatArea(property.area_sqm) }}</span>
            <span class="meta-item">{{ property.type }}</span>
          </div>
          <div class="property-price">
            <span class="price">
              {{ formatPrice(property.dynamic_price || property.price) }}
            </span>
            <span v-if="property.dynamic_price && property.dynamic_price !== property.price" class="original-price">
              {{ formatPrice(property.price) }}
            </span>
          </div>
          <div class="property-features">
            <span v-if="property.features.ai_virtual_tour_url" class="feature-badge">
              🎥 360°
            </span>
            <span v-if="property.features.ar_viewing_url" class="feature-badge">
              📱 AR
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="results-count">
      Показано {{ sortedProperties.length }} из {{ properties.length }} объектов
    </div>
  </div>
</template>

<style scoped>
.property-grid {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.filters {
  background: #f8f9fa;
  padding: 24px;
  border-radius: 12px;
  margin-bottom: 30px;
}

.filters h2 {
  font-size: 20px;
  font-weight: 600;
  margin-bottom: 16px;
  color: #1a1a1a;
}

.filter-group {
  margin-bottom: 16px;
}

.filter-group label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
}

.filter-group select,
.filter-group input[type="number"] {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  background: white;
}

.filter-group.checkbox label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 400;
  cursor: pointer;
}

.filter-group.checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.reset-button {
  width: 100%;
  padding: 12px;
  background: #64748b;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.reset-button:hover {
  background: #475569;
}

.loading {
  text-align: center;
  padding: 60px;
  font-size: 18px;
  color: #666;
}

.empty {
  text-align: center;
  padding: 60px;
  color: #666;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 24px;
  margin-bottom: 20px;
}

.property-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.property-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.property-image {
  position: relative;
  height: 200px;
  background: #e5e7eb;
  overflow: hidden;
}

.property-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #9ca3af;
  font-size: 14px;
}

.flash-badge {
  position: absolute;
  top: 12px;
  left: 12px;
  background: #ef4444;
  color: white;
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.property-info {
  padding: 16px;
}

.property-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 8px;
  color: #1a1a1a;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.property-address {
  font-size: 14px;
  color: #666;
  margin-bottom: 12px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.property-meta {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
}

.meta-item {
  font-size: 13px;
  color: #4b5563;
  background: #f3f4f6;
  padding: 4px 8px;
  border-radius: 4px;
}

.property-price {
  display: flex;
  flex-direction: column;
  margin-bottom: 12px;
}

.price {
  font-size: 22px;
  font-weight: 700;
  color: #2563eb;
}

.original-price {
  font-size: 14px;
  color: #9ca3af;
  text-decoration: line-through;
}

.property-features {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.feature-badge {
  font-size: 12px;
  padding: 4px 8px;
  background: #eff6ff;
  color: #2563eb;
  border-radius: 4px;
  font-weight: 500;
}

.results-count {
  text-align: center;
  color: #666;
  font-size: 14px;
  padding: 20px;
}
</style>
