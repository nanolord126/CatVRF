<template>
  <div class="insurance-dashboard">
    <div class="header">
      <h1>Insurance Vertical Dashboard</h1>
      <button @click="refresh" class="btn-primary">Refresh</button>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <h3>Active Policies</h3>
        <p class="stat-value">{{ stats.activePolicies }}</p>
      </div>
      <div class="stat-card">
        <h3>Pending Claims</h3>
        <p class="stat-value">{{ stats.pendingClaims }}</p>
      </div>
      <div class="stat-card">
        <h3>Revenue This Month</h3>
        <p class="stat-value">{{ formatCurrency(stats.revenueMonth) }}</p>
      </div>
      <div class="stat-card">
        <h3>Claims Processed</h3>
        <p class="stat-value">{{ stats.claimsProcessed }}</p>
      </div>
    </div>

    <div class="content-grid">
      <div class="card">
        <h2>Recent Claims</h2>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Claim ID</th>
                <th>Policy</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="claim in recentClaims" :key="claim.id">
                <td>{{ claim.id }}</td>
                <td>{{ claim.policy }}</td>
                <td>{{ formatCurrency(claim.amount) }}</td>
                <td>
                  <span :class="['status-badge', claim.status]">{{ claim.status }}</span>
                </td>
                <td>{{ formatDate(claim.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <h2>Policy Types</h2>
        <div class="policy-list">
          <div v-for="policy in policyTypes" :key="policy.type" class="policy-item">
            <div class="policy-info">
              <h4>{{ policy.type }}</h4>
              <p>{{ policy.count }} active</p>
            </div>
            <div class="policy-revenue">
              {{ formatCurrency(policy.revenue) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Stats {
  activePolicies: number
  pendingClaims: number
  revenueMonth: number
  claimsProcessed: number
}

interface Claim {
  id: number
  policy: string
  amount: number
  status: string
  created_at: string
}

interface PolicyType {
  type: string
  count: number
  revenue: number
}

const stats = ref<Stats>({
  activePolicies: 0,
  pendingClaims: 0,
  revenueMonth: 0,
  claimsProcessed: 0
})

const recentClaims = ref<Claim[]>([])
const policyTypes = ref<PolicyType[]>([])

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const refresh = async () => {
  await fetchData()
}

const fetchData = async () => {
  try {
    const response = await fetch('/api/insurance/dashboard')
    const data = await response.json()
    stats.value = data.stats
    recentClaims.value = data.recentClaims
    policyTypes.value = data.policyTypes
  } catch (error) {
    console.error('Failed to fetch insurance dashboard data:', error)
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.insurance-dashboard {
  padding: 24px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header h1 {
  margin: 0;
  font-size: 24px;
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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-card h3 {
  margin: 0 0 8px 0;
  font-size: 14px;
  color: #6b7280;
  font-weight: 500;
}

.stat-value {
  margin: 0;
  font-size: 28px;
  font-weight: 600;
  color: #1f2937;
}

.content-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
}

.card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card h2 {
  margin: 0 0 16px 0;
  font-size: 18px;
  font-weight: 600;
}

.table-container {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

th {
  font-weight: 600;
  color: #6b7280;
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

.status-badge.approved {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.rejected {
  background: #fee2e2;
  color: #991b1b;
}

.policy-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.policy-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
}

.policy-info h4 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.policy-info p {
  margin: 0;
  font-size: 12px;
  color: #6b7280;
}

.policy-revenue {
  font-size: 14px;
  font-weight: 600;
  color: #059669;
}
</style>
