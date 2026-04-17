<template>
  <div class="insurance-claims">
    <div class="header">
      <h2>Claims Management</h2>
      <div class="filters">
        <select v-model="statusFilter" @change="filterClaims">
          <option value="">All Status</option>
          <option value="submitted">Submitted</option>
          <option value="under_review">Under Review</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
    </div>

    <div class="claims-table">
      <table>
        <thead>
          <tr>
            <th>Claim ID</th>
            <th>Policy</th>
            <th>Claimant</th>
            <th>Amount</th>
            <th>Date Filed</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="claim in filteredClaims" :key="claim.id">
            <td>#{{ claim.id }}</td>
            <td>{{ claim.policy }}</td>
            <td>{{ claim.claimant }}</td>
            <td>{{ formatCurrency(claim.amount) }}</td>
            <td>{{ formatDate(claim.filed_date) }}</td>
            <td>
              <span :class="['status-badge', claim.status]">{{ claim.status }}</span>
            </td>
            <td>
              <button @click="viewClaim(claim)" class="btn-sm">View</button>
              <button @click="updateStatus(claim)" class="btn-sm btn-primary">Update</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Claim {
  id: number
  policy: string
  claimant: string
  amount: number
  filed_date: string
  status: string
}

const claims = ref<Claim[]>([])
const statusFilter = ref('')

const filteredClaims = computed(() => {
  if (!statusFilter.value) return claims.value
  return claims.value.filter(claim => claim.status === statusFilter.value)
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

const viewClaim = (claim: Claim) => {
  // Open claim details modal
}

const updateStatus = (claim: Claim) => {
  // Open status update modal
}

const filterClaims = () => {
  // Filter is handled by computed property
}

const fetchClaims = async () => {
  try {
    const response = await fetch('/api/insurance/claims')
    const data = await response.json()
    claims.value = data
  } catch (error) {
    console.error('Failed to fetch claims:', error)
  }
}

onMounted(() => {
  fetchClaims()
})
</script>

<style scoped>
.insurance-claims {
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

.claims-table {
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

.status-badge.submitted {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.under_review {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.approved {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.rejected {
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
