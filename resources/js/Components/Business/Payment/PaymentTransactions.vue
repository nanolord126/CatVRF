<template>
  <div class="payment-transactions">
    <div class="header">
      <h2>Transactions</h2>
      <button @click="addTransaction" class="btn-primary">Add Transaction</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="completed">Completed</option>
        <option value="failed">Failed</option>
        <option value="refunded">Refunded</option>
      </select>
      <select v-model="methodFilter">
        <option value="">All Methods</option>
        <option value="card">Card</option>
        <option value="bank_transfer">Bank Transfer</option>
        <option value="crypto">Crypto</option>
        <option value="wallet">Wallet</option>
      </select>
    </div>

    <div class="transactions-table">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Amount</th>
            <th>Method</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="transaction in filteredTransactions" :key="transaction.id">
            <td>#{{ transaction.id }}</td>
            <td>{{ transaction.customer }}</td>
            <td>{{ formatCurrency(transaction.amount) }}</td>
            <td>{{ transaction.method }}</td>
            <td>{{ formatDate(transaction.date) }}</td>
            <td>
              <span :class="['status-badge', transaction.status]">{{ transaction.status }}</span>
            </td>
            <td>
              <button @click="viewTransaction(transaction)" class="btn-sm">View</button>
              <button @click="refundTransaction(transaction)" class="btn-sm btn-danger">Refund</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Transaction {
  id: number
  customer: string
  amount: number
  method: string
  date: string
  status: string
}

const transactions = ref<Transaction[]>([])
const statusFilter = ref('')
const methodFilter = ref('')

const filteredTransactions = computed(() => {
  return transactions.value.filter(transaction => {
    if (statusFilter.value && transaction.status !== statusFilter.value) return false
    if (methodFilter.value && transaction.method !== methodFilter.value) return false
    return true
  })
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const addTransaction = () => {
  // Open modal to add new transaction
}

const viewTransaction = (transaction: Transaction) => {
  // Open transaction details
}

const refundTransaction = (transaction: Transaction) => {
  // Process refund
}

const fetchTransactions = async () => {
  try {
    const response = await fetch('/api/payment/transactions')
    const data = await response.json()
    transactions.value = data
  } catch (error) {
    console.error('Failed to fetch transactions:', error)
  }
}

onMounted(() => {
  fetchTransactions()
})
</script>

<style scoped>
.payment-transactions {
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

.transactions-table {
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

.status-badge.completed {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.failed {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.refunded {
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

.btn-danger {
  background: #fee2e2;
  color: #991b1b;
  border-color: #fca5a5;
}
</style>
