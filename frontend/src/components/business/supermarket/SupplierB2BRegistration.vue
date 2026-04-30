<template>
  <div class="supplier-b2b-registration bg-white rounded-xl shadow-lg p-8">
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Регистрация поставщика B2B</h2>
      <p class="text-gray-600 mt-2">Заполните форму для регистрации в качестве поставщика B2B</p>
    </div>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Registration Type -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Тип регистрации</label>
        <div class="flex gap-4">
          <label class="flex items-center">
            <input
              v-model="form.registration_type"
              type="radio"
              value="b2b_only"
              class="mr-2"
            />
            <span>Только B2B</span>
          </label>
          <label class="flex items-center">
            <input
              v-model="form.registration_type"
              type="radio"
              value="both"
              class="mr-2"
            />
            <span>B2B и B2C</span>
          </label>
        </div>
      </div>

      <!-- Supplier Tier -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Уровень поставщика</label>
        <select
          v-model="form.supplier_tier_id"
          class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          required
        >
          <option value="">Выберите уровень</option>
          <option value="1">Производитель (Tier 1)</option>
          <option value="2">Торговый дом (Tier 2)</option>
          <option value="3">Оптовик (Tier 3)</option>
          <option value="4">Розничный продавец (Tier 4)</option>
        </select>
      </div>

      <!-- Company Information -->
      <div class="border-t pt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Информация о компании</h3>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Название компании</label>
            <input
              v-model="form.company_name"
              type="text"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">ИНН</label>
            <input
              v-model="form.inn"
              type="text"
              maxlength="12"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">КПП</label>
            <input
              v-model="form.kpp"
              type="text"
              maxlength="9"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">ОГРН</label>
            <input
              v-model="form.ogrn"
              type="text"
              maxlength="15"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>

        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Юридический адрес</label>
          <input
            v-model="form.legal_address"
            type="text"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            required
          />
        </div>

        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Фактический адрес</label>
          <input
            v-model="form.actual_address"
            type="text"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
        </div>
      </div>

      <!-- Bank Information -->
      <div class="border-t pt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Банковские реквизиты</h3>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Название банка</label>
            <input
              v-model="form.bank_name"
              type="text"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">БИК</label>
            <input
              v-model="form.bik"
              type="text"
              maxlength="9"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Расчетный счет</label>
            <input
              v-model="form.account_number"
              type="text"
              maxlength="20"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Корр. счет</label>
            <input
              v-model="form.correspondent_account"
              type="text"
              maxlength="20"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <div class="border-t pt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Контактная информация</h3>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Контактное лицо</label>
            <input
              v-model="form.contact_person"
              type="text"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Телефон</label>
            <input
              v-model="form.contact_phone"
              type="tel"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              required
            />
          </div>
        </div>

        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
          <input
            v-model="form.contact_email"
            type="email"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            required
          />
        </div>
      </div>

      <!-- Warehouses -->
      <div class="border-t pt-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Склады B2B</h3>
          <button
            type="button"
            @click="addWarehouse"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
          >
            + Добавить склад
          </button>
        </div>

        <div v-if="form.warehouses.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
          <p class="text-gray-600">Нет добавленных складов</p>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="(warehouse, index) in form.warehouses"
            :key="index"
            class="bg-gray-50 rounded-lg p-4"
          >
            <div class="flex justify-between items-start mb-4">
              <h4 class="font-medium text-gray-900">Склад #{{ index + 1 }}</h4>
              <button
                type="button"
                @click="removeWarehouse(index)"
                class="text-red-600 hover:text-red-700"
              >
                Удалить
              </button>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Название</label>
                <input
                  v-model="warehouse.name"
                  type="text"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  required
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Город</label>
                <input
                  v-model="warehouse.city"
                  type="text"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  required
                />
              </div>
            </div>

            <div class="mt-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Адрес</label>
              <input
                v-model="warehouse.address"
                type="text"
                class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                required
              />
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Почтовый индекс</label>
                <input
                  v-model="warehouse.postal_code"
                  type="text"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  required
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Регион</label>
                <input
                  v-model="warehouse.region"
                  type="text"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  required
                />
              </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Площадь (м²)</label>
                <input
                  v-model.number="warehouse.area"
                  type="number"
                  step="0.01"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Вместимость</label>
                <input
                  v-model.number="warehouse.capacity"
                  type="number"
                  step="0.001"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
              </div>
            </div>

            <div class="mt-4 flex gap-4">
              <label class="flex items-center">
                <input
                  v-model="warehouse.has_cold_storage"
                  type="checkbox"
                  class="mr-2"
                />
                <span class="text-sm text-gray-700">Холодильное хранение</span>
              </label>
              
              <label class="flex items-center">
                <input
                  v-model="warehouse.has_freezer"
                  type="checkbox"
                  class="mr-2"
                />
                <span class="text-sm text-gray-700">Морозильная камера</span>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Documents -->
      <div class="border-t pt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Документы</h3>
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Гарантийное письмо от производителя <span class="text-red-600">*</span>
          </label>
          <input
            type="file"
            @change="handleGuaranteeLetterUpload"
            accept=".pdf,.jpg,.jpeg,.png"
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            required
          />
          <p v-if="form.guarantee_letter" class="mt-2 text-sm text-green-600">
            ✓ Файл загружен: {{ form.guarantee_letter.name }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Дополнительные документы (сертификаты, лицензии)
          </label>
          <input
            type="file"
            @change="handleDocumentsUpload"
            accept=".pdf,.jpg,.jpeg,.png"
            multiple
            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
          />
          <div v-if="form.attached_documents.length > 0" class="mt-2 text-sm text-gray-600">
            Загружено файлов: {{ form.attached_documents.length }}
          </div>
        </div>
      </div>

      <!-- Submit -->
      <div class="flex gap-4 pt-6">
        <button
          type="submit"
          :disabled="loading"
          class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition font-medium disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ loading ? 'Отправка...' : 'Отправить заявку' }}
        </button>
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition font-medium"
        >
          Отмена
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Warehouse {
  name: string
  address: string
  city: string
  region: string
  postal_code: string
  area: number | null
  capacity: number | null
  has_cold_storage: boolean
  has_freezer: boolean
}

const emit = defineEmits<{
  submit: [data: any]
  cancel: []
}>()

const loading = ref(false)

const form = ref({
  registration_type: 'b2b_only',
  supplier_tier_id: '',
  company_name: '',
  inn: '',
  kpp: '',
  ogrn: '',
  legal_address: '',
  actual_address: '',
  bank_name: '',
  bik: '',
  account_number: '',
  correspondent_account: '',
  contact_person: '',
  contact_phone: '',
  contact_email: '',
  warehouses: [] as Warehouse[],
  guarantee_letter: null as File | null,
  attached_documents: [] as File[]
})

const addWarehouse = () => {
  form.value.warehouses.push({
    name: '',
    address: '',
    city: '',
    region: '',
    postal_code: '',
    area: null,
    capacity: null,
    has_cold_storage: false,
    has_freezer: false
  })
}

const removeWarehouse = (index: number) => {
  form.value.warehouses.splice(index, 1)
}

const handleGuaranteeLetterUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    form.value.guarantee_letter = target.files[0]
  }
}

const handleDocumentsUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files) {
    form.value.attached_documents = Array.from(target.files)
  }
}

const handleSubmit = async () => {
  loading.value = true
  try {
    // API call to submit registration
    emit('submit', form.value)
  } finally {
    loading.value = false
  }
}
</script>
