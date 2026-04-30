<template>
  <div class="supplier-tender-list">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Доступные тендеры</h2>
      <button
        @click="checkParticipation"
        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition"
      >
        Проверить баланс
      </button>
    </div>

    <!-- Participation check -->
    <div v-if="participationCheck" class="mb-4 p-4 border rounded-lg" :class="participationCheck.can_participate ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
      <div class="flex justify-between items-center">
        <div>
          <p class="font-medium" :class="participationCheck.can_participate ? 'text-green-800' : 'text-red-800'">
            {{ participationCheck.can_participate ? 'Вы можете участвовать в тендерах' : 'Недостаточно баланса для участия' }}
          </p>
          <p class="text-sm text-gray-600 mt-1">
            Текущий баланс: {{ formatAmount(participationCheck.current_balance) }} |
            Требуется: {{ formatAmount(participationCheck.required_balance) }}
          </p>
          <p v-if="participationCheck.shortage > 0" class="text-sm text-red-600 mt-1">
            Не хватает: {{ formatAmount(participationCheck.shortage) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="mb-4">
      <select
        v-model="selectedVertical"
        @change="loadTenders"
        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
      >
        <option value="">Все вертикали</option>
        <option value="1">Supermarket</option>
        <option value="2">Restaurant</option>
        <option value="3">BeautyMasters</option>
        <option value="4">Taxi</option>
        <option value="5">RealEstate</option>
        <option value="6">Fashion</option>
        <option value="7">Hotels</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Empty state -->
    <div v-else-if="tenders.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
      <p class="text-gray-600">Нет доступных тендеров</p>
    </div>

    <!-- Tender list -->
    <div v-else class="space-y-4">
      <div
        v-for="tender in tenders"
        :key="tender.id"
        class="bg-white border rounded-lg p-6 hover:shadow-md transition"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">
                Активный
              </span>
              <span class="text-xs text-gray-500">
                {{ tender.type === 'supply' ? 'Поставка' : 'Разовая' }}
              </span>
              <span v-if="tender.requires_platform_guarantee" class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded">
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
            </div>
          </div>
          <button
            @click="showBidModal = tender.id"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
          >
            Подать заявку
          </button>
        </div>
      </div>
    </div>

    <!-- My bids section -->
    <div class="mt-8">
      <h3 class="text-xl font-bold text-gray-900 mb-4">Мои заявки</h3>
      <div v-if="myBids.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
        <p class="text-gray-600">У вас пока нет заявок</p>
      </div>
      <div v-else class="space-y-4">
        <div
          v-for="bid in myBids"
          :key="bid.id"
          class="bg-white border rounded-lg p-4"
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
              </div>
              <p class="font-semibold">{{ bid.tender?.title || `Тендер #${bid.tender_id}` }}</p>
              <p class="text-lg font-semibold text-gray-900 mt-1">
                {{ formatAmount(bid.bid_amount) }}
              </p>
              <p v-if="bid.proposal" class="text-gray-600 mt-1">{{ bid.proposal }}</p>
            </div>
            <button
              v-if="bid.status === 'submitted'"
              @click="withdrawBid(bid.id)"
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition"
            >
              Отозвать
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bid modal -->
    <BidSubmit
      v-if="showBidModal"
      :tender-id="showBidModal"
      @close="showBidModal = null"
      @submitted="onBidSubmitted"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import tenderApi from '@/services/tenderApi'
import BidSubmit from './BidSubmit.vue'

const tenders = ref<any[]>([])
const myBids = ref<any[]>([])
const loading = ref(false)
const selectedVertical = ref('')
const showBidModal = ref<number | null>(null)
const participationCheck = ref<any>(null)

const loadTenders = async () => {
  loading.value = true
  try {
    const verticalId = selectedVertical.value ? parseInt(selectedVertical.value) : undefined
    const response = await tenderApi.listAvailableTenders(verticalId)
    tenders.value = response.data
  } catch (error) {
    console.error('Error loading tenders:', error)
  } finally {
    loading.value = false
  }
}

const loadMyBids = async () => {
  try {
    const response = await tenderApi.listMyBids()
    myBids.value = response.data
  } catch (error) {
    console.error('Error loading bids:', error)
  }
}

const checkParticipation = async () => {
  try {
    participationCheck.value = await tenderApi.checkParticipation()
  } catch (error) {
    console.error('Error checking participation:', error)
  }
}

const withdrawBid = async (bidId: number) => {
  if (!confirm('Отозвать заявку?')) return
  try {
    await tenderApi.withdrawBid(bidId)
    await loadMyBids()
  } catch (error) {
    console.error('Error withdrawing bid:', error)
    alert('Ошибка при отзыве заявки')
  }
}

const onBidSubmitted = () => {
  showBidModal.value = null
  loadMyBids()
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
  loadMyBids()
})
</script>
