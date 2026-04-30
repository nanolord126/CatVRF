<template>
  <div class="product-form bg-white rounded-xl shadow-md p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-6">
      {{ isEdit ? 'Редактировать товар' : 'Создать товар' }}
    </h2>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Basic Information -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900">Основная информация</h3>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Название *</label>
          <input 
            v-model="form.name"
            type="text"
            required
            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Описание</label>
          <textarea 
            v-model="form.description"
            rows="3"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
          />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Цена (₽) *</label>
            <input 
              v-model.number="form.price"
              type="number"
              required
              min="0"
              step="0.01"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Вес (кг)</label>
            <input 
              v-model.number="form.weight"
              type="number"
              min="0"
              step="0.01"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
            />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Срок годности (дней)</label>
            <input 
              v-model.number="form.shelf_life_days"
              type="number"
              min="0"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div class="flex items-center pt-6">
            <label class="flex items-center cursor-pointer">
              <input 
                v-model="form.requires_cold_chain"
                type="checkbox"
                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
              />
              <span class="ml-2 text-sm text-gray-700">Требует холодовой цепи</span>
            </label>
          </div>
        </div>

        <div class="flex items-center pt-2">
          <label class="flex items-center cursor-pointer">
            <input 
              v-model="form.is_active"
              type="checkbox"
              class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
            />
            <span class="ml-2 text-sm text-gray-700">Активен</span>
          </label>
        </div>
      </div>

      <!-- Sub-vertical -->
      <div class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900">Классификация</h3>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Под-вертикаль *</label>
          <select 
            v-model="form.sub_vertical"
            required
            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">Выберите под-вертикаль</option>
            <option value="meat_shops">Мясные магазины</option>
            <option value="farm_direct">Фермерские продукты</option>
            <option value="vegan_products">Веганские продукты</option>
            <option value="confectionery">Кондитерские изделия</option>
            <option value="grocery_and_delivery">Бакалея и доставка</option>
            <option value="food">Еда</option>
            <option value="office_catering">Офисный кейтеринг</option>
          </select>
        </div>
      </div>

      <!-- Sub-vertical Attributes -->
      <div v-if="form.sub_vertical" class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900">Атрибуты под-вертикали</h3>
        
        <!-- Meat Shops -->
        <div v-if="form.sub_vertical === 'meat_shops'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Тип мяса</label>
            <input 
              v-model="form.attributes.meat_type"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Тип нарезки</label>
            <input 
              v-model="form.attributes.cut_type"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
            />
          </div>
        </div>

        <!-- Farm Direct -->
        <div v-if="form.sub_vertical === 'farm_direct'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Название фермы</label>
            <input 
              v-model="form.attributes.farm_name"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Сертификация</label>
            <input 
              v-model="form.attributes.certification"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
            />
          </div>
        </div>

        <!-- Vegan Products -->
        <div v-if="form.sub_vertical === 'vegan_products'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="flex items-center pt-6">
            <label class="flex items-center cursor-pointer">
              <input 
                v-model="form.attributes.is_vegan"
                type="checkbox"
                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
              />
              <span class="ml-2 text-sm text-gray-700">Веганский продукт</span>
            </label>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Аллергены</label>
            <input 
              v-model="form.attributes.allergens"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
            />
          </div>
        </div>

        <!-- Confectionery -->
        <div v-if="form.sub_vertical === 'confectionery'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Содержание сахара</label>
            <input 
              v-model="form.attributes.sugar_content"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Тип начинки</label>
            <input 
              v-model="form.attributes.filling_type"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
            />
          </div>
        </div>

        <!-- Grocery & Delivery / Food -->
        <div v-if="['grocery_and_delivery', 'food'].includes(form.sub_vertical)">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Тип хранения</label>
            <input 
              v-model="form.attributes.storage_type"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
            />
          </div>
        </div>

        <!-- Office Catering -->
        <div v-if="form.sub_vertical === 'office_catering'">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Размер порции</label>
            <input 
              v-model="form.attributes.serving_size"
              type="text"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
            />
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-4 pt-4">
        <button 
          type="submit"
          :disabled="isSubmitting"
          class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 text-white py-3 px-6 rounded-lg font-medium transition-colors"
        >
          {{ isSubmitting ? 'Сохранение...' : (isEdit ? 'Сохранить' : 'Создать') }}
        </button>
        <button 
          type="button"
          @click="handleCancel"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-3 px-6 rounded-lg font-medium transition-colors"
        >
          Отмена
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'

interface ProductForm {
  name: string
  description: string
  price: number
  weight: number
  requires_cold_chain: boolean
  shelf_life_days: number
  sub_vertical: string
  attributes: Record<string, any>
  is_active: boolean
}

const props = defineProps<{
  product?: any
  isEdit?: boolean
}>()

const emit = defineEmits<{
  submit: [form: ProductForm]
  cancel: []
}>()

const isSubmitting = ref(false)

const form = reactive<ProductForm>({
  name: props.product?.name || '',
  description: props.product?.description || '',
  price: props.product?.price || 0,
  weight: props.product?.weight || 0,
  requires_cold_chain: props.product?.requires_cold_chain || false,
  shelf_life_days: props.product?.shelf_life_days || 0,
  sub_vertical: props.product?.sub_vertical || '',
  attributes: props.product?.attributes || {},
  is_active: props.product?.is_active !== undefined ? props.product.is_active : true
})

const handleSubmit = async () => {
  isSubmitting.value = true
  try {
    emit('submit', form)
  } finally {
    isSubmitting.value = false
  }
}

const handleCancel = () => {
  emit('cancel')
}
</script>
