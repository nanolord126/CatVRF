<template>
  <div class="fashion-orders">
    <div class="header">
      <h2>Order Management</h2>
      <div class="filters">
        <select v-model="statusFilter" @change="filterOrders">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="processing">Processing</option>
          <option value="shipped">Shipped</option>
          <option value="delivered">Delivered</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
    </div>

    <div class="orders-table">
      <table>
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer</th>
            <th>Items</th>
            <th>Total</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in filteredOrders" :key="order.id">
            <td>#{{ order.id }}</td>
            <td>{{ order.customer }}</td>
            <td>{{ order.items_count }} items</td>
            <td>{{ formatCurrency(order.total) }}</td>
            <td>
              <span :class="['status-badge', order.status]">{{ order.status }}</span>
            </td>
            <td>{{ formatTime(order.created_at) }}</td>
            <td>
              <button @click="viewOrder(order)" class="btn-sm">View</button>
              <button @click="updateStatus(order)" class="btn-sm btn-primary">Update</button>
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
  items_count: number
  total: number
  status: string
  created_at: string
}

const orders = ref<Order[]>([])
const statusFilter = ref('')

const filteredOrders = computed(() => {
  if (!statusFilter.value) return orders.value
  return orders.value.filter(order => order.status === statusFilter.value)
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const formatTime = (date: string): string => {
  return new Date(date).toLocaleString('ru-RU')
}

const viewOrder = (order: Order) => {
  // Open order details modal
}

const updateStatus = (order: Order) => {
  // Open status update modal
}

const filterOrders = () => {
  // Filter is handled by computed property
}

const fetchOrders = async () => {
  try {
    const response = await fetch('/api/fashion/orders')
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
.fashion-orders {
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

.status-badge.processing {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.shipped {
  background: #e0e7ff;
  color: #4338ca;
}

.status-badge.delivered {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.cancelled {
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
