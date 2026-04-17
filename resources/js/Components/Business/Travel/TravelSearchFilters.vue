<template>
  <div class="travel-search-filters">
    <div class="filter-section">
      <input 
        v-model="localSearchQuery" 
        type="text" 
        placeholder="Search..." 
        class="search-input"
        @input="emitUpdate"
      />
    </div>

    <div class="filter-section">
      <select v-model="localStatusFilter" class="filter-select" @change="emitUpdate">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="confirmed">Confirmed</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <div class="filter-section" v-if="showTypeFilter">
      <select v-model="localTypeFilter" class="filter-select" @change="emitUpdate">
        <option value="">All Types</option>
        <option value="flight">Flight</option>
        <option value="hotel">Hotel</option>
        <option value="tour">Tour</option>
        <option value="car_rental">Car Rental</option>
      </select>
    </div>

    <div class="filter-section" v-if="showCountryFilter">
      <select v-model="localCountryFilter" class="filter-select" @change="emitUpdate">
        <option value="">All Countries</option>
        <option v-for="country in countries" :key="country" :value="country">
          {{ country }}
        </option>
      </select>
    </div>

    <div class="filter-section" v-if="showPriceRange">
      <div class="price-range">
        <input 
          v-model="localMinPrice" 
          type="number" 
          placeholder="Min" 
          class="price-input"
          @input="emitUpdate"
        />
        <span>-</span>
        <input 
          v-model="localMaxPrice" 
          type="number" 
          placeholder="Max" 
          class="price-input"
          @input="emitUpdate"
        />
      </div>
    </div>

    <div class="filter-section" v-if="showDateRange">
      <div class="date-range">
        <input 
          v-model="localStartDate" 
          type="date" 
          class="date-input"
          @change="emitUpdate"
        />
        <span>to</span>
        <input 
          v-model="localEndDate" 
          type="date" 
          class="date-input"
          @change="emitUpdate"
        />
      </div>
    </div>

    <div class="filter-section" v-if="showCheckboxFilters">
      <label class="checkbox-filter">
        <input type="checkbox" v-model="localPopularOnly" @change="emitUpdate" />
        Popular Only
      </label>
      <label class="checkbox-filter">
        <input type="checkbox" v-model="localHighRatedOnly" @change="emitUpdate" />
        High Rated (4.5+)
      </label>
    </div>

    <button @click="resetFilters" class="btn-reset">Reset</button>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

interface Props {
  searchQuery?: string
  statusFilter?: string
  typeFilter?: string
  countryFilter?: string
  minPrice?: number | null
  maxPrice?: number | null
  startDate?: string
  endDate?: string
  popularOnly?: boolean
  highRatedOnly?: boolean
  countries?: string[]
  showTypeFilter?: boolean
  showCountryFilter?: boolean
  showPriceRange?: boolean
  showDateRange?: boolean
  showCheckboxFilters?: boolean
}

interface Emits {
  (e: 'update:searchQuery', value: string): void
  (e: 'update:statusFilter', value: string): void
  (e: 'update:typeFilter', value: string): void
  (e: 'update:countryFilter', value: string): void
  (e: 'update:minPrice', value: number | null): void
  (e: 'update:maxPrice', value: number | null): void
  (e: 'update:startDate', value: string): void
  (e: 'update:endDate', value: string): void
  (e: 'update:popularOnly', value: boolean): void
  (e: 'update:highRatedOnly', value: boolean): void
  (e: 'reset'): void
}

const props = withDefaults(defineProps<Props>(), {
  searchQuery: '',
  statusFilter: '',
  typeFilter: '',
  countryFilter: '',
  minPrice: null,
  maxPrice: null,
  startDate: '',
  endDate: '',
  popularOnly: false,
  highRatedOnly: false,
  countries: () => [],
  showTypeFilter: true,
  showCountryFilter: false,
  showPriceRange: true,
  showDateRange: true,
  showCheckboxFilters: false,
})

const emit = defineEmits<Emits>()

const localSearchQuery = ref(props.searchQuery)
const localStatusFilter = ref(props.statusFilter)
const localTypeFilter = ref(props.typeFilter)
const localCountryFilter = ref(props.countryFilter)
const localMinPrice = ref<number | null>(props.minPrice)
const localMaxPrice = ref<number | null>(props.maxPrice)
const localStartDate = ref(props.startDate)
const localEndDate = ref(props.endDate)
const localPopularOnly = ref(props.popularOnly)
const localHighRatedOnly = ref(props.highRatedOnly)

const emitUpdate = () => {
  emit('update:searchQuery', localSearchQuery.value)
  emit('update:statusFilter', localStatusFilter.value)
  emit('update:typeFilter', localTypeFilter.value)
  emit('update:countryFilter', localCountryFilter.value)
  emit('update:minPrice', localMinPrice.value)
  emit('update:maxPrice', localMaxPrice.value)
  emit('update:startDate', localStartDate.value)
  emit('update:endDate', localEndDate.value)
  emit('update:popularOnly', localPopularOnly.value)
  emit('update:highRatedOnly', localHighRatedOnly.value)
}

const resetFilters = () => {
  localSearchQuery.value = ''
  localStatusFilter.value = ''
  localTypeFilter.value = ''
  localCountryFilter.value = ''
  localMinPrice.value = null
  localMaxPrice.value = null
  localStartDate.value = ''
  localEndDate.value = ''
  localPopularOnly.value = false
  localHighRatedOnly.value = false
  
  emit('reset')
  emitUpdate()
}

watch(() => props.searchQuery, (val) => localSearchQuery.value = val)
watch(() => props.statusFilter, (val) => localStatusFilter.value = val)
watch(() => props.typeFilter, (val) => localTypeFilter.value = val)
watch(() => props.countryFilter, (val) => localCountryFilter.value = val)
watch(() => props.minPrice, (val) => localMinPrice.value = val)
watch(() => props.maxPrice, (val) => localMaxPrice.value = val)
watch(() => props.startDate, (val) => localStartDate.value = val)
watch(() => props.endDate, (val) => localEndDate.value = val)
watch(() => props.popularOnly, (val) => localPopularOnly.value = val)
watch(() => props.highRatedOnly, (val) => localHighRatedOnly.value = val)
</script>

<style scoped>
.travel-search-filters {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  align-items: center;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.filter-section {
  display: flex;
  align-items: center;
}

.search-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  min-width: 250px;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  min-width: 150px;
}

.price-range {
  display: flex;
  align-items: center;
  gap: 8px;
}

.price-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  width: 100px;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 8px;
}

.date-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.checkbox-filter {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  cursor: pointer;
  margin-right: 16px;
}

.btn-reset {
  padding: 8px 16px;
  background: #6b7280;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
}

.btn-reset:hover {
  background: #4b5563;
}
</style>
