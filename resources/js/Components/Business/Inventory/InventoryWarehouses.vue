<template>
  <div class="inventory-warehouses">
    <div class="header">
      <h2>Warehouses</h2>
      <button @click="addWarehouse" class="btn-primary">Add Warehouse</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>

    <div class="warehouses-grid">
      <div v-for="warehouse in filteredWarehouses" :key="warehouse.id" class="warehouse-card">
        <div class="warehouse-header">
          <span class="name">{{ warehouse.name }}</span>
          <span :class="['status-badge', warehouse.status]">{{ warehouse.status }}</span>
        </div>
        <div class="warehouse-details">
          <p class="location">{{ warehouse.location }}</p>
          <p class="manager">Manager: {{ warehouse.manager }}</p>
          <div class="capacity">
            <div class="capacity-bar">
              <div class="capacity-fill" :style="{ width: warehouse.usage_percent + '%' }"></div>
            </div>
            <span class="capacity-text">{{ warehouse.usage_percent }}% used</span>
          </div>
          <div class="stats">
            <span>{{ warehouse.total_items }} items</span>
            <span>{{ warehouse.total_sku }} SKUs</span>
          </div>
        </div>
        <div class="warehouse-actions">
          <button @click="viewWarehouse(warehouse)" class="btn-sm">View</button>
          <button @click="editWarehouse(warehouse)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Warehouse {
  id: number
  name: string
  location: string
  manager: string
  usage_percent: number
  total_items: number
  total_sku: number
  status: string
}

const warehouses = ref<Warehouse[]>([])
const statusFilter = ref('')

const filteredWarehouses = computed(() => {
  if (!statusFilter.value) return warehouses.value
  return warehouses.value.filter(warehouse => warehouse.status === statusFilter.value)
})

const addWarehouse = () => {
  // Open modal to add new warehouse
}

const viewWarehouse = (warehouse: Warehouse) => {
  // Open warehouse details
}

const editWarehouse = (warehouse: Warehouse) => {
  // Open edit modal
}

const fetchWarehouses = async () => {
  try {
    const response = await fetch('/api/inventory/warehouses')
    const data = await response.json()
    warehouses.value = data
  } catch (error) {
    console.error('Failed to fetch warehouses:', error)
  }
}

onMounted(() => {
  fetchWarehouses()
})
</script>

<style scoped>
.inventory-warehouses {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.filters select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.warehouses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.warehouse-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.warehouse-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.name {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.inactive {
  background: #e5e7eb;
  color: #374151;
}

.warehouse-details {
  padding: 16px;
}

.location, .manager {
  margin: 0 0 12px 0;
  font-size: 12px;
  color: #6b7280;
}

.capacity {
  margin-bottom: 12px;
}

.capacity-bar {
  width: 100%;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 4px;
}

.capacity-fill {
  height: 100%;
  background: #3b82f6;
  border-radius: 4px;
}

.capacity-text {
  font-size: 12px;
  color: #6b7280;
}

.stats {
  display: flex;
  gap: 12px;
  font-size: 13px;
  color: #374151;
}

.warehouse-actions {
  padding: 12px 16px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 8px;
}

.btn-sm {
  flex: 1;
  padding: 8px;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}
</style>
