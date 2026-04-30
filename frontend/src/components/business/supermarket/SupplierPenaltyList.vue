<template>
  <div class="supplier-penalty-list">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Штрафы поставщиков</h2>
      <div class="flex gap-2">
        <select v-model="selectedStatus" class="px-4 py-2 border rounded-lg">
          <option value="">Все статусы</option>
          <option value="pending">Ожидает оплаты</option>
          <option value="charged">Начислен</option>
          <option value="paid">Оплачен</option>
          <option value="disputed">Оспаривается</option>
          <option value="waived">Аннулирован</option>
        </select>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Всего штрафов</div>
        <div class="text-2xl font-bold text-gray-900">{{ stats.totalPenalties }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Начислено</div>
        <div class="text-2xl font-bold text-red-600">{{ formatCurrency(stats.totalCharged) }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Доля платформы</div>
        <div class="text-2xl font-bold text-blue-600">{{ formatCurrency(stats.platformShare) }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Доля бизнеса</div>
        <div class="text-2xl font-bold text-green-600">{{ formatCurrency(stats.businessShare) }}</div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Загрузка...</p>
    </div>

    <!-- Empty state -->
    <div v-else-if="filteredPenalties.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="mt-2 text-gray-600">Нет штрафов</p>
    </div>

    <!-- Penalty list -->
    <div v-else class="space-y-4">
      <div
        v-for="penalty in filteredPenalties"
        :key="penalty.id"
        class="bg-white border rounded-lg p-6 hover:shadow-md transition"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span
                :class="[
                  'px-2 py-1 text-xs font-medium rounded',
                  getStatusClass(penalty.status)
                ]"
              >
                {{ getStatusLabel(penalty.status) }}
              </span>
              <span class="text-xs text-gray-500">{{ formatDate(penalty.created_at) }}</span>
            </div>
            
            <div class="grid grid-cols-2 gap-4 mb-4">
              <div>
                <p class="text-sm text-gray-600">Поставщик</p>
                <p class="font-medium text-gray-900">{{ penalty.supplier_name }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Сумма поставки</p>
                <p class="font-medium text-gray-900">{{ formatCurrency(penalty.supply_amount) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Штраф (3x)</p>
                <p class="font-bold text-red-600">{{ formatCurrency(penalty.penalty_amount) }}</p>
              </div>
              <div>
                <p class="text-sm text-gray-600">Просрочено товаров</p>
                <p class="font-medium text-gray-900">{{ penalty.expired_quantity }} шт.</p>
              </div>
            </div>

            <!-- Distribution -->
            <div class="bg-gray-50 rounded-lg p-3 mb-4">
              <div class="flex justify-between items-center">
                <div>
                  <p class="text-xs text-gray-600">Доля платформы (2x)</p>
                  <p class="font-medium text-blue-600">{{ formatCurrency(penalty.platform_share) }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-600">Доля бизнеса (1x)</p>
                  <p class="font-medium text-green-600">{{ formatCurrency(penalty.business_share) }}</p>
                </div>
              </div>
            </div>

            <div v-if="penalty.reason" class="text-sm text-gray-600 mb-2">
              <span class="font-medium">Причина:</span> {{ penalty.reason }}
            </div>
          </div>

          <div class="flex gap-2">
            <button
              v-if="penalty.status === 'pending'"
              @click="markAsPaid(penalty)"
              class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm"
            >
              Отметить оплаченным
            </button>
            <button
              v-if="penalty.status === 'pending' || penalty.status === 'charged'"
              @click="waivePenalty(penalty)"
              class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm"
            >
              Аннулировать
            </button>
            <button
              @click="viewDetails(penalty)"
              class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
            >
              Подробнее
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface SupplierPenalty {
  id: number
  supplier_id: number
  supplier_name: string
  affected_business_id: number | null
  supply_amount: number
  penalty_amount: number
  platform_share: number
  business_share: number
  status: 'pending' | 'charged' | 'paid' | 'disputed' | 'waived'
  reason: string | null
  expired_quantity: number
  created_at: string
  charged_at: string | null
  paid_at: string | null
}

const penalties = ref<SupplierPenalty[]>([])
const loading = ref(false)
const selectedStatus = ref<string>('')

const stats = ref({
  totalPenalties: 0,
  totalCharged: 0,
  platformShare: 0,
  businessShare: 0
})

const filteredPenalties = computed(() => {
  if (!selectedStatus.value) return penalties.value
  return penalties.value.filter(p => p.status === selectedStatus.value)
})

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 2
  }).format(amount)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: 'Ожидает оплаты',
    charged: 'Начислен',
    paid: 'Оплачен',
    disputed: 'Оспаривается',
    waived: 'Аннулирован'
  }
  return labels[status] || status
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    charged: 'bg-orange-100 text-orange-800',
    paid: 'bg-green-100 text-green-800',
    disputed: 'bg-red-100 text-red-800',
    waived: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const markAsPaid = async (penalty: SupplierPenalty) => {
  // API call to mark as paid
  console.log('Mark as paid:', penalty.id)
}

const waivePenalty = async (penalty: SupplierPenalty) => {
  // API call to waive penalty
  console.log('Waive penalty:', penalty.id)
}

const viewDetails = (penalty: SupplierPenalty) => {
  // Navigate to details page
  console.log('View details:', penalty.id)
}

const loadPenalties = async () => {
  loading.value = true
  try {
    // API call to load penalties
    // const response = await api.get('/supermarket/penalties')
    // penalties.value = response.data
    
    // Mock data for now
    penalties.value = []
  } catch (error) {
    console.error('Error loading penalties:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadPenalties()
})
</script>
