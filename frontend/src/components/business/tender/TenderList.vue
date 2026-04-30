<template>
  <div class="tender-list">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Тендеры</h2>
      <button
        @click="showCreateModal = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        Создать тендер
      </button>
    </div>

    <!-- Filters -->
    <div class="mb-4 flex gap-2">
      <button
        v-for="status in statusFilters"
        :key="status.value"
        @click="selectedStatus = status.value"
        :class="[
          'px-4 py-2 rounded-lg transition',
          selectedStatus === status.value
            ? 'bg-blue-600 text-white'
            : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
        ]"
      >
        {{ status.label }}
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Загрузка...</p>
    </div>

    <!-- Empty state -->
    <div v-else-if="tenders.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="mt-2 text-gray-600">Нет тендеров</p>
      <button
        @click="showCreateModal = true"
        class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        Создать первый тендер
      </button>
    </div>

    <!-- Tender list -->
    <div v-else class="space-y-4">
      <div
        v-for="tender in tenders"
        :key="tender.id"
        class="bg-white border rounded-lg p-6 hover:shadow-md transition cursor-pointer"
        @click="viewTender(tender.id)"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1">
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
            <h3 class="text-lg font-semibold text-gray-900">{{ tender.title }}</h3>
            <p v-if="tender.description" class="text-gray-600 mt-1 line-clamp-2">{{ tender.description }}</p>
            <div class="flex items-center gap-4 mt-3 text-sm text-gray-600">
              <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ formatAmount(tender.min_amount) }}
              </span>
              <span class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                {{ formatDate(tender.ends_at) }}
              </span>
              <span v-if="tender.duration_months" class="flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ tender.duration_months }} мес.
              </span>
            </div>
          </div>
          <div class="text-right">
            <div v-if="tender.credit_score" class="text-sm">
              <span class="text-gray-600">Credit Score:</span>
              <span :class="getCreditScoreClass(tender.credit_score)" class="font-medium ml-1">
                {{ tender.credit_score.toFixed(1) }}
              </span>
            </div>
            <div v-if="tender.bids_count !== undefined" class="text-sm text-gray-600 mt-1">
              {{ tender.bids_count }} заявок
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="mt-6 flex justify-center gap-2">
      <button
        v-for="page in pagination.last_page"
        :key="page"
        @click="loadPage(page)"
        :class="[
          'px-4 py-2 rounded-lg transition',
          pagination.current_page === page
            ? 'bg-blue-600 text-white'
            : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
        ]"
      >
        {{ page }}
      </button>
    </div>

    <!-- Create modal -->
    <TenderCreate
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="onTenderCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import tenderApi from '@/services/tenderApi'
import TenderCreate from './TenderCreate.vue'

const router = useRouter()
const tenders = ref<any[]>([])
const loading = ref(false)
const selectedStatus = ref<string>('')
const showCreateModal = ref(false)
const pagination = ref<any>(null)

const statusFilters = [
  { value: '', label: 'Все' },
  { value: 'draft', label: 'Черновики' },
  { value: 'active', label: 'Активные' },
  { value: 'closed', label: 'Закрытые' },
  { value: 'completed', label: 'Завершенные' }
]

const loadTenders = async (page = 1) => {
  loading.value = true
  try {
    const response = await tenderApi.listTenders(selectedStatus.value)
    tenders.value = response.data
    pagination.value = {
      current_page: response.current_page,
      last_page: response.last_page
    }
  } catch (error) {
    console.error('Error loading tenders:', error)
  } finally {
    loading.value = false
  }
}

const loadPage = (page: number) => {
  loadTenders(page)
}

const viewTender = (id: number) => {
  router.push({ name: 'tender-detail', params: { id } })
}

const onTenderCreated = () => {
  showCreateModal.value = false
  loadTenders()
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

const getCreditScoreClass = (score: number) => {
  if (score >= 80) return 'text-green-600'
  if (score >= 60) return 'text-yellow-600'
  return 'text-red-600'
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
  loadTenders()
})
</script>
