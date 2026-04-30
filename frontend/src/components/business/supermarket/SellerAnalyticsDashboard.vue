<template>
  <div class="seller-analytics-dashboard space-y-6">
    <!-- Time Range Selector -->
    <div class="bg-white rounded-xl shadow-md p-4">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Аналитика продавца</h2>
        <select 
          v-model="selectedTimeRange"
          class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
        >
          <option value="today">Сегодня</option>
          <option value="week">Эта неделя</option>
          <option value="month">Этот месяц</option>
          <option value="quarter">Этот квартал</option>
          <option value="year">Этот год</option>
        </select>
      </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm font-medium text-gray-500">Общая выручка</p>
          <div class="bg-green-100 p-2 rounded-full">
            <CurrencyDollar class="w-5 h-5 text-green-600" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(metrics.total_revenue) }}</p>
        <p :class="metrics.revenue_trend >= 0 ? 'text-green-600' : 'text-red-600'" class="text-sm mt-1">
          {{ metrics.revenue_trend >= 0 ? '+' : '' }}{{ metrics.revenue_trend }}%
        </p>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm font-medium text-gray-500">Выручка сегодня</p>
          <div class="bg-blue-100 p-2 rounded-full">
            <Calendar class="w-5 h-5 text-blue-600" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(metrics.today_revenue) }}</p>
        <p :class="metrics.today_trend >= 0 ? 'text-green-600' : 'text-red-600'" class="text-sm mt-1">
          {{ metrics.today_trend >= 0 ? '+' : '' }}{{ metrics.today_trend }}%
        </p>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm font-medium text-gray-500">Выручка за месяц</p>
          <div class="bg-purple-100 p-2 rounded-full">
            <ChartPie class="w-5 h-5 text-purple-600" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(metrics.month_revenue) }}</p>
        <p :class="metrics.month_trend >= 0 ? 'text-green-600' : 'text-red-600'" class="text-sm mt-1">
          {{ metrics.month_trend >= 0 ? '+' : '' }}{{ metrics.month_trend }}%
        </p>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm font-medium text-gray-500">Активные заказы</p>
          <div class="bg-yellow-100 p-2 rounded-full">
            <ShoppingCart class="w-5 h-5 text-yellow-600" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ metrics.active_orders }}</p>
        <p class="text-sm text-gray-500 mt-1">В обработке</p>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm font-medium text-gray-500">Всего товаров</p>
          <div class="bg-indigo-100 p-2 rounded-full">
            <Cube class="w-5 h-5 text-indigo-600" />
          </div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ metrics.total_products }}</p>
        <p class="text-sm text-gray-500 mt-1">Активных</p>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-2">
          <p class="text-sm font-medium text-gray-500">Мало на складе</p>
          <div :class="metrics.low_stock_items > 0 ? 'bg-red-100' : 'bg-green-100'" class="p-2 rounded-full">
            <AlertTriangle class="w-5 h-5" :class="metrics.low_stock_items > 0 ? 'text-red-600' : 'text-green-600'" />
          </div>
        </div>
        <p :class="metrics.low_stock_items > 0 ? 'text-red-600' : 'text-green-600'" class="text-2xl font-bold">
          {{ metrics.low_stock_items }}
        </p>
        <p class="text-sm text-gray-500 mt-1">Требует внимания</p>
      </div>
    </div>

    <!-- Charts & Tables Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Revenue Chart -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Выручка по дням</h3>
        <div class="h-64 flex items-end justify-between gap-1">
          <div 
            v-for="(value, index) in revenueChartData" 
            :key="index"
            class="flex-1 flex flex-col items-center"
          >
            <div 
              class="w-full bg-indigo-500 rounded-t hover:bg-indigo-600 transition-colors"
              :style="{ height: value + '%' }"
              :title="formatCurrency(value * 1000)"
            ></div>
            <span class="text-xs text-gray-500 mt-1">{{ chartLabels[index] }}</span>
          </div>
        </div>
      </div>

      <!-- Revenue by Sub-Vertical -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Выручка по под-вертикалям</h3>
        <div class="space-y-3">
          <div 
            v-for="item in revenueBySubVertical" 
            :key="item.sub_vertical"
            class="flex items-center justify-between"
          >
            <div class="flex-1">
              <div class="flex items-center justify-between mb-1">
                <span class="text-sm font-medium text-gray-700">{{ getSubVerticalLabel(item.sub_vertical) }}</span>
                <span class="text-sm font-bold text-gray-900">{{ formatCurrency(item.revenue) }}</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div 
                  :class="getSubVerticalColor(item.sub_vertical)"
                  class="h-2 rounded-full"
                  :style="{ width: (item.revenue / maxRevenue * 100) + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Orders -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Последние заказы</h3>
          <button 
            @click="viewAllOrders"
            class="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
          >
            Все заказы →
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead>
              <tr class="border-b border-gray-200">
                <th class="text-left py-2 text-xs font-medium text-gray-500 uppercase">UUID</th>
                <th class="text-left py-2 text-xs font-medium text-gray-500 uppercase">Сумма</th>
                <th class="text-left py-2 text-xs font-medium text-gray-500 uppercase">Статус</th>
                <th class="text-left py-2 text-xs font-medium text-gray-500 uppercase">Дата</th>
              </tr>
            </thead>
            <tbody>
              <tr 
                v-for="order in recentOrders" 
                :key="order.id"
                class="border-b border-gray-100 hover:bg-gray-50"
              >
                <td class="py-3 text-sm text-gray-900">{{ order.uuid }}</td>
                <td class="py-3 text-sm text-gray-900">{{ formatCurrency(order.total_amount) }}</td>
                <td class="py-3">
                  <span 
                    :class="getStatusBadgeColor(order.status)"
                    class="px-2 py-1 text-xs font-semibold rounded-full"
                  >
                    {{ getStatusLabel(order.status) }}
                  </span>
                </td>
                <td class="py-3 text-sm text-gray-500">{{ formatDate(order.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Low Stock Alert -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Мало на складе</h3>
          <button 
            @click="viewAllInventory"
            class="text-indigo-600 hover:text-indigo-700 text-sm font-medium"
          >
            Весь инвентарь →
          </button>
        </div>
        <div v-if="lowStockProducts.length > 0" class="space-y-3">
          <div 
            v-for="item in lowStockProducts" 
            :key="item.product_name"
            class="flex items-center justify-between p-3 bg-red-50 rounded-lg"
          >
            <div>
              <p class="text-sm font-medium text-gray-900">{{ item.product_name }}</p>
              <p class="text-xs text-gray-500">{{ item.location || 'Без локации' }}</p>
            </div>
            <div class="text-right">
              <p class="text-lg font-bold text-red-600">{{ item.available }}</p>
              <p class="text-xs text-gray-500">доступно</p>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8">
          <CheckCircle class="w-12 h-12 text-green-500 mx-auto mb-2" />
          <p class="text-sm text-gray-500">Все в норме</p>
        </div>
      </div>

      <!-- Top Products -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Топ товаров</h3>
        <div class="space-y-3">
          <div 
            v-for="(product, index) in topProducts" 
            :key="product.name"
            class="flex items-center gap-3"
          >
            <div 
              :class="getRankColor(index)"
              class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-sm"
            >
              {{ index + 1 }}
            </div>
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900">{{ product.name }}</p>
              <p class="text-xs text-gray-500">{{ getSubVerticalLabel(product.sub_vertical) }}</p>
            </div>
            <div class="text-right">
              <p class="text-lg font-bold text-green-600">{{ product.orders_count }}</p>
              <p class="text-xs text-gray-500">заказов</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Expiring Soon -->
      <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Скоро истекает</h3>
        <div v-if="expiringSoonItems.length > 0" class="space-y-3">
          <div 
            v-for="item in expiringSoonItems" 
            :key="item.product_name"
            class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg"
          >
            <div>
              <p class="text-sm font-medium text-gray-900">{{ item.product_name }}</p>
              <p class="text-xs text-gray-500">{{ item.location || 'Без локации' }}</p>
            </div>
            <div class="text-right">
              <p class="text-lg font-bold text-yellow-600">{{ item.expires_at }}</p>
              <p class="text-xs text-gray-500">истекает</p>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8">
          <CheckCircle class="w-12 h-12 text-green-500 mx-auto mb-2" />
          <p class="text-sm text-gray-500">Нет просрочек</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { 
  CurrencyDollar, 
  Calendar, 
  ChartPie, 
  ShoppingCart, 
  Cube, 
  AlertTriangle,
  CheckCircle
} from 'lucide-vue-next'

interface Metrics {
  total_revenue: number
  today_revenue: number
  month_revenue: number
  active_orders: number
  total_products: number
  low_stock_items: number
  revenue_trend: number
  today_trend: number
  month_trend: number
}

interface Order {
  id: string
  uuid: string
  total_amount: number
  status: string
  created_at: string
}

interface LowStockProduct {
  product_name: string
  available: number
  location?: string
}

interface TopProduct {
  name: string
  sub_vertical: string
  orders_count: number
}

interface RevenueBySubVertical {
  sub_vertical: string
  revenue: number
  orders_count: number
}

const props = defineProps<{
  metrics: Metrics
  recentOrders: Order[]
  lowStockProducts: LowStockProduct[]
  topProducts: TopProduct[]
  revenueBySubVertical: RevenueBySubVertical[]
}>()

const emit = defineEmits<{
  viewAllOrders: []
  viewAllInventory: []
}>()

const selectedTimeRange = ref('week')
const revenueChartData = ref([30, 45, 35, 60, 50, 70, 55, 40, 65, 45, 55, 60])
const chartLabels = ref(['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт'])

const expiringSoonItems = computed(() => {
  return props.lowStockProducts.slice(0, 5)
})

const maxRevenue = computed(() => {
  if (props.revenueBySubVertical.length === 0) return 1
  return Math.max(...props.revenueBySubVertical.map(item => item.revenue))
})

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(value)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('ru-RU')
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

const getRankColor = (index: number): string => {
  const colors = ['bg-yellow-500', 'bg-gray-400', 'bg-orange-600']
  return colors[index] || 'bg-indigo-500'
}

const viewAllOrders = () => {
  emit('viewAllOrders')
}

const viewAllInventory = () => {
  emit('viewAllInventory')
}
</script>
