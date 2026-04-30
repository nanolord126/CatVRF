<template>
  <div class="analytics-view space-y-6">
    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div v-for="kpi in kpis" :key="kpi.label" class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">{{ kpi.label }}</p>
            <p class="text-2xl font-bold" :style="{ color: kpi.color }">{{ kpi.value }}</p>
          </div>
          <div :class="kpi.iconBg" class="p-3 rounded-lg">
            <component :is="kpi.icon" :class="kpi.iconColor" class="w-6 h-6" />
          </div>
        </div>
        <p class="mt-2 text-xs text-gray-500">{{ kpi.change }}</p>
      </div>
    </div>

    <!-- Charts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Sales Trend -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Sales Trend</h3>
        <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
          <span class="text-gray-400">Sales chart placeholder</span>
        </div>
      </div>

      <!-- Customer Acquisition -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Customer Acquisition</h3>
        <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
          <span class="text-gray-400">Acquisition chart placeholder</span>
        </div>
      </div>

      <!-- Revenue by Vertical -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Revenue by Vertical</h3>
        <div class="space-y-3">
          <div v-for="vertical in verticalRevenue" :key="vertical.name" class="flex items-center">
            <span class="w-32 text-sm text-gray-900">{{ vertical.name }}</span>
            <div class="flex-1 mx-4 bg-gray-200 rounded-full h-2">
              <div class="h-2 rounded-full" :style="{ width: vertical.percent + '%', backgroundColor: vertical.color }"></div>
            </div>
            <span class="text-sm font-medium text-gray-900">{{ formatCurrency(vertical.revenue) }}</span>
          </div>
        </div>
      </div>

      <!-- Top Products -->
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="font-semibold text-gray-900 mb-4">Top Products</h3>
        <div class="space-y-3">
          <div v-for="product in topProducts" :key="product.name" class="flex items-center justify-between p-2 border-b border-gray-100 last:border-0">
            <span class="text-sm text-gray-900">{{ product.name }}</span>
            <span class="text-sm font-medium text-gray-900">{{ formatCurrency(product.revenue) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Detailed Metrics -->
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="font-semibold text-gray-900 mb-4">Detailed Metrics</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <h4 class="text-sm font-medium text-gray-600 mb-2">Conversion Rate</h4>
          <p class="text-2xl font-bold text-gray-900">12.5%</p>
          <p class="text-xs text-green-600">+2.3% vs last month</p>
        </div>
        <div>
          <h4 class="text-sm font-medium text-gray-600 mb-2">Average Order Value</h4>
          <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(2850) }}</p>
          <p class="text-xs text-green-600">+150 ₽ vs last month</p>
        </div>
        <div>
          <h4 class="text-sm font-medium text-gray-600 mb-2">Customer Lifetime Value</h4>
          <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(12500) }}</p>
          <p class="text-xs text-green-600">+8% vs last month</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const kpis = ref([
  { label: 'Total Revenue', value: '4.2M ₽', change: '+15% this month', color: '#10B981', icon: 'CurrencyDollarIcon', iconBg: 'bg-green-100', iconColor: 'text-green-600' },
  { label: 'Total Orders', value: '2,081', change: '+12% this month', color: '#3B82F6', icon: 'ShoppingCartIcon', iconBg: 'bg-blue-100', iconColor: 'text-blue-600' },
  { label: 'Active Customers', value: '1,234', change: '+8% this month', color: '#8B5CF6', icon: 'UsersIcon', iconBg: 'bg-purple-100', iconColor: 'text-purple-600' },
  { label: 'Conversion Rate', value: '12.5%', change: '+2% this month', color: '#F59E0B', icon: 'ChartBarIcon', iconBg: 'bg-yellow-100', iconColor: 'text-yellow-600' },
]);

const verticalRevenue = ref([
  { name: 'Restaurant', revenue: 850000, percent: 45, color: '#EF4444' },
  { name: 'Beauty', revenue: 620000, percent: 33, color: '#EC4899' },
  { name: 'Hotels', revenue: 280000, percent: 15, color: '#3B82F6' },
  { name: 'Fashion', revenue: 150000, percent: 7, color: '#8B5CF6' },
]);

const topProducts = ref([
  { name: 'Premium Haircut', revenue: 125000 },
  { name: 'Business Lunch', revenue: 98000 },
  { name: 'Hotel Room (Night)', revenue: 85000 },
  { name: 'Facial Treatment', revenue: 62000 },
  { name: 'Fashion Item', revenue: 45000 },
]);

function formatCurrency(amount: number): string {
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', maximumFractionDigits: 0 }).format(amount);
}
</script>
