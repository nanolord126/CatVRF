<template>
  <div class="inventory-dashboard">
    <div class="dashboard-header">
      <h1>Inventory Dashboard</h1>
      <div class="actions">
        <button @click="refreshData" class="btn btn-primary">
          <RefreshIcon class="icon" />
          Refresh
        </button>
        <button @click="exportData" class="btn btn-secondary">
          <DownloadIcon class="icon" />
          Export
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" v-for="stat in stats" :key="stat.label">
        <div class="stat-label">{{ stat.label }}</div>
        <div class="stat-value" :class="stat.trend">
          {{ stat.value }}
        </div>
        <div class="stat-trend" v-if="stat.trend">
          <TrendingUpIcon v-if="stat.trend === 'up'" class="trend-icon up" />
          <TrendingDownIcon v-else class="trend-icon down" />
          <span>{{ stat.change }}</span>
        </div>
      </div>
    </div>

    <div class="dashboard-grid">
      <div class="card">
        <div class="card-header">
          <h2>Inventory by Category</h2>
          <Select v-model="selectedWarehouse" @change="loadCategoryData">
            <option value="">All Warehouses</option>
            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
              {{ warehouse.name }}
            </option>
          </Select>
        </div>
        <div class="card-body">
          <BarChart :data="categoryChartData" :options="chartOptions" />
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>ABC-XYZ Analysis</h2>
          <Select v-model="abcFilter" @change="loadABCData">
            <option value="all">All Classes</option>
            <option value="AX">AX (High Value, Stable)</option>
            <option value="AY">AY (High Value, Variable)</option>
            <option value="AZ">AZ (High Value, Unpredictable)</option>
            <option value="BX">BX (Medium Value, Stable)</option>
            <option value="BY">BY (Medium Value, Variable)</option>
            <option value="BZ">BZ (Medium Value, Unpredictable)</option>
            <option value="CX">CX (Low Value, Stable)</option>
            <option value="CY">CY (Low Value, Variable)</option>
            <option value="CZ">CZ (Low Value, Unpredictable)</option>
          </Select>
        </div>
        <div class="card-body">
          <PieChart :data="abcChartData" :options="chartOptions" />
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Expiration Alerts</h2>
          <Badge :count="expiringItems.length" :max="99" />
        </div>
        <div class="card-body">
          <div class="expiration-list">
            <div v-for="item in expiringItems" :key="item.id" class="expiration-item">
              <div class="item-info">
                <div class="item-name">{{ item.name }}</div>
                <div class="item-batch">{{ item.batchNumber }}</div>
              </div>
              <div class="item-expiry" :class="getExpiryClass(item.daysUntilExpiry)">
                {{ item.expiryDate }}
              </div>
              <button @click="viewItem(item)" class="btn btn-sm">
                View
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Recent Activity</h2>
        </div>
        <div class="card-body">
          <div class="activity-list">
            <div v-for="activity in recentActivity" :key="activity.id" class="activity-item">
              <div class="activity-icon" :class="activity.type">
                <component :is="getActivityIcon(activity.type)" />
              </div>
              <div class="activity-content">
                <div class="activity-title">{{ activity.title }}</div>
                <div class="activity-time">{{ formatTime(activity.timestamp) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { BarChart, PieChart } from 'vue-chartjs';
import { RefreshIcon, DownloadIcon, TrendingUpIcon, TrendingDownIcon, PackageIcon, AlertTriangleIcon, CheckCircleIcon } from 'lucide-vue';
import { Select, Badge } from '@/components/ui';

const stats = ref([
  { label: 'Total Items', value: '12,456', trend: 'up', change: '+5.2%' },
  { label: 'Total Value', value: '$2.4M', trend: 'up', change: '+3.1%' },
  { label: 'Low Stock', value: '23', trend: 'down', change: '-12%' },
  { label: 'Expiring Soon', value: '8', trend: 'down', change: '-2' },
]);

const warehouses = ref([
  { id: 1, name: 'Warehouse A' },
  { id: 2, name: 'Warehouse B' },
  { id: 3, name: 'Warehouse C' },
]);

const selectedWarehouse = ref('');
const abcFilter = ref('all');

const categoryChartData = ref({
  labels: ['Pharmaceuticals', 'Medical Equipment', 'Supplies', 'Other'],
  datasets: [{
    label: 'Items',
    data: [4500, 3200, 3800, 956],
    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#6b7280'],
  }],
});

const abcChartData = ref({
  labels: ['AX', 'AY', 'AZ', 'BX', 'BY', 'BZ', 'CX', 'CY', 'CZ'],
  datasets: [{
    data: [15, 10, 5, 25, 15, 10, 20, 7, 3],
    backgroundColor: ['#22c55e', '#eab308', '#ef4444', '#22c55e', '#eab308', '#ef4444', '#22c55e', '#eab308', '#ef4444'],
  }],
});

const expiringItems = ref([
  { id: 1, name: 'Antibiotic X', batchNumber: 'BATCH-001', expiryDate: '2026-05-15', daysUntilExpiry: 17 },
  { id: 2, name: 'Insulin', batchNumber: 'BATCH-002', expiryDate: '2026-05-20', daysUntilExpiry: 22 },
  { id: 3, name: 'Vaccine A', batchNumber: 'BATCH-003', expiryDate: '2026-05-25', daysUntilExpiry: 27 },
]);

const recentActivity = ref([
  { id: 1, type: 'stock_in', title: 'Received 100 units of Antibiotic X', timestamp: new Date() },
  { id: 2, type: 'stock_out', title: 'Shipped 50 units of Medical Supplies', timestamp: new Date(Date.now() - 3600000) },
  { id: 3, type: 'alert', title: 'Low stock alert for Insulin', timestamp: new Date(Date.now() - 7200000) },
  { id: 4, type: 'check', title: 'Cycle count completed for Zone A', timestamp: new Date(Date.now() - 10800000) },
]);

const chartOptions = {
  responsive: true,
  plugins: {
    legend: {
      position: 'bottom',
    },
  },
};

const refreshData = async () => {
  // Implement refresh logic
};

const exportData = () => {
  // Implement export logic
};

const loadCategoryData = () => {
  // Implement category data loading
};

const loadABCData = () => {
  // Implement ABC data loading
};

const viewItem = (item) => {
  // Implement item view logic
};

const getExpiryClass = (days) => {
  if (days <= 7) return 'critical';
  if (days <= 30) return 'warning';
  return 'normal';
};

const getActivityIcon = (type) => {
  switch (type) {
    case 'stock_in': return PackageIcon;
    case 'stock_out': return PackageIcon;
    case 'alert': return AlertTriangleIcon;
    case 'check': return CheckCircleIcon;
    default: return PackageIcon;
  }
};

const formatTime = (timestamp) => {
  const now = new Date();
  const diff = now - timestamp;
  const hours = Math.floor(diff / 3600000);
  if (hours < 1) return 'Just now';
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  return `${days}d ago`;
};

onMounted(() => {
  refreshData();
});
</script>

<style scoped>
.inventory-dashboard {
  padding: 24px;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.dashboard-header h1 {
  font-size: 24px;
  font-weight: 600;
}

.actions {
  display: flex;
  gap: 12px;
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-secondary {
  background-color: #6b7280;
  color: white;
}

.icon {
  width: 16px;
  height: 16px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-label {
  font-size: 14px;
  color: #6b7280;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 28px;
  font-weight: 600;
  color: #111827;
}

.stat-value.up {
  color: #10b981;
}

.stat-value.down {
  color: #ef4444;
}

.stat-trend {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 8px;
  font-size: 12px;
}

.trend-icon {
  width: 14px;
  height: 14px;
}

.trend-icon.up {
  color: #10b981;
}

.trend-icon.down {
  color: #ef4444;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 16px;
}

.card {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.card-header h2 {
  font-size: 16px;
  font-weight: 600;
}

.card-body {
  padding: 16px;
}

.expiration-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.expiration-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
}

.item-info {
  flex: 1;
}

.item-name {
  font-weight: 500;
  margin-bottom: 4px;
}

.item-batch {
  font-size: 12px;
  color: #6b7280;
}

.item-expiry {
  font-size: 14px;
  font-weight: 500;
  margin-right: 12px;
}

.item-expiry.critical {
  color: #ef4444;
}

.item-expiry.warning {
  color: #f59e0b;
}

.item-expiry.normal {
  color: #10b981;
}

.btn-sm {
  padding: 6px 12px;
  font-size: 12px;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.activity-item {
  display: flex;
  gap: 12px;
  align-items: flex-start;
}

.activity-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.activity-icon.stock_in {
  background: #dbeafe;
  color: #3b82f6;
}

.activity-icon.stock_out {
  background: #fce7f3;
  color: #ec4899;
}

.activity-icon.alert {
  background: #fee2e2;
  color: #ef4444;
}

.activity-icon.check {
  background: #d1fae5;
  color: #10b981;
}

.activity-content {
  flex: 1;
}

.activity-title {
  font-weight: 500;
  margin-bottom: 4px;
}

.activity-time {
  font-size: 12px;
  color: #6b7280;
}
</style>
