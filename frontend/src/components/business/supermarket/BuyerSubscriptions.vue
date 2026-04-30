<template>
  <div class="buyer-subscriptions min-h-screen bg-gray-50 pb-20">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-4 py-4 flex items-center justify-between">
      <h1 class="text-xl font-bold text-gray-900">Мои подписки</h1>
      <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors">
        + Новая подписка
      </button>
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

    <!-- Subscriptions List -->
    <div class="p-4 space-y-4">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
      </div>

      <div v-else-if="filteredSubscriptions.length === 0" class="text-center py-12">
        <Infinity class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Подписок нет</h3>
        <p class="text-gray-500 mb-4">Создайте свою первую подписку для регулярных доставок</p>
        <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
          Создать подписку
        </button>
      </div>

      <div v-else class="space-y-4">
        <div 
          v-for="subscription in filteredSubscriptions"
          :key="subscription.id"
          class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
        >
          <!-- Subscription Card -->
          <div class="p-4">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-3">
                <div class="bg-green-100 text-green-600 p-3 rounded-xl">
                  <Infinity class="w-6 h-6" />
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900">{{ getFrequencyLabel(subscription.frequency) }}</h3>
                  <p class="text-sm text-gray-500">{{ getSubVerticalLabel(subscription.sub_vertical) }}</p>
                </div>
              </div>
              <span :class="getStatusColor(subscription.status)" class="text-xs px-3 py-1 rounded-full font-medium">
                {{ getStatusLabel(subscription.status) }}
              </span>
            </div>

            <!-- Next Delivery Timer -->
            <div class="bg-orange-50 rounded-lg p-3 mb-3 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <Timer class="w-5 h-5 text-orange-500" />
                <span class="text-sm text-orange-700 font-medium">Следующая доставка:</span>
              </div>
              <span class="text-sm font-bold text-orange-600">{{ getTimeUntilDelivery(subscription.next_delivery_at) }}</span>
            </div>

            <!-- Subscription Items Preview -->
            <div v-if="subscription.items && subscription.items.length > 0" class="mb-3">
              <div class="flex gap-2 overflow-x-auto pb-2">
                <div 
                  v-for="item in subscription.items.slice(0, 4)"
                  :key="item.id"
                  class="flex-shrink-0 w-20 text-center"
                >
                  <div class="w-16 h-16 bg-gray-100 rounded-lg mx-auto mb-1 flex items-center justify-center">
                    <Package class="w-6 h-6 text-gray-400" />
                  </div>
                  <p class="text-xs text-gray-600 truncate">{{ item.product?.name || 'Товар' }}</p>
                  <p class="text-xs font-medium text-gray-900">{{ item.quantity }} шт</p>
                </div>
                <div v-if="subscription.items.length > 4" class="flex-shrink-0 w-20 text-center">
                  <div class="w-16 h-16 bg-gray-50 rounded-lg mx-auto mb-1 flex items-center justify-center">
                    <Plus class="w-6 h-6 text-gray-400" />
                  </div>
                  <p class="text-xs text-gray-600">Еще {{ subscription.items.length - 4 }}</p>
                </div>
              </div>
            </div>

            <!-- Price -->
            <div class="flex items-center justify-between mb-3">
              <span class="text-sm text-gray-500">Стоимость доставки:</span>
              <span class="text-lg font-bold text-gray-900">{{ subscription.total_amount }} ₽</span>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2">
              <button 
                v-if="subscription.status === 'active'"
                @click="handlePauseSubscription(subscription.id)"
                class="flex-1 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 py-2 rounded-lg font-medium text-sm transition-colors"
              >
                <Pause class="w-4 h-4 inline mr-1" />
                Приостановить
              </button>
              <button 
                v-if="subscription.status === 'paused'"
                @click="handleResumeSubscription(subscription.id)"
                class="flex-1 bg-green-50 hover:bg-green-100 text-green-700 py-2 rounded-lg font-medium text-sm transition-colors"
              >
                <Play class="w-4 h-4 inline mr-1" />
                Возобновить
              </button>
              <button 
                @click="handleEditSubscription(subscription.id)"
                class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-700 py-2 rounded-lg font-medium text-sm transition-colors"
              >
                <Edit class="w-4 h-4 inline mr-1" />
                Изменить
              </button>
              <button 
                @click="handleTrackSubscription(subscription.id)"
                class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 rounded-lg font-medium text-sm transition-colors"
              >
                <MapPin class="w-4 h-4 inline mr-1" />
                Отследить
              </button>
              <button 
                @click="handleCancelSubscription(subscription.id)"
                class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 py-2 rounded-lg font-medium text-sm transition-colors"
              >
                <X class="w-4 h-4 inline mr-1" />
                Отменить
              </button>
            </div>
          </div>

          <!-- Expandable Details -->
          <button 
            @click="toggleSubscriptionDetails(subscription.id)"
            class="w-full px-4 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between text-sm text-gray-600 hover:bg-gray-100 transition-colors"
          >
            <span>Подробнее</span>
            <ChevronDown :class="expandedSubscriptions.includes(subscription.id) ? 'rotate-180' : ''" class="w-5 h-5 transition-transform" />
          </button>

          <!-- Expanded Details -->
          <div v-if="expandedSubscriptions.includes(subscription.id)" class="px-4 py-4 bg-white border-t border-gray-100">
            <!-- Full Items List -->
            <h4 class="font-semibold text-gray-900 mb-3">Состав подписки</h4>
            <div class="space-y-3 mb-4">
              <div 
                v-for="item in subscription.items"
                :key="item.id"
                class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0"
              >
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                    <Package class="w-5 h-5 text-gray-400" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ item.product?.name || 'Товар' }}</p>
                    <p class="text-xs text-gray-500">{{ item.quantity }} шт × {{ item.price_per_unit_at_creation }} ₽</p>
                  </div>
                </div>
                <button class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                  Убрать
                </button>
              </div>
            </div>

            <button class="w-full bg-blue-50 hover:bg-blue-100 text-blue-700 py-2 rounded-lg font-medium text-sm transition-colors mb-4">
              + Добавить товары
            </button>

            <!-- Schedule -->
            <h4 class="font-semibold text-gray-900 mb-2">График</h4>
            <div class="bg-gray-50 rounded-lg p-3 mb-4">
              <p class="text-sm text-gray-700">
                <span class="font-medium">Частота:</span> {{ getFrequencyLabel(subscription.frequency) }}
              </p>
              <p class="text-sm text-gray-700">
                <span class="font-medium">День:</span> {{ getDayLabel(subscription.delivery_day) }}
              </p>
              <p v-if="subscription.pause_until" class="text-sm text-yellow-700">
                <span class="font-medium">Приостановлено до:</span> {{ formatDate(subscription.pause_until) }}
              </p>
            </div>

            <!-- Delivery History -->
            <h4 class="font-semibold text-gray-900 mb-2">История доставок</h4>
            <div class="space-y-2">
              <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <div>
                  <p class="text-sm font-medium text-gray-900">Доставка #{{ subscription.id }}-001</p>
                  <p class="text-xs text-gray-500">{{ formatDate(subscription.next_delivery_at) }}</p>
                </div>
                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Доставлено</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pause Modal -->
    <div v-if="showPauseModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-xl p-6 w-full max-w-md">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Приостановить подписку</h3>
        <p class="text-sm text-gray-600 mb-4">На какой срок приостановить подписку?</p>
        <div class="space-y-2 mb-6">
          <button 
            @click="handlePauseWithDuration('1week')"
            class="w-full p-3 border border-gray-200 rounded-lg hover:border-gray-300 hover:bg-gray-50 transition-colors text-left"
          >
            <p class="font-medium text-gray-900">На 1 неделю</p>
            <p class="text-xs text-gray-500">Возобновится автоматически</p>
          </button>
          <button 
            @click="handlePauseWithDuration('1month')"
            class="w-full p-3 border border-gray-200 rounded-lg hover:border-gray-300 hover:bg-gray-50 transition-colors text-left"
          >
            <p class="font-medium text-gray-900">На 1 месяц</p>
            <p class="text-xs text-gray-500">Возобновится автоматически</p>
          </button>
          <button 
            @click="handlePauseWithDuration('indefinite')"
            class="w-full p-3 border border-gray-200 rounded-lg hover:border-gray-300 hover:bg-gray-50 transition-colors text-left"
          >
            <p class="font-medium text-gray-900">До востребования</p>
            <p class="text-xs text-gray-500">Требуется ручное возобновление</p>
          </button>
        </div>
        <div class="flex gap-2">
          <button 
            @click="showPauseModal = false"
            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 rounded-lg font-medium transition-colors"
          >
            Отмена
          </button>
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
        <button class="flex flex-col items-center gap-1 text-gray-400 hover:text-gray-600 transition-colors">
          <ShoppingBag class="w-6 h-6" />
          <span class="text-xs">Заказы</span>
        </button>
        <button class="flex flex-col items-center gap-1 text-green-600">
          <Infinity class="w-6 h-6" />
          <span class="text-xs font-medium">Подписки</span>
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
  Infinity, Timer, Package, Plus, Pause, Play, Edit, MapPin, X, 
  ChevronDown, Home, ShoppingBag, User
} from 'lucide-vue-next'
import { useSupermarketApi } from '@/composables/useSupermarketApi'

