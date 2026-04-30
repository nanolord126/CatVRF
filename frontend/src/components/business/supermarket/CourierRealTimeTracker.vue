<template>
  <div class="courier-real-time-tracker">
    <div class="tracker-header">
      <div class="courier-info">
        <div class="courier-avatar">
          <User class="avatar-icon" />
        </div>
        <div class="courier-details">
          <h4 class="courier-name">{{ courierName }}</h4>
          <p class="courier-phone">{{ courierPhone }}</p>
        </div>
      </div>
      <div class="eta-badge" :class="etaClass">
        <Clock class="eta-icon" />
        <span class="eta-text">{{ etaText }}</span>
      </div>
    </div>

    <div v-if="showMap" class="map-container">
      <div class="map-placeholder">
        <MapPin class="map-icon" />
        <p class="map-text">Карта загружается...</p>
      </div>
      <CourierMarker 
        v-if="courierLocation"
        :lat="courierLocation.latitude"
        :lng="courierLocation.longitude"
        :destination="deliveryAddress"
      />
    </div>

    <div class="route-info">
      <div class="route-step" :class="{ active: currentStep >= 0, completed: currentStep > 0 }">
        <div class="step-icon">
          <Package class="icon" />
        </div>
        <div class="step-content">
          <span class="step-title">Склад</span>
          <span class="step-time">{{ formatTime(warehouseTime) }}</span>
        </div>
      </div>

      <div class="route-connector" :class="{ active: currentStep >= 1 }"></div>

      <div class="route-step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
        <div class="step-icon">
          <Truck class="icon" />
        </div>
        <div class="step-content">
          <span class="step-title">В пути</span>
          <span class="step-time">{{ formatTime(pickupTime) }}</span>
        </div>
      </div>

      <div class="route-connector" :class="{ active: currentStep >= 2 }"></div>

      <div class="route-step" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
        <div class="step-icon">
          <Home class="icon" />
        </div>
        <div class="step-content">
          <span class="step-title">Доставка</span>
          <span class="step-time">{{ formatTime(deliveryTime) }}</span>
        </div>
      </div>
    </div>

    <div v-if="courierLocation" class="location-details">
      <div class="location-item">
        <Navigation class="location-icon" />
        <div>
          <span class="location-label">Расстояние</span>
          <span class="location-value">{{ distance }} км</span>
        </div>
      </div>
      <div class="location-item">
        <Gauge class="location-icon" />
        <div>
          <span class="location-label">Скорость</span>
          <span class="location-value">{{ speed }} км/ч</span>
        </div>
      </div>
    </div>

    <div class="tracker-actions">
      <button @click="callCourier" class="action-btn primary">
        <Phone class="btn-icon" />
        Позвонить
      </button>
      <button @click="chatWithCourier" class="action-btn secondary">
        <MessageCircle class="btn-icon" />
        Чат
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
  User, 
  Clock, 
  MapPin, 
  Package, 
  Truck, 
  Home, 
  Navigation,
  Gauge,
  Phone,
  MessageCircle
} from 'lucide-vue-next'
import CourierMarker from '@/components/CourierMarker.vue'
import { useSupermarketWebSocket, useSupermarketEventListeners } from '@/composables/useSupermarketWebSocket'

interface CourierLocation {
  latitude: number
  longitude: number
  eta_minutes?: number
  speed?: number
  distance?: number
}

const props = defineProps<{
  orderId: string
  courierName: string
  courierPhone: string
  deliveryAddress: string
  initialLocation?: CourierLocation
  showMap?: boolean
}>()

const emit = defineEmits<{
  callCourier: []
  chatWithCourier: []
}>()

const courierLocation = ref<CourierLocation | null>(props.initialLocation || null)
const currentStep = ref(1)
const warehouseTime = ref(new Date(Date.now() - 30 * 60000))
const pickupTime = ref(new Date(Date.now() - 15 * 60000))
const deliveryTime = ref(new Date(Date.now() + 15 * 60000))

const { connect, subscribeToOrder, unsubscribeFromOrder } = useSupermarketWebSocket()
const { onCourierLocationUpdate } = useSupermarketEventListeners()

let unsubscribeLocationUpdate: (() => void) | null = null

onMounted(async () => {
  await connect('supermarket')
  subscribeToOrder(props.orderId)
  
  unsubscribeLocationUpdate = onCourierLocationUpdate((data) => {
    if (data.order_id === props.orderId) {
      courierLocation.value = {
        latitude: data.latitude,
        longitude: data.longitude,
        eta_minutes: data.eta_minutes,
      }
      deliveryTime.value = new Date(Date.now() + (data.eta_minutes || 15) * 60000)
    }
  })
})

