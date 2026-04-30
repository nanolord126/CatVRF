<template>
  <div class="product-list">
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Поиск</label>
          <input 
            v-model="searchQuery"
            type="text"
            placeholder="Название товара..."
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Под-вертикаль</label>
          <select 
            v-model="selectedSubVertical"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">Все</option>
            <option value="meat_shops">Мясные магазины</option>
            <option value="farm_direct">Фермерские продукты</option>
            <option value="vegan_products">Веганские продукты</option>
            <option value="confectionery">Кондитерские изделия</option>
            <option value="grocery_and_delivery">Бакалея и доставка</option>
            <option value="food">Еда</option>
            <option value="office_catering">Офисный кейтеринг</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
          <select 
            v-model="selectedStatus"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">Все</option>
            <option value="active">Активные</option>
            <option value="inactive">Неактивные</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Холодовая цепь</label>
          <select 
            v-model="selectedColdChain"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">Все</option>
            <option value="true">Требует холода</option>
            <option value="false">Не требует</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Products Grid -->
    <div v-if="filteredProducts.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <ProductCard
        v-for="product in filteredProducts"
        :key="product.id"
        :product="product"
        @view-details="handleViewDetails"
        @edit-product="handleEditProduct"
      />
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white rounded-xl shadow-md p-12 text-center">
      <Package class="w-16 h-16 text-gray-400 mx-auto mb-4" />
      <h3 class="text-lg font-semibold text-gray-900 mb-2">Товары не найдены</h3>
      <p class="text-gray-500 mb-4">Попробуйте изменить параметры фильтрации</p>
      <button 
        @click="resetFilters"
        class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-6 rounded-lg font-medium transition-colors"
      >
        Сбросить фильтры
      </button>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex justify-center mt-6">
      <div class="flex gap-2">
        <button 
          v-for="page in totalPages"
          :key="page"
          @click="currentPage = page"
          :class="currentPage === page ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
          class="px-4 py-2 rounded-lg font-medium transition-colors border border-gray-300"
        >
          {{ page }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Package } from 'lucide-vue-next'
import ProductCard from './ProductCard.vue'

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
  products: Product[]
}>()

const emit = defineEmits<{
  viewDetails: [product: Product]
  editProduct: [product: Product]
  createProduct: []
}>()

const searchQuery = ref('')
const selectedSubVertical = ref('')
const selectedStatus = ref('')
const selectedColdChain = ref('')
const currentPage = ref(1)
const itemsPerPage = 12

const filteredProducts = computed(() => {
  let filtered = [...props.products]

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(p => 
      p.name.toLowerCase().includes(query) ||
      p.description?.toLowerCase().includes(query)
    )
  }

  if (selectedSubVertical.value) {
    filtered = filtered.filter(p => p.sub_vertical === selectedSubVertical.value)
  }

  if (selectedStatus.value) {
    filtered = filtered.filter(p => 
      selectedStatus.value === 'active' ? p.is_active : !p.is_active
    )
  }

  if (selectedColdChain.value) {
    filtered = filtered.filter(p => 
      selectedColdChain.value === 'true' ? p.requires_cold_chain : !p.requires_cold_chain
    )
  }

  const start = (currentPage.value - 1) * itemsPerPage
  return filtered.slice(start, start + itemsPerPage)
})

const totalPages = computed(() => {
  return Math.ceil(props.products.length / itemsPerPage)
})

const handleViewDetails = (product: Product) => {
  emit('viewDetails', product)
}

const handleEditProduct = (product: Product) => {
  emit('editProduct', product)
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedSubVertical.value = ''
  selectedStatus.value = ''
  selectedColdChain.value = ''
  currentPage.value = 1
}
</script>
