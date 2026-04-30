<template>
  <div class="warehouse-dashboard">
    <!-- Header -->
    <div class="dashboard-header">
      <h1 class="text-2xl font-bold text-gray-900">Warehouse Dashboard</h1>
      <div class="header-actions">
        <button
          @click="refreshData"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2"
          :disabled="loading"
        >
          <svg class="w-5 h-5" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Refresh
        </button>
        <button
          @click="toggleAutoRefresh"
          class="px-4 py-2 rounded-lg transition flex items-center gap-2"
          :class="autoRefresh ? 'bg-green-600 text-white' : 'bg-gray-600 text-white'"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ autoRefresh ? 'Auto: ON' : 'Auto: OFF' }}
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon bg-blue-100">
          <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Total Warehouses</div>
          <div class="stat-value">{{ stats.totalWarehouses }}</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon bg-green-100">
          <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Active Warehouses</div>
          <div class="stat-value text-green-600">{{ stats.activeWarehouses }}</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon bg-purple-100">
          <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Total Zones</div>
          <div class="stat-value">{{ stats.totalZones }}</div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon bg-yellow-100">
          <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Avg Utilization</div>
          <div class="stat-value" :class="getUtilizationClass(stats.avgUtilization)">
            {{ stats.avgUtilization.toFixed(1) }}%
          </div>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon bg-orange-100">
          <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div class="stat-content">
          <div class="stat-label">Low Stock Alerts</div>
          <div class="stat-value text-orange-600">{{ stats.lowStockAlerts }}</div>
        </div>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
      <!-- Warehouses List -->
      <div class="card warehouses-card">
        <div class="card-header">
          <h2 class="text-lg font-semibold">Warehouses</h2>
          <button @click="showCreateWarehouseModal = true" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
            + Add Warehouse
          </button>
        </div>
        <div class="card-content">
          <div v-if="loading" class="loading-spinner">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
          <div v-else-if="warehouses.length === 0" class="empty-state">
            <p class="text-gray-500">No warehouses found</p>
          </div>
          <div v-else class="warehouse-list">
            <div
              v-for="warehouse in warehouses"
              :key="warehouse.id"
              @click="selectWarehouse(warehouse)"
              class="warehouse-item"
              :class="{ 'selected': selectedWarehouse?.id === warehouse.id }"
            >
              <div class="warehouse-info">
                <div class="warehouse-name">{{ warehouse.name }}</div>
                <div class="warehouse-type">{{ formatType(warehouse.type) }}</div>
              </div>
              <div class="warehouse-metrics">
                <span class="warehouse-status" :class="warehouse.is_active ? 'text-green-600' : 'text-gray-400'">
                  {{ warehouse.is_active ? 'Active' : 'Inactive' }}
                </span>
                <span class="warehouse-utilization">
                  {{ warehouse.utilization_percentage.toFixed(1) }}%
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Zones Panel -->
      <div v-if="selectedWarehouse" class="card zones-card">
        <div class="card-header">
          <h2 class="text-lg font-semibold">Zones - {{ selectedWarehouse.name }}</h2>
        </div>
        <div class="card-content">
          <div v-if="loadingZones" class="loading-spinner">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
          <div v-else-if="zones.length === 0" class="empty-state">
            <p class="text-gray-500">No zones found</p>
          </div>
          <div v-else class="zone-list">
            <div v-for="zone in zones" :key="zone.id" class="zone-item">
              <div class="zone-info">
                <div class="zone-name">{{ zone.name }}</div>
                <div class="zone-type">{{ formatZoneType(zone.type) }}</div>
              </div>
              <div class="zone-stock">
                <div class="stock-bar">
                  <div
                    class="stock-fill"
                    :class="getUtilizationClass(zone.utilization_percentage)"
                    :style="{ width: `${zone.utilization_percentage}%` }"
                  ></div>
                </div>
                <div class="stock-label">
                  {{ zone.current_stock }} / {{ zone.capacity }} units
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Movements -->
      <div v-if="selectedWarehouse" class="card movements-card">
        <div class="card-header">
          <h2 class="text-lg font-semibold">Recent Movements</h2>
        </div>
        <div class="card-content">
          <div v-if="loadingMovements" class="loading-spinner">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </div>
          <div v-else-if="recentMovements.length === 0" class="empty-state">
            <p class="text-gray-500">No recent movements</p>
          </div>
          <div v-else class="movement-list">
            <div v-for="movement in recentMovements" :key="movement.id" class="movement-item">
              <div class="movement-info">
                <div class="movement-sku">{{ movement.product_sku }}</div>
                <div class="movement-type" :class="getMovementTypeClass(movement.movement_type)">
                  {{ formatMovementType(movement.movement_type) }}
                </div>
              </div>
              <div class="movement-quantity">
                Qty: {{ movement.quantity }}
              </div>
              <div class="movement-time">
                {{ formatTime(movement.created_at) }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Warehouse Modal -->
    <div v-if="showCreateWarehouseModal" class="modal-overlay" @click="showCreateWarehouseModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3 class="text-lg font-semibold">Create New Warehouse</h3>
          <button @click="showCreateWarehouseModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <form @submit.prevent="createWarehouse" class="modal-body">
          <div class="form-group">
            <label class="form-label">Warehouse Name</label>
            <input v-model="newWarehouse.name" type="text" class="form-input" required />
          </div>
          <div class="form-group">
            <label class="form-label">Address</label>
            <textarea v-model="newWarehouse.address" class="form-input" rows="3" required></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Type</label>
            <select v-model="newWarehouse.type" class="form-input" required>
              <option value="central">Central</option>
              <option value="regional">Regional</option>
              <option value="local">Local</option>
              <option value="transit">Transit</option>
              <option value="returns">Returns</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Capacity</label>
            <input v-model.number="newWarehouse.capacity" type="number" class="form-input" required min="1" />
          </div>
          <div class="form-group">
            <label class="form-label">Branch ID (optional)</label>
            <input v-model="newWarehouse.branch_id" type="text" class="form-input" />
          </div>
          <div class="modal-footer">
            <button type="button" @click="showCreateWarehouseModal = false" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="creating">
              {{ creating ? 'Creating...' : 'Create Warehouse' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useWarehouseApi } from '@/composables/useWarehouseApi';
import { formatTime } from '@/utils/dateFormatter';

const warehouseApi = useWarehouseApi();

// State
const warehouses = ref([]);
const zones = ref([]);
const recentMovements = ref([]);
const selectedWarehouse = ref(null);
const stats = ref({
  totalWarehouses: 0,
  activeWarehouses: 0,
  totalZones: 0,
  avgUtilization: 0,
  lowStockAlerts: 0,
});

const loading = ref(false);
const loadingZones = ref(false);
const loadingMovements = ref(false);
const autoRefresh = ref(true);
const refreshInterval = ref(30);
let refreshTimer = null;

// Modal state
const showCreateWarehouseModal = ref(false);
const creating = ref(false);
const newWarehouse = ref({
  name: '',
  address: '',
  type: 'central',
  capacity: 10000,
  branch_id: '',
});

// Methods
const refreshData = async () => {
  loading.value = true;
  try {
    const data = await warehouseApi.getWarehouses();
    warehouses.value = data;
    calculateStats(data);
  } catch (error) {
    console.error('Failed to load warehouses:', error);
  } finally {
    loading.value = false;
  }
};

const selectWarehouse = async (warehouse) => {
  selectedWarehouse.value = warehouse;
  await loadZones(warehouse.id);
  await loadRecentMovements(warehouse.id);
};

const loadZones = async (warehouseId) => {
  loadingZones.value = true;
  try {
    zones.value = await warehouseApi.getZonesByWarehouse(warehouseId);
    stats.value.totalZones = zones.value.length;
  } catch (error) {
    console.error('Failed to load zones:', error);
  } finally {
    loadingZones.value = false;
  }
};

const loadRecentMovements = async (warehouseId) => {
  loadingMovements.value = true;
  try {
    const movements = await warehouseApi.getMovementsByWarehouse(warehouseId);
    recentMovements.value = movements.slice(0, 10);
  } catch (error) {
    console.error('Failed to load movements:', error);
  } finally {
    loadingMovements.value = false;
  }
};

const calculateStats = (warehouseList) => {
  stats.value.totalWarehouses = warehouseList.length;
  stats.value.activeWarehouses = warehouseList.filter(w => w.is_active).length;
  
  const utilizations = warehouseList.map(w => w.utilization_percentage);
  stats.value.avgUtilization = utilizations.length > 0
    ? utilizations.reduce((a, b) => a + b, 0) / utilizations.length
    : 0;
};

const createWarehouse = async () => {
  creating.value = true;
  try {
    await warehouseApi.createWarehouse(newWarehouse.value);
    showCreateWarehouseModal.value = false;
    newWarehouse.value = {
      name: '',
      address: '',
      type: 'central',
      capacity: 10000,
      branch_id: '',
    };
    await refreshData();
  } catch (error) {
    console.error('Failed to create warehouse:', error);
  } finally {
    creating.value = false;
  }
};

const toggleAutoRefresh = () => {
  autoRefresh.value = !autoRefresh.value;
  if (autoRefresh.value) {
    startAutoRefresh();
  } else {
    stopAutoRefresh();
  }
};

const startAutoRefresh = () => {
  if (refreshTimer) clearInterval(refreshTimer);
  refreshTimer = setInterval(refreshData, refreshInterval.value * 1000);
};

const stopAutoRefresh = () => {
  if (refreshTimer) {
    clearInterval(refreshTimer);
    refreshTimer = null;
  }
};

// Helper functions
const formatType = (type) => {
  const types = {
    central: 'Central',
    regional: 'Regional',
    local: 'Local',
    transit: 'Transit',
    returns: 'Returns',
  };
  return types[type] || type;
};

const formatZoneType = (type) => {
  const types = {
    receiving: 'Receiving',
    storage: 'Storage',
    picking: 'Picking',
    packing: 'Packing',
    shipping: 'Shipping',
    quarantine: 'Quarantine',
    returns: 'Returns',
  };
  return types[type] || type;
};

const formatMovementType = (type) => {
  const types = {
    receipt: 'Receipt',
    transfer: 'Transfer',
    picking: 'Picking',
    packing: 'Packing',
    shipment: 'Shipment',
    return: 'Return',
    adjustment: 'Adjustment',
    damage: 'Damage',
    loss: 'Loss',
    conversion: 'Conversion',
  };
  return types[type] || type;
};

const getUtilizationClass = (value) => {
  if (value > 80) return 'text-red-600';
  if (value > 50) return 'text-yellow-600';
  return 'text-green-600';
};

const getMovementTypeClass = (type) => {
  const classes = {
    receipt: 'text-green-600',
    transfer: 'text-blue-600',
    picking: 'text-yellow-600',
    packing: 'text-purple-600',
    shipment: 'text-indigo-600',
    return: 'text-gray-600',
    adjustment: 'text-orange-600',
    damage: 'text-red-600',
    loss: 'text-red-600',
  };
  return classes[type] || 'text-gray-600';
};

// Lifecycle
onMounted(() => {
  refreshData();
  if (autoRefresh.value) {
    startAutoRefresh();
  }
});

onUnmounted(() => {
  stopAutoRefresh();
});
</script>

<style scoped>
.warehouse-dashboard {
  padding: 24px;
  background-color: #f9fafb;
  min-height: 100vh;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header-actions {
  display: flex;
  gap: 12px;
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
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 16px;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 14px;
  color: #6b7280;
  margin-bottom: 4px;
}

.stat-value {
  font-size: 24px;
  font-weight: 600;
  color: #111827;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 24px;
}

@media (max-width: 1024px) {
  .content-grid {
    grid-template-columns: 1fr;
  }
}

.card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-content {
  padding: 20px;
  max-height: 400px;
  overflow-y: auto;
}

.warehouse-list,
.zone-list,
.movement-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.warehouse-item,
.zone-item,
.movement-item {
  padding: 12px;
  border-radius: 8px;
  background-color: #f9fafb;
  cursor: pointer;
  transition: background-color 0.2s;
}

.warehouse-item:hover,
.zone-item:hover {
  background-color: #f3f4f6;
}

.warehouse-item.selected {
  background-color: #dbeafe;
  border: 2px solid #3b82f6;
}

.warehouse-info,
.zone-info,
.movement-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.warehouse-name,
.zone-name,
.movement-sku {
  font-weight: 600;
  color: #111827;
}

.warehouse-type,
.zone-type,
.movement-type {
  font-size: 14px;
  color: #6b7280;
}

.warehouse-metrics {
  display: flex;
  gap: 12px;
  margin-top: 8px;
}

.warehouse-status,
.warehouse-utilization {
  font-size: 12px;
  color: #6b7280;
}

.stock-bar {
  width: 100%;
  height: 8px;
  background-color: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
  margin-top: 8px;
}

.stock-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.stock-label {
  font-size: 12px;
  color: #6b7280;
  margin-top: 4px;
}

.movement-quantity,
.movement-time {
  font-size: 14px;
  color: #6b7280;
}

.loading-spinner {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 40px;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #9ca3af;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 50;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  padding: 20px;
}

.form-group {
  margin-bottom: 16px;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  margin-bottom: 8px;
}

.form-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
}

.btn {
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-secondary {
  background-color: #e5e7eb;
  color: #374151;
}

.btn-secondary:hover {
  background-color: #d1d5db;
}

.btn-primary {
  background-color: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background-color: #2563eb;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