onUnmounted(() => {
  unsubscribeFromOrder(props.orderId)
  if (unsubscribeLocationUpdate) {
    unsubscribeLocationUpdate()
  }
})

const etaClass = computed(() => {
  if (!courierLocation.value?.eta_minutes) return 'normal'
  const eta = courierLocation.value.eta_minutes
  if (eta <= 5) return 'urgent'
  if (eta <= 15) return 'soon'
  return 'normal'
})

const etaText = computed(() => {
  if (!courierLocation.value?.eta_minutes) return 'Расчёт...'
  const eta = courierLocation.value.eta_minutes
  if (eta <= 1) return '< 1 мин'
  if (eta < 60) return `~${eta} мин`
  const hours = Math.floor(eta / 60)
  const mins = eta % 60
  return `~${hours}ч ${mins}мин`
})

const distance = computed(() => {
  return courierLocation.value?.distance?.toFixed(1) || '0.0'
})

const speed = computed(() => {
  return courierLocation.value?.speed?.toFixed(0) || '0'
})

const formatTime = (date: Date): string => {
  return date.toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
}

const callCourier = () => {
  emit('callCourier')
}

const chatWithCourier = () => {
  emit('chatWithCourier')
}
</script>

<style scoped>
.courier-real-time-tracker {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.tracker-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.courier-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.courier-avatar {
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-icon {
  width: 1.5rem;
  height: 1.5rem;
  color: #4f46e5;
}

.courier-details {
  display: flex;
  flex-direction: column;
}

.courier-name {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.courier-phone {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
}

.eta-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 600;
}

.eta-badge.normal {
  background: #f3f4f6;
  color: #374151;
}

.eta-badge.soon {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  color: #d97706;
}

.eta-badge.urgent {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #dc2626;
  animation: urgentPulse 1.5s ease-in-out infinite;
}

@keyframes urgentPulse {
  0%, 100% {
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4);
  }
  50% {
    box-shadow: 0 0 0 8px rgba(239, 68, 68, 0);
  }
}

.eta-icon {
  width: 1rem;
  height: 1rem;
}

.map-container {
  position: relative;
  height: 200px;
  background: #f3f4f6;
  border-radius: 0.75rem;
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.map-placeholder {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
}

.map-icon {
  width: 2rem;
  height: 2rem;
  margin-bottom: 0.5rem;
}

.map-text {
  font-size: 0.875rem;
}

.route-info {
  display: flex;
  align-items: center;
  margin-bottom: 1.5rem;
}

.route-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

.step-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 50%;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.step-icon .icon {
  width: 1.25rem;
  height: 1.25rem;
  color: #9ca3af;
}

.route-step.active .step-icon {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.route-step.active .step-icon .icon {
  color: white;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
}

.route-step.completed .step-icon {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
}

.route-step.completed .step-icon .icon {
  color: #059669;
}

.step-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.step-title {
  font-size: 0.75rem;
  font-weight: 500;
  color: #6b7280;
}

.step-time {
  font-size: 0.625rem;
  color: #9ca3af;
}

.route-connector {
  width: 2px;
  height: 1rem;
  background: #e5e7eb;
  margin: 0 -0.5rem;
  flex-shrink: 0;
}

.route-connector.active {
  background: linear-gradient(180deg, #22c55e 0%, #16a34a 100%);
}

.location-details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.75rem;
  margin-bottom: 1.5rem;
}

.location-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.location-icon {
  width: 1.25rem;
  height: 1.25rem;
  color: #6b7280;
}

.location-item > div {
  display: flex;
  flex-direction: column;
}

.location-label {
  font-size: 0.75rem;
  color: #6b7280;
}

.location-value {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
}

.tracker-actions {
  display: flex;
  gap: 0.75rem;
}

.action-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.action-btn.primary {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: white;
}

.action-btn.primary:hover {
  background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(34, 197, 94, 0.3);
}

.action-btn.secondary {
  background: #f3f4f6;
  color: #374151;
}

.action-btn.secondary:hover {
  background: #e5e7eb;
}

.btn-icon {
  width: 1rem;
  height: 1rem;
}

@media (max-width: 640px) {
  .tracker-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .eta-badge {
    width: 100%;
    justify-content: center;
  }
  
  .location-details {
    grid-template-columns: 1fr;
  }
}
</style>