const api = useSupermarketApi()
const subscriptions = ref<any[]>([])
const loading = ref(false)
const selectedFilter = ref('active')
const expandedSubscriptions = ref<number[]>([])
const showPauseModal = ref(false)
const selectedSubscriptionId = ref<number | null>(null)

const filters = [
  { label: 'Активные', value: 'active' },
  { label: 'Приостановленные', value: 'paused' },
  { label: 'Завершённые', value: 'ended' }
]

const filteredSubscriptions = computed(() => {
  if (selectedFilter.value === 'all') {
    return subscriptions.value
  }
  return subscriptions.value.filter(s => s.status === selectedFilter.value)
})

const loadSubscriptions = async () => {
  loading.value = true
  try {
    subscriptions.value = await api.fetchBuyerSubscriptions()
  } catch (error) {
    console.error('Failed to load subscriptions:', error)
  } finally {
    loading.value = false
  }
}

const toggleSubscriptionDetails = (subscriptionId: number) => {
  const index = expandedSubscriptions.value.indexOf(subscriptionId)
  if (index === -1) {
    expandedSubscriptions.value.push(subscriptionId)
  } else {
    expandedSubscriptions.value.splice(index, 1)
  }
}

const getFrequencyLabel = (frequency: string): string => {
  const labels: Record<string, string> = {
    'weekly': 'Каждую неделю',
    'biweekly': 'Раз в 2 недели',
    'monthly': 'Раз в месяц'
  }
  return labels[frequency] || frequency
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

const getStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    'active': 'Активна',
    'paused': 'Приостановлена',
    'ended': 'Завершена',
    'cancelled': 'Отменена'
  }
  return labels[status] || status
}

