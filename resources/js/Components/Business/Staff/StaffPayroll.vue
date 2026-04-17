<template>
  <div class="staff-payroll">
    <div class="header">
      <h2>Payroll</h2>
      <button @click="addPayroll" class="btn-primary">Add Payroll</button>
    </div>

    <div class="filters">
      <select v-model="periodFilter">
        <option value="">All Periods</option>
        <option value="monthly">Monthly</option>
        <option value="bi_weekly">Bi-Weekly</option>
        <option value="weekly">Weekly</option>
      </select>
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="draft">Draft</option>
        <option value="processed">Processed</option>
        <option value="paid">Paid</option>
      </select>
    </div>

    <div class="payroll-table">
      <table>
        <thead>
          <tr>
            <th>Payroll ID</th>
            <th>Employee</th>
            <th>Period</th>
            <th>Gross Pay</th>
            <th>Deductions</th>
            <th>Net Pay</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="payroll in filteredPayroll" :key="payroll.id">
            <td>#{{ payroll.id }}</td>
            <td>{{ payroll.employee }}</td>
            <td>{{ payroll.period }}</td>
            <td>{{ formatCurrency(payroll.gross_pay) }}</td>
            <td>{{ formatCurrency(payroll.deductions) }}</td>
            <td>{{ formatCurrency(payroll.net_pay) }}</td>
            <td>
              <span :class="['status-badge', payroll.status]">{{ payroll.status }}</span>
            </td>
            <td>
              <button @click="viewPayroll(payroll)" class="btn-sm">View</button>
              <button @click="editPayroll(payroll)" class="btn-sm">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Payroll {
  id: number
  employee: string
  period: string
  gross_pay: number
  deductions: number
  net_pay: number
  status: string
}

const payroll = ref<Payroll[]>([])
const periodFilter = ref('')
const statusFilter = ref('')

const filteredPayroll = computed(() => {
  return payroll.value.filter(item => {
    if (statusFilter.value && item.status !== statusFilter.value) return false
    return true
  })
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const addPayroll = () => {
  // Open modal to add new payroll
}

const viewPayroll = (item: Payroll) => {
  // Open payroll details
}

const editPayroll = (item: Payroll) => {
  // Open edit modal
}

const fetchPayroll = async () => {
  try {
    const response = await fetch('/api/staff/payroll')
    const data = await response.json()
    payroll.value = data
  } catch (error) {
    console.error('Failed to fetch payroll:', error)
  }
}

onMounted(() => {
  fetchPayroll()
})
</script>

<style scoped>
.staff-payroll {
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

.payroll-table {
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

.status-badge.processed {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.paid {
  background: #d1fae5;
  color: #065f46;
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
