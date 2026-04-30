<template>
  <div class="inventory-dashboard">
    <div class="dashboard-header">
      <h1>WMS Inventory Dashboard</h1>
      <div class="actions">
        <button @click="refreshData" class="btn btn-primary" :disabled="loading">
          <RefreshCw :class="{ 'animate-spin': loading }" class="w-4 h-4 mr-2" />
          Refresh
        </button>
        <button @click="exportReport" class="btn btn-secondary">
          <Download class="w-4 h-4 mr-2" />
          Export Report
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <StatCard
        title="Total Items"
        :value="stats.totalItems"
        icon="Package"
        :change="stats.itemsChange"
        :trend="stats.itemsTrend"
      />
      <StatCard
        title="Total Stock"
        :value="stats.totalStock"
        icon="Box"
        :change="stats.stockChange"
        :trend="stats.stockTrend"
      />
      <StatCard
        title="Low Stock Alerts"
        :value="stats.lowStockCount"
        icon="AlertTriangle"
        :change="stats.alertsChange"
        :trend="stats.alertsTrend"
        :is-warning="stats.lowStockCount > 0"
      />
      <StatCard
        title="Expiring Soon"
        :value="stats.expiringCount"
        icon="Clock"
        :change="stats.expiringChange"
        :trend="stats.expiringTrend"
        :is-warning="stats.expiringCount > 0"
      />
    </div>

    <div class="content-grid">
      <div class="card">
        <div class="card-header">
          <h2>Recent Stock Movements</h2>
          <router-link to="/wms/movements" class="text-sm text-blue-600 hover:underline">
            View All
          </router-link>
        </div>
        <div class="card-body">
          <StockMovementsTable :movements="recentMovements" :loading="loading" />
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Low Stock Items</h2>
          <router-link to="/wms/reorder" class="text-sm text-blue-600 hover:underline">
            View All
          </router-link>
        </div>
        <div class="card-body">
          <LowStockTable :items="lowStockItems" :loading="loading" />
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h2>Stock by Warehouse</h2>
      </div>
      <div class="card-body">
        <WarehouseStockChart :data="warehouseStockData" :loading="loading" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { RefreshCw, Download } from 'lucide-vue-next';
import StatCard from './StatCard.vue';
import StockMovementsTable from './StockMovementsTable.vue';
import LowStockTable from './LowStockTable.vue';
import WarehouseStockChart from './WarehouseStockChart.vue';
import { useWMSApi } from '@/composables/useWMSApi';

const { getInventoryStats, getRecentMovements, getLowStockItems } = useWMSApi();

const loading = ref(false);
const stats = ref({
  totalItems: 0,
  totalStock: 0,
  lowStockCount: 0,
  expiringCount: 0,
  itemsChange: 0,
  stockChange: 0,
  alertsChange: 0,
  expiringChange: 0,
  itemsTrend: 'neutral' as 'up' | 'down' | 'neutral',
  stockTrend: 'neutral' as 'up' | 'down' | 'neutral',
  alertsTrend: 'neutral' as 'up' | 'down' | 'neutral',
  expiringTrend: 'neutral' as 'up' | 'down' | 'neutral',
});

const recentMovements = ref([]);
const lowStockItems = ref([]);
const warehouseStockData = ref([]);

const refreshData = async () => {
  loading.value = true;
  try {
    const [statsData, movementsData, lowStockData] = await Promise.all([
      getInventoryStats(),
      getRecentMovements(),
      getLowStockItems(),
    ]);

    stats.value = statsData;
    recentMovements.value = movementsData;
    lowStockItems.value = lowStockData;
    warehouseStockData.value = generateWarehouseChartData(statsData);
  } catch (error) {
    console.error('Failed to refresh data:', error);
  } finally {
    loading.value = false;
  }
};

const exportReport = async () => {
  loading.value = true;
  try {
    await getInventoryStats({ export: true });
  } catch (error) {
    console.error('Failed to export report:', error);
  } finally {
    loading.value = false;
  }
};

const generateWarehouseChartData = (data: any) => {
  return [
    { name: 'Warehouse 1', value: data.warehouse1Stock || 0 },
    { name: 'Warehouse 2', value: data.warehouse2Stock || 0 },
    { name: 'Warehouse 3', value: data.warehouse3Stock || 0 },
  ];
};

onMounted(() => {
  refreshData();
});
</script>

<style scoped>
.inventory-dashboard {
  padding: 24px;
  background-color: #f8fafc;
  min-height: 100vh;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.dashboard-header h1 {
  font-size: 28px;
  font-weight: 700;
  color: #1e293b;
}

.actions {
  display: flex;
  gap: 12px;
}

.btn {
  display: inline-flex;
  align-items: center;
  padding: 10px 16px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #2563eb;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #64748b;
  color: white;
}

.btn-secondary:hover {
  background-color: #475569;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.content-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.card-header h2 {
  font-size: 18px;
  font-weight: 600;
  color: #1e293b;
}

.card-body {
  padding: 20px;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>
