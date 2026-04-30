<template>
  <div class="order-tracking-container">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Отслеживание заказа</h2>
          <p class="text-sm text-gray-500">Заказ #{{ orderUuid }}</p>
        </div>
        <div :class="getStatusBadgeColor(orderStatus)" class="px-4 py-2 rounded-full">
          <span class="font-medium">{{ getStatusLabel(orderStatus) }}</span>
        </div>
      </div>
    </div>

    <!-- ETA and Cold Chain Status -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center gap-3 mb-2">
          <Clock class="w-6 h-6 text-blue-600" />
          <p class="text-sm text-gray-500">Время доставки</p>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ etaMinutes }} мин</p>
        <p class="text-sm text-gray-500 mt-1">{{ estimatedArrival }}</p>
      </div>

      <div v-if="coldChainRequired" class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center gap-3 mb-2">
          <Snowflake class="w-6 h-6" :class="temperatureStatus.color" />
          <p class="text-sm text-gray-500">Температура</p>
        </div>
        <p class="text-2xl font-bold" :class="temperatureStatus.color">
          {{ currentTemperature }}°C
        </p>
        <p class="text-sm mt-1">{{ temperatureStatus.label }}</p>
      </div>

      <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center gap-3 mb-2">
          <MapPin class="w-6 h-6 text-green-600" />
          <p class="text-sm text-gray-500">Расстояние</p>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ distance }} км</p>
        <p class="text-sm text-gray-500 mt-1">до вас</p>
      </div>
    </div>

    <!-- Map -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Карта доставки</h3>
      <div class="relative bg-gray-100 rounded-lg h-96 overflow-hidden">
        <!-- Placeholder for map integration -->
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="text-center">
            <Map class="w-16 h-16 text-gray-400 mx-auto mb-2" />
            <p class="text-gray-500">Карта загружается...</p>
          </div>
        </div>
        
        <!-- Courier Marker -->
        <div 
          v-if="courierLocation"
          class="absolute w-8 h-8 bg-blue-600 rounded-full border-4 border-white shadow-lg flex items-center justify-center"
          :style="{ left: courierLocation.x + '%', top: courierLocation.y + '%' }"
        >
          <Truck class="w-4 h-4 text-white" />
        </div>

        <!-- Destination Marker -->
        <div 
          class="absolute w-8 h-8 bg-red-600 rounded-full border-4 border-white shadow-lg flex items-center justify-center"
          style="left: 80%; top: 70%"
        >
          <MapPin class="w-4 h-4 text-white" />
        </div>

        <!-- Route Line -->
        <svg class="absolute inset-0 w-full h-full pointer-events-none">
          <line 
            x1="20%" 
            y1="30%" 
            x2="80%" 
            y2="70%" 
            stroke="#4F46E5" 
            stroke-width="3" 
            stroke-dasharray="10,5"
          />
        </svg>
      </div>
    </div>

    <!-- Order Progress -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Статус заказа</h3>
      <div class="space-y-4">
        <div 
          v-for="(step, index) in orderSteps" 
          :key="index"
          class="flex items-start gap-4"
        >
          <div 
            :class="getStepStatusClass(step.status)"
            class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
          >
            <CheckCircle v-if="step.status === 'completed'" class="w-5 h-5" />
            <Loader v-else-if="step.status === 'in_progress'" class="w-5 h-5 animate-spin" />
            <Circle v-else class="w-5 h-5" />
          </div>
          <div class="flex-1">
            <p 
              :class="step.status === 'completed' || step.status === 'in_progress' ? 'text-gray-900 font-medium' : 'text-gray-500'"
            >
              {{ step.label }}
            </p>
            <p v-if="step.time" class="text-sm text-gray-500 mt-1">{{ step.time }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Courier Info -->
    <div v-if="courier" class="bg-white rounded-xl shadow-md p-6 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Курьер</h3>
      <div class="flex items-center gap-4">
        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center">
          <User class="w-8 h-8 text-gray-500" />
        </div>
        <div class="flex-1">
          <p class="font-semibold text-gray-900">{{ courier.name }}</p>
          <p class="text-sm text-gray-500">{{ courier.vehicle }}</p>
          <div class="flex items-center gap-1 mt-1">
            <Star class="w-4 h-4 text-yellow-500 fill-current" />
            <span class="text-sm font-medium">{{ courier.rating }}</span>
          </div>
        </div>
        <button 
          @click="callCourier"
          class="bg-green-600 hover:bg-green-700 text-white p-3 rounded-full transition-colors"
        >
          <Phone class="w-5 h-5" />
        </button>
        <button 
          @click="openChat"
          class="bg-indigo-600 hover:bg-indigo-700 text-white p-3 rounded-full transition-colors"
        >
          <MessageCircle class="w-5 h-5" />
        </button>
      </div>
    </div>

    <!-- Temperature History (Cold Chain) -->
    <div v-if="coldChainRequired" class="bg-white rounded-xl shadow-md p-6 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">История температуры</h3>
      <div class="h-48 flex items-end justify-between gap-2">
        <div 
          v-for="(temp, index) in temperatureHistory" 
          :key="index"
          class="flex-1 flex flex-col items-center"
        >
          <div 
            :class="getTemperatureColor(temp)"
            class="w-full rounded-t transition-all"
            :style="{ height: getTemperatureHeight(temp) + '%' }"
            :title="temp + '°C'"
          ></div>
          <span class="text-xs text-gray-500 mt-1">{{ temperatureLabels[index] }}</span>
        </div>
      </div>
      <div class="flex justify-between mt-2 text-xs text-gray-500">
        <span>2°C</span>
        <span>8°C (норма)</span>
        <span>15°C</span>
      </div>
    </div>

    <!-- Chat with Courier -->
    <div v-if="showChat" class="bg-white rounded-xl shadow-md p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Чат с курьером</h3>
        <button 
          @click="showChat = false"
          class="text-gray-400 hover:text-gray-600"
        >
          <X class="w-5 h-5" />
        </button>
      </div>
      
      <div class="space-y-4 max-h-96 overflow-y-auto mb-4">
        <div 
          v-for="message in messages" 
          :key="message.id"
          :class="message.sender === 'me' ? 'flex justify-end' : 'flex justify-start'"
        >
          <div 
            :class="message.sender === 'me' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-900'"
            class="max-w-xs rounded-lg px-4 py-2"
          >
            <p class="text-sm">{{ message.text }}</p>
            <p class="text-xs opacity-70 mt-1">{{ message.time }}</p>
          </div>
        </div>
      </div>
      
      <div class="flex gap-2">
        <input 
          v-model="newMessage"
          type="text"
          placeholder="Написать сообщение..."
          @keyup.enter="sendMessage"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500"
        />
        <button 
          @click="sendMessage"
          class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition-colors"
        >
          <Send class="w-5 h-5" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
  Clock, Snowflake, MapPin, Truck, CheckCircle, Loader, Circle, 
  User, Phone, MessageCircle, Star, Map, X, Send 
} from 'lucide-vue-next'
import { useSupermarketApi } from '@/composables/useSupermarketApi'

