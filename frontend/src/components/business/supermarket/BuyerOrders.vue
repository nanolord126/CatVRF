<template>
  <div class="buyer-orders min-h-screen bg-gray-50 pb-20">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-4 py-4">
      <h1 class="text-xl font-bold text-gray-900">Мои заказы</h1>
    </div>

    <!-- Filters -->
    <div class="bg-white px-4 py-3 border-b border-gray-200">
      <div class="flex gap-2 overflow-x-auto">
        <button 
          v-for="filter in filters"
          :key="filter.value"
          @click="selectedFilter = filter.value"
          :class="selectedFilter === filter.value ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
          class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors"
        >
          {{ filter.label }}
        </button>
      </div>
    </div>

    <!-- Orders List -->
    <div class="p-4 space-y-4">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
      </div>

      <div v-else-if="filteredOrders.length === 0" class="text-center py-12">
        <ShoppingBag class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Заказы не найдены</h3>
        <p class="text-gray-500">У вас пока нет заказов в этой категории</p>
      </div>

      <div v-else class="space-y-4">
        <div 
          v-for="order in filteredOrders"
          :key="order.id"
          class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
        >
          <!-- Order Card -->
          <div class="p-4">
            <div class="flex items-center justify-between mb-3">
              <div>
                <p class="font-semibold text-gray-900">Заказ #{{ order.id }}</p>
                <p class="text-sm text-gray-500">{{ formatDate(order.created_at) }}</p>
              </div>
              <div class="text-right">
                <p class="text-lg font-bold text-gray-900">{{ order.total_amount }} ₽</p>
                <span :class="getStatusColor(order.status)" class="text-xs px-2 py-1 rounded-full">
                  {{ getStatusLabel(order.status) }}
                </span>
              </div>
            </div>

            <!-- Badges -->
            <div class="flex items-center gap-2 mb-3">
              <span v-if="order.cold_chain_required" class="flex items-center gap-1 text-xs bg-blue-50 text-blue-600 px-2 py-1 rounded-full">
                <Snowflake class="w-3 h-3" />
                Холодная цепь
              </span>
              <span v-if="order.sub_vertical" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                {{ getSubVerticalLabel(order.sub_vertical) }}
              </span>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2">
              <button 
                v-if="order.status === 'processing' || order.status === 'ready'"
                @click="handleTrackOrder(order.uuid)"
                class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg font-medium text-sm transition-colors"
              >
                <MapPin class="w-4 h-4 inline mr-1" />
                Отследить
              </button>
              <button 
                @click="handleRepeatOrder(order.id)"
                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 rounded-lg font-medium text-sm transition-colors"
              >
                <RefreshCw class="w-4 h-4 inline mr-1" />
                Повторить
              </button>
              <button 
                v-if="order.status === 'delivered'"
                @click="handleReturnOrder(order.id)"
                class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 py-2 rounded-lg font-medium text-sm transition-colors"
              >
                <ArrowLeft class="w-4 h-4 inline mr-1" />
                Вернуть
              </button>
            </div>
          </div>

          <!-- Expandable Order Details -->
          <button 
            @click="toggleOrderDetails(order.id)"
            class="w-full px-4 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between text-sm text-gray-600 hover:bg-gray-100 transition-colors"
          >
            <span>Подробнее о заказе</span>
            <ChevronDown :class="expandedOrders.includes(order.id) ? 'rotate-180' : ''" class="w-5 h-5 transition-transform" />
          </button>

          <!-- Expanded Details -->
          <div v-if="expandedOrders.includes(order.id)" class="px-4 py-4 bg-white border-t border-gray-100">
            <!-- Live Tracking (for active orders) -->
            <div v-if="order.status === 'processing' || order.status === 'ready'" class="mb-4 p-4 bg-blue-50 rounded-lg">
              <h4 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">
                <MapPin class="w-4 h-4 text-blue-600" />
                Live-трекинг
              </h4>
              <div class="space-y-2">
                <div class="flex items-center gap-2 text-sm text-gray-700">
                  <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                  <span>Заказ принят</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                  <div :class="order.status === 'ready' ? 'w-2 h-2 bg-green-500 rounded-full' : 'w-2 h-2 bg-gray-300 rounded-full'"></div>
                  <span>Курьер в пути</span>
                </div>
                <div class="flex items-center gap-2 text-sm text-gray-700">
                  <div class="w-2 h-2 bg-gray-300 rounded-full"></div>
                  <span>Доставлен</span>
                </div>
              </div>
              <p v-if="order.delivery_eta" class="text-sm text-blue-600 mt-2">ETA: ~{{ order.delivery_eta }} мин</p>
            </div>

            <!-- Temperature (for cold chain) -->
            <div v-if="order.cold_chain_required" class="mb-4 p-4 bg-cyan-50 rounded-lg">
              <h4 class="font-semibold text-gray-900 mb-2 flex items-center gap-2">
                <Snowflake class="w-4 h-4 text-cyan-600" />
                Температура
              </h4>
              <div class="flex items-center gap-4">
                <div class="text-center">
                  <p class="text-2xl font-bold text-cyan-600">+4°C</p>
                  <p class="text-xs text-gray-500">Текущая</p>
                </div>
                <div class="text-center">
                  <p class="text-2xl font-bold text-green-600">+2°C</p>
                  <p class="text-xs text-gray-500">Норма</p>
                </div>
              </div>
            </div>

            <!-- Order Items -->
            <h4 class="font-semibold text-gray-900 mb-2">Состав заказа</h4>
            <div class="space-y-2">
              <div 
                v-for="item in order.items"
                :key="item.id"
                class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0"
              >
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                    <Package class="w-5 h-5 text-gray-400" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ item.product?.name || 'Товар' }}</p>
                    <p class="text-xs text-gray-500">{{ item.quantity }} шт × {{ item.price_per_unit }} ₽</p>
                  </div>
                </div>
                <p class="text-sm font-semibold text-gray-900">{{ item.total_price }} ₽</p>
              </div>
            </div>

            <!-- Delivery Info -->
            <div v-if="order.delivery_address" class="mt-4 pt-4 border-t border-gray-100">
              <h4 class="font-semibold text-gray-900 mb-2">Доставка</h4>
              <p class="text-sm text-gray-600 flex items-center gap-2">
                <MapPin class="w-4 h-4" />
                {{ order.delivery_address }}
              </p>
              <p v-if="order.delivery_slot" class="text-sm text-gray-600 flex items-center gap-2 mt-1">
                <Clock class="w-4 h-4" />
                {{ order.delivery_slot }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Navigation -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-6 py-3">
      <div class="flex items-center justify-around">
        <button class="flex flex-col items-center gap-1 text-gray-400 hover:text-gray-600 transition-colors">
          <Home class="w-6 h-6" />
          <span class="text-xs">Главная</span>
        </button>
        <button class="flex flex-col items-center gap-1 text-green-600">
          <ShoppingBag class="w-6 h-6" />
          <span class="text-xs font-medium">Заказы</span>
        </button>
        <button class="flex flex-col items-center gap-1 text-gray-400 hover:text-gray-600 transition-colors">
          <Infinity class="w-6 h-6" />
          <span class="text-xs">Подписки</span>
        </button>
        <button class="flex flex-col items-center gap-1 text-gray-400 hover:text-gray-600 transition-colors">
          <User class="w-6 h-6" />
          <span class="text-xs">Профиль</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { 
  ShoppingBag, Snowflake, MapPin, RefreshCw, ArrowLeft, 
  ChevronDown, Package, Clock, Home, Infinity, User
} from 'lucide-vue-next'
import { useSupermarketApi } from '@/composables/useSupermarketApi'

const api = useSupermarketApi()
const orders = ref<any[]>([])
const loading = ref(false)
const selectedFilter = ref('all')
const expandedOrders = ref<number[]>([])

const filters = [
  { label: 'Все', value: 'all' },
  { label: 'Активные', value: 'active' },
  { label: 'Доставленные', value: 'delivered' },
  { label: 'Возвраты', value: 'returns' }
]

const filteredOrders = computed(() => {
  if (selectedFilter.value === 'all') {
    return orders.value
  }
  if (selectedFilter.value === 'active') {
    return orders.value.filter(o => ['pending', 'confirmed', 'processing', 'ready'].includes(o.status))
  }
  if (selectedFilter.value === 'delivered') {
    return orders.value.filter(o => o.status === 'delivered')
  }
  if (selectedFilter.value === 'returns') {
    return orders.value.filter(o => o.status === 'returned' || o.status === 'return_requested')
  }
  return orders.value
})

const loadOrders = async () => {
  loading.value = true
  try {
    orders.value = await api.fetchBuyerOrders()
  } catch (error) {
    console.error('Failed to load orders:', error)
  } finally {
    loading.value = false
  }
}

const toggleOrderDetails = (orderId: number) => {
  const index = expandedOrders.value.indexOf(orderId)
  if (index === -1) {
    expandedOrders.value.push(orderId)
  } else {
    expandedOrders.value.splice(index, 1)
  }
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' })
}

const getStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    'pending': 'Ожидает',
    'confirmed': 'Подтверждён',
    'processing': 'В доставке',
    'ready': 'Готов',
    'delivered': 'Доставлен',
    'cancelled': 'Отменён',
    'returned': 'Возвращён',
    'return_requested': 'Возврат запрошен'
  }
  return labels[status] || status
}

