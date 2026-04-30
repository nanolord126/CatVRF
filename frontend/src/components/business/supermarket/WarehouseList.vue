<template>
  <div class="warehouse-list">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">Склады</h2>
      <div class="flex gap-2">
        <select v-model="selectedType" class="px-4 py-2 border rounded-lg">
          <option value="">Все типы</option>
          <option value="b2b">B2B</option>
          <option value="b2c">B2C</option>
          <option value="mixed">Смешанный</option>
        </select>
        <button
          @click="showCreateModal = true"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
        >
          + Добавить склад
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Всего складов</div>
        <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">B2B</div>
        <div class="text-2xl font-bold text-blue-600">{{ stats.b2b }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">B2C</div>
        <div class="text-2xl font-bold text-green-600">{{ stats.b2c }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-600">Смешанные</div>
        <div class="text-2xl font-bold text-purple-600">{{ stats.mixed }}</div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-2 text-gray-600">Загрузка...</p>
    </div>

    <!-- Empty state -->
    <div v-else-if="filteredWarehouses.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
      </svg>
      <p class="mt-2 text-gray-600">Нет складов</p>
      <button
        @click="showCreateModal = true"
        class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
      >
        Создать первый склад
      </button>
    </div>

    <!-- Warehouse list -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="warehouse in filteredWarehouses"
        :key="warehouse.id"
        class="bg-white border rounded-lg p-6 hover:shadow-md transition cursor-pointer"
        @click="viewWarehouse(warehouse.id)"
      >
        <div class="flex justify-between items-start mb-4">
          <div>
            <span
              :class="[
                'px-2 py-1 text-xs font-medium rounded',
                getTypeClass(warehouse.type)
              ]"
            >
              {{ getTypeLabel(warehouse.type) }}
            </span>
            <span
              :class="[
                'ml-2 px-2 py-1 text-xs font-medium rounded',
                getStatusClass(warehouse.status)
              ]"
            >
              {{ getStatusLabel(warehouse.status) }}
            </span>
          </div>
        </div>

        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ warehouse.name }}</h3>
        
        <div class="space-y-2 text-sm text-gray-600">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>{{ warehouse.city }}, {{ warehouse.region }}</span>
          </div>
          
          <div v-if="warehouse.area" class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
            </svg>
            <span>{{ warehouse.area }} м²</span>
          </div>

          <div v-if="warehouse.capacity" class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
            </svg>
            <span>{{ warehouse.capacity }} ед.</span>
          </div>
        </div>

        <div class="mt-4 flex gap-2">
          <span
            v-if="warehouse.has_cold_storage"
            class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs"
          >
            Холодильник
          </span>
          <span
            v-if="warehouse.has_freezer"
            class="px-2 py-1 bg-cyan-100 text-cyan-800 rounded text-xs"
          >
            Морозилка
          </span>
        </div>
      </div>
    </div>

    <!-- Create modal -->
    <WarehouseCreate
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="onWarehouseCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Warehouse {
  id: number
  name: string
  type: 'b2b' | 'b2c' | 'mixed'
  status: 'active' | 'inactive' | 'maintenance'
  city: string
  region: string
  address: string
  area: number | null
  capacity: number | null
  has_cold_storage: boolean
  has_freezer: boolean
}

const warehouses = ref<Warehouse[]>([])
const loading = ref(false)
const selectedType = ref<string>('')
const showCreateModal = ref(false)

const stats = ref({
  total: 0,
  b2b: 0,
  b2c: 0,
  mixed: 0
})

const filteredWarehouses = computed(() => {
  if (!selectedType.value) return warehouses.value
  return warehouses.value.filter(w => w.type === selectedType.value)
})

const getTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    b2b: 'B2B',
    b2c: 'B2C',
    mixed: 'Смешанный'
  }
  return labels[type] || type
}

const getTypeClass = (type: string) => {
  const classes: Record<string, string> = {
    b2b: 'bg-blue-100 text-blue-800',
    b2c: 'bg-green-100 text-green-800',
    mixed: 'bg-purple-100 text-purple-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    active: 'Активен',
    inactive: 'Неактивен',
    maintenance: 'На обслуживании'
  }
  return labels[status] || status
}

const getStatusClass = (status: string) => {
  const classes: Record<string, string> = {
    active: 'bg-green-100 text-green-800',
    inactive: 'bg-gray-100 text-gray-800',
    maintenance: 'bg-yellow-100 text-yellow-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const viewWarehouse = (id: number) => {
  // Navigate to warehouse details
  console.log('View warehouse:', id)
}

const onWarehouseCreated = () => {
  showCreateModal.value = false
  loadWarehouses()
}

const loadWarehouses = async () => {
  loading.value = true
  try {
    // API call to load warehouses
    // const response = await api.get('/supermarket/warehouses')
    // warehouses.value = response.data
    
    // Mock data for now
    warehouses.value = []
  } catch (error) {
    console.error('Error loading warehouses:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadWarehouses()
})
</script>
