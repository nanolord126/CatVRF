<template>
  <div class="payment-invoices">
    <div class="header">
      <h2>Invoices</h2>
      <button @click="addInvoice" class="btn-primary">Add Invoice</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="draft">Draft</option>
        <option value="sent">Sent</option>
        <option value="paid">Paid</option>
        <option value="overdue">Overdue</option>
      </select>
      <select v-model="typeFilter">
        <option value="">All Types</option>
        <option value="subscription">Subscription</option>
        <option value="one_time">One Time</option>
        <option value="recurring">Recurring</option>
      </select>
    </div>

    <div class="invoices-table">
      <table>
        <thead>
          <tr>
            <th>Invoice ID</th>
            <th>Customer</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Due Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="invoice in filteredInvoices" :key="invoice.id">
            <td>#{{ invoice.id }}</td>
            <td>{{ invoice.customer }}</td>
            <td>{{ invoice.type }}</td>
            <td>{{ formatCurrency(invoice.amount) }}</td>
            <td>{{ formatDate(invoice.due_date) }}</td>
            <td>
              <span :class="['status-badge', invoice.status]">{{ invoice.status }}</span>
            </td>
            <td>
              <button @click="viewInvoice(invoice)" class="btn-sm">View</button>
              <button @click="sendInvoice(invoice)" class="btn-sm btn-primary">Send</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Invoice {
  id: number
  customer: string
  type: string
  amount: number
  due_date: string
  status: string
}

const invoices = ref<Invoice[]>([])
const statusFilter = ref('')
const typeFilter = ref('')

const filteredInvoices = computed(() => {
  return invoices.value.filter(invoice => {
    if (statusFilter.value && invoice.status !== statusFilter.value) return false
    if (typeFilter.value && invoice.type !== typeFilter.value) return false
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

const addInvoice = () => {
  // Open modal to add new invoice
}

const viewInvoice = (invoice: Invoice) => {
  // Open invoice details
}

const sendInvoice = (invoice: Invoice) => {
  // Send invoice to customer
}

const fetchInvoices = async () => {
  try {
    const response = await fetch('/api/payment/invoices')
    const data = await response.json()
    invoices.value = data
  } catch (error) {
    console.error('Failed to fetch invoices:', error)
  }
}

onMounted(() => {
  fetchInvoices()
})
</script>

<style scoped>
.payment-invoices {
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

.invoices-table {
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

.status-badge.sent {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.paid {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.overdue {
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
