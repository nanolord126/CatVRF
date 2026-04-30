<template>
  <div class="restaurant-order-card bg-white rounded-xl shadow-md overflow-hidden">
    <!-- Header -->
    <div class="relative h-32 bg-gradient-to-r from-orange-500 to-red-500">
      <img 
        :src="restaurant.imageUrl" 
        :alt="restaurant.name"
        class="w-full h-full object-cover opacity-90"
      />
      <span :class="getStatusClass(order.status)" class="absolute top-2 right-2 px-3 py-1 rounded-full text-xs font-bold bg-white/90">
        {{ order.status }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <!-- Restaurant Info -->
      <div class="mb-4">
        <h3 class="text-lg font-bold text-gray-900">{{ restaurant.name }}</h3>
        <p class="text-sm text-gray-500">{{ restaurant.cuisine }} • {{ restaurant.location }}</p>
      </div>

      <!-- Order Details -->
      <div class="bg-gray-50 rounded-lg p-3 mb-4">
        <div class="space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Order #:</span>
            <span class="font-medium">{{ order.orderNumber }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Ordered:</span>
            <span class="font-medium">{{ order.orderTime }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Delivery:</span>
            <span class="font-medium">{{ order.deliveryTime }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Items:</span>
            <span class="font-medium">{{ order.items.length }}</span>
          </div>
        </div>
      </div>

      <!-- Order Items -->
      <div class="mb-4">
        <p class="text-sm font-medium text-gray-700 mb-2">Items</p>
        <div class="space-y-1 max-h-24 overflow-y-auto">
          <div 
            v-for="item in order.items.slice(0, 4)" 
            :key="item.id"
            class="flex justify-between text-sm"
          >
            <span class="text-gray-600">{{ item.quantity }}x {{ item.name }}</span>
            <span class="text-gray-900">{{ formatPrice(item.price) }}</span>
          </div>
          <div v-if="order.items.length > 4" class="text-sm text-gray-500">
            +{{ order.items.length - 4 }} more items
          </div>
        </div>
      </div>

      <!-- Price Breakdown -->
      <div class="bg-orange-50 rounded-lg p-3 mb-4">
        <div class="space-y-1 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-600">Subtotal:</span>
            <span>{{ formatPrice(order.subtotal) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Delivery:</span>
            <span>{{ formatPrice(order.deliveryFee) }}</span>
          </div>
          <div class="flex justify-between font-bold text-gray-900 pt-1 border-t border-orange-200">
            <span>Total:</span>
            <span>{{ formatPrice(order.total) }}</span>
          </div>
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
          v-if="order.status === 'Preparing' || order.status === 'Out for Delivery'"
          @click="contactSupport"
          class="flex-1 bg-orange-600 hover:bg-orange-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Support
        </button>
        <button 
          v-else
          @click="reorder"
          class="flex-1 bg-orange-600 hover:bg-orange-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
        >
          Reorder
        </button>
      </div>
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

interface Restaurant {
  name: string
  cuisine: string
  location: string
  imageUrl: string
}

interface Order {
  id: string
  orderNumber: string
  status: 'Received' | 'Preparing' | 'Out for Delivery' | 'Delivered' | 'Cancelled'
  orderTime: string
  deliveryTime: string
  items: OrderItem[]
  subtotal: number
  deliveryFee: number
  total: number
}

const props = defineProps<{
  restaurant: Restaurant
  order: Order
}>()

const emit = defineEmits<{
  trackOrder: [order: Order]
  contactSupport: [order: Order]
  reorder: [order: Order]
}>()

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(price)
}

const getStatusClass = (status: string) => {
  const classes = {
    'Received': 'text-blue-900',
    'Preparing': 'text-yellow-900',
    'Out for Delivery': 'text-orange-900',
    'Delivered': 'text-green-900',
    'Cancelled': 'text-red-900',
  }
  return classes[status as keyof typeof classes] || 'text-gray-900'
}

const trackOrder = () => {
  emit('trackOrder', props.order)
}

const contactSupport = () => {
  emit('contactSupport', props.order)
}

const reorder = () => {
  emit('reorder', props.order)
}
</script>
