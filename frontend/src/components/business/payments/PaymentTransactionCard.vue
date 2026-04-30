<template>
  <div class="payment-transaction-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">Payment #{{ transaction.id }}</h3>
          <p class="text-sm text-gray-500">{{ transaction.date }}</p>
        </div>
        <span :class="getStatusClass(transaction.status)" class="px-3 py-1 rounded-full text-xs font-bold">
          {{ transaction.status }}
        </span>
      </div>
    </div>

    <!-- Amount -->
    <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg p-4 mb-4 text-white">
      <p class="text-sm opacity-90 mb-1">Amount</p>
      <p class="text-3xl font-bold">{{ formatPrice(transaction.amount) }}</p>
    </div>

    <!-- Transaction Details -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Method:</span>
          <span class="font-medium">{{ transaction.method }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Currency:</span>
          <span class="font-medium">{{ transaction.currency }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Merchant:</span>
          <span class="font-medium">{{ transaction.merchant }}</span>
        </div>
      </div>
    </div>

    <!-- Fees -->
    <div class="mb-4">
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Processing Fee:</span>
        <span class="font-medium">{{ formatPrice(transaction.fee) }}</span>
      </div>
      <div class="flex justify-between text-sm font-bold text-gray-900 pt-2 border-t border-gray-200">
        <span>Net Amount:</span>
        <span>{{ formatPrice(transaction.netAmount) }}</span>
      </div>
    </div>

    <!-- Risk Score -->
    <div v-if="transaction.riskScore !== undefined" class="mb-4">
      <div class="flex justify-between text-sm mb-1">
        <span class="text-gray-600">Risk Score:</span>
        <span :class="getRiskClass(transaction.riskScore)" class="font-medium">
          {{ transaction.riskScore }}/100
        </span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div 
          :class="getRiskBarClass(transaction.riskScore)"
          class="h-2 rounded-full" 
          :style="{ width: transaction.riskScore + '%' }"
        ></div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="viewDetails"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Details
      </button>
      <button 
        v-if="transaction.status === 'Pending'"
        @click="capture"
        class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Capture
      </button>
      <button 
        v-else-if="transaction.status === 'Completed'"
        @click="refund"
        class="flex-1 bg-red-100 hover:bg-red-200 text-red-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Refund
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface PaymentTransaction {
  id: string
  date: string
  status: 'Pending' | 'Completed' | 'Failed' | 'Refunded'
  amount: number
  fee: number
  netAmount: number
  currency: string
  method: string
  merchant: string
  riskScore?: number
}

const props = defineProps<{
  transaction: PaymentTransaction
}>()

const emit = defineEmits<{
  viewDetails: [transaction: PaymentTransaction]
  capture: [transaction: PaymentTransaction]
  refund: [transaction: PaymentTransaction]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: props.transaction.currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price)
}

const getStatusClass = (status: string) => {
  const classes = {
    'Pending': 'bg-yellow-100 text-yellow-900',
    'Completed': 'bg-green-100 text-green-900',
    'Failed': 'bg-red-100 text-red-900',
    'Refunded': 'bg-gray-100 text-gray-900',
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const getRiskClass = (score: number) => {
  if (score < 30) return 'text-green-600'
  if (score < 70) return 'text-yellow-600'
  return 'text-red-600'
}

const getRiskBarClass = (score: number) => {
  if (score < 30) return 'bg-green-600'
  if (score < 70) return 'bg-yellow-600'
  return 'bg-red-600'
}

const viewDetails = () => {
  emit('viewDetails', props.transaction)
}

const capture = () => {
  emit('capture', props.transaction)
}

const refund = () => {
  emit('refund', props.transaction)
}
</script>
