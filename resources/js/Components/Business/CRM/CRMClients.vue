<template>
  <div class="crm-clients">
    <div class="header">
      <h2>Clients</h2>
      <button @click="addClient" class="btn-primary">Add Client</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
        <option value="lead">Lead</option>
      </select>
      <select v-model="segmentFilter">
        <option value="">All Segments</option>
        <option value="vip">VIP</option>
        <option value="regular">Regular</option>
        <option value="enterprise">Enterprise</option>
      </select>
    </div>

    <div class="clients-table">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Company</th>
            <th>Segment</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="client in filteredClients" :key="client.id">
            <td>#{{ client.id }}</td>
            <td>{{ client.name }}</td>
            <td>{{ client.email }}</td>
            <td>{{ client.company }}</td>
            <td>{{ client.segment }}</td>
            <td>
              <span :class="['status-badge', client.status]">{{ client.status }}</span>
            </td>
            <td>
              <button @click="viewClient(client)" class="btn-sm">View</button>
              <button @click="editClient(client)" class="btn-sm">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Client {
  id: number
  name: string
  email: string
  company: string
  segment: string
  status: string
}

const clients = ref<Client[]>([])
const statusFilter = ref('')
const segmentFilter = ref('')

const filteredClients = computed(() => {
  return clients.value.filter(client => {
    if (statusFilter.value && client.status !== statusFilter.value) return false
    if (segmentFilter.value && client.segment !== segmentFilter.value) return false
    return true
  })
})

const addClient = () => {
  // Open modal to add new client
}

const viewClient = (client: Client) => {
  // Open client details
}

const editClient = (client: Client) => {
  // Open edit modal
}

const fetchClients = async () => {
  try {
    const response = await fetch('/api/crm/clients')
    const data = await response.json()
    clients.value = data
  } catch (error) {
    console.error('Failed to fetch clients:', error)
  }
}

onMounted(() => {
  fetchClients()
})
</script>

<style scoped>
.crm-clients {
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

.clients-table {
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

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.inactive {
  background: #e5e7eb;
  color: #374151;
}

.status-badge.lead {
  background: #dbeafe;
  color: #1e40af;
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
