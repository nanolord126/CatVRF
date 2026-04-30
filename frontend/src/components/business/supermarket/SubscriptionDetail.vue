<template>
  <div class="subscription-detail min-h-screen bg-gray-50 pb-20">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-4 py-4">
      <div class="flex items-center gap-4">
        <button @click="$emit('back')" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
          <ArrowLeft class="w-5 h-5 text-gray-600" />
        </button>
        <h1 class="text-xl font-bold text-gray-900">Подписка #{{ subscription?.id }}</h1>
      </div>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
    </div>

    <div v-else-if="subscription" class="p-4 space-y-4">
      <!-- Overall Information -->
      <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="bg-green-100 text-green-600 p-3 rounded-xl">
              <Infinity class="w-6 h-6" />
            </div>
            <div>
              <h2 class="font-semibold text-gray-900">{{ subscription.name || getFrequencyLabel(subscription.frequency) }}</h2>
              <p class="text-sm text-gray-500">{{ getSubVerticalLabel(subscription.sub_vertical) }}</p>
            </div>
          </div>
          <span :class="getStatusColor(subscription.status)" class="text-xs px-3 py-1 rounded-full font-medium">
            {{ getStatusLabel(subscription.status) }}
          </span>
        </div>

        <!-- Next Delivery -->
        <div class="bg-orange-50 rounded-lg p-4 mb-4">
          <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
              <Timer class="w-5 h-5 text-orange-500" />
              <span class="text-sm text-orange-700 font-medium">Следующая доставка:</span>
            </div>
            <span class="text-sm font-bold text-orange-600">{{ formatDate(subscription.next_delivery_at) }}</span>
          </div>
          <p class="text-sm text-orange-600">{{ getTimeUntilDelivery(subscription.next_delivery_at) }}</p>
        </div>

        <!-- Schedule Info -->
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500 mb-1">Частота</p>
            <p class="text-sm font-medium text-gray-900">{{ getFrequencyLabel(subscription.frequency) }}</p>
          </div>
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500 mb-1">День</p>
            <p class="text-sm font-medium text-gray-900">{{ getDayLabel(subscription.delivery_day) }}</p>
          </div>
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500 mb-1">Время</p>
            <p class="text-sm font-medium text-gray-900">{{ getTimeSlotLabel(subscription.time_slot || 'morning') }}</p>
          </div>
          <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-xs text-gray-500 mb-1">Стоимость</p>
            <p class="text-sm font-medium text-gray-900">{{ subscription.total_amount }} ₽</p>
          </div>
        </div>

        <!-- Badges -->
        <div class="flex items-center gap-2 mt-4">
          <span v-if="hasColdChainItems()" class="flex items-center gap-1 text-xs bg-blue-50 text-blue-600 px-2 py-1 rounded-full">
            <Snowflake class="w-3 h-3" />
            Холодная цепь
          </span>
          <span v-if="hasAgeRestrictedItems()" class="flex items-center gap-1 text-xs bg-red-50 text-red-600 px-2 py-1 rounded-full">
            <AlertCircle class="w-3 h-3" />
            18+
          </span>
          <span v-if="subscription.is_b2b" class="flex items-center gap-1 text-xs bg-purple-50 text-purple-600 px-2 py-1 rounded-full">
            <Building2 class="w-3 h-3" />
            B2B
          </span>
        </div>
      </div>

      <!-- Subscription Composition -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-4 border-b border-gray-100 flex items-center justify-between">
          <h3 class="font-semibold text-gray-900">Состав подписки</h3>
          <button @click="editMode = !editMode" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
            {{ editMode ? 'Готово' : 'Изменить' }}
          </button>
        </div>
        
        <div class="p-4 space-y-3">
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
                <div class="flex items-center gap-1 mt-1">
                  <Snowflake v-if="item.product?.requires_cold_chain" class="w-3 h-3 text-blue-500" />
                  <AlertCircle v-if="item.product?.is_age_restricted" class="w-3 h-3 text-red-500" />
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <p class="text-sm font-semibold text-gray-900">{{ item.total_price }} ₽</p>
              <button 
                v-if="editMode"
                @click="handleRemoveItem(item.id)"
                class="p-1 text-red-500 hover:text-red-600"
              >
                <X class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <div v-if="editMode" class="px-4 py-3 bg-gray-50 border-t border-gray-100">
          <button class="w-full bg-blue-50 hover:bg-blue-100 text-blue-700 py-2 rounded-lg font-medium text-sm transition-colors flex items-center justify-center gap-2">
            <Plus class="w-4 h-4" />
            Добавить товары
          </button>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="grid grid-cols-2 gap-3">
        <button 
          @click="handleOneTimeOrder"
          class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 hover:border-gray-200 transition-colors text-left"
        >
          <ShoppingBag class="w-5 h-5 text-green-600 mb-2" />
          <p class="text-sm font-medium text-gray-900">Разовый заказ</p>
          <p class="text-xs text-gray-500">По этой подписке</p>
        </button>
        <button 
          @click="handleAddProducts"
          class="bg-white rounded-xl shadow-sm p-4 border border-gray-100 hover:border-gray-200 transition-colors text-left"
        >
          <Plus class="w-5 h-5 text-blue-600 mb-2" />
          <p class="text-sm font-medium text-gray-900">Добавить товары</p>
          <p class="text-xs text-gray-500">В подписку</p>
        </button>
      </div>

      <!-- Settings -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900">Настройки</h3>
        </div>
        
        <div class="divide-y divide-gray-100">
          <button 
            v-if="subscription.status === 'active'"
            @click="handlePause"
            class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <Pause class="w-5 h-5 text-yellow-600" />
              <span class="text-sm text-gray-900">Приостановить</span>
            </div>
            <ChevronRight class="w-5 h-5 text-gray-400" />
          </button>
          
          <button 
            v-if="subscription.status === 'paused'"
            @click="handleResume"
            class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <Play class="w-5 h-5 text-green-600" />
              <span class="text-sm text-gray-900">Возобновить</span>
            </div>
            <ChevronRight class="w-5 h-5 text-gray-400" />
          </button>
          
          <button 
            @click="handleChangeDate"
            class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <Calendar class="w-5 h-5 text-blue-600" />
              <span class="text-sm text-gray-900">Изменить дату доставки</span>
            </div>
            <ChevronRight class="w-5 h-5 text-gray-400" />
          </button>
          
          <button 
            @click="handleChangeAddress"
            class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <MapPin class="w-5 h-5 text-purple-600" />
              <span class="text-sm text-gray-900">Изменить адрес</span>
            </div>
            <ChevronRight class="w-5 h-5 text-gray-400" />
          </button>
          
          <button 
            @click="handleCancel"
            class="w-full px-4 py-3 flex items-center justify-between hover:bg-red-50 transition-colors"
          >
            <div class="flex items-center gap-3">
              <X class="w-5 h-5 text-red-600" />
              <span class="text-sm text-red-600">Отменить подписку</span>
            </div>
            <ChevronRight class="w-5 h-5 text-gray-400" />
          </button>
        </div>
      </div>

      <!-- Delivery History -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-100">
          <h3 class="font-semibold text-gray-900">История доставок</h3>
        </div>
        
        <div class="p-4 space-y-3">
          <div 
            v-for="delivery in deliveryHistory"
            :key="delivery.id"
            class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0"
          >
            <div>
              <p class="text-sm font-medium text-gray-900">Доставка #{{ delivery.id }}</p>
              <p class="text-xs text-gray-500">{{ formatDate(delivery.date) }}</p>
            </div>
            <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">
              {{ delivery.status }}
            </span>
          </div>
          
          <div v-if="deliveryHistory.length === 0" class="text-center py-4 text-gray-500 text-sm">
            История пуста
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
        <button 
          @click="showPauseModal = false"
          class="w-full bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 rounded-lg font-medium transition-colors"
        >
          Отмена
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { 
  ArrowLeft, Infinity, Timer, Package, Snowflake, AlertCircle, Building2,
  X, Plus, ShoppingBag, Pause, Play, Calendar, MapPin, ChevronRight
} from 'lucide-vue-next'
import { useSupermarketApi } from '@/composables/useSupermarketApi'

