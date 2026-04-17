<template>
  <div class="freelance-contracts">
    <div class="header">
      <h2>Contracts</h2>
      <button @click="addContract" class="btn-primary">Add Contract</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="draft">Draft</option>
        <option value="signed">Signed</option>
        <option value="active">Active</option>
        <option value="completed">Completed</option>
      </select>
    </div>

    <div class="contracts-table">
      <table>
        <thead>
          <tr>
            <th>Contract ID</th>
            <th>Project</th>
            <th>Freelancer</th>
            <th>Amount</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="contract in filteredContracts" :key="contract.id">
            <td>#{{ contract.id }}</td>
            <td>{{ contract.project }}</td>
            <td>{{ contract.freelancer }}</td>
            <td>{{ formatCurrency(contract.amount) }}</td>
            <td>{{ formatDate(contract.start_date) }}</td>
            <td>{{ formatDate(contract.end_date) }}</td>
            <td>
              <span :class="['status-badge', contract.status]">{{ contract.status }}</span>
            </td>
            <td>
              <button @click="viewContract(contract)" class="btn-sm">View</button>
              <button @click="editContract(contract)" class="btn-sm">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Contract {
  id: number
  project: string
  freelancer: string
  amount: number
  start_date: string
  end_date: string
  status: string
}

const contracts = ref<Contract[]>([])
const statusFilter = ref('')

const filteredContracts = computed(() => {
  if (!statusFilter.value) return contracts.value
  return contracts.value.filter(contract => contract.status === statusFilter.value)
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

const addContract = () => {
  // Open modal to add new contract
}

const viewContract = (contract: Contract) => {
  // Open contract details
}

const editContract = (contract: Contract) => {
  // Open edit modal
}

const fetchContracts = async () => {
  try {
    const response = await fetch('/api/freelance/contracts')
    const data = await response.json()
    contracts.value = data
  } catch (error) {
    console.error('Failed to fetch contracts:', error)
  }
}

onMounted(() => {
  fetchContracts()
})
</script>

<style scoped>
.freelance-contracts {
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

.contracts-table {
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

.status-badge.draft {
  background: #e5e7eb;
  color: #374151;
}

.status-badge.signed {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.active {
  background: #d1fae5;
  color: #065f46;
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
</style>
