<template>
  <div class="order-list">
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Поиск</label>
          <input 
            v-model="searchQuery"
            type="text"
            placeholder="UUID заказа..."
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
          <select 
            v-model="selectedStatus"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">Все</option>
            <option value="pending">Ожидает</option>
            <option value="paid">Оплачен</option>
            <option value="processing">В обработке</option>
            <option value="shipped">Отправлен</option>
            <option value="delivered">Доставлен</option>
            <option value="cancelled">Отменён</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Под-вертикаль</label>
          <select 
            v-model="selectedSubVertical"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">Все</option>
            <option value="meat_shops">Мясные магазины</option>
            <option value="farm_direct">Фермерские продукты</option>
            <option value="vegan_products">Веганские продукты</option>
            <option value="confectionery">Кондитерские изделия</option>
            <option value="grocery_and_delivery">Бакалея и доставка</option>
            <option value="food">Еда</option>
            <option value="office_catering">Офисный кейтеринг</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Холодовая цепь</label>
          <select 
            v-model="selectedColdChain"
            class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
          >
            <option value="">Все</option>
            <option value="true">Требует холода</option>
            <option value="false">Не требует</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-xl shadow-md p-4">
        <p class="text-sm text-gray-500 mb-1">Всего заказов</p>
        <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-md p-4">
        <p class="text-sm text-gray-500 mb-1">Новых</p>
        <p class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-md p-4">
        <p class="text-sm text-gray-500 mb-1">В обработке</p>
        <p class="text-2xl font-bold text-blue-600">{{ stats.processing }}</p>
      </div>
      <div class="bg-white rounded-xl shadow-md p-4">
        <p class="text-sm text-gray-500 mb-1">Отправлено</p>
        <p class="text-2xl font-bold text-indigo-600">{{ stats.shipped }}</p>
      </div>
    </div>

    <!-- Orders Grid -->
    <div v-if="filteredOrders.length > 0" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
      <OrderCard
        v-for="order in paginatedOrders"
        :key="order.id"
        :order="order"
        @view-details="handleViewDetails"
        @accept-order="handleAcceptOrder"
        @reject-order="handleRejectOrder"
        @ready-for-delivery="handleReadyForDelivery"
      />
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white rounded-xl shadow-md p-12 text-center">
      <ShoppingCart class="w-16 h-16 text-gray-400 mx-auto mb-4" />
      <h3 class="text-lg font-semibold text-gray-900 mb-2">Заказы не найдены</h3>
      <p class="text-gray-500 mb-4">Попробуйте изменить параметры фильтрации</p>
      <button 
        @click="resetFilters"
        class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-6 rounded-lg font-medium transition-colors"
      >
        Сбросить фильтры
      </button>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex justify-center mt-6">
      <div class="flex gap-2">
        <button 
          v-for="page in totalPages"
          :key="page"
          @click="currentPage = page"
          :class="currentPage === page ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
          class="px-4 py-2 rounded-lg font-medium transition-colors border border-gray-300"
        >
          {{ page }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { ShoppingCart } from 'lucide-vue-next'
import OrderCard from './OrderCard.vue'

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
  orders: Order[]
}>()

const emit = defineEmits<{
  viewDetails: [order: Order]
  acceptOrder: [order: Order]
  rejectOrder: [order: Order]
  readyForDelivery: [order: Order]
}>()

const searchQuery = ref('')
const selectedStatus = ref('')
const selectedSubVertical = ref('')
const selectedColdChain = ref('')
const currentPage = ref(1)
const itemsPerPage = 12

const filteredOrders = computed(() => {
  let filtered = [...props.orders]

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(o => o.uuid.toLowerCase().includes(query))
  }

  if (selectedStatus.value) {
    filtered = filtered.filter(o => o.status === selectedStatus.value)
  }

  if (selectedSubVertical.value) {
    filtered = filtered.filter(o => o.sub_vertical === selectedSubVertical.value)
  }

  if (selectedColdChain.value) {
    filtered = filtered.filter(o => 
      selectedColdChain.value === 'true' ? o.cold_chain_required : !o.cold_chain_required
    )
  }

  return filtered.sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
})

const paginatedOrders = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage
  return filteredOrders.value.slice(start, start + itemsPerPage)
})

const totalPages = computed(() => {
  return Math.ceil(filteredOrders.value.length / itemsPerPage)
})

const stats = computed(() => ({
  total: props.orders.length,
  pending: props.orders.filter(o => o.status === 'pending').length,
  processing: props.orders.filter(o => o.status === 'processing').length,
  shipped: props.orders.filter(o => o.status === 'shipped').length
}))

const handleViewDetails = (order: Order) => {
  emit('viewDetails', order)
}

const handleAcceptOrder = (order: Order) => {
  emit('acceptOrder', order)
}

const handleRejectOrder = (order: Order) => {
  emit('rejectOrder', order)
}

const handleReadyForDelivery = (order: Order) => {
  emit('readyForDelivery', order)
}

const resetFilters = () => {
  searchQuery.value = ''
  selectedStatus.value = ''
  selectedSubVertical.value = ''
  selectedColdChain.value = ''
  currentPage.value = 1
}
</script>
