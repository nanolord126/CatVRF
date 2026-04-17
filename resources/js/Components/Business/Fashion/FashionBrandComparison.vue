<template>
  <div class="fashion-brand-comparison bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-xl font-bold text-gray-900 mb-6">Brand Comparison</h3>

    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Select Brands to Compare</label>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="brand in availableBrands"
          :key="brand.id"
          @click="toggleBrand(brand.id)"
          :class="selectedBrands.includes(brand.id) ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'"
          class="px-4 py-2 rounded-lg font-medium transition-colors"
        >
          {{ brand.name }}
        </button>
      </div>
    </div>

    <div v-if="selectedBrands.length > 0" class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-gray-200">
            <th class="text-left py-3 px-4 font-semibold text-gray-900">Metric</th>
            <th v-for="brandId in selectedBrands" :key="brandId" class="text-left py-3 px-4 font-semibold text-gray-900">
              {{ getBrandName(brandId) }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-gray-100">
            <td class="py-3 px-4 font-medium">Product Count</td>
            <td v-for="brandId in selectedBrands" :key="'products-' + brandId" class="py-3 px-4">
              {{ getBrandMetric(brandId, 'product_count') }}
            </td>
          </tr>
          <tr class="border-b border-gray-100">
            <td class="py-3 px-4 font-medium">Total Sales</td>
            <td v-for="brandId in selectedBrands" :key="'sales-' + brandId" class="py-3 px-4">
              {{ formatCurrency(getBrandMetric(brandId, 'total_sales')) }}
            </td>
          </tr>
          <tr class="border-b border-gray-100">
            <td class="py-3 px-4 font-medium">Average Rating</td>
            <td v-for="brandId in selectedBrands" :key="'rating-' + brandId" class="py-3 px-4">
              <div class="flex items-center gap-1">
                <span>{{ getBrandMetric(brandId, 'average_rating') }}</span>
                <span class="text-yellow-500">★</span>
              </div>
            </td>
          </tr>
          <tr class="border-b border-gray-100">
            <td class="py-3 px-4 font-medium">Sustainability Score</td>
            <td v-for="brandId in selectedBrands" :key="'sustainability-' + brandId" class="py-3 px-4">
              <div class="flex items-center gap-2">
                <div class="w-24 bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-green-500 rounded-full h-2"
                    :style="{ width: getSustainabilityScore(brandId) + '%' }"
                  ></div>
                </div>
                <span>{{ getSustainabilityScore(brandId) }}%</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="text-center text-gray-500 py-8">
      Select at least 2 brands to compare
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

interface Brand {
  id: number;
  name: string;
}

interface BrandComparison {
  brand_id: number;
  name: string;
  analytics: any;
  sustainability: any;
}

const availableBrands = ref<Brand[]>([]);
const selectedBrands = ref<number[]>([]);
const comparisonData = ref<BrandComparison[]>([]);

const toggleBrand = (brandId: number) => {
  if (selectedBrands.value.includes(brandId)) {
    selectedBrands.value = selectedBrands.value.filter(id => id !== brandId);
  } else if (selectedBrands.value.length < 4) {
    selectedBrands.value.push(brandId);
  }
};

const getBrandName = (brandId: number): string => {
  const brand = comparisonData.value.find(b => b.brand_id === brandId);
  return brand?.name || '';
};

const getBrandMetric = (brandId: number, metric: string): number => {
  const brand = comparisonData.value.find(b => b.brand_id === brandId);
  return brand?.analytics?.[metric] || 0;
};

const getSustainabilityScore = (brandId: number): number => {
  const brand = comparisonData.value.find(b => b.brand_id === brandId);
  return brand?.sustainability?.overall_score || 0;
};

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
  }).format(value);
};

onMounted(async () => {
  try {
    const response = await fetch('/api/fashion/brands');
    availableBrands.value = await response.json();
  } catch (error) {
    console.error('Failed to load brands:', error);
  }
});
</script>
