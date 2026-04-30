<template>
  <div class="inventory-item-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">{{ item.name }}</h3>
          <p class="text-sm text-gray-500">{{ item.sku }}</p>
        </div>
        <span :class="getStatusClass(item.status)" class="px-3 py-1 rounded-full text-xs font-medium">
          {{ item.status }}
        </span>
      </div>
    </div>

    <!-- Stock Info -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <div class="space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">In Stock:</span>
          <span class="font-medium">{{ item.quantity }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Reserved:</span>
          <span class="font-medium">{{ item.reserved }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Available:</span>
          <span :class="item.available < item.reorderLevel ? 'font-bold text-red-600' : 'font-medium'">
            {{ item.available }}
          </span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Reorder Level:</span>
          <span class="font-medium">{{ item.reorderLevel }}</span>
        </div>
      </div>
      <!-- Stock Progress Bar -->
      <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
        <div 
          :class="getStockBarClass(item.available, item.reorderLevel)"
          class="h-2 rounded-full transition-all" 
          :style="{ width: Math.min((item.available / item.reorderLevel) * 100, 100) + '%' }"
        ></div>
      </div>
    </div>

    <!-- Location -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-1">Location</p>
      <p class="text-sm text-gray-600">{{ item.location }}</p>
      <p class="text-xs text-gray-400">{{ item.warehouse }}</p>
    </div>

    <!-- Last Movement -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-1">Last Movement</p>
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">{{ item.lastMovementType }}</span>
        <span class="text-gray-500">{{ item.lastMovementDate }}</span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="viewHistory"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        History
      </button>
      <button 
        @click="adjustStock"
        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Adjust Stock
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface InventoryItem {
  id: string
  name: string
  sku: string
  status: 'Active' | 'Inactive' | 'Discontinued'
  quantity: number
  reserved: number
  available: number
  reorderLevel: number
  location: string
  warehouse: string
  lastMovementType: string
  lastMovementDate: string
}

const props = defineProps<{
  item: InventoryItem
}>()

const emit = defineEmits<{
  viewHistory: [item: InventoryItem]
  adjustStock: [item: InventoryItem]
}>()

const getStatusClass = (status: string) => {
  const classes = {
    'Active': 'bg-green-100 text-green-900',
    'Inactive': 'bg-gray-100 text-gray-900',
    'Discontinued': 'bg-red-100 text-red-900',
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const getStockBarClass = (available: number, reorderLevel: number) => {
  if (available < reorderLevel) return 'bg-red-600'
  if (available < reorderLevel * 2) return 'bg-yellow-600'
  return 'bg-green-600'
}

const viewHistory = () => {
  emit('viewHistory', props.item)
}

const adjustStock = () => {
  emit('adjustStock', props.item)
}
</script>
