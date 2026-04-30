<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b">
        <div class="flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">
            {{ warehouse ? 'Редактировать склад' : 'Создать склад' }}
          </h2>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <form @submit.prevent="submit" class="p-6 space-y-4">
        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Название склада *</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="Склад №1"
          />
        </div>

        <!-- Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Тип склада *</label>
          <select
            v-model="form.type"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
          >
            <option value="b2b">B2B (для бизнеса)</option>
            <option value="b2c">B2C (для розницы)</option>
            <option value="mixed">Смешанный</option>
          </select>
        </div>

        <!-- Address -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Адрес *</label>
          <input
            v-model="form.address"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="ул. Примерная, д. 1"
          />
        </div>

        <!-- City -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Город *</label>
          <input
            v-model="form.city"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="Москва"
          />
        </div>

        <!-- Region -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Регион *</label>
          <input
            v-model="form.region"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="Московская область"
          />
        </div>

        <!-- Postal Code -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Почтовый индекс *</label>
          <input
            v-model="form.postal_code"
            type="text"
            required
            pattern="[0-9]{6}"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="123456"
          />
        </div>

        <!-- Coordinates -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Широта</label>
            <input
              v-model.number="form.latitude"
              type="number"
              step="any"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
              placeholder="55.7558"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Долгота</label>
            <input
              v-model.number="form.longitude"
              type="number"
              step="any"
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
              placeholder="37.6173"
            />
          </div>
        </div>

        <!-- Area -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Площадь (м²)</label>
          <input
            v-model.number="form.area"
            type="number"
            min="0"
            step="0.1"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="1000"
          />
        </div>

        <!-- Capacity -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Вместимость (ед. товара)</label>
          <input
            v-model.number="form.capacity"
            type="number"
            min="0"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="10000"
          />
        </div>

        <!-- Storage Options -->
        <div class="border rounded-lg p-4 bg-gray-50">
          <h3 class="text-sm font-medium text-gray-900 mb-3">Условия хранения</h3>
          <div class="space-y-2">
            <div class="flex items-center gap-2">
              <input
                v-model="form.has_cold_storage"
                type="checkbox"
                id="cold_storage"
                class="w-4 h-4 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500"
              />
              <label for="cold_storage" class="text-sm text-gray-700">
                Холодильное хранение
              </label>
            </div>
            <div class="flex items-center gap-2">
              <input
                v-model="form.has_freezer"
                type="checkbox"
                id="freezer"
                class="w-4 h-4 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500"
              />
              <label for="freezer" class="text-sm text-gray-700">
                Морозильная камера
              </label>
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
            class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ submitting ? 'Сохранение...' : (warehouse ? 'Сохранить' : 'Создать') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import supermarketApi from '@/services/supermarketApi'
import type { Warehouse, WarehouseCreateData } from '@/services/supermarketApi'

interface Props {
  warehouse?: Warehouse | null
}

const props = defineProps<Props>()
const emit = defineEmits(['close', 'saved'])

const form = ref<WarehouseCreateData>({
  name: '',
  address: '',
  city: '',
  region: '',
  postal_code: '',
  latitude: null,
  longitude: null,
  area: null,
  capacity: null,
  has_cold_storage: false,
  has_freezer: false,
  type: 'b2b'
})

const submitting = ref(false)
const error = ref('')

watch(() => props.warehouse, (newWarehouse) => {
  if (newWarehouse) {
    form.value = {
      name: newWarehouse.name,
      address: newWarehouse.address,
      city: newWarehouse.city,
      region: newWarehouse.region,
      postal_code: newWarehouse.postal_code,
      latitude: newWarehouse.latitude,
      longitude: newWarehouse.longitude,
      area: newWarehouse.area,
      capacity: newWarehouse.capacity,
      has_cold_storage: newWarehouse.has_cold_storage,
      has_freezer: newWarehouse.has_freezer,
      type: newWarehouse.type
    }
  }
}, { immediate: true })

const submit = async () => {
  submitting.value = true
  error.value = ''

  try {
    if (props.warehouse) {
      await supermarketApi.updateWarehouse(props.warehouse.id, form.value)
    } else {
      await supermarketApi.createWarehouse(form.value)
    }
    emit('saved')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Ошибка при сохранении склада'
  } finally {
    submitting.value = false
  }
}
</script>
