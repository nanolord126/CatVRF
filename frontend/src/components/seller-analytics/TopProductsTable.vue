<template>
  <div class="top-products-table bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <!-- Loading State -->
    <div v-if="loading" class="animate-pulse">
      <div class="h-4 bg-gray-200 rounded w-1/3 mb-4"></div>
      <div class="space-y-3">
        <div class="h-12 bg-gray-200 rounded"></div>
        <div class="h-12 bg-gray-200 rounded"></div>
        <div class="h-12 bg-gray-200 rounded"></div>
      </div>
    </div>

    <!-- Content -->
    <div v-else>
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Top Products</h3>
        <span class="text-xs text-gray-500">{{ displayedProducts.length }} products</span>
      </div>

      <!-- No Products -->
      <div v-if="displayedProducts.length === 0" class="text-center py-8">
        <div class="text-gray-400 text-4xl mb-2">📦</div>
        <p class="text-gray-500">No products data available</p>
      </div>

      <!-- Products Table -->
      <div v-else class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              <th class="pb-3 pr-4">#</th>
              <th class="pb-3 pr-4">Product</th>
              <th class="pb-3 pr-4">Category</th>
              <th class="pb-3 pr-4 text-right">Revenue</th>
              <th class="pb-3 pr-4 text-right">Orders</th>
              <th class="pb-3 text-right">Conversion</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="(product, index) in displayedProducts"
              :key="product.id"
              class="hover:bg-gray-50 transition-colors"
            >
              <td class="py-3 pr-4">
                <span
                  :class="[
                    'inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold',
                    index < 3 ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-600'
                  ]"
                >
                  {{ index + 1 }}
                </span>
              </td>
              <td class="py-3 pr-4">
                <div class="font-medium text-gray-900">{{ product.name }}</div>
              </td>
              <td class="py-3 pr-4">
                <span class="text-sm text-gray-600">
                  {{ product.category || 'N/A' }}
                </span>
              </td>
              <td class="py-3 pr-4 text-right">
                <span class="font-medium text-gray-900">
                  {{ formatCurrency(product.value) }}
                </span>
              </td>
              <td class="py-3 pr-4 text-right">
                <span class="text-sm text-gray-600">
                  {{ product.metadata.orders.toLocaleString() }}
                </span>
              </td>
              <td class="py-3 text-right">
                <span
                  :class="[
                    'text-sm font-medium',
                    getConversionColor(product.metadata.conversion_rate)
                  ]"
                >
                  {{ product.metadata.conversion_rate.toFixed(1) }}%
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Show More/Less -->
      <div v-if="products.length > maxItems" class="mt-4 text-center">
        <button
          @click="showAll = !showAll"
          class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
        >
          {{ showAll ? 'Show less' : `View all ${products.length} products` }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { TopProduct } from '@/types/analytics'

interface Props {
  products: TopProduct[]
  loading?: boolean
  maxItems?: number
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  maxItems: 10
})

const showAll = ref(false)

const displayedProducts = computed(() => {
  if (showAll.value) {
    return props.products
  }
  return props.products.slice(0, props.maxItems)
})

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(value)
}

const getConversionColor = (rate: number): string => {
  if (rate >= 5) return 'text-green-600'
  if (rate >= 3) return 'text-yellow-600'
  return 'text-red-600'
}
</script>
