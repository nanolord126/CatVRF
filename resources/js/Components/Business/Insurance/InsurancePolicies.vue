<template>
  <div class="insurance-policies">
    <div class="header">
      <h2>Insurance Policies</h2>
      <button @click="addPolicy" class="btn-primary">Add Policy</button>
    </div>

    <div class="filters">
      <select v-model="typeFilter">
        <option value="">All Types</option>
        <option value="health">Health</option>
        <option value="auto">Auto</option>
        <option value="home">Home</option>
        <option value="life">Life</option>
      </select>
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="pending">Pending</option>
        <option value="expired">Expired</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <div class="policies-table">
      <table>
        <thead>
          <tr>
            <th>Policy ID</th>
            <th>Holder</th>
            <th>Type</th>
            <th>Premium</th>
            <th>Coverage</th>
            <th>Status</th>
            <th>Expiry</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="policy in filteredPolicies" :key="policy.id">
            <td>#{{ policy.id }}</td>
            <td>{{ policy.holder }}</td>
            <td>{{ policy.type }}</td>
            <td>{{ formatCurrency(policy.premium) }}</td>
            <td>{{ formatCurrency(policy.coverage) }}</td>
            <td>
              <span :class="['status-badge', policy.status]">{{ policy.status }}</span>
            </td>
            <td>{{ formatDate(policy.expiry_date) }}</td>
            <td>
              <button @click="viewPolicy(policy)" class="btn-sm">View</button>
              <button @click="editPolicy(policy)" class="btn-sm">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Policy {
  id: number
  holder: string
  type: string
  premium: number
  coverage: number
  status: string
  expiry_date: string
}

const policies = ref<Policy[]>([])
const typeFilter = ref('')
const statusFilter = ref('')

const filteredPolicies = computed(() => {
  return policies.value.filter(policy => {
    if (typeFilter.value && policy.type !== typeFilter.value) return false
    if (statusFilter.value && policy.status !== statusFilter.value) return false
    return true
  })
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

const addPolicy = () => {
  // Open modal to add new policy
}

const viewPolicy = (policy: Policy) => {
  // Open policy details
}

const editPolicy = (policy: Policy) => {
  // Open edit modal
}

const fetchPolicies = async () => {
  try {
    const response = await fetch('/api/insurance/policies')
    const data = await response.json()
    policies.value = data
  } catch (error) {
    console.error('Failed to fetch policies:', error)
  }
}

onMounted(() => {
  fetchPolicies()
})
</script>

<style scoped>
.insurance-policies {
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

.policies-table {
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

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.expired {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.cancelled {
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
