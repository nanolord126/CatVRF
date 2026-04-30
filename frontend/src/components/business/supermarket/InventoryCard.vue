<template>
  <div class="inventory-card bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow">
    <!-- Header -->
    <div class="flex items-start justify-between mb-4">
      <div class="flex-1">
        <h3 class="font-semibold text-gray-900 text-lg">{{ product.name }}</h3>
        <p class="text-sm text-gray-500">{{ product.sku || 'Без SKU' }}</p>
      </div>
      <div class="text-right">
        <p 
          :class="availableQuantity <= 10 ? 'text-red-600' : 'text-green-600'"
          class="text-2xl font-bold"
        >
          {{ availableQuantity }}
        </p>
        <p class="text-sm text-gray-500">доступно</p>
      </div>
    </div>

    <!-- Quantity Bars -->
    <div class="mb-4">
      <div class="flex items-center justify-between text-sm mb-1">
        <span class="text-gray-600">Всего: {{ inventory.quantity }}</span>
        <span class="text-gray-600">Зарезервировано: {{ inventory.reserved }}</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div 
          class="bg-indigo-600 h-2 rounded-full"
          :style="{ width: (inventory.quantity / (inventory.quantity + 10) * 100) + '%' }"
        ></div>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
        <div 
          class="bg-yellow-500 h-2 rounded-full"
          :style="{ width: (inventory.reserved / (inventory.quantity + 10) * 100) + '%' }"
        ></div>
      </div>
    </div>

    <!-- Status Badges -->
    <div class="flex flex-wrap gap-2 mb-4">
      <span 
        v-if="isLowStock"
        class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full flex items-center gap-1"
      >
        <AlertTriangle class="w-3 h-3" />
        Мало на складе
      </span>
      <span 
        v-if="isExpiringSoon"
        class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full flex items-center gap-1"
      >
        <Clock class="w-3 h-3" />
        Скоро истекает
      </span>
      <span 
        v-if="isExpired"
        class="px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded-full flex items-center gap-1"
      >
        <XCircle class="w-3 h-3" />
        Просрочено
      </span>
    </div>

    <!-- Details -->
    <div class="space-y-2 mb-4">
      <div v-if="inventory.batch_number" class="flex items-center gap-2 text-sm text-gray-600">
        <Package class="w-4 h-4" />
        <span>Партия: {{ inventory.batch_number }}</span>
      </div>
      <div v-if="inventory.location" class="flex items-center gap-2 text-sm text-gray-600">
        <MapPin class="w-4 h-4" />
        <span>{{ inventory.location }}</span>
      </div>
      <div v-if="inventory.expires_at" class="flex items-center gap-2 text-sm text-gray-600">
        <Calendar class="w-4 h-4" />
        <span>Срок: {{ formatDate(inventory.expires_at) }}</span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2 pt-4 border-t border-gray-200">
      <button 
        @click="editInventory"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Редактировать
      </button>
      <button 
        @click="adjustQuantity"
        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Изменить количество
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { AlertTriangle, Clock, XCircle, Package, MapPin, Calendar } from 'lucide-vue-next'

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

const props = defineProps<{
  inventory: InventoryItem
  product: Product
}>()

const emit = defineEmits<{
  editInventory: [inventory: InventoryItem]
  adjustQuantity: [inventory: InventoryItem]
}>()

const availableQuantity = computed(() => Math.max(0, props.inventory.quantity - props.inventory.reserved))

const isLowStock = computed(() => availableQuantity.value <= 10)

const isExpiringSoon = computed(() => {
  if (!props.inventory.expires_at) return false
  const expiryDate = new Date(props.inventory.expires_at)
  const sevenDaysFromNow = new Date()
  sevenDaysFromNow.setDate(sevenDaysFromNow.getDate() + 7)
  return expiryDate <= sevenDaysFromNow && expiryDate > new Date()
})

const isExpired = computed(() => {
  if (!props.inventory.expires_at) return false
  return new Date(props.inventory.expires_at) < new Date()
})

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const editInventory = () => {
  emit('editInventory', props.inventory)
}

const adjustQuantity = () => {
  emit('adjustQuantity', props.inventory)
}
</script>
