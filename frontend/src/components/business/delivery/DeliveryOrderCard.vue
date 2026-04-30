<template>
  <div class="delivery-order-card bg-white rounded-xl shadow-md p-6">
    <!-- Header -->
    <div class="mb-4">
      <div class="flex justify-between items-start">
        <div>
          <h3 class="text-lg font-bold text-gray-900">Order #{{ order.id }}</h3>
          <p class="text-sm text-gray-500">{{ formatDate(order.createdAt) }}</p>
        </div>
        <span :class="getStatusClass(order.status)" class="px-3 py-1 rounded-full text-xs font-medium">
          {{ order.status }}
        </span>
      </div>
    </div>

    <!-- Items -->
    <div class="mb-4">
      <p class="text-sm font-medium text-gray-700 mb-2">Items ({{ order.items.length }})</p>
      <div class="space-y-2">
        <div v-for="item in order.items" :key="item.id" class="flex justify-between text-sm">
          <span class="text-gray-600">{{ item.name }} x{{ item.quantity }}</span>
          <span class="font-medium">{{ formatPrice(item.price * item.quantity) }}</span>
        </div>
      </div>
    </div>

    <!-- Addresses -->
    <div class="bg-gray-50 rounded-lg p-3 mb-4">
      <div class="mb-2">
        <p class="text-xs text-gray-500">Pickup</p>
        <p class="text-sm font-medium">{{ order.pickupAddress }}</p>
      </div>
      <div>
        <p class="text-xs text-gray-500">Delivery</p>
        <p class="text-sm font-medium">{{ order.deliveryAddress }}</p>
      </div>
    </div>

    <!-- Driver Info -->
    <div v-if="order.driver" class="flex items-center gap-3 mb-4 p-3 bg-blue-50 rounded-lg">
      <div class="w-10 h-10 bg-blue-200 rounded-full flex items-center justify-center">
        <span class="text-blue-700 font-bold">{{ order.driver.initials }}</span>
      </div>
      <div class="flex-1">
        <p class="font-medium text-gray-900">{{ order.driver.name }}</p>
        <p class="text-sm text-gray-500">{{ order.driver.vehicle }}</p>
      </div>
      <button @click="callDriver" class="p-2 bg-blue-600 text-white rounded-full">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
        </svg>
      </button>
    </div>

    <!-- ETA -->
    <div v-if="order.eta" class="flex items-center gap-2 mb-4 text-sm text-gray-600">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <span>ETA: {{ order.eta }}</span>
    </div>

    <!-- Price -->
    <div class="border-t pt-3 mb-4">
      <div class="flex justify-between text-sm mb-1">
        <span class="text-gray-600">Subtotal:</span>
        <span>{{ formatPrice(order.subtotal) }}</span>
      </div>
      <div class="flex justify-between text-sm mb-1">
        <span class="text-gray-600">Delivery fee:</span>
        <span>{{ formatPrice(order.deliveryFee) }}</span>
      </div>
      <div class="flex justify-between font-bold">
        <span>Total:</span>
        <span class="text-blue-600">{{ formatPrice(order.total) }}</span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2">
      <button 
        @click="trackOrder"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Track
      </button>
      <button 
        v-if="order.status === 'Delivering'"
        @click="contactSupport"
        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Contact Support
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface OrderItem {
  id: string
  name: string
  quantity: number
  price: number
}

interface Driver {
  name: string
  initials: string
  vehicle: string
}

interface DeliveryOrder {
  id: string
  status: 'Pending' | 'Preparing' | 'Delivering' | 'Delivered' | 'Cancelled'
  createdAt: string
  items: OrderItem[]
  pickupAddress: string
  deliveryAddress: string
  driver?: Driver
  eta?: string
  subtotal: number
  deliveryFee: number
  total: number
}

const props = defineProps<{
  order: DeliveryOrder
}>()

const emit = defineEmits<{
  trackOrder: [order: DeliveryOrder]
  contactSupport: [order: DeliveryOrder]
  callDriver: [order: DeliveryOrder]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusClass = (status: string) => {
  const classes = {
    'Pending': 'bg-gray-100 text-gray-900',
    'Preparing': 'bg-blue-100 text-blue-900',
    'Delivering': 'bg-yellow-100 text-yellow-900',
    'Delivered': 'bg-green-100 text-green-900',
    'Cancelled': 'bg-red-100 text-red-900',
  }
  return classes[status as keyof typeof classes] || 'bg-gray-100 text-gray-900'
}

const trackOrder = () => {
  emit('trackOrder', props.order)
}

const contactSupport = () => {
  emit('contactSupport', props.order)
}

const callDriver = () => {
  emit('callDriver', props.order)
}
</script>
