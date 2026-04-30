<template>
  <div class="commission-payout-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ payout.period }}</h3>
          <p class="text-sm text-gray-500">{{ payout.payoutId }}</p>
        </div>
        <span :class="getStatusClass(payout.status)" class="px-3 py-1 rounded-full text-xs font-bold">
          {{ payout.status }}
        </span>
      </div>
    </div>

    <!-- Amount -->
    <div class="bg-gradient-to-r from-emerald-500 to-teal-500 rounded-lg p-4 mb-4 text-white">
      <p class="text-sm opacity-90 mb-1">Total Commission</p>
      <p class="text-3xl font-bold">{{ formatPrice(payout.amount) }}</p>
    </div>

    <!-- Breakdown -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Base Commission:</span>
          <span class="font-medium">{{ formatPrice(payout.baseCommission) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Bonus:</span>
          <span class="font-medium text-green-600">+{{ formatPrice(payout.bonus) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Adjustments:</span>
          <span :class="payout.adjustments >= 0 ? 'text-green-600' : 'text-red-600'" class="font-medium">
            {{ payout.adjustments >= 0 ? '+' : '' }}{{ formatPrice(payout.adjustments) }}
          </span>
        </div>
        <div class="flex justify-between font-bold text-gray-900 pt-2 border-t border-gray-200">
          <span>Net Amount:</span>
          <span>{{ formatPrice(payout.netAmount) }}</span>
        </div>
      </div>
    </div>

    <!-- Transactions -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Transactions: {{ payout.transactionCount }}</p>
      <div class="flex items-center gap-2 text-sm text-gray-600">
        <span>📊 {{ payout.totalSales }} total sales</span>
      </div>
    </div>

    <!-- Date Info -->
    <div class="mb-4">
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Period Start:</span>
        <span class="font-medium">{{ payout.periodStart }}</span>
      </div>
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Payout Date:</span>
        <span class="font-medium">{{ payout.payoutDate }}</span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="viewDetails"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        View Details
      </button>
      <button 
        v-if="payout.status === 'Ready'"
        @click="requestPayout"
        class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Request Payout
      </button>
      <button 
        v-else
        @click="downloadStatement"
        class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Download Statement
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface CommissionPayout {
  id: string
  payoutId: string
  period: string
  status: 'Pending' | 'Ready' | 'Paid'
  amount: number
  baseCommission: number
  bonus: number
  adjustments: number
  netAmount: number
  transactionCount: number
  totalSales: string
  periodStart: string
  payoutDate: string
}

const props = defineProps<{
  payout: CommissionPayout
}>()

const emit = defineEmits<{
  viewDetails: [payout: CommissionPayout]
  requestPayout: [payout: CommissionPayout]
  downloadStatement: [payout: CommissionPayout]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getStatusClass = (status: string) => {
  const classes = {
    'Pending': 'bg-yellow-100 text-yellow-900',
    'Ready': 'bg-green-100 text-green-900',
    'Paid': 'bg-blue-100 text-blue-900',
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const viewDetails = () => {
  emit('viewDetails', props.payout)
}

const requestPayout = () => {
  emit('requestPayout', props.payout)
}

const downloadStatement = () => {
  emit('downloadStatement', props.payout)
}
</script>
