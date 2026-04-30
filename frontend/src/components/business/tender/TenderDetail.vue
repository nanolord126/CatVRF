<template>
  <div class="tender-detail">
    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Tender not found -->
    <div v-else-if="!tender" class="text-center py-12">
      <p class="text-gray-600">Тендер не найден</p>
      <button @click="$router.back()" class="mt-4 text-blue-600 hover:underline">
        Вернуться назад
      </button>
    </div>

    <!-- Tender detail -->
    <div v-else class="space-y-6">
      <!-- Header -->
      <div class="bg-white border rounded-lg p-6">
        <div class="flex justify-between items-start">
          <div>
            <div class="flex items-center gap-2 mb-2">
              <span
                :class="[
                  'px-2 py-1 text-xs font-medium rounded',
                  getStatusClass(tender.status)
                ]"
              >
                {{ getStatusLabel(tender.status) }}
              </span>
              <span class="text-xs text-gray-500">
                {{ tender.type === 'supply' ? 'Поставка' : 'Разовая' }}
              </span>
              <span v-if="tender.requires_platform_guarantee" class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">
                Гарантия платформы
              </span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ tender.title }}</h1>
            <p v-if="tender.description" class="text-gray-600 mt-2">{{ tender.description }}</p>
          </div>
          <div class="flex gap-2">
            <button
              v-if="tender.status === 'draft'"
              @click="activateTender"
              class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
            >
              Активировать
            </button>
            <button
              v-if="tender.status === 'active'"
              @click="closeTender"
              class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition"
            >
              Закрыть
            </button>
            <button
              v-if="tender.status === 'active' && !tender.requires_platform_guarantee"
              @click="showGuaranteeModal = true"
              class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
            >
              Запросить гарантию
            </button>
          </div>
        </div>

        <!-- Tender info -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
          <div>
            <p class="text-sm text-gray-500">Минимальная сумма</p>
            <p class="text-lg font-semibold">{{ formatAmount(tender.min_amount) }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Дата окончания</p>
            <p class="text-lg font-semibold">{{ formatDate(tender.ends_at) }}</p>
          </div>
          <div v-if="tender.duration_months">
            <p class="text-sm text-gray-500">Срок контракта</p>
            <p class="text-lg font-semibold">{{ tender.duration_months }} мес.</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Комиссия платформы</p>
            <p class="text-lg font-semibold">7%</p>
          </div>
        </div>

        <!-- ML Assessment -->
        <div v-if="tender.credit_score" class="mt-6 p-4 bg-gray-50 rounded-lg">
          <h3 class="font-semibold text-gray-900 mb-2">ML Оценка</h3>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <p class="text-sm text-gray-500">Credit Score</p>
              <p :class="['text-lg font-semibold', getCreditScoreClass(tender.credit_score)]">
                {{ tender.credit_score?.toFixed(1) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Уровень риска</p>
              <p :class="['text-lg font-semibold', getRiskLevelClass(tender.credit_risk_level)]">
                {{ getRiskLevelLabel(tender.credit_risk_level) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Фрауд проверка</p>
              <p :class="['text-lg font-semibold', tender.fraud_check_passed ? 'text-green-600' : 'text-red-600']">
                {{ tender.fraud_check_passed ? 'Пройдена' : 'Не пройдена' }}
              </p>
            </div>
            <div v-if="tender.guarantee_fee_amount">
              <p class="text-sm text-gray-500">Плата за гарантию</p>
              <p class="text-lg font-semibold">{{ formatAmount(tender.guarantee_fee_amount) }}</p>
            </div>
          </div>
        </div>

        <!-- Hold info -->
        <div v-if="tender.hold_amount" class="mt-6 p-4 bg-blue-50 rounded-lg">
          <h3 class="font-semibold text-gray-900 mb-2">Холд средств</h3>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500">Заблокировано</p>
              <p class="text-lg font-semibold">{{ formatAmount(tender.hold_amount) }}</p>
            </div>
            <div>
              <p class="text-sm text-gray-500">Дата разблокировки</p>
              <p class="text-lg font-semibold">{{ formatDate(tender.hold_release_date) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Bids -->
      <div class="bg-white border rounded-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Заявки ({{ bids.length }})</h2>
        
        <div v-if="bids.length === 0" class="text-center py-8 text-gray-600">
          Заявки пока нет
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="bid in bids"
            :key="bid.id"
            class="border rounded-lg p-4"
          >
            <div class="flex justify-between items-start">
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <span
                    :class="[
                      'px-2 py-1 text-xs font-medium rounded',
                      getBidStatusClass(bid.status)
                    ]"
                  >
                    {{ getBidStatusLabel(bid.status) }}
                  </span>
                  <span class="text-sm text-gray-500">Поставщик #{{ bid.supplier_id }}</span>
                </div>
                <p class="text-lg font-semibold">{{ formatAmount(bid.bid_amount) }}</p>
                <p v-if="bid.proposal" class="text-gray-600 mt-1">{{ bid.proposal }}</p>
                <div v-if="bid.guarantee_amount" class="mt-2 text-sm text-gray-500">
                  Гарантия: {{ formatAmount(bid.guarantee_amount) }}
                </div>
              </div>
              <div v-if="tender.status === 'active' && bid.status === 'submitted'" class="flex gap-2">
                <button
                  @click="selectWinner(bid.id)"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                >
                  Выбрать победителем
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Documents -->
      <div class="bg-white border rounded-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Документы</h2>
        <TenderDocuments :tender-id="tender.id" />
      </div>
    </div>

    <!-- Guarantee Modal -->
    <PlatformGuarantee
      v-if="showGuaranteeModal"
      :tender-id="tender.id"
      @close="showGuaranteeModal = false"
      @requested="loadTender"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import tenderApi from '@/services/tenderApi'
import TenderDocuments from './TenderDocuments.vue'
import PlatformGuarantee from './PlatformGuarantee.vue'

const route = useRoute()
const tender = ref<any>(null)
const bids = ref<any[]>([])
const loading = ref(true)
const showGuaranteeModal = ref(false)

const loadTender = async () => {
  loading.value = true
  try {
    const data = await tenderApi.getTender(parseInt(route.params.id as string))
    tender.value = data
    bids.value = data.bids || []
  } catch (error) {
    console.error('Error loading tender:', error)
  } finally {
    loading.value = false
  }
}

const activateTender = async () => {
  try {
    await tenderApi.activateTender(tender.value.id)
    await loadTender()
  } catch (error) {
    console.error('Error activating tender:', error)
  }
}

const closeTender = async () => {
  if (!confirm('Вы уверены, что хотите закрыть тендер?')) return
  try {
    await tenderApi.closeTender(tender.value.id)
    await loadTender()
  } catch (error) {
    console.error('Error closing tender:', error)
  }
}

const selectWinner = async (bidId: number) => {
  if (!confirm('Выбрать эту заявку победителем? Это действие нельзя отменить.')) return
  
  // Check documents
  const requiredDocs = ['contract', 'upd', 'invoice', 'act']
  const signedDocs = tender.value.documents?.filter((d: any) => 
    requiredDocs.includes(d.type) && d.status === 'signed'
  ).length || 0
  
  if (signedDocs < requiredDocs.length) {
    alert(`Необходимо подписать все документы: ${requiredDocs.join(', ')}`)
    return
  }

  try {
    await tenderApi.selectWinner(tender.value.id, bidId)
    await loadTender()
  } catch (error: any) {
    alert(error.response?.data?.message || 'Ошибка при выборе победителя')
  }
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    draft: 'Черновик',
    active: 'Активный',
    closed: 'Закрыт',
    cancelled: 'Отменен',
    completed: 'Завершен'
  }
  return labels[status] || status
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-800',
    active: 'bg-green-100 text-green-800',
    closed: 'bg-yellow-100 text-yellow-800',
    cancelled: 'bg-red-100 text-red-800',
    completed: 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getBidStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    submitted: 'Подана',
    withdrawn: 'Отозвана',
    under_review: 'На рассмотрении',
    accepted: 'Принята',
    rejected: 'Отклонена'
  }
  return labels[status] || status
}

const getBidStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    submitted: 'bg-blue-100 text-blue-800',
    withdrawn: 'bg-gray-100 text-gray-800',
    under_review: 'bg-yellow-100 text-yellow-800',
    accepted: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getCreditScoreClass = (score: number) => {
  if (score >= 80) return 'text-green-600'
  if (score >= 60) return 'text-yellow-600'
  return 'text-red-600'
}

const getRiskLevelClass = (level: string) => {
  const classes: Record<string, string> = {
    low: 'text-green-600',
    medium: 'text-yellow-600',
    high: 'text-orange-600',
    critical: 'text-red-600'
  }
  return classes[level] || 'text-gray-600'
}

const getRiskLevelLabel = (level: string) => {
  const labels: Record<string, string> = {
    low: 'Низкий',
    medium: 'Средний',
    high: 'Высокий',
    critical: 'Критический'
  }
  return labels[level] || level
}

const formatAmount = (amount: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(amount)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU')
}

onMounted(() => {
  loadTender()
})
</script>
