<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b">
        <div class="flex justify-between items-center">
          <h2 class="text-xl font-bold text-gray-900">Создать лида</h2>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <form @submit.prevent="submit" class="p-6 space-y-4">
        <!-- Company Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Название компании *</label>
          <input
            v-model="form.company_name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="ООО Компания"
          />
        </div>

        <!-- Contact Person -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Контактное лицо *</label>
          <input
            v-model="form.contact_person"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="Иван Иванов"
          />
        </div>

        <!-- Contact Email -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
          <input
            v-model="form.contact_email"
            type="email"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="email@company.com"
          />
        </div>

        <!-- Contact Phone -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Телефон *</label>
          <input
            v-model="form.contact_phone"
            type="tel"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="+7 (999) 123-45-67"
          />
        </div>

        <!-- Requirement -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Потребность *</label>
          <textarea
            v-model="form.requirement"
            rows="3"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
            placeholder="Опишите потребность компании"
          />
        </div>

        <!-- Budget Range -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Бюджет *</label>
          <select
            v-model="form.budget_range"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
          >
            <option value="">Выберите диапазон</option>
            <option value="до 100 000 ₽">до 100 000 ₽</option>
            <option value="100 000 - 500 000 ₽">100 000 - 500 000 ₽</option>
            <option value="500 000 - 1 000 000 ₽">500 000 - 1 000 000 ₽</option>
            <option value="1 000 000 - 5 000 000 ₽">1 000 000 - 5 000 000 ₽</option>
            <option value="более 5 000 000 ₽">более 5 000 000 ₽</option>
          </select>
        </div>

        <!-- Category -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Категория *</label>
          <select
            v-model="form.category"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
          >
            <option value="">Выберите категорию</option>
            <option value="Продукты питания">Продукты питания</option>
            <option value="Медицинские товары">Медицинские товары</option>
            <option value="Строительные материалы">Строительные материалы</option>
            <option value="Электроника">Электроника</option>
            <option value="Одежда и текстиль">Одежда и текстиль</option>
            <option value="Другое">Другое</option>
          </select>
        </div>

        <!-- Source -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Источник *</label>
          <select
            v-model="form.source"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
          >
            <option value="">Выберите источник</option>
            <option value="Сайт">Сайт</option>
            <option value="Реклама">Реклама</option>
            <option value="Рекомендация">Рекомендация</option>
            <option value="Выставка">Выставка</option>
            <option value="Холодный звонок">Холодный звонок</option>
            <option value="Социальные сети">Социальные сети</option>
          </select>
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
            {{ submitting ? 'Создание...' : 'Создать лида' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import crmApi from '@/services/crmApi'
import type { LeadCreateData } from '@/services/crmApi'

const emit = defineEmits(['close', 'created'])

const form = ref<LeadCreateData>({
  company_name: '',
  contact_person: '',
  contact_email: '',
  contact_phone: '',
  requirement: '',
  budget_range: '',
  category: '',
  source: '',
  notes: ''
})

const submitting = ref(false)
const error = ref('')

const submit = async () => {
  submitting.value = true
  error.value = ''

  try {
    await crmApi.createLead(form.value)
    emit('created')
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Ошибка при создании лида'
  } finally {
    submitting.value = false
  }
}
</script>
