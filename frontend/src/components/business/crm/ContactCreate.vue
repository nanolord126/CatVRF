<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b">
        <div class="flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">Создать контакт</h2>
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
          <label class="block text-sm font-medium text-gray-700 mb-1">Имя *</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="Иван Иванов"
          />
        </div>

        <!-- Company -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Компания *</label>
          <input
            v-model="form.company"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="ООО Компания"
          />
        </div>

        <!-- Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="email@company.com"
          />
        </div>

        <!-- Phone -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Телефон *</label>
          <input
            v-model="form.phone"
            type="tel"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="+7 (999) 123-45-67"
          />
        </div>

        <!-- Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Тип контакта *</label>
          <select
            v-model="form.type"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
          >
            <option value="">Выберите тип</option>
            <option value="customer">Клиент</option>
            <option value="supplier">Поставщик</option>
            <option value="partner">Партнер</option>
            <option value="employee">Сотрудник</option>
          </select>
        </div>

        <!-- Is Primary -->
        <div class="flex items-center gap-2">
          <input
            v-model="form.is_primary"
            type="checkbox"
            id="is_primary"
            class="w-4 h-4 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500"
          />
          <label for="is_primary" class="text-sm text-gray-700">Основной контакт</label>
        </div>

        <!-- Position -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Должность</label>
          <input
            v-model="form.position"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="Директор"
          />
        </div>

        <!-- Department -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Отдел</label>
          <input
            v-model="form.department"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="Отдел закупок"
          />
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Заметки</label>
          <textarea
            v-model="form.notes"
            rows="2"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="Дополнительная информация"
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
            :disabled="submitting"
            class="px-4 py-2 bg-cyan-600 text-white rounded-lg hover:bg-cyan-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ submitting ? 'Создание...' : 'Создать контакт' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import crmApi from '@/services/crmApi'
import type { ContactCreateData } from '@/services/crmApi'

const emit = defineEmits(['close', 'created'])

const form = ref<ContactCreateData>({
  name: '',
  company: '',
  email: '',
  phone: '',
  type: 'customer',
  is_primary: false,
  position: '',
  department: '',
  notes: ''
})

const submitting = ref(false)
const error = ref('')

const submit = async () => {
  submitting.value = true
  error.value = ''

  try {
    await crmApi.createContact(form.value)
    emit('created')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Ошибка при создании контакта'
  } finally {
    submitting.value = false
  }
}
</script>
