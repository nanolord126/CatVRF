<template>
  <div class="logistics-shipments">
    <div class="header">
      <h2>Shipments</h2>
      <button @click="addShipment" class="btn-primary">Add Shipment</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="in_transit">In Transit</option>
        <option value="delivered">Delivered</option>
        <option value="returned">Returned</option>
      </select>
      <select v-model="priorityFilter">
        <option value="">All Priorities</option>
        <option value="standard">Standard</option>
        <option value="express">Express</option>
        <option value="urgent">Urgent</option>
      </select>
    </div>

    <div class="shipments-table">
      <table>
        <thead>
          <tr>
            <th>Shipment ID</th>
            <th>Customer</th>
            <th>Destination</th>
            <th>Weight</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="shipment in filteredShipments" :key="shipment.id">
            <td>#{{ shipment.id }}</td>
            <td>{{ shipment.customer }}</td>
            <td>{{ shipment.destination }}</td>
            <td>{{ shipment.weight }} kg</td>
            <td>{{ formatDate(shipment.date) }}</td>
            <td>
              <span :class="['status-badge', shipment.status]">{{ shipment.status }}</span>
            </td>
            <td>
              <button @click="viewShipment(shipment)" class="btn-sm">View</button>
              <button @click="trackShipment(shipment)" class="btn-sm btn-primary">Track</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Shipment {
  id: number
  customer: string
  destination: string
  weight: number
  date: string
  status: string
  priority: string
}

const shipments = ref<Shipment[]>([])
const statusFilter = ref('')
const priorityFilter = ref('')

const filteredShipments = computed(() => {
  return shipments.value.filter(shipment => {
    if (statusFilter.value && shipment.status !== statusFilter.value) return false
    if (priorityFilter.value && shipment.priority !== priorityFilter.value) return false
    return true
  })
})

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const addShipment = () => {
  // Open modal to add new shipment
}

const viewShipment = (shipment: Shipment) => {
  // Open shipment details
}

const trackShipment = (shipment: Shipment) => {
  // Open tracking modal
}

const fetchShipments = async () => {
  try {
    const response = await fetch('/api/logistics/shipments')
    const data = await response.json()
    shipments.value = data
  } catch (error) {
    console.error('Failed to fetch shipments:', error)
  }
}

onMounted(() => {
  fetchShipments()
})
</script>

<style scoped>
.logistics-shipments {
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

.shipments-table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.in_transit {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.delivered {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.returned {
  background: #fee2e2;
  color: #991b1b;
}

.btn-sm {
  padding: 6px 12px;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
  margin-right: 4px;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}
</style>
