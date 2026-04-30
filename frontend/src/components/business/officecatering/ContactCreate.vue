<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b">
        <div class="flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">Создать контакт (Office Catering B2B)</h2>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <form @submit.prevent="submit" class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Имя *</label>
          <input v-model="form.name" type="text" required class="w-full px-3 py-2 border rounded-lg" placeholder="Иван Иванов" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Компания *</label>
          <input v-model="form.company" type="text" required class="w-full px-3 py-2 border rounded-lg" placeholder="ООО Компания" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
          <input v-model="form.email" type="email" required class="w-full px-3 py-2 border rounded-lg" placeholder="email@company.com" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Телефон *</label>
          <input v-model="form.phone" type="tel" required class="w-full px-3 py-2 border rounded-lg" placeholder="+7 (999) 123-45-67" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Тип *</label>
          <select v-model="form.type" required class="w-full px-3 py-2 border rounded-lg">
            <option value="client">Клиент</option>
            <option value="supplier">Поставщик</option>
            <option value="partner">Партнер</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Должность</label>
          <input v-model="form.position" type="text" class="w-full px-3 py-2 border rounded-lg" placeholder="Директор" />
        </div>

        <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ error }}</div>

        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="$emit('close')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Отмена</button>
          <button type="submit" :disabled="submitting" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 disabled:opacity-50">
            {{ submitting ? 'Создание...' : 'Создать контакт' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import officecateringB2bApi from '@/services/officecateringB2bApi'

const emit = defineEmits(['close', 'created'])

const form = ref({
  name: '',
  company: '',
  email: '',
  phone: '',
  type: 'client' as const,
  is_primary: false,
  position: '',
  notes: ''
})

const submitting = ref(false)
const error = ref('')

const submit = async () => {
  submitting.value = true
  error.value = ''
  try {
    await officecateringB2bApi.createContact(form.value)
    emit('created')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Ошибка при создании контакта'
  } finally {
    submitting.value = false
  }
}
</script>
