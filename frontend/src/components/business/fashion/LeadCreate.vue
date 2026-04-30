<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b">
        <div class="flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">Создать лида (Fashion B2B)</h2>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <form @submit.prevent="submit" class="p-6 space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Название компании *</label>
          <input v-model="form.company_name" type="text" required class="w-full px-3 py-2 border rounded-lg" placeholder="ООО Компания" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Контактное лицо *</label>
          <input v-model="form.contact_person" type="text" required class="w-full px-3 py-2 border rounded-lg" placeholder="Иван Иванов" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
          <input v-model="form.contact_email" type="email" required class="w-full px-3 py-2 border rounded-lg" placeholder="email@company.com" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Телефон *</label>
          <input v-model="form.contact_phone" type="tel" required class="w-full px-3 py-2 border rounded-lg" placeholder="+7 (999) 123-45-67" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Потребность *</label>
          <textarea v-model="form.requirement" rows="3" required class="w-full px-3 py-2 border rounded-lg" placeholder="Опишите потребность" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Бюджет *</label>
          <select v-model="form.budget_range" required class="w-full px-3 py-2 border rounded-lg">
            <option value="">Выберите диапазон</option>
            <option value="до 300 000 ₽">до 300 000 ₽</option>
            <option value="300 000 - 1 500 000 ₽">300 000 - 1 500 000 ₽</option>
            <option value="1 500 000 - 5 000 000 ₽">1 500 000 - 5 000 000 ₽</option>
            <option value="более 5 000 000 ₽">более 5 000 000 ₽</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Категория *</label>
          <select v-model="form.category" required class="w-full px-3 py-2 border rounded-lg">
            <option value="">Выберите категорию</option>
            <option value="wholesale">Опт</option>
            <option value="boutique_chain">Сеть бутиков</option>
            <option value="manufacturing">Производство</option>
            <option value="franchise">Франшиза</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Источник *</label>
          <select v-model="form.source" required class="w-full px-3 py-2 border rounded-lg">
            <option value="">Выберите источник</option>
            <option value="Сайт">Сайт</option>
            <option value="Реклама">Реклама</option>
            <option value="Рекомендация">Рекомендация</option>
            <option value="Выставка">Выставка</option>
          </select>
        </div>

        <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">{{ error }}</div>

        <div class="flex justify-end gap-3 pt-4">
          <button type="button" @click="$emit('close')" class="px-4 py-2 border rounded-lg hover:bg-gray-50">Отмена</button>
          <button type="submit" :disabled="submitting" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50">
            {{ submitting ? 'Создание...' : 'Создать лида' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import fashionB2bApi from '@/services/fashionB2bApi'

const emit = defineEmits(['close', 'created'])

const form = ref({
  company_name: '',
  contact_person: '',
  contact_email: '',
  contact_phone: '',
  requirement: '',
  budget_range: '',
  category: 'wholesale' as const,
  source: '',
  notes: ''
})

const submitting = ref(false)
const error = ref('')

const submit = async () => {
  submitting.value = true
  error.value = ''
  try {
    await fashionB2bApi.createLead(form.value)
    emit('created')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Ошибка при создании лида'
  } finally {
    submitting.value = false
  }
}
</script>
