<template>
  <div class="buyer-dashboard min-h-screen bg-gray-50 pb-20">
    <!-- Header with Greeting and Bonuses -->
    <div class="bg-gradient-to-r from-green-600 to-emerald-600 p-6 text-white">
      <div class="flex items-center justify-between mb-4">
        <div>
          <p class="text-green-100 text-sm">Добрый день, {{ dashboardData?.user?.name || 'Пользователь' }}!</p>
          <h1 class="text-2xl font-bold">У вас {{ dashboardData?.user?.bonus_balance || 0 }} бонусов</h1>
        </div>
        <div class="relative">
          <Bell class="w-6 h-6" />
          <span class="absolute -top-1 -right-1 bg-red-500 text-xs w-4 h-4 rounded-full flex items-center justify-center">3</span>
        </div>
      </div>
      
      <!-- Cashback Card from Last Order -->
      <div v-if="dashboardData?.last_order" class="bg-white/20 backdrop-blur rounded-xl p-4">
        <p class="text-green-100 text-sm mb-1">Кэшбэк за последний заказ</p>
        <p class="text-3xl font-bold">{{ calculateCashback(dashboardData.last_order.total_amount) }} ₽</p>
      </div>
    </div>

    <!-- Main Content -->
    <div class="p-4 space-y-6">
      <!-- Active Subscriptions -->
      <section v-if="dashboardData?.active_subscriptions?.length > 0">
        <h2 class="text-lg font-bold text-gray-900 mb-3">Активные подписки</h2>
        <div class="space-y-3">
          <div 
            v-for="sub in dashboardData.active_subscriptions" 
            :key="sub.id"
            class="bg-white rounded-xl shadow-sm p-4 border border-gray-100"
          >
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2">
                <div class="bg-green-100 text-green-600 p-2 rounded-lg">
                  <Infinity class="w-5 h-5" />
                </div>
                <div>
                  <p class="font-semibold text-gray-900">{{ getFrequencyLabel(sub.frequency) }}</p>
                  <p class="text-sm text-gray-500">Следующая доставка: {{ formatDate(sub.next_delivery_at) }}</p>
                </div>
              </div>
              <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Active</span>
            </div>
            <div class="flex items-center gap-2">
              <Timer class="w-4 h-4 text-orange-500" />
              <span class="text-sm text-orange-600 font-medium">{{ getTimeUntilDelivery(sub.next_delivery_at) }}</span>
            </div>
            <button class="mt-3 w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg font-medium transition-colors">
              Отследить
            </button>
          </div>
        </div>
      </section>

      <!-- Last Order -->
      <section v-if="dashboardData?.last_order">
        <h2 class="text-lg font-bold text-gray-900 mb-3">Последний заказ</h2>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
          <div class="flex items-center justify-between mb-3">
            <div>
              <p class="font-semibold text-gray-900">Заказ #{{ dashboardData.last_order.id }}</p>
              <p class="text-sm text-gray-500">{{ getStatusLabel(dashboardData.last_order.status) }}</p>
            </div>
            <span class="text-xl font-bold text-gray-900">{{ dashboardData.last_order.total_amount }} ₽</span>
          </div>
          <div v-if="dashboardData.last_order.cold_chain_required" class="flex items-center gap-2 mb-3">
            <Snowflake class="w-4 h-4 text-blue-500" />
            <span class="text-sm text-blue-600">Холодная цепь</span>
          </div>
          <button 
            @click="handleRepeatOrder(dashboardData.last_order.id)"
            class="w-full bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 rounded-lg font-medium transition-colors"
          >
            Повторить заказ
          </button>
        </div>
      </section>

      <!-- AI Recommendations -->
      <section v-if="dashboardData?.ai_recommendations?.length > 0">
        <h2 class="text-lg font-bold text-gray-900 mb-3 flex items-center gap-2">
          <Sparkles class="w-5 h-5 text-purple-500" />
          Для вас
        </h2>
        <div class="grid grid-cols-2 gap-3">
          <div 
            v-for="product in dashboardData.ai_recommendations.slice(0, 4)" 
            :key="product.id"
            class="bg-white rounded-xl shadow-sm p-3 border border-gray-100"
          >
            <div class="aspect-square bg-gray-100 rounded-lg mb-2 flex items-center justify-center">
              <Package class="w-8 h-8 text-gray-400" />
            </div>
            <p class="text-sm font-medium text-gray-900 line-clamp-2">{{ product.name }}</p>
            <p class="text-sm font-bold text-gray-900 mt-1">{{ product.price }} ₽</p>
            <div v-if="product.requires_cold_chain" class="flex items-center gap-1 mt-1">
              <Snowflake class="w-3 h-3 text-blue-400" />
              <span class="text-xs text-blue-500">Холод</span>
            </div>
          </div>
        </div>
      </section>

      <!-- Quick Access Categories -->
      <section>
        <h2 class="text-lg font-bold text-gray-900 mb-3">Быстрый доступ</h2>
        <div class="grid grid-cols-4 gap-3">
          <button 
            v-for="category in dashboardData?.quick_access_categories || []"
            :key="category.id"
            class="flex flex-col items-center gap-2 p-3 bg-white rounded-xl shadow-sm border border-gray-100 hover:border-gray-200 transition-colors"
          >
            <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
              <component :is="getIcon(category.icon)" class="w-6 h-6 text-orange-600" />
            </div>
            <span class="text-xs text-gray-700 text-center">{{ category.name }}</span>
          </button>
          <button class="flex flex-col items-center gap-2 p-3 bg-white rounded-xl shadow-sm border border-gray-100 hover:border-gray-200 transition-colors">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
              <AlertCircle class="w-6 h-6 text-red-600" />
            </div>
            <span class="text-xs text-gray-700 text-center">18+</span>
          </button>
        </div>
      </section>

      <!-- Promotions -->
      <section v-if="dashboardData?.promotions?.length > 0">
        <h2 class="text-lg font-bold text-gray-900 mb-3">Акции и спецпредложения</h2>
        <div class="space-y-3">
          <div 
            v-for="promo in dashboardData.promotions" 
            :key="promo.id"
            class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-4 text-white"
          >
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-bold">{{ promo.title }}</h3>
              <span class="bg-white/20 px-2 py-1 rounded-full text-sm">-{{ promo.discount_percent }}%</span>
            </div>
            <p class="text-sm text-purple-100 mb-2">{{ promo.description }}</p>
            <p class="text-xs text-purple-200">Действует до {{ formatDate(promo.valid_until) }}</p>
          </div>
        </div>
      </section>
    </div>

    <!-- Floating Action Button -->
    <button class="fixed bottom-24 right-4 bg-green-600 hover:bg-green-700 text-white p-4 rounded-full shadow-lg transition-colors">
      <Plus class="w-6 h-6" />
    </button>

    <!-- Bottom Navigation -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 px-6 py-3">
      <div class="flex items-center justify-around">
        <button class="flex flex-col items-center gap-1 text-green-600">
          <Home class="w-6 h-6" />
          <span class="text-xs font-medium">Главная</span>
        </button>
        <button class="flex flex-col items-center gap-1 text-gray-400 hover:text-gray-600 transition-colors">
          <ShoppingBag class="w-6 h-6" />
          <span class="text-xs">Заказы</span>
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

    <!-- Loading State -->
    <div v-if="loading" class="fixed inset-0 bg-white/80 flex items-center justify-center z-50">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { 
  Bell, Infinity, Timer, Package, Snowflake, Sparkles, 
  Plus, Home, ShoppingBag, User, AlertCircle, 
  Beef, Carrot, Milk, Cake, ShoppingBasket
} from 'lucide-vue-next'
import { useSupermarketApi } from '@/composables/useSupermarketApi'