const getStatusColor = (status: string): string => {
  const colors: Record<string, string> = {
    'active': 'bg-green-100 text-green-700',
    'paused': 'bg-yellow-100 text-yellow-700',
    'ended': 'bg-gray-100 text-gray-700',
    'cancelled': 'bg-red-100 text-red-700'
  }
  return colors[status] || 'bg-gray-100 text-gray-700'
}

const getDayLabel = (day: number): string => {
  const days = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота']
  return days[day] || `${day}-е число`
}

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('ru-RU', { day: 'numeric', month: 'short', year: 'numeric' })
}

const getTimeUntilDelivery = (dateString: string): string => {
  const now = new Date()
  const deliveryDate = new Date(dateString)
  const diffMs = deliveryDate.getTime() - now.getTime()
  const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))
  const diffHours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  
  if (diffDays > 0) {
    return `через ${diffDays} дн.`
  } else if (diffHours > 0) {
    return `через ${diffHours} ч.`
  }
  return 'Скоро'
}

const handlePauseSubscription = (subscriptionId: number) => {
  selectedSubscriptionId.value = subscriptionId
  showPauseModal.value = true
}

const handlePauseWithDuration = async (duration: string) => {
  if (!selectedSubscriptionId.value) return
  
  try {
    await api.pauseSubscription(selectedSubscriptionId.value.toString(), duration)
    showPauseModal.value = false
    await loadSubscriptions()
  } catch (error) {
    console.error('Failed to pause subscription:', error)
  }
}

const handleResumeSubscription = async (subscriptionId: number) => {
  try {
    await api.pauseSubscription(subscriptionId.toString(), '0')
    await loadSubscriptions()
  } catch (error) {
    console.error('Failed to resume subscription:', error)
  }
}

const handleEditSubscription = (subscriptionId: number) => {
  window.location.href = `/subscriptions/${subscriptionId}/edit`
}

const handleTrackSubscription = (subscriptionId: number) => {
  window.location.href = `/subscriptions/${subscriptionId}/track`
}

const handleCancelSubscription = async (subscriptionId: number) => {
  if (!confirm('Вы уверены, что хотите отменить подписку?')) return
  
  try {
    await api.cancelSubscription(subscriptionId.toString())
    await loadSubscriptions()
  } catch (error) {
    console.error('Failed to cancel subscription:', error)
  }
}

onMounted(() => {
  loadSubscriptions()
})
</script>