const getStatusColor = (status: string): string => {
  const colors: Record<string, string> = {
    'pending': 'bg-gray-100 text-gray-600',
    'confirmed': 'bg-blue-100 text-blue-600',
    'processing': 'bg-orange-100 text-orange-600',
    'ready': 'bg-purple-100 text-purple-600',
    'delivered': 'bg-green-100 text-green-600',
    'cancelled': 'bg-red-100 text-red-600',
    'returned': 'bg-gray-100 text-gray-600',
    'return_requested': 'bg-yellow-100 text-yellow-600'
  }
  return colors[status] || 'bg-gray-100 text-gray-600'
}

const getSubVerticalLabel = (subVertical: string): string => {
  const labels: Record<string, string> = {
    'meat_shops': 'Мясо',
    'farm_direct': 'Фермерские',
    'vegan_products': 'Веган',
    'confectionery': 'Кондитерка',
    'grocery_and_delivery': 'Бакалея'
  }
  return labels[subVertical] || subVertical
}

const handleTrackOrder = (orderUuid: string) => {
  window.location.href = `/orders/${orderUuid}/track`
}

const handleRepeatOrder = async (orderId: number) => {
  try {
    await api.repeatOrder(orderId.toString())
    window.location.href = '/cart'
  } catch (error) {
    console.error('Failed to repeat order:', error)
  }
}

const handleReturnOrder = (orderId: number) => {
  window.location.href = `/orders/${orderId}/return`
}

onMounted(() => {
  loadOrders()
})
</script>
