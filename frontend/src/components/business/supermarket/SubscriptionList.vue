<template>
  <div class="subscription-list min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-4 py-4">
      <div class="flex items-center gap-4">
        <button @click="$emit('back')" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
          <ArrowLeft class="w-5 h-5 text-gray-600" />
        </button>
        <h1 class="text-xl font-bold text-gray-900">Мои подписки</h1>
      </div>
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

    <!-- Subscriptions -->
    <div class="p-4 space-y-4">
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
      </div>

      <div v-else-if="filteredSubscriptions.length === 0" class="text-center py-12">
        <Infinity class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Подписок нет</h3>
        <p class="text-gray-500 mb-4">Создайте подписку для регулярных доставок</p>
        <button @click="$emit('create')" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
          Создать подписку
        </button>
      </div>

      <div v-else class="space-y-4">
        <div 
          v-for="subscription in filteredSubscriptions"
          :key="subscription.id"
          class="bg-white rounded-xl shadow-sm border border-gray-100 p-4"
        >
          <!-- Subscription Header -->
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
              <div class="bg-green-100 text-green-600 p-3 rounded-xl">
                <Infinity class="w-6 h-6" />
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">{{ subscription.name || getFrequencyLabel(subscription.frequency) }}</h3>
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

          <!-- Items Preview -->
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

          <!-- Badges -->
          <div class="flex items-center gap-2 mb-3">
            <span v-if="hasColdChainItems(subscription)" class="flex items-center gap-1 text-xs bg-blue-50 text-blue-600 px-2 py-1 rounded-full">
              <Snowflake class="w-3 h-3" />
              Холодная цепь
            </span>
            <span v-if="hasAgeRestrictedItems(subscription)" class="flex items-center gap-1 text-xs bg-red-50 text-red-600 px-2 py-1 rounded-full">
              <AlertCircle class="w-3 h-3" />
              18+
            </span>
            <span v-if="subscription.is_b2b" class="flex items-center gap-1 text-xs bg-purple-50 text-purple-600 px-2 py-1 rounded-full">
              <Building2 class="w-3 h-3" />
              B2B
            </span>
          </div>

          <!-- Price -->
          <div class="flex items-center justify-between mb-3">
            <span class="text-sm text-gray-500">Стоимость:</span>
            <span class="text-lg font-bold text-gray-900">{{ subscription.total_amount }} ₽</span>
          </div>

          <!-- Action Buttons -->
          <div class="flex gap-2">
            <button 
              v-if="subscription.status === 'active'"
              @click="$emit('pause', subscription.id)"
              class="flex-1 bg-yellow-50 hover:bg-yellow-100 text-yellow-700 py-2 rounded-lg font-medium text-sm transition-colors"
            >
              <Pause class="w-4 h-4 inline mr-1" />
              Приостановить
            </button>
            <button 
              v-if="subscription.status === 'paused'"
              @click="$emit('resume', subscription.id)"
              class="flex-1 bg-green-50 hover:bg-green-100 text-green-700 py-2 rounded-lg font-medium text-sm transition-colors"
            >
              <Play class="w-4 h-4 inline mr-1" />
              Возобновить
            </button>
            <button 
              @click="$emit('edit', subscription.id)"
              class="flex-1 bg-blue-50 hover:bg-blue-100 text-blue-700 py-2 rounded-lg font-medium text-sm transition-colors"
            >
              <Edit class="w-4 h-4 inline mr-1" />
              Изменить
            </button>
            <button 
              @click="$emit('track', subscription.id)"
              class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 rounded-lg font-medium text-sm transition-colors"
            >
              <MapPin class="w-4 h-4 inline mr-1" />
              Отследить
            </button>
            <button 
              @click="$emit('cancel', subscription.id)"
              class="flex-1 bg-red-50 hover:bg-red-100 text-red-600 py-2 rounded-lg font-medium text-sm transition-colors"
            >
              <X class="w-4 h-4 inline mr-1" />
              Отменить
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Floating Action Button -->
    <button 
      @click="$emit('create')"
      class="fixed bottom-6 right-6 bg-green-600 hover:bg-green-700 text-white p-4 rounded-full shadow-lg transition-colors"
    >
      <Plus class="w-6 h-6" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { 
  Infinity, Timer, Package, Plus, Pause, Play, Edit, MapPin, X,
  Snowflake, AlertCircle, Building2, ArrowLeft
} from 'lucide-vue-next'
import { useSupermarketApi } from '@/composables/useSupermarketApi'

defineEmits(['back', 'create', 'pause', 'resume', 'edit', 'track', 'cancel'])

const api = useSupermarketApi()
const subscriptions = ref<any[]>([])
const loading = ref(false)
const selectedFilter = ref('active')

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

const hasColdChainItems = (subscription: any): boolean => {
  return subscription.items?.some((item: any) => item.product?.requires_cold_chain) || false
}

const hasAgeRestrictedItems = (subscription: any): boolean => {
  return subscription.items?.some((item: any) => item.product?.is_age_restricted) || false
}

onMounted(() => {
  loadSubscriptions()
})
</script>
