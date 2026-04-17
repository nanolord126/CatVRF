<template>
  <div class="electronics-search-filter">
    <!-- Search Header -->
    <div class="search-header">
      <div class="search-input-wrapper">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Поиск по названию, бренду, SKU..."
          class="search-input"
          @input="debouncedSearch"
          @focus="showSuggestions = true"
          @blur="hideSuggestions"
        />
        <button @click="clearSearch" class="clear-btn" v-if="searchQuery">×</button>
      </div>
      
      <!-- Suggestions Dropdown -->
      <div v-if="showSuggestions && suggestions.length > 0" class="suggestions-dropdown">
        <div
          v-for="suggestion in suggestions"
          :key="suggestion.id"
          class="suggestion-item"
          @click="selectSuggestion(suggestion)"
        >
          <div class="suggestion-info">
            <span class="suggestion-name">{{ suggestion.name }}</span>
            <span class="suggestion-brand">{{ suggestion.brand }}</span>
          </div>
          <span class="suggestion-price">{{ formatCurrency(suggestion.price_kopecks / 100) }}</span>
        </div>
      </div>

      <!-- Popular Searches -->
      <div v-if="!searchQuery && popularSearches.length > 0" class="popular-searches">
        <span class="popular-label">Популярное:</span>
        <button
          v-for="term in popularSearches"
          :key="term"
          @click="searchQuery = term; debouncedSearch()"
          class="popular-tag"
        >
          {{ term }}
        </button>
      </div>
    </div>

    <!-- Filters Panel -->
    <div class="filters-panel">
      <div class="filters-header">
        <h3>Фильтры</h3>
        <button @click="resetFilters" class="reset-btn">Сбросить</button>
      </div>

      <!-- Accordion Filters -->
      <div class="filters-accordion">
        <!-- Price Range -->
        <div class="filter-section">
          <button @click="toggleSection('price')" class="section-header">
            <span>Цена</span>
            <span class="toggle-icon">{{ openSections.price ? '−' : '+' }}</span>
          </button>
          <div v-if="openSections.price" class="section-content">
            <div class="price-inputs">
              <input
                v-model.number="filters.minPrice"
                type="number"
                placeholder="От"
                class="price-input"
                @input="applyFilters"
              />
              <span class="price-separator">—</span>
              <input
                v-model.number="filters.maxPrice"
                type="number"
                placeholder="До"
                class="price-input"
                @input="applyFilters"
              />
            </div>
            <div class="price-ranges">
              <button
                v-for="(count, range) in availableFilters.priceRanges"
                :key="range"
                @click="setPriceRange(range)"
                class="price-range-btn"
                :class="{ active: isPriceRangeActive(range) }"
              >
                {{ formatPriceRange(range) }} ({{ count }})
              </button>
            </div>
          </div>
        </div>

        <!-- Brands -->
        <div class="filter-section">
          <button @click="toggleSection('brands')" class="section-header">
            <span>Бренды</span>
            <span class="toggle-icon">{{ openSections.brands ? '−' : '+' }}</span>
          </button>
          <div v-if="openSections.brands" class="section-content">
            <input
              v-model="brandSearch"
              type="text"
              placeholder="Поиск бренда..."
              class="filter-search"
            />
            <div class="filter-checkboxes">
              <label
                v-for="(count, brand) in filteredBrands"
                :key="brand"
                class="checkbox-label"
              >
                <input
                  type="checkbox"
                  :value="brand"
                  v-model="filters.brands"
                  @change="applyFilters"
                />
                <span class="checkbox-text">{{ brand }}</span>
                <span class="checkbox-count">({{ count }})</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Categories -->
        <div class="filter-section">
          <button @click="toggleSection('categories')" class="section-header">
            <span>Категории</span>
            <span class="toggle-icon">{{ openSections.categories ? '−' : '+' }}</span>
          </button>
          <div v-if="openSections.categories" class="section-content">
            <div class="filter-checkboxes">
              <label
                v-for="(count, category) in availableFilters.categories"
                :key="category"
                class="checkbox-label"
              >
                <input
                  type="checkbox"
                  :value="category"
                  v-model="filters.categories"
                  @change="applyFilters"
                />
                <span class="checkbox-text">{{ category }}</span>
                <span class="checkbox-count">({{ count }})</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Colors -->
        <div class="filter-section">
          <button @click="toggleSection('colors')" class="section-header">
            <span>Цвет</span>
            <span class="toggle-icon">{{ openSections.colors ? '−' : '+' }}</span>
          </button>
          <div v-if="openSections.colors" class="section-content">
            <div class="color-options">
              <button
                v-for="(count, color) in availableFilters.colors"
                :key="color"
                @click="toggleColor(color)"
                class="color-btn"
                :class="{ active: filters.colors.includes(color) }"
                :title="color"
              >
                <div class="color-swatch" :style="{ backgroundColor: getColorHex(color) }"></div>
                <span class="color-count">{{ count }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Technical Specs -->
        <div class="filter-section">
          <button @click="toggleSection('specs')" class="section-header">
            <span>Технические характеристики</span>
            <span class="toggle-icon">{{ openSections.specs ? '−' : '+' }}</span>
          </button>
          <div v-if="openSections.specs" class="section-content">
            <div v-for="(values, specKey) in availableFilters.specs" :key="specKey" class="spec-group">
              <h4 class="spec-title">{{ formatSpecKey(specKey) }}</h4>
              <div class="filter-checkboxes">
                <label
                  v-for="(count, value) in values"
                  :key="value"
                  class="checkbox-label"
                >
                  <input
                    type="checkbox"
                    :value="value"
                    v-model="filters.specs[specKey]"
                    @change="applyFilters"
                  />
                  <span class="checkbox-text">{{ value }}</span>
                  <span class="checkbox-count">({{ count }})</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Availability -->
        <div class="filter-section">
          <button @click="toggleSection('availability')" class="section-header">
            <span>Наличие</span>
            <span class="toggle-icon">{{ openSections.availability ? '−' : '+' }}</span>
          </button>
          <div v-if="openSections.availability" class="section-content">
            <label class="checkbox-label">
              <input
                type="checkbox"
                v-model="filters.inStockOnly"
                @change="applyFilters"
              />
              <span class="checkbox-text">Только в наличии</span>
            </label>
            <label class="checkbox-label">
              <input
                type="checkbox"
                v-model="filters.withDiscount"
                @change="applyFilters"
              />
              <span class="checkbox-text">Со скидкой</span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Active Filters -->
    <div v-if="hasActiveFilters" class="active-filters">
      <span class="active-label">Активные фильтры:</span>
      <div class="active-tags">
        <span
          v-for="brand in filters.brands"
          :key="`brand-${brand}`"
          class="active-tag"
          @click="removeFilter('brands', brand)"
        >
          {{ brand }} ×
        </span>
        <span
          v-for="category in filters.categories"
          :key="`category-${category}`"
          class="active-tag"
          @click="removeFilter('categories', category)"
        >
          {{ category }} ×
        </span>
        <span
          v-for="color in filters.colors"
          :key="`color-${color}`"
          class="active-tag"
          @click="removeFilter('colors', color)"
        >
          {{ color }} ×
        </span>
        <span
          v-if="filters.minPrice || filters.maxPrice"
          class="active-tag"
          @click="filters.minPrice = null; filters.maxPrice = null; applyFilters()"
        >
          Цена: {{ filters.minPrice ? formatCurrency(filters.minPrice) : '0' }} — {{ filters.maxPrice ? formatCurrency(filters.maxPrice) : '∞' }} ×
        </span>
      </div>
    </div>

    <!-- Sort Options -->
    <div class="sort-options">
      <span class="sort-label">Сортировка:</span>
      <select v-model="sort.field" @change="applyFilters" class="sort-select">
        <option value="relevance">По релевантности</option>
        <option value="price">По цене</option>
        <option value="rating">По рейтингу</option>
        <option value="reviews">По отзывам</option>
        <option value="newest">Новинки</option>
        <option value="popularity">По популярности</option>
        <option value="discount">По скидке</option>
      </select>
      <button
        @click="toggleSortDirection"
        class="sort-direction-btn"
        :title="sort.direction === 'asc' ? 'По возрастанию' : 'По убыванию'"
      >
        {{ sort.direction === 'asc' ? '↑' : '↓' }}
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Загрузка...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'

interface FilterDto {
  brands: Record<string, number>
  categories: Record<string, number>
  colors: Record<string, number>
  specs: Record<string, Record<string, number>>
  priceRanges: Record<string, number>
}

interface Product {
  id: number
  name: string
  brand: string
  category: string
  price_kopecks: number
}

const emit = defineEmits<{
  search: [params: any]
  filterChange: [filters: any]
}>()

const searchQuery = ref('')
const suggestions = ref<Product[]>([])
const showSuggestions = ref(false)
const popularSearches = ref<string[]>([])
const loading = ref(false)
const brandSearch = ref('')

const openSections = ref({
  price: true,
  brands: true,
  categories: false,
  colors: false,
  specs: false,
  availability: false,
})

const filters = ref({
  brands: [] as string[],
  categories: [] as string[],
  colors: [] as string[],
  specs: {} as Record<string, string[]>,
  minPrice: null as number | null,
  maxPrice: null as number | null,
  inStockOnly: false,
  withDiscount: false,
})

const sort = ref({
  field: 'relevance',
  direction: 'desc',
})

const availableFilters = ref<FilterDto>({
  brands: {},
  categories: {},
  colors: {},
  specs: {},
  priceRanges: {},
})

const filteredBrands = computed(() => {
  if (!brandSearch.value) return availableFilters.value.brands
  const search = brandSearch.value.toLowerCase()
  return Object.fromEntries(
    Object.entries(availableFilters.value.brands).filter(([brand]) =>
      brand.toLowerCase().includes(search)
    )
  )
})

const hasActiveFilters = computed(() => {
  return (
    filters.value.brands.length > 0 ||
    filters.value.categories.length > 0 ||
    filters.value.colors.length > 0 ||
    filters.value.minPrice !== null ||
    filters.value.maxPrice !== null ||
    filters.value.inStockOnly ||
    filters.value.withDiscount ||
    Object.keys(filters.value.specs).length > 0
  )
})

let searchTimeout: NodeJS.Timeout

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchSuggestions()
    applyFilters()
  }, 300)
}

