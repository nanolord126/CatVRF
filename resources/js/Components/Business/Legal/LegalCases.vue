<template>
  <div class="legal-cases">
    <div class="header">
      <h2>Legal Cases</h2>
      <button @click="addCase" class="btn-primary">Add Case</button>
    </div>

    <div class="filters">
      <select v-model="typeFilter">
        <option value="">All Types</option>
        <option value="civil">Civil</option>
        <option value="criminal">Criminal</option>
        <option value="commercial">Commercial</option>
        <option value="family">Family</option>
      </select>
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="open">Open</option>
        <option value="in_progress">In Progress</option>
        <option value="closed">Closed</option>
        <option value="appealed">Appealed</option>
      </select>
    </div>

    <div class="cases-table">
      <table>
        <thead>
          <tr>
            <th>Case ID</th>
            <th>Client</th>
            <th>Type</th>
            <th>Lawyer</th>
            <th>Date Opened</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="caseItem in filteredCases" :key="caseItem.id">
            <td>#{{ caseItem.id }}</td>
            <td>{{ caseItem.client }}</td>
            <td>{{ caseItem.type }}</td>
            <td>{{ caseItem.lawyer }}</td>
            <td>{{ formatDate(caseItem.opened_date) }}</td>
            <td>
              <span :class="['status-badge', caseItem.status]">{{ caseItem.status }}</span>
            </td>
            <td>
              <button @click="viewCase(caseItem)" class="btn-sm">View</button>
              <button @click="editCase(caseItem)" class="btn-sm">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Case {
  id: number
  client: string
  type: string
  lawyer: string
  opened_date: string
  status: string
}

const cases = ref<Case[]>([])
const typeFilter = ref('')
const statusFilter = ref('')

const filteredCases = computed(() => {
  return cases.value.filter(caseItem => {
    if (typeFilter.value && caseItem.type !== typeFilter.value) return false
    if (statusFilter.value && caseItem.status !== statusFilter.value) return false
    return true
  })
})

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const addCase = () => {
  // Open modal to add new case
}

const viewCase = (caseItem: Case) => {
  // Open case details
}

const editCase = (caseItem: Case) => {
  // Open edit modal
}

const fetchCases = async () => {
  try {
    const response = await fetch('/api/legal/cases')
    const data = await response.json()
    cases.value = data
  } catch (error) {
    console.error('Failed to fetch cases:', error)
  }
}

onMounted(() => {
  fetchCases()
})
</script>

<style scoped>
.legal-cases {
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

.cases-table {
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

.status-badge.appealed {
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