const api = useSupermarketApi()
const dashboardData = ref<any>(null)
const loading = ref(false)

const loadDashboard = async () => {
  loading.value = true
  try {
    dashboardData.value = await api.fetchBuyerDashboard()
  } catch (error) {
    console.error('Failed to load dashboard:', error)
  } finally {
    loading.value = false
  }
}

const calculateCashback = (totalAmount: number): number => {
  return Math.round(totalAmount * 0.05) // 5% cashback
}

const getFrequencyLabel = (frequency: string): string => {
  const labels: Record<string, string> = {
    'weekly': 'Каждую неделю',
    'biweekly': 'Раз в 2 недели',
    'monthly': 'Раз в месяц'
  }
  return labels[frequency] || frequency
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short' })
}

const getTimeUntilDelivery = (dateString: string): string => {
  const now = new Date()
  const deliveryDate = new Date(dateString)
  const diffMs = deliveryDate.getTime() - now.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  const diffHours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  
  if (diffDays > 0) {
    return `через ${diffDays} дней`
  } else if (diffHours > 0) {
    return `через ${diffHours} часов`
  }
  return 'Скоро'
}

const getStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    'pending': 'Ожидает',
    'confirmed': 'Подтверждён',
    'processing': 'В обработке',
    'ready': 'Готов к выдаче',
    'delivered': 'Доставлен',
    'cancelled': 'Отменён'
  }
  return labels[status] || status
}

const handleRepeatOrder = async (orderId: string) => {
  try {
    await api.repeatOrder(orderId)
    // Navigate to cart or checkout
    window.location.href = '/cart'
  } catch (error) {
    console.error('Failed to repeat order:', error)
  }
}

const getIcon = (iconName: string) => {
  const icons: Record<string, any> = {
    'meat': Beef,
    'vegetables': Carrot,
    'dairy': Milk,
    'confectionery': Cake,
    'grocery': ShoppingBasket
  }
  return icons[iconName] || ShoppingBasket
}

onMounted(() => {
  loadDashboard()
})
</script>
