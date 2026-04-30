<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b">
        <div class="flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">
            {{ lot ? 'Редактировать лот' : 'Добавить лот' }}
          </h2>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <form @submit.prevent="submit" class="p-6 space-y-4">
        <!-- Product Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Наименование товара *</label>
          <input
            v-model="form.product_name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Наименование товара"
          />
        </div>

        <!-- Product SKU -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">SKU / Артикул</label>
          <input
            v-model="form.product_sku"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="SKU-001"
          />
        </div>

        <!-- Quantity -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Количество *</label>
          <input
            v-model.number="form.quantity"
            type="number"
            required
            min="1"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="100"
          />
        </div>

        <!-- Unit -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Единица измерения *</label>
          <select
            v-model="form.unit"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="">Выберите единицу</option>
            <option value="шт">шт</option>
            <option value="кг">кг</option>
            <option value="л">л</option>
            <option value="м">м</option>
            <option value="м²">м²</option>
            <option value="м³">м³</option>
            <option value="упак">упак</option>
            <option value="кор">кор</option>
            <option value="пал">пал</option>
          </select>
        </div>

        <!-- Starting Price -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Начальная цена (₽)
            <span class="text-xs text-gray-500">оставьте пустым для запроса цены</span>
          </label>
          <input
            v-model.number="form.starting_price"
            type="number"
            min="0"
            step="0.01"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="0.00"
          />
        </div>

        <!-- Reserve Price -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Резервная цена (₽)
            <span class="text-xs text-gray-500">минимальная приемлемая цена</span>
          </label>
          <input
            v-model.number="form.reserve_price"
            type="number"
            min="0"
            step="0.01"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="0.00"
          />
        </div>

        <!-- Request for Price -->
        <div class="flex items-center gap-2">
          <input
            v-model="form.request_for_price"
            type="checkbox"
            id="request_for_price"
            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <label for="request_for_price" class="text-sm text-gray-700">
            Запросить предложения цены (без начальной цены)
          </label>
        </div>

        <!-- Brand -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Бренд</label>
          <input
            v-model="form.brand"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Бренд"
          />
        </div>

        <!-- Manufacturer -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Производитель</label>
          <input
            v-model="form.manufacturer"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Производитель"
          />
        </div>

        <!-- Country of Origin -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Страна происхождения</label>
          <input
            v-model="form.country_of_origin"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Россия"
          />
        </div>

        <!-- Expiry Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Срок годности</label>
          <input
            v-model="form.expiry_date"
            type="date"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <!-- Cold Chain -->
        <div class="flex items-center gap-2">
          <input
            v-model="form.requires_cold_chain"
            type="checkbox"
            id="cold_chain"
            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <label for="cold_chain" class="text-sm text-gray-700">
            Требуется холодовая цепь
          </label>
        </div>

        <!-- Specifications -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Спецификации (JSON)</label>
          <textarea
            v-model="specificationsJson"
            rows="4"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
            placeholder='{"weight": "1kg", "color": "white", "dimensions": "10x20x30"}'
          />
          <p v-if="specificationsError" class="text-red-600 text-xs mt-1">{{ specificationsError }}</p>
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
            {{ submitting ? 'Сохранение...' : (lot ? 'Сохранить' : 'Добавить') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import tenderApi from '@/services/tenderApi'
import type { TenderLot } from '@/types/crm'

interface Props {
  tenderId: number
  lot?: TenderLot | null
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'saved'])

const form = ref({
  product_name: '',
  product_sku: '',
  quantity: 1,
  unit: 'шт',
  starting_price: null as number | null,
  reserve_price: null as number | null,
  request_for_price: false,
  brand: '',
  manufacturer: '',
  country_of_origin: '',
  expiry_date: '',
  requires_cold_chain: false,
  specifications: null as Record<string, any> | null
})

const specificationsJson = ref('')
const specificationsError = ref('')
const submitting = ref(false)
const error = ref('')

watch(() => props.lot, (newLot) => {
  if (newLot) {
    form.value = {
      product_name: newLot.product_name,
      product_sku: newLot.product_sku || '',
      quantity: newLot.quantity,
      unit: newLot.unit,
      starting_price: newLot.starting_price,
      reserve_price: newLot.reserve_price,
      request_for_price: newLot.request_for_price,
      brand: newLot.brand || '',
      manufacturer: newLot.manufacturer || '',
      country_of_origin: newLot.country_of_origin || '',
      expiry_date: newLot.expiry_date || '',
      requires_cold_chain: newLot.requires_cold_chain,
      specifications: newLot.specifications
    }
    specificationsJson.value = newLot.specifications ? JSON.stringify(newLot.specifications, null, 2) : ''
  }
}, { immediate: true })

watch(specificationsJson, (newJson) => {
  if (!newJson.trim()) {
    form.value.specifications = null
    specificationsError.value = ''
    return
  }
  try {
    form.value.specifications = JSON.parse(newJson)
    specificationsError.value = ''
  } catch (e) {
    specificationsError.value = 'Неверный формат JSON'
  }
})

const submit = async () => {
  if (specificationsError.value) {
    error.value = 'Исправьте ошибки перед отправкой'
    return
  }

  submitting.value = true
  error.value = ''

  try {
    // TODO: Implement API call for creating/updating tender lots
    // await tenderApi.createTenderLot(props.tenderId, form.value)
    emit('saved')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Ошибка при сохранении лота'
  } finally {
    submitting.value = false
  }
}
</script>
