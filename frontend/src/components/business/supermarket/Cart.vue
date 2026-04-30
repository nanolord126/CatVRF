<template>
  <div class="cart-container">
    <!-- Header with Timer -->
    <div class="bg-white rounded-xl shadow-md p-4 mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Корзина</h2>
          <p class="text-sm text-gray-500">{{ cartItems.length }} товаров</p>
        </div>
        
        <!-- Reservation Timer -->
        <div v-if="reservationExpiresAt" class="flex items-center gap-3">
          <div :class="timeRemaining <= 300 ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-200'" class="px-4 py-2 rounded-lg border">
            <div class="flex items-center gap-2">
              <Clock class="w-5 h-5" :class="timeRemaining <= 300 ? 'text-red-600' : 'text-blue-600'" />
              <div>
                <p class="text-xs font-medium" :class="timeRemaining <= 300 ? 'text-red-700' : 'text-blue-700'">
                  Резерв истекает через
                </p>
                <p class="text-lg font-bold" :class="timeRemaining <= 300 ? 'text-red-900' : 'text-blue-900'">
                  {{ formatTime(timeRemaining) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Progress Bar -->
      <div v-if="reservationExpiresAt" class="mt-4">
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div 
            :class="timeRemaining <= 300 ? 'bg-red-500' : 'bg-blue-500'"
            class="h-2 rounded-full transition-all duration-1000"
            :style="{ width: (timeRemaining / 1200 * 100) + '%' }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Cold Chain Warning -->
    <div v-if="hasColdChainItems" class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
      <div class="flex items-start gap-3">
        <Snowflake class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" />
        <div>
          <p class="font-medium text-blue-900">Товары требующие холодовой цепи</p>
          <p class="text-sm text-blue-700 mt-1">
            В вашем заказе есть скоропортящиеся товары. Доставка будет осуществлена в специальном транспорте с контролем температуры.
          </p>
        </div>
      </div>
    </div>

    <!-- Cart Items -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
      <div v-if="cartItems.length > 0" class="space-y-4">
        <div 
          v-for="item in cartItems" 
          :key="item.id"
          class="flex gap-4 p-4 border border-gray-200 rounded-lg hover:border-gray-300 transition-colors"
        >
          <!-- Product Image -->
          <div class="w-24 h-24 flex-shrink-0">
            <img 
              :src="item.product.image || '/placeholder-product.jpg'"
              :alt="item.product.name"
              class="w-full h-full object-cover rounded-lg"
            />
          </div>

          <!-- Product Info -->
          <div class="flex-1">
            <div class="flex items-start justify-between mb-2">
              <div>
                <h3 class="font-semibold text-gray-900">{{ item.product.name }}</h3>
                <p class="text-sm text-gray-500">{{ item.product.sub_vertical_label }}</p>
              </div>
              <button 
                @click="removeItem(item.id)"
                class="text-gray-400 hover:text-red-600 transition-colors"
              >
                <X class="w-5 h-5" />
              </button>
            </div>

            <!-- Attributes -->
            <div v-if="item.attributes && Object.keys(item.attributes).length > 0" class="flex flex-wrap gap-2 mb-3">
              <span 
                v-for="(value, key) in item.attributes" 
                :key="key"
                class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded"
              >
                {{ key }}: {{ value }}
              </span>
            </div>

            <!-- Price & Quantity -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="flex items-center border border-gray-300 rounded-lg">
                  <button 
                    @click="updateQuantity(item.id, item.quantity - 1)"
                    :disabled="item.quantity <= 1"
                    class="px-3 py-1 text-gray-600 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <Minus class="w-4 h-4" />
                  </button>
                  <span class="px-4 py-1 font-medium">{{ item.quantity }}</span>
                  <button 
                    @click="updateQuantity(item.id, item.quantity + 1)"
                    class="px-3 py-1 text-gray-600 hover:bg-gray-100"
                  >
                    <Plus class="w-4 h-4" />
                  </button>
                </div>
                <p class="text-sm text-gray-500">
                  {{ formatPrice(item.product.price * item.quantity) }}
                </p>
              </div>
              <p class="text-lg font-bold text-gray-900">
                {{ formatPrice(item.total) }}
              </p>
            </div>

            <!-- Cold Chain Badge -->
            <div v-if="item.product.requires_cold_chain" class="mt-2">
              <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full">
                <Snowflake class="w-3 h-3" />
                Холодовая цепь
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-12">
        <ShoppingCart class="w-16 h-16 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Корзина пуста</h3>
        <p class="text-gray-500 mb-4">Добавьте товары из каталога</p>
        <button 
          @click="goToCatalog"
          class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-6 rounded-lg font-medium transition-colors"
        >
          Перейти в каталог
        </button>
      </div>
    </div>

    <!-- Cross-sell Recommendations -->
    <div v-if="crossSellProducts.length > 0 && cartItems.length > 0" class="bg-white rounded-xl shadow-md p-6 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Рекомендуем к заказу</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div 
          v-for="product in crossSellProducts" 
          :key="product.id"
          class="border border-gray-200 rounded-lg p-3 hover:border-indigo-300 transition-colors cursor-pointer"
          @click="addToCart(product)"
        >
          <img 
            :src="product.image || '/placeholder-product.jpg'"
            :alt="product.name"
            class="w-full aspect-square object-cover rounded-lg mb-2"
          />
          <h4 class="text-sm font-medium text-gray-900 line-clamp-2">{{ product.name }}</h4>
          <p class="text-sm font-bold text-gray-900 mt-1">{{ formatPrice(product.price) }}</p>
          <button class="w-full mt-2 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 py-1 px-3 rounded-lg text-sm font-medium transition-colors">
            Добавить
          </button>
        </div>
      </div>
    </div>

    <!-- Summary & Checkout -->
    <div v-if="cartItems.length > 0" class="bg-white rounded-xl shadow-md p-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Итого</h3>
      
      <div class="space-y-3 mb-6">
        <div class="flex justify-between text-sm">
          <span class="text-gray-600">Товары</span>
          <span class="font-medium">{{ formatPrice(subtotal) }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-gray-600">Доставка</span>
          <span class="font-medium">{{ deliveryCost > 0 ? formatPrice(deliveryCost) : 'Рассчитывается' }}</span>
        </div>
        <div v-if="coldChainFee > 0" class="flex justify-between text-sm">
          <span class="text-gray-600">Холодовая цепь</span>
          <span class="font-medium">{{ formatPrice(coldChainFee) }}</span>
        </div>
        <div class="border-t border-gray-200 pt-3">
          <div class="flex justify-between">
            <span class="text-lg font-bold text-gray-900">К оплате</span>
            <span class="text-lg font-bold text-gray-900">{{ formatPrice(total) }}</span>
          </div>
        </div>
      </div>

      <button 
        @click="proceedToCheckout"
        :disabled="!reservationExpiresAt || timeRemaining <= 0"
        class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white py-3 px-6 rounded-lg font-medium transition-colors"
      >
        Оформить заказ
      </button>

      <p v-if="!reservationExpiresAt" class="text-center text-sm text-gray-500 mt-3">
        Сначала добавьте товары в корзину для резервирования
      </p>
      <p v-else-if="timeRemaining <= 0" class="text-center text-sm text-red-600 mt-3">
        Время резервирования истекло. Пожалуйста, оформите заказ заново.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Clock, Snowflake, X, Minus, Plus, ShoppingCart } from 'lucide-vue-next'
import { useSupermarketApi } from '@/composables/useSupermarketApi'

interface CartItem {
  id: string
  product: {
    id: string
    name: string
    price: number
    image?: string
    requires_cold_chain: boolean
    sub_vertical_label: string
  }
  quantity: number
  total: number
  attributes?: Record<string, any>
}

interface Product {
  id: string
  name: string
  price: number
  image?: string
}

const api = useSupermarketApi()

const cartItems = ref<CartItem[]>([])
const crossSellProducts = ref<Product[]>([])
const reservationExpiresAt = ref<Date | null>(null)
const timeRemaining = ref(1200) // 20 minutes in seconds
let timerInterval: number | null = null

const subtotal = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + item.total, 0)
})