const props = defineProps<{
  subscriptionId: string
}>()

defineEmits(['back'])

const api = useSupermarketApi()
const subscription = ref<any>(null)
const loading = ref(false)
const editMode = ref(false)
const showPauseModal = ref(false)

const deliveryHistory = ref([
  { id: 1, date: '2026-04-20', status: 'Доставлено' },
  { id: 2, date: '2026-04-13', status: 'Доставлено' }
])

const loadSubscription = async () => {
  loading.value = true
  try {
    const subscriptions = await api.fetchBuyerSubscriptions()
    subscription.value = subscriptions.find((s: any) => s.id === parseInt(props.subscriptionId))
  } catch (error) {
    console.error('Failed to load subscription:', error)
  } finally {
    loading.value = false
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

const getTimeSlotLabel = (slot: string): string => {
  const labels: Record<string, string> = {
    'morning': 'Утро (8:00-12:00)',
    'day': 'День (12:00-17:00)',
    'evening': 'Вечер (17:00-22:00)'
  }
  return labels[slot] || slot
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

const hasColdChainItems = (): boolean => {
  return subscription.value?.items?.some((item: any) => item.product?.requires_cold_chain) || false
}

const hasAgeRestrictedItems = (): boolean => {
  return subscription.value?.items?.some((item: any) => item.product?.is_age_restricted) || false
}

const handleRemoveItem = (itemId: number) => {
  console.log('Remove item:', itemId)
  // Implement remove logic
}

const handleOneTimeOrder = () => {
  console.log('Create one-time order')
  window.location.href = `/checkout?subscription=${props.subscriptionId}`
}

const handleAddProducts = () => {
  console.log('Add products')
  window.location.href = `/subscriptions/${props.subscriptionId}/edit`
}

const handlePause = () => {
  showPauseModal.value = true
}

const handlePauseWithDuration = async (duration: string) => {
  try {
    await api.pauseSubscription(props.subscriptionId, duration)
    showPauseModal.value = false
    await loadSubscription()
  } catch (error) {
    console.error('Failed to pause subscription:', error)
  }
}

const handleResume = async () => {
  try {
    await api.pauseSubscription(props.subscriptionId, '0')
    await loadSubscription()
  } catch (error) {
    console.error('Failed to resume subscription:', error)
  }
}

const handleChangeDate = () => {
  window.location.href = `/subscriptions/${props.subscriptionId}/schedule`
}

const handleChangeAddress = () => {
  window.location.href = `/subscriptions/${props.subscriptionId}/address`
}

const handleCancel = async () => {
  if (!confirm('Вы уверены, что хотите отменить подписку?')) return
  
  try {
    await api.cancelSubscription(props.subscriptionId)
    window.location.href = '/subscriptions'
  } catch (error) {
    console.error('Failed to cancel subscription:', error)
  }
}

onMounted(() => {
  loadSubscription()
})
</script>
