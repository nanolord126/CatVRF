<template>
  <div class="supermarket-catalog">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg p-8 mb-6 text-white">
      <h1 class="text-3xl font-bold mb-2">Супермаркет</h1>
      <p class="text-indigo-100">Свежие продукты от лучших продавцов</p>
      
      <!-- Sub-vertical Quick Links -->
      <div class="flex flex-wrap gap-2 mt-4">
        <button 
          v-for="sv in subVerticals" 
          :key="sv.id"
          @click="selectSubVertical(sv.id)"
          :class="selectedSubVertical === sv.id ? 'bg-white text-indigo-600' : 'bg-white/20 hover:bg-white/30'"
          class="px-4 py-2 rounded-full text-sm font-medium transition-colors"
        >
          {{ sv.name }}
        </button>
      </div>
    </div>

    <div class="flex gap-6">
      <!-- Filters Sidebar -->
      <div class="w-64 flex-shrink-0">
        <div class="bg-white rounded-xl shadow-md p-6 sticky top-4">
          <h3 class="font-semibold text-gray-900 mb-4">Фильтры</h3>
          
          <!-- Search -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
            <div class="relative">
              <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
              <input 
                v-model="searchQuery"
                type="text"
                placeholder="Название товара..."
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
              />
            </div>
          </div>

          <!-- Sub-vertical -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Под-вертикаль</label>
            <div class="space-y-2">
              <label 
                v-for="sv in subVerticals" 
                :key="sv.id"
                class="flex items-center cursor-pointer"
              >
                <input 
                  type="checkbox"
                  :checked="selectedSubVertical === sv.id"
                  @change="toggleSubVertical(sv.id)"
                  class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm text-gray-700">{{ sv.name }}</span>
              </label>
            </div>
          </div>

          <!-- Price Range -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Цена</label>
            <div class="flex gap-2">
              <input 
                v-model.number="priceRange.min"
                type="number"
                placeholder="От"
                class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
              />
              <input 
                v-model.number="priceRange.max"
                type="number"
                placeholder="До"
                class="w-1/2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
              />
            </div>
          </div>

          <!-- Cold Chain -->
          <div class="mb-6">
            <label class="flex items-center cursor-pointer">
              <input 
                v-model="coldChainOnly"
                type="checkbox"
                class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
              />
              <span class="ml-2 text-sm text-gray-700">Только с холодовой цепью</span>
            </label>
          </div>

          <!-- Sort -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Сортировка</label>
            <select 
              v-model="sortBy"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500"
            >
              <option value="popular">Популярные</option>
              <option value="price_asc">Сначала дешевле</option>
              <option value="price_desc">Сначала дороже</option>
              <option value="newest">Новинки</option>
              <option value="rating">По рейтингу</option>
            </select>
          </div>

          <!-- Reset Filters -->
          <button 
            @click="resetFilters"
            class="w-full bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg font-medium transition-colors"
          >
            Сбросить фильтры
          </button>
        </div>
      </div>

      <!-- Main Content -->
      <div class="flex-1">
        <!-- AI Recommendations -->
        <AIRecommendations class="mb-6" />

        <!-- Products Grid -->
        <div class="bg-white rounded-xl shadow-md p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-900">
              {{ selectedSubVertical ? getSubVerticalLabel(selectedSubVertical) : 'Все товары' }}
            </h2>
            <p class="text-sm text-gray-500">{{ filteredProducts.length }} товаров</p>
          </div>

          <div v-if="filteredProducts.length > 0" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <ProductCard
              v-for="product in paginatedProducts"
              :key="product.id"
              :product="product"
              @view-details="handleViewDetails"
              @edit-product="handleEditProduct"
            />
          </div>

          <!-- Empty State -->
          <div v-else class="text-center py-12">
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
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Search, Package } from 'lucide-vue-next'
import ProductCard from './ProductCard.vue'
import AIRecommendations from './AIRecommendations.vue'
import { useSupermarketApi } from '@/composables/useSupermarketApi'

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

interface SubVertical {
  id: string
  name: string
}

const api = useSupermarketApi()

const products = ref<Product[]>([])
const searchQuery = ref('')
const selectedSubVertical = ref('')
const priceRange = ref({ min: 0, max: 0 })
const coldChainOnly = ref(false)
const sortBy = ref('popular')
const currentPage = ref(1)
const itemsPerPage = 12

const subVerticals: SubVertical[] = [
  { id: 'meat_shops', name: 'Мясные магазины' },
  { id: 'farm_direct', name: 'Фермерские продукты' },
  { id: 'vegan_products', name: 'Веганские продукты' },
  { id: 'confectionery', name: 'Кондитерские изделия' },
  { id: 'grocery_and_delivery', name: 'Бакалея и доставка' },
  { id: 'food', name: 'Еда' },
  { id: 'office_catering', name: 'Офисный кейтеринг' }
]

const filteredProducts = computed(() => {
  let filtered = [...products.value]

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

  if (priceRange.value.min > 0) {
    filtered = filtered.filter(p => p.price >= priceRange.value.min)
  }

  if (priceRange.value.max > 0) {
    filtered = filtered.filter(p => p.price <= priceRange.value.max)
  }

  if (coldChainOnly.value) {
    filtered = filtered.filter(p => p.requires_cold_chain)
  }

  // Sort
  switch (sortBy.value) {
    case 'price_asc':
      filtered.sort((a, b) => a.price - b.price)
      break
    case 'price_desc':
      filtered.sort((a, b) => b.price - a.price)
      break
    case 'newest':
      filtered.sort((a, b) => b.id.localeCompare(a.id))
      break
    default:
      // popular or rating - default order
      break
  }

  return filtered
})

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredProducts.value.slice(start, start + itemsPerPage)
})

const totalPages = computed(() => {
  return Math.ceil(filteredProducts.value.length / itemsPerPage)
})

const getSubVerticalLabel = (id: string) => {
  const sv = subVerticals.find(s => s.id === id)
  return sv?.name || id
}

const selectSubVertical = (id: string) => {
  selectedSubVertical.value = selectedSubVertical.value === id ? '' : id
}

const toggleSubVertical = (id: string) => {
  selectedSubVertical.value = selectedSubVertical.value === id ? '' : id
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedSubVertical.value = ''
  priceRange.value = { min: 0, max: 0 }
  coldChainOnly.value = false
  sortBy.value = 'popular'
  currentPage.value = 1
}

const handleViewDetails = (product: Product) => {
  window.location.href = `/supermarket/product/${product.id}`
}

const handleEditProduct = (product: Product) => {
  // Only for sellers
  console.log('Edit product:', product.id)
}

const loadProducts = async () => {
  await api.fetchProducts()
  products.value = api.products
}

onMounted(() => {
  loadProducts()
})
</script>