const clearSearch = () => {
  searchQuery.value = ''
  suggestions.value = []
  applyFilters()
}

const hideSuggestions = () => {
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

const selectSuggestion = (suggestion: Product) => {
  searchQuery.value = suggestion.name
  showSuggestions.value = false
  applyFilters()
}

const fetchSuggestions = async () => {
  if (searchQuery.value.length < 2) {
    suggestions.value = []
    return
  }

  try {
    const response = await fetch(`/api/electronics/search/suggestions?query=${encodeURIComponent(searchQuery.value)}&limit=8`)
    const data = await response.json()
    suggestions.value = data.suggestions || []
  } catch (error) {
    console.error('Failed to fetch suggestions:', error)
  }
}

const fetchPopularSearches = async () => {
  try {
    const response = await fetch('/api/electronics/search/popular')
    const data = await response.json()
    popularSearches.value = data.popular_searches || []
  } catch (error) {
    console.error('Failed to fetch popular searches:', error)
  }
}

const fetchAvailableFilters = async () => {
  try {
    const response = await fetch('/api/electronics/search/filters')
    const data = await response.json()
    availableFilters.value = data
  } catch (error) {
    console.error('Failed to fetch filters:', error)
  }
}

const applyFilters = () => {
  const params = {
    query: searchQuery.value,
    page: 1,
    per_page: 20,
    min_price: filters.value.minPrice,
    max_price: filters.value.maxPrice,
    brands: filters.value.brands,
    categories: filters.value.categories,
    colors: filters.value.colors,
    specs: filters.value.specs,
    in_stock_only: filters.value.inStockOnly || undefined,
    with_discount: filters.value.withDiscount || undefined,
    sort: sort.value,
  }

  emit('search', params)
  emit('filterChange', filters.value)
}

const toggleSection = (section: keyof typeof openSections.value) => {
  openSections.value[section] = !openSections.value[section]
}

const toggleColor = (color: string) => {
  const index = filters.value.colors.indexOf(color)
  if (index > -1) {
    filters.value.colors.splice(index, 1)
  } else {
    filters.value.colors.push(color)
  }
  applyFilters()
}

const removeFilter = (type: string, value: string) => {
  if (type === 'brands') {
    filters.value.brands = filters.value.brands.filter(b => b !== value)
  } else if (type === 'categories') {
    filters.value.categories = filters.value.categories.filter(c => c !== value)
  } else if (type === 'colors') {
    filters.value.colors = filters.value.colors.filter(c => c !== value)
  }
  applyFilters()
}

const resetFilters = () => {
  filters.value = {
    brands: [],
    categories: [],
    colors: [],
    specs: {},
    minPrice: null,
    maxPrice: null,
    inStockOnly: false,
    withDiscount: false,
  }
  searchQuery.value = ''
  sort.value = { field: 'relevance', direction: 'desc' }
  applyFilters()
}

const setPriceRange = (range: string) => {
  const [min, max] = range.split('-').map(Number)
  filters.value.minPrice = min / 100
  filters.value.maxPrice = max / 100
  applyFilters()
}

const isPriceRangeActive = (range: string): boolean => {
  const [min, max] = range.split('-').map(Number)
  return filters.value.minPrice === min / 100 && filters.value.maxPrice === max / 100
}

const toggleSortDirection = () => {
  sort.value.direction = sort.value.direction === 'asc' ? 'desc' : 'asc'
  applyFilters()
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    maximumFractionDigits: 0,
  }).format(amount)
}

