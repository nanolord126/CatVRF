<template>
  <div class="luxury-orders">
    <div class="header">
      <h2>Client Orders</h2>
      <div class="filters">
        <select v-model="statusFilter" @change="filterOrders">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
        </select>
      </div>
    </div>

    <div class="orders-table">
      <table>
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Client</th>
            <th>Experience</th>
            <th>Date</th>
            <th>Total</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="order in filteredOrders" :key="order.id">
            <td>#{{ order.id }}</td>
            <td>{{ order.client }}</td>
            <td>{{ order.experience }}</td>
            <td>{{ formatDate(order.date) }}</td>
            <td>{{ formatCurrency(order.total) }}</td>
            <td>
              <span :class="['status-badge', order.status]">{{ order.status }}</span>
            </td>
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
  client: string
  experience: string
  date: string
  total: number
  status: string
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

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
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
    const response = await fetch('/api/luxury/orders')
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
.luxury-orders {
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

.status-badge.confirmed {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.in_progress {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.completed {
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