const deliveryCost = computed(() => {
  return 0 // Will be calculated by GeoLogistics
})

const coldChainFee = computed(() => {
  return hasColdChainItems.value ? 500 : 0 // 5 RUB for cold chain
})

const total = computed(() => {
  return subtotal.value + deliveryCost.value + coldChainFee.value
})

const hasColdChainItems = computed(() => {
  return cartItems.value.some(item => item.product.requires_cold_chain)
})

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(price)
}

const formatTime = (seconds: number) => {
  const mins = Math.floor(seconds / 60)
  const secs = seconds % 60
  return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
}

const updateTimer = () => {
  if (!reservationExpiresAt.value) return
  
  const now = new Date()
  const diff = Math.floor((reservationExpiresAt.value.getTime() - now.getTime()) / 1000)
  
  if (diff <= 0) {
    timeRemaining.value = 0
    if (timerInterval) {
      clearInterval(timerInterval)
      timerInterval = null
    }
  } else {
    timeRemaining.value = diff
  }
}

const removeItem = async (itemId: string) => {
  await api.removeFromCart(itemId)
  cartItems.value = cartItems.value.filter(item => item.id !== itemId)
}

const updateQuantity = async (itemId: string, quantity: number) => {
  if (quantity < 1) return
  await api.updateCartItem(itemId, quantity)
  const item = cartItems.value.find(i => i.id === itemId)
  if (item) {
    item.quantity = quantity
    item.total = item.product.price * quantity
  }
}

const addToCart = async (product: Product) => {
  await api.addToCart(product.id)
  // Refresh cart
  await loadCart()
}

const loadCart = async () => {
  const data = await api.fetchCart()
  cartItems.value = data.items || []
  reservationExpiresAt.value = data.reservation_expires_at ? new Date(data.reservation_expires_at) : null
  
  if (reservationExpiresAt.value) {
    updateTimer()
    timerInterval = setInterval(updateTimer, 1000) as unknown as number
  }
}

const loadCrossSell = async () => {
  if (cartItems.value.length === 0) return
  const data = await api.fetchCrossSellRecommendations()
  crossSellProducts.value = data || []
}

const proceedToCheckout = () => {
  // Navigate to checkout page
  window.location.href = '/supermarket/checkout'
}

const goToCatalog = () => {
  window.location.href = '/supermarket/catalog'
}

onMounted(async () => {
  await loadCart()
  await loadCrossSell()
})

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval)
  }
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
