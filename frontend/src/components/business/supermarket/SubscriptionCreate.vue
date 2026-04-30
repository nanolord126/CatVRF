<template>
  <div class="subscription-create min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b border-gray-200 px-4 py-4">
      <div class="flex items-center gap-4">
        <button @click="$emit('back')" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
          <ArrowLeft class="w-5 h-5 text-gray-600" />
        </button>
        <h1 class="text-xl font-bold text-gray-900">Создать подписку</h1>
      </div>
    </div>

    <!-- Progress Steps -->
    <div class="bg-white px-4 py-4 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div 
          v-for="(step, index) in steps"
          :key="index"
          class="flex items-center"
          :class="{ 'flex-1': index < steps.length - 1 }"
        >
          <div 
            class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium transition-colors"
            :class="currentStep > index ? 'bg-green-600 text-white' : currentStep === index ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-600'"
          >
            <Check v-if="currentStep > index" class="w-4 h-4" />
            <span v-else>{{ index + 1 }}</span>
          </div>
          <span 
            class="ml-2 text-sm font-medium"
            :class="currentStep >= index ? 'text-gray-900' : 'text-gray-400'"
          >
            {{ step.label }}
          </span>
          <div 
            v-if="index < steps.length - 1" 
            class="flex-1 h-1 mx-4 rounded"
            :class="currentStep > index ? 'bg-green-600' : 'bg-gray-200'"
          ></div>
        </div>
      </div>
    </div>

    <!-- Step Content -->
    <div class="p-4">
      <!-- Step 1: Select Products -->
      <div v-if="currentStep === 0" class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
          <h3 class="font-semibold text-gray-900 mb-3">Выберите товары</h3>
          
          <!-- Search -->
          <div class="relative mb-4">
            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
            <input 
              v-model="searchQuery"
              type="text"
              placeholder="Поиск товаров..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
            />
          </div>

          <!-- Sub-vertical Filters -->
          <div class="flex gap-2 mb-4 overflow-x-auto pb-2">
            <button 
              v-for="sv in subVerticals"
              :key="sv.id"
              @click="selectedSubVertical = selectedSubVertical === sv.id ? '' : sv.id"
              :class="selectedSubVertical === sv.id ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
              class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition-colors"
            >
              {{ sv.name }}
            </button>
          </div>

          <!-- Products Grid -->
          <div v-if="filteredProducts.length > 0" class="grid grid-cols-2 gap-3 max-h-96 overflow-y-auto">
            <div 
              v-for="product in filteredProducts"
              :key="product.id"
              @click="toggleProduct(product)"
              class="border rounded-lg p-3 cursor-pointer transition-colors"
              :class="isProductSelected(product) ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300'"
            >
              <div class="aspect-square bg-gray-100 rounded-lg mb-2 flex items-center justify-center">
                <Package class="w-8 h-8 text-gray-400" />
              </div>
              <p class="text-sm font-medium text-gray-900 line-clamp-2">{{ product.name }}</p>
              <p class="text-sm font-bold text-gray-900 mt-1">{{ product.price }} ₽</p>
              <div class="flex items-center gap-1 mt-1">
                <Snowflake v-if="product.requires_cold_chain" class="w-3 h-3 text-blue-500" />
                <AlertCircle v-if="product.is_age_restricted" class="w-3 h-3 text-red-500" />
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            Товары не найдены
          </div>
        </div>

        <!-- Minimum Requirements -->
        <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-200">
          <div class="flex items-center gap-3">
            <AlertCircle class="w-5 h-5 text-yellow-600" />
            <div>
              <p class="font-medium text-yellow-900">Минимальные требования</p>
              <p class="text-sm text-yellow-800">
                Минимум {{ minItems }} товаров или {{ minAmount }} ₽
              </p>
              <p class="text-sm text-yellow-800">
                Текущий выбор: {{ selectedProducts.length }} товаров, {{ totalAmount }} ₽
              </p>
            </div>
          </div>
        </div>

        <!-- Add from Last Order -->
        <button class="w-full bg-blue-50 hover:bg-blue-100 text-blue-700 py-3 rounded-xl font-medium transition-colors flex items-center justify-center gap-2">
          <ShoppingBag class="w-5 h-5" />
          Добавить из последнего заказа
        </button>
      </div>

      <!-- Step 2: Schedule -->
      <div v-if="currentStep === 1" class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
          <h3 class="font-semibold text-gray-900 mb-4">Настройка графика доставки</h3>
          
          <!-- Frequency -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Частота доставки</label>
            <div class="grid grid-cols-3 gap-2">
              <button 
                v-for="freq in frequencies"
                :key="freq.value"
                @click="schedule.frequency = freq.value"
                :class="schedule.frequency === freq.value ? 'bg-green-600 text-white border-green-600' : 'bg-white text-gray-700 border-gray-300'"
                class="p-3 border-2 rounded-lg font-medium transition-colors"
              >
                {{ freq.label }}
              </button>
            </div>
          </div>

          <!-- Delivery Day -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">День доставки</label>
            <div v-if="schedule.frequency === 'weekly'" class="grid grid-cols-4 gap-2">
              <button 
                v-for="day in weekDays"
                :key="day.value"
                @click="schedule.delivery_day = day.value"
                :class="schedule.delivery_day === day.value ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700'"
                class="p-2 rounded-lg text-sm font-medium transition-colors"
              >
                {{ day.label }}
              </button>
            </div>
            <select 
              v-else
              v-model="schedule.delivery_day"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500"
            >
              <option v-for="day in monthDays" :key="day" :value="day">{{ day }}-е число</option>
            </select>
          </div>

          <!-- Time Slot -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Время доставки</label>
            <div class="grid grid-cols-3 gap-2">
              <button 
                v-for="slot in timeSlots"
                :key="slot.value"
                @click="schedule.time_slot = slot.value"
                :class="schedule.time_slot === slot.value ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700'"
                class="p-3 rounded-lg text-sm font-medium transition-colors"
              >
                {{ slot.label }}
              </button>
            </div>
          </div>

          <!-- Cold Chain Toggle -->
          <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
            <div class="flex items-center gap-3">
              <Snowflake class="w-5 h-5 text-blue-600" />
              <div>
                <p class="font-medium text-gray-900">Холодная цепь</p>
                <p class="text-xs text-gray-500">Для товаров требующих холода</p>
              </div>
            </div>
            <button 
              @click="schedule.cold_chain = !schedule.cold_chain"
              class="w-12 h-6 rounded-full relative transition-colors"
              :class="schedule.cold_chain ? 'bg-blue-500' : 'bg-gray-300'"
            >
              <span 
                class="absolute top-1 w-4 h-4 bg-white rounded-full transition-all"
                :class="schedule.cold_chain ? 'right-1' : 'left-1'"
              ></span>
            </button>
          </div>

          <!-- Comment -->
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Комментарий для курьера</label>
            <textarea 
              v-model="schedule.comment"
              rows="3"
              placeholder="Например: оставить у двери, позвонить за 10 минут..."
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Step 3: Address and Payment -->
      <div v-if="currentStep === 2" class="space-y-4">
        <!-- Address -->
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
          <h3 class="font-semibold text-gray-900 mb-4">Адрес доставки</h3>
          
          <div class="space-y-3">
            <div 
              v-for="address in addresses"
              :key="address.id"
              @click="selectedAddressId = address.id"
              class="p-3 border-2 rounded-lg cursor-pointer transition-colors"
              :class="selectedAddressId === address.id ? 'border-green-500 bg-green-50' : 'border-gray-200'"
            >
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-medium text-gray-900">{{ address.label }}</p>
                  <p class="text-sm text-gray-600">{{ address.value }}</p>
                </div>
                <Check v-if="selectedAddressId === address.id" class="w-5 h-5 text-green-600" />
              </div>
            </div>
          </div>
          
          <button class="w-full mt-3 bg-gray-100 hover:bg-gray-200 text-gray-900 py-2 rounded-lg font-medium text-sm transition-colors">
            + Добавить новый адрес
          </button>
        </div>

        <!-- Payment Method -->
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
          <h3 class="font-semibold text-gray-900 mb-4">Способ оплаты</h3>
          
          <div class="space-y-3">
            <div 
              @click="paymentMethod = 'card'"
              class="p-3 border-2 rounded-lg cursor-pointer transition-colors flex items-center justify-between"
              :class="paymentMethod === 'card' ? 'border-green-500 bg-green-50' : 'border-gray-200'"
            >
              <div class="flex items-center gap-3">
                <CreditCard class="w-5 h-5 text-gray-600" />
                <div>
                  <p class="font-medium text-gray-900">Рекуррентная карта</p>
                  <p class="text-xs text-gray-500">Автоматическое списание</p>
                </div>
              </div>
              <Check v-if="paymentMethod === 'card'" class="w-5 h-5 text-green-600" />
            </div>
            
            <div 
              @click="paymentMethod = 'sbp'"
              class="p-3 border-2 rounded-lg cursor-pointer transition-colors flex items-center justify-between"
              :class="paymentMethod === 'sbp' ? 'border-green-500 bg-green-50' : 'border-gray-200'"
            >
              <div class="flex items-center gap-3">
                <div class="w-5 h-5 bg-green-500 rounded flex items-center justify-center">
                  <span class="text-white text-xs font-bold">СБП</span>
                </div>
                <div>
                  <p class="font-medium text-gray-900">СБП</p>
                  <p class="text-xs text-gray-500">Только для первой доставки</p>
                </div>
              </div>
              <Check v-if="paymentMethod === 'sbp'" class="w-5 h-5 text-green-600" />
            </div>
          </div>
        </div>

        <!-- Promo Code -->
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
          <label class="block text-sm font-medium text-gray-700 mb-2">Промокод</label>
          <div class="flex gap-2">
            <input 
              v-model="promoCode"
              type="text"
              placeholder="Введите промокод"
              class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500"
            />
            <button class="bg-gray-100 hover:bg-gray-200 text-gray-900 px-4 py-2 rounded-lg font-medium transition-colors">
              Применить
            </button>
          </div>
        </div>
      </div>

      <!-- Step 4: Confirmation -->
      <div v-if="currentStep === 3" class="space-y-4">
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100">
          <h3 class="font-semibold text-gray-900 mb-4">Подтверждение подписки</h3>
          
          <!-- Subscription Name -->
          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Название подписки</label>
            <input 
              v-model="subscriptionName"
              type="text"
              placeholder="Моя подписка"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500"
            />
          </div>

          <!-- Items Summary -->
          <div class="mb-4">
            <h4 class="font-medium text-gray-900 mb-2">Состав подписки</h4>
            <div class="space-y-2 max-h-48 overflow-y-auto">
              <div 
                v-for="item in selectedProducts"
                :key="item.id"
                class="flex items-center justify-between py-2 border-b border-gray-100"
              >
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <Package class="w-4 h-4 text-gray-400" />
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ item.name }}</p>
                    <p class="text-xs text-gray-500">{{ item.quantity }} шт × {{ item.price }} ₽</p>
                  </div>
                </div>
                <p class="text-sm font-semibold text-gray-900">{{ item.price * item.quantity }} ₽</p>
              </div>
            </div>
          </div>

          <!-- Schedule Summary -->
          <div class="bg-gray-50 rounded-lg p-3 mb-4">
            <h4 class="font-medium text-gray-900 mb-2">График</h4>
            <div class="space-y-1 text-sm text-gray-700">
              <p><span class="font-medium">Частота:</span> {{ getFrequencyLabel(schedule.frequency) }}</p>
              <p><span class="font-medium">День:</span> {{ getDayLabel(schedule.delivery_day) }}</p>
              <p><span class="font-medium">Время:</span> {{ getTimeSlotLabel(schedule.time_slot) }}</p>
              <p v-if="schedule.cold_chain" class="text-blue-600"><span class="font-medium">Холодная цепь</span></p>
            </div>
          </div>

          <!-- Pricing -->
          <div class="space-y-2 mb-4">
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Первая доставка:</span>
              <span class="font-medium text-gray-900">{{ totalAmount }} ₽</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-600">Регулярная доставка:</span>
              <span class="font-medium text-gray-900">{{ totalAmount }} ₽</span>
            </div>
            <div v-if="promoDiscount > 0" class="flex justify-between text-sm text-green-600">
              <span>Скидка:</span>
              <span>-{{ promoDiscount }} ₽</span>
            </div>
            <div class="flex justify-between text-lg font-bold pt-2 border-t">
              <span>Итого:</span>
              <span>{{ finalAmount }} ₽</span>
            </div>
          </div>

          <!-- Checkboxes -->
          <div class="space-y-2 mb-4">
            <label class="flex items-start gap-2 cursor-pointer">
              <input type="checkbox" v-model="agreedTerms" class="mt-1 w-4 h-4 text-green-600 border-gray-300 rounded" />
              <span class="text-sm text-gray-600">Я ознакомлен с условиями подписки</span>
            </label>
            <label class="flex items-start gap-2 cursor-pointer">
              <input type="checkbox" v-model="agreedAutoPayment" class="mt-1 w-4 h-4 text-green-600 border-gray-300 rounded" />
              <span class="text-sm text-gray-600">Разрешаю списывать средства автоматически</span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Actions -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4">
      <div class="flex gap-3">
        <button 
          v-if="currentStep > 0"
          @click="prevStep"
          class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-900 py-3 rounded-xl font-medium transition-colors"
        >
          Назад
        </button>
        <button 
          @click="nextStep"
          :disabled="!canProceed"
          :class="canProceed ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-300 cursor-not-allowed'"
          class="flex-1 text-white py-3 rounded-xl font-medium transition-colors"
        >
          {{ currentStep === steps.length - 1 ? 'Оформить подписку' : 'Далее' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { 
  ArrowLeft, Check, Search, Package, Snowflake, AlertCircle, ShoppingBag,
  CreditCard
} from 'lucide-vue-next'

defineEmits(['back', 'created'])

const currentStep = ref(0)
const searchQuery = ref('')
const selectedSubVertical = ref('')
const selectedProducts = ref<any[]>([])
const schedule = ref({
  frequency: 'weekly',
  delivery_day: 1,
  time_slot: 'morning',
  cold_chain: false,
  comment: ''
})
const selectedAddressId = ref<number | null>(null)
const paymentMethod = ref('card')
const promoCode = ref('')
const subscriptionName = ref('')
const agreedTerms = ref(false)
const agreedAutoPayment = ref(false)

const minItems = 8
const minAmount = 1500
const promoDiscount = ref(0)

const steps = [
  { label: 'Товары' },
  { label: 'График' },
  { label: 'Доставка' },
  { label: 'Подтверждение' }
]

const subVerticals = [
  { id: 'meat_shops', name: 'Мясо' },
  { id: 'farm_direct', name: 'Фермерские' },
  { id: 'vegan_products', name: 'Веган' },
  { id: 'confectionery', name: 'Кондитерка' },
  { id: 'grocery_and_delivery', name: 'Бакалея' }
]

const frequencies = [
  { label: 'Еженедельно', value: 'weekly' },
  { label: 'Раз в 2 недели', value: 'biweekly' },
  { label: 'Раз в месяц', value: 'monthly' }
]

const weekDays = [
  { label: 'Пн', value: 1 },
  { label: 'Вт', value: 2 },
  { label: 'Ср', value: 3 },
  { label: 'Чт', value: 4 },
  { label: 'Пт', value: 5 },
  { label: 'Сб', value: 6 },
  { label: 'Вс', value: 0 }
]

const monthDays = Array.from({ length: 25 }, (_, i) => i + 1)

const timeSlots = [
  { label: 'Утро (8-12)', value: 'morning' },
  { label: 'День (12-17)', value: 'day' },
  { label: 'Вечер (17-22)', value: 'evening' }
]

const addresses = ref([
  { id: 1, label: 'Дом', value: 'г. Москва, ул. Примерная, д. 123, кв. 45' },
  { id: 2, label: 'Работа', value: 'г. Москва, ул. Рабочая, д. 10, офис 205' }
])

const products = ref<any[]>([])

const filteredProducts = computed(() => {
  let filtered = products.value
  if (searchQuery.value) {
    filtered = filtered.filter(p => p.name.toLowerCase().includes(searchQuery.value.toLowerCase()))
  }
  if (selectedSubVertical.value) {
    filtered = filtered.filter(p => p.sub_vertical === selectedSubVertical.value)
  }
  return filtered
})

const totalAmount = computed(() => {
  return selectedProducts.value.reduce((sum, p) => sum + p.price * p.quantity, 0)
})

const finalAmount = computed(() => {
  return Math.max(0, totalAmount.value - promoDiscount.value)
})

const canProceed = computed(() => {
  if (currentStep.value === 0) {
    return selectedProducts.value.length >= minItems || totalAmount.value >= minAmount
  }
  if (currentStep.value === 2) {
    return selectedAddressId.value !== null
  }
  if (currentStep.value === 3) {
    return agreedTerms.value && agreedAutoPayment.value
  }
  return true
})

const isProductSelected = (product: any) => {
  return selectedProducts.value.some(p => p.id === product.id)
}

const toggleProduct = (product: any) => {
  const index = selectedProducts.value.findIndex(p => p.id === product.id)
  if (index === -1) {
    selectedProducts.value.push({ ...product, quantity: 1 })
  } else {
    selectedProducts.value.splice(index, 1)
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

const getDayLabel = (day: number): string => {
  if (schedule.value.frequency === 'weekly') {
    const dayNames = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота']
    return dayNames[day]
  }
  return `${day}-е число`
}

const getTimeSlotLabel = (slot: string): string => {
  const labels: Record<string, string> = {
    'morning': 'Утро (8:00-12:00)',
    'day': 'День (12:00-17:00)',
    'evening': 'Вечер (17:00-22:00)'
  }
  return labels[slot] || slot
}

const prevStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const nextStep = () => {
  if (currentStep.value < steps.length - 1) {
    currentStep.value++
  } else {
    // Submit subscription
    console.log('Creating subscription:', {
      items: selectedProducts.value,
      schedule: schedule.value,
      addressId: selectedAddressId.value,
      paymentMethod: paymentMethod.value,
      promoCode: promoCode.value,
      name: subscriptionName.value
    })
    // Emit created event
    // emit('created')
  }
}

const loadProducts = async () => {
  // Mock data - in real app would fetch from API
  products.value = [
    { id: 1, name: 'Говядина высшего сорта', price: 850, sub_vertical: 'meat_shops', requires_cold_chain: true, is_age_restricted: false },
    { id: 2, name: 'Свежее молоко 3.2%', price: 120, sub_vertical: 'farm_direct', requires_cold_chain: true, is_age_restricted: false },
    { id: 3, name: 'Органические яйца', price: 180, sub_vertical: 'farm_direct', requires_cold_chain: true, is_age_restricted: false },
    { id: 4, name: 'Шоколадные конфеты', price: 450, sub_vertical: 'confectionery', requires_cold_chain: false, is_age_restricted: false },
    { id: 5, name: 'Вино сухое', price: 1200, sub_vertical: 'grocery_and_delivery', requires_cold_chain: false, is_age_restricted: true }
  ]
}

onMounted(() => {
  loadProducts()
  // Select first address by default
  if (addresses.value.length > 0) {
    selectedAddressId.value = addresses.value[0].id
  }
})
</script>
