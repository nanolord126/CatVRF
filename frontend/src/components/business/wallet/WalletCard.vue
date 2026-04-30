<template>
  <div class="wallet-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ wallet.name }}</h3>
          <p class="text-sm text-gray-500">{{ wallet.type }}</p>
        </div>
        <div class="text-right">
          <p class="font-bold text-blue-600">{{ formatPrice(wallet.balance) }}</p>
          <p class="text-xs text-gray-400">Available</p>
        </div>
      </div>
    </div>

    <!-- Card Number -->
    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg p-4 mb-4 text-white">
      <p class="text-lg tracking-widest">{{ maskCardNumber(wallet.cardNumber) }}</p>
      <div class="flex justify-between mt-2 text-sm">
        <span>{{ wallet.cardHolder }}</span>
        <span>{{ wallet.expiryDate }}</span>
      </div>
    </div>

    <!-- Transaction Limit -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Daily limit:</span>
          <span class="font-medium">{{ formatPrice(wallet.dailyLimit) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Used today:</span>
          <span class="font-medium">{{ formatPrice(wallet.usedToday) }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
          <div 
            class="bg-blue-600 h-2 rounded-full" 
            :style="{ width: (wallet.usedToday / wallet.dailyLimit * 100) + '%' }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-4 gap-2 mb-4">
      <button @click="topUp" class="flex flex-col items-center p-2 bg-gray-50 rounded hover:bg-gray-100 transition-colors">
        <span class="text-xl">💰</span>
        <span class="text-xs mt-1">Top Up</span>
      </button>
      <button @click="transfer" class="flex flex-col items-center p-2 bg-gray-50 rounded hover:bg-gray-100 transition-colors">
        <span class="text-xl">↔️</span>
        <span class="text-xs mt-1">Transfer</span>
      </button>
      <button @click="viewHistory" class="flex flex-col items-center p-2 bg-gray-50 rounded hover:bg-gray-100 transition-colors">
        <span class="text-xl">📋</span>
        <span class="text-xs mt-1">History</span>
      </button>
      <button @click="settings" class="flex flex-col items-center p-2 bg-gray-50 rounded hover:bg-gray-100 transition-colors">
        <span class="text-xl">⚙️</span>
        <span class="text-xs mt-1">Settings</span>
      </button>
    </div>

    <!-- Recent Transactions -->
    <div>
      <p class="text-sm font-medium text-gray-700 mb-2">Recent Transactions</p>
      <div class="space-y-2">
        <div 
          v-for="tx in wallet.recentTransactions.slice(0, 3)" 
          :key="tx.id"
          class="flex justify-between items-center text-sm"
        >
          <div class="flex items-center gap-2">
            <span>{{ tx.icon }}</span>
            <div>
              <p class="font-medium text-gray-900">{{ tx.description }}</p>
              <p class="text-xs text-gray-500">{{ tx.date }}</p>
            </div>
          </div>
          <span 
            :class="tx.amount > 0 ? 'text-green-600' : 'text-gray-900'"
            class="font-medium"
          >
            {{ tx.amount > 0 ? '+' : '' }}{{ formatPrice(tx.amount) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Transaction {
  id: string
  description: string
  amount: number
  date: string
  icon: string
}

interface Wallet {
  id: string
  name: string
  type: string
  balance: number
  cardNumber: string
  cardHolder: string
  expiryDate: string
  dailyLimit: number
  usedToday: number
  recentTransactions: Transaction[]
}

const props = defineProps<{
  wallet: Wallet
}>()

const emit = defineEmits<{
  topUp: [wallet: Wallet]
  transfer: [wallet: Wallet]
  viewHistory: [wallet: Wallet]
  settings: [wallet: Wallet]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price)
}

const maskCardNumber = (number: string) => {
  return '•••• •••• •••• ' + number.slice(-4)
}

const topUp = () => {
  emit('topUp', props.wallet)
}

const transfer = () => {
  emit('transfer', props.wallet)
}

const viewHistory = () => {
  emit('viewHistory', props.wallet)
}

const settings = () => {
  emit('settings', props.wallet)
}
</script>
