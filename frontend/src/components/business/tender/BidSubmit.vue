<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b">
        <div class="flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">Подача заявки</h2>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <form @submit.prevent="submit" class="p-6 space-y-4">
        <!-- Tender info -->
        <div v-if="tender" class="p-4 bg-gray-50 rounded-lg">
          <h3 class="font-semibold text-gray-900">{{ tender.title }}</h3>
          <p class="text-sm text-gray-600 mt-1">Минимальная сумма: {{ formatAmount(tender.min_amount) }}</p>
        </div>

        <!-- Balance check -->
        <div v-if="balanceCheck" class="p-4 rounded-lg" :class="balanceCheck.can_participate ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
          <div class="flex items-center gap-2">
            <svg
              :class="['w-5 h-5', balanceCheck.can_participate ? 'text-green-600' : 'text-red-600']"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                v-if="balanceCheck.can_participate"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
              />
              <path
                v-else
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <span class="font-medium" :class="balanceCheck.can_participate ? 'text-green-800' : 'text-red-800'">
              {{ balanceCheck.can_participate ? 'Баланс достаточен' : 'Недостаточно баланса' }}
            </span>
          </div>
          <p class="text-sm text-gray-600 mt-2">
            Текущий баланс: {{ formatAmount(balanceCheck.current_balance) }} |
            Требуется: {{ formatAmount(balanceCheck.required_balance) }}
          </p>
        </div>

        <!-- Bid Amount -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Сумма заявки (₽) *
            <span class="text-xs text-gray-500">минимум {{ tender?.min_amount ? formatAmount(tender.min_amount) : '100 000' }} ₽</span>
          </label>
          <input
            v-model.number="form.bid_amount"
            type="number"
            required
            :min="tender?.min_amount || 100000"
            step="1000"
            :disabled="!balanceCheck?.can_participate"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed"
            placeholder="100000"
          />
        </div>

        <!-- Guarantee calculation -->
        <div v-if="form.bid_amount" class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <p class="text-sm text-gray-700">
            Гарантия будет заблокирована: <span class="font-semibold">{{ formatGuarantee }}</span>
            <span class="text-xs text-gray-500 block mt-1">
              (10% от суммы или минимум 500 000 ₽)
            </span>
          </p>
        </div>

        <!-- Proposal -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Коммерческое предложение</label>
          <textarea
            v-model="form.proposal"
            rows="4"
            :disabled="!balanceCheck?.can_participate"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed"
            placeholder="Опишите ваше предложение, условия поставки, сроки и т.д."
          />
        </div>

        <!-- Terms -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Условия</label>
          <textarea
            v-model="form.terms"
            rows="2"
            :disabled="!balanceCheck?.can_participate"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed"
            placeholder="Дополнительные условия (опционально)"
          />
        </div>

        <!-- Error -->
        <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
          {{ error }}
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-4">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition"
          >
            Отмена
          </button>
          <button
            type="submit"
            :disabled="submitting || !balanceCheck?.can_participate"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ submitting ? 'Отправка...' : 'Подать заявку' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import tenderApi from '@/services/tenderApi'

const props = defineProps<{
  tenderId: number
}>()

const emit = defineEmits(['close', 'submitted'])

const tender = ref<any>(null)
const balanceCheck = ref<any>(null)
const form = ref({
  bid_amount: 0,
  proposal: '',
  terms: ''
})
const submitting = ref(false)
const error = ref('')

const formatGuarantee = computed(() => {
  if (!form.value.bid_amount) return '0 ₽'
  const guarantee = Math.max(form.value.bid_amount * 0.1, 500000)
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(guarantee)
})

const loadTender = async () => {
  try {
    tender.value = await tenderApi.getTender(props.tenderId)
    form.value.bid_amount = tender.value.min_amount
  } catch (error) {
    console.error('Error loading tender:', error)
  }
}

const checkBalance = async () => {
  try {
    balanceCheck.value = await tenderApi.checkParticipation()
  } catch (error) {
    console.error('Error checking balance:', error)
  }
}

const submit = async () => {
  if (!balanceCheck.value?.can_participate) {
    error.value = 'Недостаточно баланса для участия'
    return
  }

  submitting.value = true
  error.value = ''

  try {
    await tenderApi.submitBid(props.tenderId, form.value)
    emit('submitted')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Ошибка при подаче заявки'
  } finally {
    submitting.value = false
  }
}

const formatAmount = (amount: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(amount)
}

onMounted(() => {
  loadTender()
  checkBalance()
})
</script>
