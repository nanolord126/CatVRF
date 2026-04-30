<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b">
        <div class="flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">Гарантия платформы</h2>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <div class="p-6">
        <!-- Loading -->
        <div v-if="loading" class="text-center py-8">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-gray-600">ML оценка и фрауд проверка...</p>
        </div>

        <!-- Assessment result -->
        <div v-else-if="assessment">
          <div v-if="assessment.eligible" class="space-y-4">
            <!-- Success -->
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium text-green-800">Гарантия доступна</span>
              </div>
            </div>

            <!-- Credit score -->
            <div class="border rounded-lg p-4">
              <h3 class="font-semibold text-gray-900 mb-3">ML Оценка</h3>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">Credit Score</span>
                  <span :class="['font-semibold', getCreditScoreClass(assessment.credit_assessment.score)]">
                    {{ assessment.credit_assessment.score.toFixed(1) }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Уровень риска</span>
                  <span :class="['font-semibold', getRiskLevelClass(assessment.credit_assessment.risk_level)]">
                    {{ getRiskLevelLabel(assessment.credit_assessment.risk_level) }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Вертикаль</span>
                  <span class="font-semibold">{{ assessment.credit_assessment.vertical }}</span>
                </div>
              </div>
            </div>

            <!-- Fraud check -->
            <div class="border rounded-lg p-4">
              <h3 class="font-semibold text-gray-900 mb-3">Фрауд проверка</h3>
              <div class="flex items-center gap-2">
                <svg
                  :class="['w-5 h-5', assessment.fraud_check.passed ? 'text-green-600' : 'text-red-600']"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    v-if="assessment.fraud_check.passed"
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
                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                <span class="font-medium">
                  {{ assessment.fraud_check.passed ? 'Пройдена' : 'Не пройдена' }}
                </span>
              </div>
              <p v-if="assessment.fraud_check.notes" class="text-sm text-gray-600 mt-2">
                {{ assessment.fraud_check.notes }}
              </p>
            </div>

            <!-- Fee -->
            <div class="border rounded-lg p-4 bg-gray-50">
              <div class="flex justify-between items-center">
                <div>
                  <p class="text-sm text-gray-600">Плата за гарантию (5.2%)</p>
                  <p class="text-2xl font-bold text-gray-900">
                    {{ formatAmount(assessment.fee_amount) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Pay button -->
            <button
              v-if="!paid"
              @click="payFee"
              :disabled="paying"
              class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ paying ? 'Оплата...' : 'Оплатить гарантию' }}
            </button>
            <div v-else class="p-3 bg-green-50 border border-green-200 rounded-lg text-center text-green-800">
              Гарантия оплачена
            </div>
          </div>

          <!-- Not eligible -->
          <div v-else class="space-y-4">
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
              <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium text-red-800">Гарантия недоступна</span>
              </div>
              <p class="text-sm text-red-700 mt-2">
                Причина: {{ getRiskLevelLabel(assessment.credit_assessment.risk_level) }} уровень риска
              </p>
            </div>

            <!-- Credit score details -->
            <div class="border rounded-lg p-4">
              <h3 class="font-semibold text-gray-900 mb-3">ML Оценка</h3>
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-gray-600">Credit Score</span>
                  <span :class="['font-semibold', getCreditScoreClass(assessment.credit_assessment.score)]">
                    {{ assessment.credit_assessment.score.toFixed(1) }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Уровень риска</span>
                  <span :class="['font-semibold', getRiskLevelClass(assessment.credit_assessment.risk_level)]">
                    {{ getRiskLevelLabel(assessment.credit_assessment.risk_level) }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Features -->
            <div class="border rounded-lg p-4">
              <h3 class="font-semibold text-gray-900 mb-3">Детали оценки</h3>
              <div class="space-y-2 text-sm">
                <div v-for="(value, key) in assessment.credit_assessment.features" :key="key" class="flex justify-between">
                  <span class="text-gray-600">{{ formatFeatureName(key) }}</span>
                  <span class="font-medium">{{ value.toFixed(1) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import tenderApi from '@/services/tenderApi'

const props = defineProps<{
  tenderId: number
}>()

const emit = defineEmits(['close', 'requested'])

const assessment = ref<any>(null)
const loading = ref(false)
const paying = ref(false)
const paid = ref(false)

const loadAssessment = async () => {
  loading.value = true
  try {
    assessment.value = await tenderApi.requestGuarantee(props.tenderId)
  } catch (error) {
    console.error('Error loading assessment:', error)
  } finally {
    loading.value = false
  }
}

const payFee = async () => {
  paying.value = true
  try {
    await tenderApi.payGuaranteeFee(props.tenderId)
    paid.value = true
    emit('requested')
  } catch (error) {
    console.error('Error paying fee:', error)
    alert('Ошибка при оплате')
  } finally {
    paying.value = false
  }
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

const formatFeatureName = (key: string) => {
  const names: Record<string, string> = {
    balance_ratio: 'Баланс',
    transaction_history: 'История транзакций',
    vertical_experience: 'Опыт в вертикали',
    payment_reliability: 'Надежность платежей',
    geographic_risk: 'Географический риск',
    reputation_score: 'Репутация',
    seasonal_factor: 'Сезонный фактор'
  }
  return names[key] || key
}

const formatAmount = (amount: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 2
  }).format(amount)
}

onMounted(() => {
  loadAssessment()
})
</script>
