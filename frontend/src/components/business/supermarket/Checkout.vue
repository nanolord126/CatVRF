<template>
  <div class="checkout-container max-w-4xl mx-auto">
    <!-- Progress Bar -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold text-gray-900">Оформление заказа</h2>
        <div v-if="reservationExpiresAt" class="flex items-center gap-2">
          <Clock class="w-5 h-5 text-blue-600" />
          <span class="font-medium" :class="timeRemaining <= 300 ? 'text-red-600' : 'text-blue-600'">
            {{ formatTime(timeRemaining) }}
          </span>
        </div>
      </div>
      
      <div class="flex items-center justify-between">
        <div 
          v-for="(step, index) in steps" 
          :key="index"
          class="flex items-center"
          :class="index < steps.length - 1 ? 'flex-1' : ''"
        >
          <div class="flex flex-col items-center">
            <div 
              :class="getStepClass(index)"
              class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-colors"
            >
              <span v-if="currentStep > index">✓</span>
              <span v-else>{{ index + 1 }}</span>
            </div>
            <span 
              :class="currentStep === index ? 'text-indigo-600 font-medium' : 'text-gray-500'"
              class="text-xs mt-2 text-center"
            >
              {{ step }}
            </span>
          </div>
          <div 
            v-if="index < steps.length - 1"
            class="flex-1 h-1 mx-2"
            :class="currentStep > index ? 'bg-indigo-600' : 'bg-gray-200'"
          ></div>
        </div>
      </div>
    </div>

    <!-- Step Content -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
      <!-- Step 1: Address -->
      <div v-if="currentStep === 0" class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-900">Адрес доставки</h3>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Выберите адрес</label>
            <select 
              v-model="selectedAddressId"
              class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">Новый адрес</option>
              <option 
                v-for="address in savedAddresses" 
                :key="address.id"
                :value="address.id"
              >
                {{ address.label }}: {{ address.full_address }}
              </option>
            </select>
          </div>

          <div v-if="!selectedAddressId" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Город *</label>
              <input 
                v-model="address.city"
                type="text"
                required
                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Улица *</label>
              <input 
                v-model="address.street"
                type="text"
                required
                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
              />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Дом *</label>
                <input 
                  v-model="address.house"
                  type="text"
                  required
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Квартира</label>
                <input 
                  v-model="address.apartment"
                  type="text"
                  class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
                />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Комментарий для курьера</label>
              <textarea 
                v-model="address.comment"
                rows="2"
                class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
              />
            </div>
          </div>

          <button 
            @click="saveAddress"
            class="bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 px-4 rounded-lg font-medium transition-colors"
          >
            Сохранить адрес
          </button>
        </div>
      </div>

      <!-- Step 2: Delivery Slot -->
      <div v-if="currentStep === 1" class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-900">Выберите слот доставки</h3>
        
        <!-- Delivery Zones -->
        <div class="mb-4">
          <p class="text-sm text-gray-600 mb-2">Ваша зона доставки:</p>
          <div class="flex items-center gap-2 p-3 bg-green-50 rounded-lg">
            <MapPin class="w-5 h-5 text-green-600" />
            <span class="font-medium text-green-900">{{ deliveryZone.name }}</span>
            <span class="text-sm text-green-700">({{ deliveryZone.eta }} мин)</span>
          </div>
        </div>

        <!-- Available Slots -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Доступные слоты:</label>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div 
              v-for="slot in availableSlots" 
              :key="slot.id"
              @click="selectSlot(slot)"
              :class="selectedSlot?.id === slot.id ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300'"
              class="border-2 rounded-lg p-4 cursor-pointer transition-colors"
            >
              <div class="flex items-center justify-between mb-2">
                <span class="font-medium text-gray-900">{{ slot.time_range }}</span>
                <span class="text-sm font-bold text-gray-900">{{ formatPrice(slot.cost) }}</span>
              </div>
              <p class="text-sm text-gray-500">{{ slot.date }}</p>
            </div>
          </div>
        </div>

        <!-- Cold Chain Info -->
        <div v-if="hasColdChainItems" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex items-start gap-3">
            <Snowflake class="w-5 h-5 text-blue-600 mt-0.5" />
            <div>
              <p class="font-medium text-blue-900">Холодовая цепь</p>
              <p class="text-sm text-blue-700 mt-1">
                Доставка товаров требующих охлаждения будет осуществлена в специальном транспорте с контролем температуры.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 3: Payment -->
      <div v-if="currentStep === 2" class="space-y-6">
        <h3 class="text-lg font-semibold text-gray-900">Способ оплаты</h3>
        
        <!-- Order Summary -->
        <div class="bg-gray-50 rounded-lg p-4">
          <h4 class="font-medium text-gray-900 mb-3">Ваш заказ</h4>
          <div class="space-y-2">
            <div v-for="item in cartItems" :key="item.id" class="flex justify-between text-sm">
              <span>{{ item.product.name }} x {{ item.quantity }}</span>
              <span>{{ formatPrice(item.total) }}</span>
            </div>
          </div>
          <div class="border-t border-gray-200 mt-3 pt-3">
            <div class="flex justify-between font-bold">
              <span>Итого</span>
              <span>{{ formatPrice(total) }}</span>
            </div>
          </div>
        </div>

        <!-- Payment Methods -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Выберите способ оплаты:</label>
          <div class="space-y-3">
            <div 
              v-for="method in paymentMethods" 
              :key="method.id"
              @click="selectPaymentMethod(method)"
              :class="selectedPaymentMethod?.id === method.id ? 'border-indigo-600 bg-indigo-50' : 'border-gray-200'"
              class="border-2 rounded-lg p-4 cursor-pointer transition-colors"
            >
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                  <CreditCard class="w-6 h-6 text-gray-600" />
                </div>
                <div>
                  <p class="font-medium text-gray-900">{{ method.name }}</p>
                  <p class="text-sm text-gray-500">{{ method.description }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Fraud Check -->
        <div v-if="fraudCheckInProgress" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <div class="flex items-center gap-3">
            <Loader class="w-5 h-5 text-yellow-600 animate-spin" />
            <p class="text-sm text-yellow-800">Проверка безопасности...</p>
          </div>
        </div>
      </div>

      <!-- Step 4: Confirmation -->
      <div v-if="currentStep === 3" class="space-y-6">
        <div class="text-center">
          <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <CheckCircle class="w-8 h-8 text-green-600" />
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Заказ успешно оформлен!</h3>
          <p class="text-gray-600">Номер заказа: #{{ orderNumber }}</p>
        </div>

        <!-- Order Details -->
        <div class="bg-gray-50 rounded-lg p-4 space-y-3">
          <div class="flex justify-between">
            <span class="text-gray-600">Адрес доставки:</span>
            <span class="font-medium">{{ fullAddress }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Слот доставки:</span>
            <span class="font-medium">{{ selectedSlot?.time_range }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Способ оплаты:</span>
            <span class="font-medium">{{ selectedPaymentMethod?.name }}</span>
          </div>
          <div class="border-t border-gray-200 pt-3">
            <div class="flex justify-between font-bold">
              <span>К оплате:</span>
              <span>{{ formatPrice(total) }}</span>
            </div>
          </div>
        </div>

        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
          <p class="text-sm text-indigo-900">
            Мы отправим уведомление когда курьер отправится к вам. Вы сможете отслеживать заказ в реальном времени.
          </p>
        </div>

        <button 
          @click="goToTracking"
          class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 px-6 rounded-lg font-medium transition-colors"
        >
          Отследить заказ
        </button>
      </div>
    </div>

    <!-- Navigation Buttons -->
    <div v-if="currentStep < 3" class="flex gap-4">
      <button 
        v-if="currentStep > 0"
        @click="previousStep"
        class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-3 px-6 rounded-lg font-medium transition-colors"
      >
        Назад
      </button>
      <button 
        @click="nextStep"
        :disabled="!canProceed"
        class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white py-3 px-6 rounded-lg font-medium transition-colors"
      >
        {{ currentStep === 2 ? 'Оплатить' : 'Далее' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Clock, MapPin, Snowflake, CreditCard, Loader, CheckCircle } from 'lucide-vue-next'
import { useSupermarketApi } from '@/composables/useSupermarketApi'

interface Address {
  id?: string
  city: string
  street: string
  house: string
  apartment: string
  comment: string
  full_address?: string
  label?: string
}

interface DeliverySlot {
  id: string
  date: string
  time_range: string
  cost: number
}

interface PaymentMethod {
  id: string
  name: string
  description: string
}

const api = useSupermarketApi()

const steps = ['Адрес', 'Слот', 'Оплата', 'Подтверждение']
const currentStep = ref(0)
const reservationExpiresAt = ref<Date | null>(null)
const timeRemaining = ref(1200)
let timerInterval: number | null = null

// Step 1: Address
const savedAddresses = ref<Address[]>([])
const selectedAddressId = ref<string>('')
const address = ref<Address>({
  city: '',
  street: '',
  house: '',
  apartment: '',
  comment: ''
})

// Step 2: Delivery
const deliveryZone = ref({ name: 'Центральный', eta: 30 })
const availableSlots = ref<DeliverySlot[]>([])
const selectedSlot = ref<DeliverySlot | null>(null)

// Step 3: Payment
const paymentMethods = ref<PaymentMethod[]>([
  { id: 'card', name: 'Банковская карта', description: 'Visa, Mastercard, МИР' },
  { id: 'sbp', name: 'СБП', description: 'Система быстрых платежей' },
  { id: 'tinkoff', name: 'Т-Банк', description: 'Т-Банк Pay' }
])
const selectedPaymentMethod = ref<PaymentMethod | null>(null)
const fraudCheckInProgress = ref(false)

// Step 4: Confirmation
const orderNumber = ref('')

// Cart data
const cartItems = ref<any[]>([])
const hasColdChainItems = ref(false)

const total = computed(() => {
  return cartItems.value.reduce((sum, item) => sum + item.total, 0) + 
         (selectedSlot.value?.cost || 0) + 
         (hasColdChainItems.value ? 500 : 0)
})

const fullAddress = computed(() => {
  if (selectedAddressId.value) {
    const addr = savedAddresses.value.find(a => a.id === selectedAddressId.value)
    return addr?.full_address || ''
  }
  return `${address.value.city}, ${address.value.street}, д. ${address.value.house}${address.value.apartment ? ', кв. ' + address.value.apartment : ''}`
})

const canProceed = computed(() => {
  if (currentStep.value === 0) {
    if (selectedAddressId.value) return true
    return address.value.city && address.value.street && address.value.house
  }
  if (currentStep.value === 1) {
    return selectedSlot.value !== null
  }
  if (currentStep.value === 2) {
    return selectedPaymentMethod.value !== null
  }
  return true
})

const getStepClass = (index: number) => {
  if (currentStep.value > index) return 'bg-green-600 text-white'
  if (currentStep.value === index) return 'bg-indigo-600 text-white'
  return 'bg-gray-200 text-gray-600'
}

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

const saveAddress = () => {
  const newAddress = {
    ...address.value,
    id: Date.now().toString(),
    full_address: fullAddress.value,
    label: `Адрес ${savedAddresses.value.length + 1}`
  }
  savedAddresses.value.push(newAddress)
  selectedAddressId.value = newAddress.id
}

const selectSlot = (slot: DeliverySlot) => {
  selectedSlot.value = slot
}

const selectPaymentMethod = (method: PaymentMethod) => {
  selectedPaymentMethod.value = method
}

const nextStep = async () => {
  if (currentStep.value === 2) {
    // Process payment
    fraudCheckInProgress.value = true
    try {
      const result = await api.processCheckout({
        address: fullAddress.value,
        slot_id: selectedSlot.value!.id,
        payment_method: selectedPaymentMethod.value!.id
      })
      orderNumber.value = result.order_uuid
      currentStep.value = 3
    } catch (error) {
      console.error('Payment failed', error)
    } finally {
      fraudCheckInProgress.value = false
    }
  } else {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const goToTracking = () => {
  window.location.href = `/supermarket/tracking/${orderNumber.value}`
}

const loadInitialData = async () => {
  const cart = await api.fetchCart()
  cartItems.value = cart.items || []
  hasColdChainItems.value = cart.items?.some((i: any) => i.product.requires_cold_chain) || false
  reservationExpiresAt.value = cart.reservation_expires_at ? new Date(cart.reservation_expires_at) : null
  
  if (reservationExpiresAt.value) {
    updateTimer()
    timerInterval = setInterval(updateTimer, 1000) as unknown as number
  }

  // Load delivery slots
  const slots = await api.fetchDeliverySlots()
  availableSlots.value = slots || []
}

onMounted(() => {
  loadInitialData()
})

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval)
  }
})
</script>
