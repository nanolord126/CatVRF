<template>
  <div class="delivery-orders">
    <div class="header">
      <h2>Delivery Orders</h2>
      <button @click="addOrder" class="btn-primary">Add Order</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="picked_up">Picked Up</option>
        <option value="in_transit">In Transit</option>
        <option value="delivered">Delivered</option>
      </select>
      <select v-model="priorityFilter">
        <option value="">All Priorities</option>
        <option value="standard">Standard</option>
        <option value="express">Express</option>
        <option value="same_day">Same Day</option>
      </select>
    </div>

    <div class="orders-table">
      <table>
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Address</th>
            <th>Courier</th>
            <th>ETA</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in filteredOrders" :key="order.id">
            <td>#{{ order.id }}</td>
            <td>{{ order.customer }}</td>
            <td>{{ order.address }}</td>
            <td>{{ order.courier || 'Unassigned' }}</td>
            <td>{{ order.eta }}</td>
            <td>
              <span :class="['status-badge', order.status]">{{ order.status }}</span>
            </td>
            <td>
              <button @click="viewOrder(order)" class="btn-sm">View</button>
              <button @click="trackOrder(order)" class="btn-sm btn-primary">Track</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Order {
  id: number
  customer: string
  address: string
  courier: string
  eta: string
  status: string
  priority: string
}

const orders = ref<Order[]>([])
const statusFilter = ref('')
const priorityFilter = ref('')

const filteredOrders = computed(() => {
  return orders.value.filter(order => {
    if (statusFilter.value && order.status !== statusFilter.value) return false
    if (priorityFilter.value && order.priority !== priorityFilter.value) return false
    return true
  })
}

const addOrder = () => {
  // Open modal to add new order
}

const viewOrder = (order: Order) => {
  // Open order details
}

const trackOrder = (order: Order) => {
  // Open tracking modal
}

const fetchOrders = async () => {
  try {
    const response = await fetch('/api/delivery/orders')
    const data = await response.json()
    orders.value = data
  } catch (error) {
    console.error('Failed to fetch orders:', error)
  }
}

onMounted(() => {
  fetchOrders()
})
</script>

<style scoped>
.delivery-orders {
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

.orders-table {
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

.status-badge.picked_up {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.in_transit {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.delivered {
  background: #e5e7eb;
  color: #374151;
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
