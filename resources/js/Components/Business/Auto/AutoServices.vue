<template>
  <div class="auto-services">
    <div class="header">
      <h2>Service Management</h2>
      <button @click="addService" class="btn-primary">Add Service</button>
    </div>

    <div class="services-table">
      <table>
        <thead>
          <tr>
            <th>Service ID</th>
            <th>Car</th>
            <th>Type</th>
            <th>Status</th>
            <th>Date</th>
            <th>Cost</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="service in services" :key="service.id">
            <td>#{{ service.id }}</td>
            <td>{{ service.car }}</td>
            <td>{{ service.type }}</td>
            <td>
              <span :class="['status-badge', service.status]">{{ service.status }}</span>
            </td>
            <td>{{ formatDate(service.date) }}</td>
            <td>{{ formatCurrency(service.cost) }}</td>
            <td>
              <button @click="viewService(service)" class="btn-sm">View</button>
              <button @click="editService(service)" class="btn-sm">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Service {
  id: number
  car: string
  type: string
  status: string
  date: string
  cost: number
}

const services = ref<Service[]>([])

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const addService = () => {
  // Open modal to add new service
}

const viewService = (service: Service) => {
  // Open service details
}

const editService = (service: Service) => {
  // Open edit modal
}

const fetchServices = async () => {
  try {
    const response = await fetch('/api/auto/services')
    const data = await response.json()
    services.value = data
  } catch (error) {
    console.error('Failed to fetch services:', error)
  }
}

onMounted(() => {
  fetchServices()
})
</script>

<style scoped>
.auto-services {
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

.services-table {
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

.status-badge.in_progress {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.completed {
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
</style>
