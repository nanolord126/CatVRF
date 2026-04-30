<template>
  <div class="order-card bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow">
    <!-- Header -->
    <div class="flex items-start justify-between mb-4">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span class="text-sm text-gray-500">#{{ order.uuid }}</span>
          <span 
            :class="getStatusBadgeColor(order.status)"
            class="px-2 py-1 text-xs font-semibold rounded-full"
          >
            {{ getStatusLabel(order.status) }}
          </span>
        </div>
        <p class="text-sm text-gray-500">{{ formatDate(order.created_at) }}</p>
      </div>
      <div class="text-right">
        <p class="text-2xl font-bold text-gray-900">{{ formatPrice(order.total_amount) }}</p>
        <p v-if="order.delivery_cost" class="text-sm text-gray-500">
          Доставка: {{ formatPrice(order.delivery_cost) }}
        </p>
      </div>
    </div>

    <!-- Sub-vertical Badge -->
    <div class="mb-4">
      <span 
        :class="getSubVerticalColor(order.sub_vertical)"
        class="px-3 py-1 text-white text-sm font-medium rounded-full"
      >
        {{ getSubVerticalLabel(order.sub_vertical) }}
      </span>
    </div>

    <!-- Cold Chain Indicator -->
    <div v-if="order.cold_chain_required" class="flex items-center gap-2 text-blue-600 mb-4">
      <Snowflake class="w-4 h-4" />
      <span class="text-sm font-medium">Требует холодовой цепи</span>
    </div>

    <!-- Delivery Info -->
    <div v-if="order.delivery_address" class="mb-4">
      <div class="flex items-start gap-2 text-sm text-gray-600">
        <MapPin class="w-4 h-4 mt-0.5 flex-shrink-0" />
        <span>{{ order.delivery_address }}</span>
      </div>
      <div v-if="order.delivery_slot" class="flex items-center gap-2 text-sm text-gray-600 mt-1">
        <Clock class="w-4 h-4" />
        <span>Слот: {{ order.delivery_slot }}</span>
      </div>
      <div v-if="order.delivery_eta" class="flex items-center gap-2 text-sm text-gray-600 mt-1">
        <Truck class="w-4 h-4" />
        <span>ETA: {{ order.delivery_eta }} мин</span>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex gap-2 pt-4 border-t border-gray-200">
      <button 
        @click="viewDetails"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Подробнее
      </button>
      
      <button 
        v-if="canAccept"
        @click="acceptOrder"
        class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Принять
      </button>
      
      <button 
        v-if="canReject"
        @click="rejectOrder"
        class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Отклонить
      </button>
      
      <button 
        v-if="canReadyForDelivery"
        @click="readyForDelivery"
        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors"
      >
        Готов к доставке
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Snowflake, MapPin, Clock, Truck } from 'lucide-vue-next'

interface Order {
  id: string
  uuid: string
  status: string
  sub_vertical: string
  total_amount: number
  delivery_cost?: number
  delivery_address?: string
  delivery_slot?: string
  delivery_eta?: number
  cold_chain_required: boolean
  created_at: string
}

const props = defineProps<{
  order: Order
}>()

const emit = defineEmits<{
  viewDetails: [order: Order]
  acceptOrder: [order: Order]
  rejectOrder: [order: Order]
  readyForDelivery: [order: Order]
}>()

const canAccept = computed(() => props.order.status === 'pending')
const canReject = computed(() => ['pending', 'processing'].includes(props.order.status))
const canReadyForDelivery = computed(() => props.order.status === 'processing')

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(price / 100)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleString('ru-RU')
}

const getStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    pending: 'Ожидает',
    paid: 'Оплачен',
    processing: 'В обработке',
    shipped: 'Отправлен',
    delivered: 'Доставлен',
    cancelled: 'Отменён'
  }
  return labels[status] || status
}

const getStatusBadgeColor = (status: string): string => {
  const colors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    paid: 'bg-green-100 text-green-800',
    processing: 'bg-blue-100 text-blue-800',
    shipped: 'bg-indigo-100 text-indigo-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const getSubVerticalLabel = (subVertical: string): string => {
  const labels: Record<string, string> = {
    meat_shops: 'Мясные',
    farm_direct: 'Фермерские',
    vegan_products: 'Веган',
    confectionery: 'Кондитерские',
    grocery_and_delivery: 'Бакалея',
    food: 'Еда',
    office_catering: 'Кейтеринг'
  }
  return labels[subVertical] || subVertical
}

const getSubVerticalColor = (subVertical: string): string => {
  const colors: Record<string, string> = {
    meat_shops: 'bg-red-500',
    farm_direct: 'bg-green-500',
    vegan_products: 'bg-yellow-500',
    confectionery: 'bg-blue-500',
    grocery_and_delivery: 'bg-indigo-500',
    food: 'bg-purple-500',
    office_catering: 'bg-pink-500'
  }
  return colors[subVertical] || 'bg-gray-500'
}

const viewDetails = () => {
  emit('viewDetails', props.order)
}

const acceptOrder = () => {
  emit('acceptOrder', props.order)
}

const rejectOrder = () => {
  emit('rejectOrder', props.order)
}

const readyForDelivery = () => {
  emit('readyForDelivery', props.order)
}
</script>
