<template>
  <div class="zone-management">
    <div class="zone-header">
      <h2 class="text-xl font-bold">Zone Management</h2>
      <button
        @click="showCreateZoneModal = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        + Add Zone
      </button>
    </div>

    <!-- Zone Grid -->
    <div class="zone-grid">
      <div v-for="zone in zones" :key="zone.id" class="zone-card">
        <div class="zone-card-header">
          <div class="zone-name">{{ zone.name }}</div>
          <div class="zone-type-badge" :class="getZoneTypeClass(zone.type)">
            {{ formatZoneType(zone.type) }}
          </div>
        </div>

        <div class="zone-metrics">
          <div class="metric">
            <div class="metric-label">Capacity</div>
            <div class="metric-value">{{ zone.capacity.toLocaleString() }}</div>
          </div>
          <div class="metric">
            <div class="metric-label">Current Stock</div>
            <div class="metric-value">{{ zone.current_stock.toLocaleString() }}</div>
          </div>
          <div class="metric">
            <div class="metric-label">Utilization</div>
            <div class="metric-value" :class="getUtilizationClass(zone.utilization_percentage)">
              {{ zone.utilization_percentage.toFixed(1) }}%
            </div>
          </div>
        </div>

        <div class="zone-progress">
          <div class="progress-bar">
            <div
              class="progress-fill"
              :class="getUtilizationClass(zone.utilization_percentage)"
              :style="{ width: `${zone.utilization_percentage}%` }"
            ></div>
          </div>
        </div>

        <div class="zone-actions">
          <button
            @click="editZone(zone)"
            class="btn btn-sm btn-outline"
          >
            Edit
          </button>
          <button
            @click="deleteZone(zone.id)"
            class="btn btn-sm btn-danger"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Create Zone Modal -->
    <div v-if="showCreateZoneModal" class="modal-overlay" @click="showCreateZoneModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3 class="text-lg font-semibold">Create New Zone</h3>
          <button @click="showCreateZoneModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <form @submit.prevent="createZone" class="modal-body">
          <div class="form-group">
            <label class="form-label">Zone Name</label>
            <input v-model="newZone.name" type="text" class="form-input" required />
          </div>
          <div class="form-group">
            <label class="form-label">Type</label>
            <select v-model="newZone.type" class="form-input" required>
              <option value="receiving">Receiving</option>
              <option value="storage">Storage</option>
              <option value="picking">Picking</option>
              <option value="packing">Packing</option>
              <option value="shipping">Shipping</option>
              <option value="quarantine">Quarantine</option>
              <option value="returns">Returns</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Capacity</label>
            <input v-model.number="newZone.capacity" type="number" class="form-input" required min="1" />
          </div>
          <div class="form-group">
            <label class="form-label">Branch ID (optional)</label>
            <input v-model="newZone.branch_id" type="text" class="form-input" />
          </div>
          <div class="modal-footer">
            <button type="button" @click="showCreateZoneModal = false" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="creating">
              {{ creating ? 'Creating...' : 'Create Zone' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Zone Modal -->
    <div v-if="showEditZoneModal" class="modal-overlay" @click="showEditZoneModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3 class="text-lg font-semibold">Edit Zone</h3>
          <button @click="showEditZoneModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <form @submit.prevent="updateZone" class="modal-body">
          <div class="form-group">
            <label class="form-label">Zone Name</label>
            <input v-model="editingZone.name" type="text" class="form-input" required />
          </div>
          <div class="form-group">
            <label class="form-label">Type</label>
            <select v-model="editingZone.type" class="form-input" required>
              <option value="receiving">Receiving</option>
              <option value="storage">Storage</option>
              <option value="picking">Picking</option>
              <option value="packing">Packing</option>
              <option value="shipping">Shipping</option>
              <option value="quarantine">Quarantine</option>
              <option value="returns">Returns</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Capacity</label>
            <input v-model.number="editingZone.capacity" type="number" class="form-input" required min="1" />
          </div>
          <div class="modal-footer">
            <button type="button" @click="showEditZoneModal = false" class="btn btn-secondary">
              Cancel
            </button>
            <button type="submit" class="btn btn-primary" :disabled="updating">
              {{ updating ? 'Updating...' : 'Update Zone' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useWarehouseApi } from '@/composables/useWarehouseApi';

interface Props {
  warehouseId: string;
}

const props = defineProps<Props>();

const warehouseApi = useWarehouseApi();

const zones = ref([]);
const loading = ref(false);
const showCreateZoneModal = ref(false);
const showEditZoneModal = ref(false);
const creating = ref(false);
const updating = ref(false);

const newZone = ref({
  name: '',
  type: 'storage',
  capacity: 1000,
  branch_id: '',
});

const editingZone = ref({
  id: '',
  name: '',
  type: 'storage',
  capacity: 1000,
});

const loadZones = async () => {
  loading.value = true;
  try {
    zones.value = await warehouseApi.getZonesByWarehouse(props.warehouseId);
  } catch (error) {
    console.error('Failed to load zones:', error);
  } finally {
    loading.value = false;
  }
};

const createZone = async () => {
  creating.value = true;
  try {
    await warehouseApi.createZone({
      warehouse_id: props.warehouseId,
      ...newZone.value,
    });
    showCreateZoneModal.value = false;
    newZone.value = {
      name: '',
      type: 'storage',
      capacity: 1000,
      branch_id: '',
    };
    await loadZones();
  } catch (error) {
    console.error('Failed to create zone:', error);
  } finally {
    creating.value = false;
  }
};

const editZone = (zone: any) => {
  editingZone.value = {
    id: zone.id,
    name: zone.name,
    type: zone.type,
    capacity: zone.capacity,
  };
  showEditZoneModal.value = true;
};

const updateZone = async () => {
  updating.value = true;
  try {
    // Note: API endpoint for updating zone details would need to be implemented
    showEditZoneModal.value = false;
    await loadZones();
  } catch (error) {
    console.error('Failed to update zone:', error);
  } finally {
    updating.value = false;
  }
};

const deleteZone = async (zoneId: string) => {
  if (!confirm('Are you sure you want to delete this zone?')) return;
  
  try {
    await warehouseApi.deleteZone(zoneId);
    await loadZones();
  } catch (error) {
    console.error('Failed to delete zone:', error);
  }
};

const formatZoneType = (type: string) => {
  const types: Record<string, string> = {
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

const getZoneTypeClass = (type: string) => {
  const classes: Record<string, string> = {
    receiving: 'bg-blue-100 text-blue-800',
    storage: 'bg-green-100 text-green-800',
    picking: 'bg-yellow-100 text-yellow-800',
    packing: 'bg-purple-100 text-purple-800',
    shipping: 'bg-indigo-100 text-indigo-800',
    quarantine: 'bg-red-100 text-red-800',
    returns: 'bg-gray-100 text-gray-800',
  };
  return classes[type] || 'bg-gray-100 text-gray-800';
};

const getUtilizationClass = (value: number) => {
  if (value > 80) return 'text-red-600';
  if (value > 50) return 'text-yellow-600';
  return 'text-green-600';
};

onMounted(() => {
  loadZones();
});
</script>

<style scoped>
.zone-management {
  padding: 20px;
}

.zone-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.zone-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.zone-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
}

.zone-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.zone-name {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}

.zone-type-badge {
  padding: 4px 12px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 500;
}

.zone-metrics {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
  margin-bottom: 16px;
}

.metric {
  text-align: center;
}

.metric-label {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 4px;
}

.metric-value {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
}

.zone-progress {
  margin-bottom: 16px;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background-color: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.3s ease;
}

.progress-fill.text-green-600 {
  background-color: #10b981;
}

.progress-fill.text-yellow-600 {
  background-color: #f59e0b;
}

.progress-fill.text-red-600 {
  background-color: #ef4444;
}

.zone-actions {
  display: flex;
  gap: 8px;
}

.btn {
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
  border: none;
}

.btn-sm {
  padding: 4px 8px;
  font-size: 12px;
}

.btn-outline {
  background-color: transparent;
  border: 1px solid #d1d5db;
  color: #374151;
}

.btn-outline:hover {
  background-color: #f3f4f6;
}

.btn-danger {
  background-color: #ef4444;
  color: white;
}

.btn-danger:hover {
  background-color: #dc2626;
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
</style>