const props = defineProps<{
  orderUuid: string
}>()

const api = useSupermarketApi()

const orderStatus = ref('shipped')
const coldChainRequired = ref(true)
const etaMinutes = ref(15)
const currentTemperature = ref(4.5)
const distance = ref(2.3)
const courierLocation = ref({ x: 20, y: 30 })
const courier = ref({
  name: 'Алексей',
  vehicle: 'Peugeot Partner',
  rating: 4.8
})
const showChat = ref(false)
const newMessage = ref('')

const temperatureHistory = ref([3.2, 3.5, 4.0, 4.2, 4.5, 4.3, 4.1, 4.5, 4.8, 4.5])
const temperatureLabels = ref(['10:00', '10:05', '10:10', '10:15', '10:20', '10:25', '10:30', '10:35', '10:40', '10:45'])

const messages = ref([
  { id: 1, sender: 'courier', text: 'Здравствуйте! Везу ваш заказ.', time: '10:30' },
  { id: 2, sender: 'me', text: 'Спасибо! Буду ждать.', time: '10:32' }
])

const orderSteps = ref([
  { label: 'Заказ принят', status: 'completed', time: '10:15' },
  { label: 'Собирается', status: 'completed', time: '10:20' },
  { label: 'В пути', status: 'in_progress', time: '' },
  { label: 'Доставлен', status: 'pending', time: '' }
])

const estimatedArrival = computed(() => {
  const now = new Date()
  now.setMinutes(now.getMinutes() + etaMinutes.value)
  return now.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
})

const temperatureStatus = computed(() => {
  if (currentTemperature.value > 8) {
    return { color: 'text-red-600', label: 'Выше нормы' }
  }
  if (currentTemperature.value < 2) {
    return { color: 'text-yellow-600', label: 'Ниже нормы' }
  }
  return { color: 'text-green-600', label: 'В норме' }
})

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: 'Ожидает',
    processing: 'В обработке',
    shipped: 'В пути',
    delivered: 'Доставлен',
    cancelled: 'Отменён'
  }
  return labels[status] || status
}

const getStatusBadgeColor = (status: string) => {
  const colors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    shipped: 'bg-indigo-100 text-indigo-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const getStepStatusClass = (status: string) => {
  if (status === 'completed') return 'bg-green-600 text-white'
  if (status === 'in_progress') return 'bg-blue-600 text-white'
  return 'bg-gray-200 text-gray-400'
}

const getTemperatureColor = (temp: number) => {
  if (temp > 8) return 'bg-red-500'
  if (temp < 2) return 'bg-yellow-500'
  return 'bg-green-500'
}

const getTemperatureHeight = (temp: number) => {
  // Scale 0-15°C to 0-100%
  return Math.min(100, Math.max(0, (temp / 15) * 100))
}

const callCourier = () => {
  window.location.href = `tel:+79001234567`
}

const openChat = () => {
  showChat.value = true
}

const sendMessage = () => {
  if (!newMessage.value.trim()) return
  messages.value.push({
    id: Date.now(),
    sender: 'me',
    text: newMessage.value,
    time: new Date().toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
  })
  newMessage.value = ''
}

const loadOrderTracking = async () => {
  const data = await api.fetchOrderTracking(props.orderUuid)
  orderStatus.value = data.status
  coldChainRequired.value = data.cold_chain_required
  etaMinutes.value = data.eta_minutes
  currentTemperature.value = data.temperature
  distance.value = data.distance
  courierLocation.value = data.courier_location
  courier.value = data.courier
}

let trackingInterval: number | null = null

onMounted(() => {
  loadOrderTracking()
  // Update tracking every 10 seconds
  trackingInterval = setInterval(loadOrderTracking, 10000) as unknown as number
})

onUnmounted(() => {
  if (trackingInterval) {
    clearInterval(trackingInterval)
  }
})
</script>