const formatPriceRange = (range: string): string => {
  const [min, max] = range.split('-').map(v => Number(v) / 100)
  return `${formatCurrency(min)} — ${formatCurrency(max)}`
}

const formatSpecKey = (key: string): string => {
  const translations: Record<string, string> = {
    screen_size: 'Размер экрана',
    ram: 'Оперативная память',
    storage: 'Память',
    cpu: 'Процессор',
    battery: 'Батарея',
    camera: 'Камера',
    os: 'Операционная система',
  }
  return translations[key] || key
}

const getColorHex = (color: string): string => {
  const colorMap: Record<string, string> = {
    'black': '#000000',
    'white': '#ffffff',
    'red': '#ef4444',
    'blue': '#3b82f6',
    'green': '#10b981',
    'yellow': '#f59e0b',
    'gray': '#6b7280',
    'silver': '#c0c0c0',
    'gold': '#ffd700',
    'pink': '#ec4899',
    'purple': '#8b5cf6',
  }
  return colorMap[color.toLowerCase()] || '#cccccc'
}

onMounted(() => {
  fetchPopularSearches()
  fetchAvailableFilters()
})
</script>

<style scoped>
.electronics-search-filter {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.search-header {
  position: relative;
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-input {
  width: 100%;
  padding: 12px 40px 12px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.clear-btn {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  color: #6b7280;
}

.clear-btn:hover {
  color: #1f2937;
}

.suggestions-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  margin-top: 4px;
  z-index: 100;
  max-height: 300px;
  overflow-y: auto;
}

.suggestion-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  cursor: pointer;
  transition: background 0.2s;
}

.suggestion-item:hover {
  background: #f3f4f6;
}

.suggestion-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.suggestion-name {
  font-weight: 500;
  color: #1f2937;
}

.suggestion-brand {
  font-size: 12px;
  color: #6b7280;
}

.suggestion-price {
  font-weight: 600;
  color: #3b82f6;
}

.popular-searches {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  margin-top: 12px;
}

.popular-label {
  font-size: 13px;
  color: #6b7280;
}

.popular-tag {
  padding: 6px 12px;
  background: #f3f4f6;
  border: none;
  border-radius: 16px;
  font-size: 13px;
  cursor: pointer;
  transition: background 0.2s;
}

.popular-tag:hover {
  background: #e5e7eb;
}

.filters-panel {
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.filters-header h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.reset-btn {
  background: none;
  border: none;
  color: #3b82f6;
  font-size: 14px;
  cursor: pointer;
}

.reset-btn:hover {
  text-decoration: underline;
}

.filters-accordion {
  max-height: 500px;
  overflow-y: auto;
}

.filter-section {
  border-bottom: 1px solid #e5e7eb;
}

.section-header {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 16px;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background 0.2s;
}

.section-header:hover {
  background: #f9fafb;
}

.toggle-icon {
  font-size: 18px;
  color: #6b7280;
}

.section-content {
  padding: 12px 16px;
  background: #f9fafb;
}

.price-inputs {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
}

.price-input {
  width: 100px;
  padding: 8px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 14px;
}

.price-separator {
  color: #6b7280;
}

.price-ranges {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.price-range-btn {
  padding: 6px 12px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.price-range-btn:hover {
  border-color: #3b82f6;
}

.price-range-btn.active {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.filter-search {
  width: 100%;
  padding: 8px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 13px;
  margin-bottom: 12px;
}

.filter-checkboxes {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 200px;
  overflow-y: auto;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 13px;
}

.checkbox-text {
  flex: 1;
}

.checkbox-count {
  color: #6b7280;
  font-size: 12px;
}

.color-options {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.color-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  background: none;
  border: 2px solid transparent;
  border-radius: 8px;
  padding: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.color-btn:hover {
  border-color: #d1d5db;
}

.color-btn.active {
  border-color: #3b82f6;
  background: #eff6ff;
}

.color-swatch {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1px solid #e5e7eb;
}

.color-count {
  font-size: 11px;
  color: #6b7280;
}

.spec-group {
  margin-bottom: 16px;
}

.spec-title {
  margin: 0 0 8px 0;
  font-size: 13px;
  font-weight: 600;
  color: #374151;
}

.active-filters {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: #f0f9ff;
  border-radius: 8px;
}

.active-label {
  font-size: 13px;
  font-weight: 500;
  color: #1f2937;
}

.active-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.active-tag {
  padding: 4px 10px;
  background: #3b82f6;
  color: white;
  border-radius: 16px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.2s;
}

.active-tag:hover {
  background: #2563eb;
}

.sort-options {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.sort-label {
  font-size: 14px;
  font-weight: 500;
}

.sort-select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 14px;
  cursor: pointer;
}

.sort-direction-btn {
  padding: 8px 12px;
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: background 0.2s;
}

.sort-direction-btn:hover {
  background: #e5e7eb;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  gap: 12px;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.loading-state p {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
}
</style>
