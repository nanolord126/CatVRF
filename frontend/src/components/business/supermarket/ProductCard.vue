<template>
  <div class="product-card group bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
    <!-- Image -->
    <div class="relative aspect-square overflow-hidden">
      <img 
        :src="product.image || '/placeholder-product.jpg'" 
        :alt="product.name"
        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
      />
      
      <!-- Badges -->
      <div class="absolute top-2 left-2 flex gap-1 flex-wrap">
        <span 
          v-if="product.requires_cold_chain" 
          class="px-2 py-1 bg-blue-500 text-white text-xs font-semibold rounded-full flex items-center gap-1"
        >
          <Snowflake class="w-3 h-3" />
          Холод
        </span>
        <span 
          :class="getSubVerticalColor(product.sub_vertical)"
          class="px-2 py-1 text-white text-xs font-semibold rounded-full"
        >
          {{ getSubVerticalLabel(product.sub_vertical) }}
        </span>
      </div>

      <!-- Price -->
      <div class="absolute bottom-2 left-2 bg-white/90 backdrop-blur-sm rounded-lg px-3 py-2">
        <p class="text-lg font-bold text-gray-900">{{ formatPrice(product.price) }}</p>
      </div>

      <!-- Active Status -->
      <div v-if="!product.is_active" class="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded-lg text-xs font-semibold">
        Неактивен
      </div>
    </div>

    <!-- Product Info -->
    <div class="p-4">
      <!-- Name -->
      <h3 class="font-semibold text-gray-900 text-lg line-clamp-2 mb-2">{{ product.name }}</h3>
      
      <!-- Description -->
      <p v-if="product.description" class="text-gray-500 text-sm line-clamp-2 mb-3">
        {{ product.description }}
      </p>

      <!-- Weight & Shelf Life -->
      <div class="flex items-center gap-4 text-sm text-gray-600 mb-3">
        <span v-if="product.weight" class="flex items-center gap-1">
          <Package class="w-4 h-4" />
          {{ product.weight }} кг
        </span>
        <span v-if="product.shelf_life_days" class="flex items-center gap-1">
          <Clock class="w-4 h-4" />
          {{ product.shelf_life_days }} дней
        </span>
      </div>

      <!-- Sub-vertical Attributes -->
      <div v-if="hasSubVerticalAttributes" class="mb-3">
        <div 
          v-for="(value, key) in product.attributes" 
          :key="key"
          class="text-sm text-gray-600"
        >
          <span class="font-medium">{{ getAttributeLabel(key) }}:</span> {{ value }}
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button 
          @click="viewDetails"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg font-medium transition-colors"
        >
          Подробнее
        </button>
        <button 
          @click="editProduct"
          class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg font-medium transition-colors"
        >
          Редактировать
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Snowflake, Package, Clock } from 'lucide-vue-next'

interface Product {
  id: string
  name: string
  description?: string
  price: number
  weight?: number
  requires_cold_chain: boolean
  shelf_life_days?: number
  sub_vertical: string
  attributes?: Record<string, any>
  is_active: boolean
  image?: string
}

const props = defineProps<{
  product: Product
}>()

const emit = defineEmits<{
  viewDetails: [product: Product]
  editProduct: [product: Product]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(price)
}

const getSubVerticalLabel = (subVertical: string): string => {
  const labels: Record<string, string> = {
    meat_shops: 'Мясные',
    farm_direct: 'Фермерские',
    vegan_products: 'Веган',
    confectionery: 'Кондитерские',
    grocery_and_delivery: 'Бакалея',
    food: 'Еда',
    office_catering: 'Кейтеринг'
  }
  return labels[subVertical] || subVertical
}

const getSubVerticalColor = (subVertical: string): string => {
  const colors: Record<string, string> = {
    meat_shops: 'bg-red-500',
    farm_direct: 'bg-green-500',
    vegan_products: 'bg-yellow-500',
    confectionery: 'bg-blue-500',
    grocery_and_delivery: 'bg-indigo-500',
    food: 'bg-purple-500',
    office_catering: 'bg-pink-500'
  }
  return colors[subVertical] || 'bg-gray-500'
}

const getAttributeLabel = (key: string): string => {
  const labels: Record<string, string> = {
    meat_type: 'Тип мяса',
    cut_type: 'Нарезка',
    farm_name: 'Ферма',
    certification: 'Сертификация',
    is_vegan: 'Веганский',
    allergens: 'Аллергены',
    sugar_content: 'Сахар',
    filling_type: 'Начинка',
    storage_type: 'Хранение',
    serving_size: 'Порция'
  }
  return labels[key] || key
}

const hasSubVerticalAttributes = computed(() => {
  return props.product.attributes && Object.keys(props.product.attributes).length > 0
})

const viewDetails = () => {
  emit('viewDetails', props.product)
}

const editProduct = () => {
  emit('editProduct', props.product)
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
