<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b">
        <div class="flex justify-between items-center">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Создать тендер из лида</h2>
            <p v-if="lead" class="text-sm text-gray-600 mt-1">
              Лид: {{ lead.company_name }} ({{ lead.contact_person }})
            </p>
          </div>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <form @submit.prevent="submit" class="p-6 space-y-4">
        <!-- Lead Info (Read-only) -->
        <div v-if="lead" class="bg-gray-50 rounded-lg p-4">
          <h3 class="text-sm font-medium text-gray-700 mb-2">Информация о лиде</h3>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span class="text-gray-600">Компания:</span>
              <span class="ml-2 font-medium">{{ lead.company_name }}</span>
            </div>
            <div>
              <span class="text-gray-600">Бюджет:</span>
              <span class="ml-2 font-medium">{{ lead.budget_range }}</span>
            </div>
            <div class="col-span-2">
              <span class="text-gray-600">Потребность:</span>
              <span class="ml-2 font-medium">{{ lead.requirement }}</span>
            </div>
          </div>
        </div>

        <!-- Title -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Название тендера *</label>
          <input
            v-model="form.title"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :placeholder="lead ? `Тендер для ${lead.company_name}` : 'Название тендера'"
          />
        </div>

        <!-- Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
          <textarea
            v-model="form.description"
            rows="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            :placeholder="lead ? lead.requirement : 'Подробное описание тендера'"
          />
        </div>

        <!-- Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Тип тендера *</label>
          <select
            v-model="form.type"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="supply">Поставка (долгосрочный контракт)</option>
            <option value="one_time">Разовая закупка</option>
          </select>
        </div>

        <!-- Min Amount -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Минимальная сумма (₽) *
            <span class="text-xs text-gray-500">минимум 100 000 ₽</span>
          </label>
          <input
            v-model.number="form.min_amount"
            type="number"
            required
            min="100000"
            step="1000"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="100000"
          />
        </div>

        <!-- Duration (for supply type) -->
        <div v-if="form.type === 'supply'">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Срок контракта (месяцы) *
            <span class="text-xs text-gray-500">минимум 3 месяца</span>
          </label>
          <input
            v-model.number="form.duration_months"
            type="number"
            required
            min="3"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="3"
          />
        </div>

        <!-- End Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Дата окончания приема заявок *</label>
          <input
            v-model="form.ends_at"
            type="datetime-local"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <!-- Delivery Dates -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Дата начала поставки</label>
            <input
              v-model="form.delivery_start_date"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Дата окончания поставки</label>
            <input
              v-model="form.delivery_end_date"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
        </div>

        <!-- Platform Guarantee Option -->
        <div class="border rounded-lg p-4 bg-gray-50">
          <div class="flex items-start gap-3">
            <input
              v-model="requestGuarantee"
              type="checkbox"
              id="guarantee"
              class="mt-1 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
            />
            <div class="flex-1">
              <label for="guarantee" class="block text-sm font-medium text-gray-900">
                Гарантия платформы
              </label>
              <p class="text-xs text-gray-600 mt-1">
                Платформа гарантирует платеж поставщику. Стоимость: 5.2% от суммы тендера.
                Требуется ML-оценка кредитоспособности и проверка на фрод.
              </p>
              <div v-if="requestGuarantee && form.min_amount" class="mt-2 text-sm">
                <span class="text-gray-600">Плата за гарантию:</span>
                <span class="font-medium ml-1">{{ formatGuaranteeFee }}</span>
              </div>
            </div>
          </div>
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
            :disabled="submitting"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ submitting ? 'Создание...' : 'Создать тендер' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import crmApi from '@/services/crmApi'
import tenderApi from '@/services/tenderApi'
import type { TenderCreateData } from '@/services/tenderApi'
import type { CRMLead } from '@/services/crmApi'

interface Props {
  leadId?: number
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'created'])

const lead = ref<CRMLead | null>(null)

const form = ref<TenderCreateData>({
  title: '',
  description: '',
  type: 'supply',
  min_amount: 100000,
  duration_months: 3,
  ends_at: '',
  delivery_start_date: '',
  delivery_end_date: ''
})

const requestGuarantee = ref(false)
const submitting = ref(false)
const error = ref('')

const formatGuaranteeFee = computed(() => {
  const fee = form.value.min_amount * 0.052
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 2
  }).format(fee)
})

onMounted(async () => {
  if (props.leadId) {
    try {
      lead.value = await crmApi.getLead(props.leadId)
      form.value.title = `Тендер для ${lead.value.company_name}`
      form.value.description = lead.value.requirement
    } catch (err: any) {
      error.value = 'Ошибка при загрузке данных лида'
    }
  }
})

const submit = async () => {
  submitting.value = true
  error.value = ''

  try {
    const tenderData = {
      ...form.value,
      vertical_id: 1 // TODO: Get from current context
    }

    const tender = await tenderApi.createTender(tenderData, 1)

    // Convert lead to tender
    if (props.leadId) {
      await crmApi.convertLeadToTender(props.leadId)
    }

    // Request guarantee if selected
    if (requestGuarantee.value) {
      const assessment = await tenderApi.requestGuarantee(tender.id)
      if (!assessment.eligible) {
        error.value = `Гарантия недоступна: ${assessment.credit_assessment.risk_level} риск`
        return
      }
    }

    emit('created')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Ошибка при создании тендера'
  } finally {
    submitting.value = false
  }
}
</script>
