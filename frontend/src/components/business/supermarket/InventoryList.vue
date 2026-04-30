<template>
  <div class="inventory-list">
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Поиск</label>
          <input 
            v-model="searchQuery"
            type="text"
            placeholder="Название товара или SKU..."
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
          <select 
            v-model="selectedStatus"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">Все</option>
            <option value="low_stock">Мало на складе</option>
            <option value="expiring_soon">Скоро истекает</option>
            <option value="expired">Просрочено</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Местоположение</label>
          <input 
            v-model="locationFilter"
            type="text"
            placeholder="Место хранения..."
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Партия</label>
          <input 
            v-model="batchFilter"
            type="text"
            placeholder="Номер партии..."
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
          />
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-xl shadow-md p-4">
        <p class="text-sm text-gray-500 mb-1">Всего позиций</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-md p-4">
        <p class="text-sm text-gray-500 mb-1">Мало на складе</p>
        <p class="text-2xl font-bold text-red-600">{{ stats.lowStock }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-md p-4">
        <p class="text-sm text-gray-500 mb-1">Скоро истекает</p>
        <p class="text-2xl font-bold text-yellow-600">{{ stats.expiringSoon }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-md p-4">
        <p class="text-sm text-gray-500 mb-1">Просрочено</p>
        <p class="text-2xl font-bold text-red-600">{{ stats.expired }}</p>
      </div>
    </div>

    <!-- Bulk Actions -->
    <div v-if="selectedItems.length > 0" class="bg-indigo-50 rounded-xl p-4 mb-6 flex items-center justify-between">
      <span class="text-sm font-medium text-indigo-900">
        Выбрано: {{ selectedItems.length }}
      </span>
      <div class="flex gap-2">
        <button 
          @click="bulkUpdateQuantity"
          class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Обновить количество
        </button>
        <button 
          @click="bulkResetReservations"
          class="bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Сбросить резервации
        </button>
        <button 
          @click="clearSelection"
          class="bg-gray-200 hover:bg-gray-300 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Отмена
        </button>
      </div>
    </div>

    <!-- Inventory Grid -->
    <div v-if="filteredInventory.length > 0" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
      <div 
        v-for="item in paginatedInventory"
        :key="item.inventory.id"
        class="relative"
      >
        <input 
          type="checkbox"
          :checked="selectedItems.includes(item.inventory.id)"
          @change="toggleSelection(item.inventory.id)"
          class="absolute top-4 left-4 w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 z-10"
        />
        <InventoryCard
          :inventory="item.inventory"
          :product="item.product"
          @edit-inventory="handleEditInventory"
          @adjust-quantity="handleAdjustQuantity"
        />
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white rounded-xl shadow-md p-12 text-center">
      <Package class="w-16 h-16 text-gray-400 mx-auto mb-4" />
      <h3 class="text-lg font-semibold text-gray-900 mb-2">Инвентарь не найден</h3>
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
import InventoryCard from './InventoryCard.vue'

interface InventoryItem {
  id: string
  quantity: number
  reserved: number
  batch_number?: string
  location?: string
  expires_at?: string
}

interface Product {
  id: string
  name: string
  sku?: string
}

interface InventoryWithProduct {
  inventory: InventoryItem
  product: Product
}

const props = defineProps<{
  inventoryItems: InventoryWithProduct[]
}>()

const emit = defineEmits<{
  editInventory: [inventory: InventoryItem]
  adjustQuantity: [inventory: InventoryItem]
  bulkUpdateQuantity: [items: string[]]
  bulkResetReservations: [items: string[]]
}>()

const searchQuery = ref('')
const selectedStatus = ref('')
const locationFilter = ref('')
const batchFilter = ref('')
const selectedItems = ref<string[]>([])
const currentPage = ref(1)
const itemsPerPage = 12

const filteredInventory = computed(() => {
  let filtered = [...props.inventoryItems]

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(item => 
      item.product.name.toLowerCase().includes(query) ||
      item.product.sku?.toLowerCase().includes(query)
    )
  }

  if (locationFilter.value) {
    filtered = filtered.filter(item => 
      item.inventory.location?.toLowerCase().includes(locationFilter.value.toLowerCase())
    )
  }

  if (batchFilter.value) {
    filtered = filtered.filter(item => 
      item.inventory.batch_number?.toLowerCase().includes(batchFilter.value.toLowerCase())
    )
  }

  if (selectedStatus.value) {
    const available = (item: InventoryWithProduct) => item.inventory.quantity - item.inventory.reserved
    const now = new Date()
    const sevenDaysFromNow = new Date()
    sevenDaysFromNow.setDate(sevenDaysFromNow.getDate() + 7)

    filtered = filtered.filter(item => {
      if (selectedStatus.value === 'low_stock') {
        return available(item) <= 10
      }
      if (selectedStatus.value === 'expiring_soon' && item.inventory.expires_at) {
        const expiry = new Date(item.inventory.expires_at)
        return expiry <= sevenDaysFromNow && expiry > now
      }
      if (selectedStatus.value === 'expired' && item.inventory.expires_at) {
        return new Date(item.inventory.expires_at) < now
      }
      return true
    })
  }

  return filtered
})

const paginatedInventory = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredInventory.value.slice(start, start + itemsPerPage)
})

const totalPages = computed(() => {
  return Math.ceil(filteredInventory.value.length / itemsPerPage)
})

const stats = computed(() => {
  const now = new Date()
  const sevenDaysFromNow = new Date()
  sevenDaysFromNow.setDate(sevenDaysFromNow.getDate() + 7)

  return {
    total: props.inventoryItems.length,
    lowStock: props.inventoryItems.filter(item => (item.inventory.quantity - item.inventory.reserved) <= 10).length,
    expiringSoon: props.inventoryItems.filter(item => {
      if (!item.inventory.expires_at) return false
      const expiry = new Date(item.inventory.expires_at)
      return expiry <= sevenDaysFromNow && expiry > now
    }).length,
    expired: props.inventoryItems.filter(item => {
      if (!item.inventory.expires_at) return false
      return new Date(item.inventory.expires_at) < now
    }).length
  }
})

const toggleSelection = (id: string) => {
  const index = selectedItems.value.indexOf(id)
  if (index > -1) {
    selectedItems.value.splice(index, 1)
  } else {
    selectedItems.value.push(id)
  }
}

const clearSelection = () => {
  selectedItems.value = []
}

const handleEditInventory = (inventory: InventoryItem) => {
  emit('editInventory', inventory)
}

const handleAdjustQuantity = (inventory: InventoryItem) => {
  emit('adjustQuantity', inventory)
}

const bulkUpdateQuantity = () => {
  emit('bulkUpdateQuantity', selectedItems.value)
}

const bulkResetReservations = () => {
  emit('bulkResetReservations', selectedItems.value)
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = ''
  locationFilter.value = ''
  batchFilter.value = ''
  currentPage.value = 1
}
</script>
