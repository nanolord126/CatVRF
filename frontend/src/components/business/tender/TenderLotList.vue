<template>
  <div class="tender-lot-list">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-lg font-semibold text-gray-900">Лоты тендера</h3>
      <button
        @click="showAddLot = true"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
      >
        + Добавить лот
      </button>
    </div>

    <div v-if="lots.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
      <p class="text-gray-600">Нет лотов</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="lot in lots"
        :key="lot.id"
        class="bg-white border rounded-lg p-6 hover:shadow-md transition"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                {{ lot.product_name }}
              </span>
              <span v-if="lot.product_sku" class="text-xs text-gray-500">
                SKU: {{ lot.product_sku }}
              </span>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-4">
              <div>
                <p class="text-sm text-gray-600">Количество</p>
                <p class="font-medium text-gray-900">
                  {{ lot.quantity }} {{ getUnitLabel(lot.unit) }}
                </p>
              </div>
              
              <div>
                <p class="text-sm text-gray-600">Стартовая цена</p>
                <p class="font-medium text-gray-900">
                  {{ lot.starting_price ? formatCurrency(lot.starting_price) : 'Запрос цены' }}
                </p>
              </div>

              <div>
                <p class="text-sm text-gray-600">Резервная цена</p>
                <p class="font-medium text-gray-900">
                  {{ lot.reserve_price ? formatCurrency(lot.reserve_price) : 'Не указана' }}
                </p>
              </div>
            </div>

            <div v-if="lot.specifications" class="mb-4">
              <p class="text-sm font-medium text-gray-700 mb-2">Спецификации</p>
              <div class="bg-gray-50 rounded p-3">
                <div
                  v-for="(value, key) in lot.specifications"
                  :key="key"
                  class="flex justify-between text-sm"
                >
                  <span class="text-gray-600">{{ key }}:</span>
                  <span class="text-gray-900">{{ value }}</span>
                </div>
              </div>
            </div>

            <div class="flex gap-4 text-sm">
              <div v-if="lot.brand">
                <span class="text-gray-600">Бренд:</span>
                <span class="text-gray-900 ml-1">{{ lot.brand }}</span>
              </div>
              <div v-if="lot.manufacturer">
                <span class="text-gray-600">Производитель:</span>
                <span class="text-gray-900 ml-1">{{ lot.manufacturer }}</span>
              </div>
              <div v-if="lot.country_of_origin">
                <span class="text-gray-600">Страна:</span>
                <span class="text-gray-900 ml-1">{{ lot.country_of_origin }}</span>
              </div>
            </div>

            <div v-if="lot.expiry_date" class="mt-2 text-sm">
              <span class="text-gray-600">Срок годности:</span>
              <span class="text-gray-900 ml-1">{{ formatDate(lot.expiry_date) }}</span>
            </div>

            <div class="mt-4 flex gap-2">
              <span
                v-if="lot.requires_cold_chain"
                class="px-2 py-1 bg-cyan-100 text-cyan-800 rounded text-xs"
              >
                Требуется холодная цепь
              </span>
              <span
                v-if="lot.request_for_price"
                class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs"
              >
                Запрос цены
              </span>
            </div>
          </div>

          <div class="flex gap-2">
            <button
              @click="editLot(lot)"
              class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm"
            >
              Редактировать
            </button>
            <button
              @click="deleteLot(lot.id)"
              class="px-3 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition text-sm"
            >
              Удалить
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Add/Edit lot modal -->
    <TenderLotForm
      v-if="showAddLot || editingLot"
      :lot="editingLot"
      :tender-id="tenderId"
      @close="closeLotForm"
      @saved="onLotSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface TenderLot {
  id: number
  product_name: string
  product_sku: string | null
  quantity: number
  unit: string
  starting_price: number | null
  reserve_price: number | null
  request_for_price: boolean
  specifications: Record<string, any> | null
  brand: string | null
  manufacturer: string | null
  country_of_origin: string | null
  expiry_date: string | null
  requires_cold_chain: boolean
}

const props = defineProps<{
  tenderId: number
  lots: TenderLot[]
}>()

const emit = defineEmits<{
  update: [lots: TenderLot[]]
}>()

const showAddLot = ref(false)
const editingLot = ref<TenderLot | null>(null)

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

const getUnitLabel = (unit: string) => {
  const labels: Record<string, string> = {
    pcs: 'шт',
    kg: 'кг',
    ton: 'тонн',
    pallet: 'паллет'
  }
  return labels[unit] || unit
}

const editLot = (lot: TenderLot) => {
  editingLot.value = lot
}

const deleteLot = async (lotId: number) => {
  if (confirm('Удалить лот?')) {
    // API call to delete lot
    const updatedLots = props.lots.filter(l => l.id !== lotId)
    emit('update', updatedLots)
  }
}

const closeLotForm = () => {
  showAddLot.value = false
  editingLot.value = null
}

const onLotSaved = (lot: TenderLot) => {
  closeLotForm()
  // Reload lots
}
</script>
