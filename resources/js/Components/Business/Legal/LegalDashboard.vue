<template>
  <div class="legal-dashboard">
    <div class="header">
      <h1>Legal Vertical Dashboard</h1>
      <button @click="refresh" class="btn-primary">Refresh</button>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <h3>Active Cases</h3>
        <p class="stat-value">{{ stats.activeCases }}</p>
      </div>
      <div class="stat-card">
        <h3>Pending Documents</h3>
        <p class="stat-value">{{ stats.pendingDocuments }}</p>
      </div>
      <div class="stat-card">
        <h3>Billable Hours</h3>
        <p class="stat-value">{{ stats.billableHours }}h</p>
      </div>
      <div class="stat-card">
        <h3>Revenue This Month</h3>
        <p class="stat-value">{{ formatCurrency(stats.revenueMonth) }}</p>
      </div>
    </div>

    <div class="content-grid">
      <div class="card">
        <h2>Recent Cases</h2>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Case ID</th>
                <th>Client</th>
                <th>Type</th>
                <th>Status</th>
                <th>Created</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="caseItem in recentCases" :key="caseItem.id">
                <td>{{ caseItem.id }}</td>
                <td>{{ caseItem.client }}</td>
                <td>{{ caseItem.type }}</td>
                <td>
                  <span :class="['status-badge', caseItem.status]">{{ caseItem.status }}</span>
                </td>
                <td>{{ formatDate(caseItem.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <h2>Case Types</h2>
        <div class="case-list">
          <div v-for="caseType in caseTypes" :key="caseType.type" class="case-item">
            <div class="case-info">
              <h4>{{ caseType.type }}</h4>
              <p>{{ caseType.count }} cases</p>
            </div>
            <div class="case-hours">
              {{ caseType.hours }}h
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
  activeCases: number
  pendingDocuments: number
  billableHours: number
  revenueMonth: number
}

interface CaseItem {
  id: number
  client: string
  type: string
  status: string
  created_at: string
}

interface CaseType {
  type: string
  count: number
  hours: number
}

const stats = ref<Stats>({
  activeCases: 0,
  pendingDocuments: 0,
  billableHours: 0,
  revenueMonth: 0
})

const recentCases = ref<CaseItem[]>([])
const caseTypes = ref<CaseType[]>([])

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
    const response = await fetch('/api/legal/dashboard')
    const data = await response.json()
    stats.value = data.stats
    recentCases.value = data.recentCases
    caseTypes.value = data.caseTypes
  } catch (error) {
    console.error('Failed to fetch legal dashboard data:', error)
  }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.legal-dashboard {
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

.status-badge.open {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.in_progress {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.closed {
  background: #d1fae5;
  color: #065f46;
}

.case-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.case-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
}

.case-info h4 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.case-info p {
  margin: 0;
  font-size: 12px;
  color: #6b7280;
}

.case-hours {
  font-size: 14px;
  font-weight: 600;
  color: #059669;
}
</style>
