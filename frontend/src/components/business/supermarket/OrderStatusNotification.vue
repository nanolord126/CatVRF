<template>
  <div 
    class="order-status-notification"
    :class="[`status-${notification.data.new_status}`, { dismissed: isDismissed }]"
  >
    <div class="notification-icon">
      <component :is="getStatusIcon(notification.data.new_status)" class="icon" />
    </div>
    
    <div class="notification-content">
      <div class="notification-header">
        <h4 class="notification-title">{{ notification.title }}</h4>
        <span class="notification-time">{{ formatTime(notification.timestamp) }}</span>
      </div>
      
      <p class="notification-message">{{ getOrderStatusMessage() }}</p>
      
      <div class="order-info">
        <div class="order-number">Заказ #{{ notification.data.order_number }}</div>
        <div class="delivery-type">
          <Truck v-if="notification.data.delivery_type === 'courier'" class="delivery-icon" />
          <MapPin v-else class="delivery-icon" />
          <span>{{ notification.data.delivery_type === 'courier' ? 'Доставка' : 'Самозабор' }}</span>
        </div>
      </div>
      
      <div v-if="showStatusTransition" class="status-transition">
        <span class="old-status">{{ getStatusLabel(notification.data.old_status) }}</span>
        <ArrowRight class="arrow" />
        <span class="new-status">{{ getStatusLabel(notification.data.new_status) }}</span>
      </div>
      
      <div class="notification-actions">
        <button @click="viewOrder" class="action-btn primary">
          Открыть заказ
        </button>
        <button @click="trackOrder" class="action-btn secondary">
          Отследить
        </button>
      </div>
    </div>
    
    <button @click="dismiss" class="dismiss-btn">
      <X class="dismiss-icon" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { 
  CheckCircle, 
  Clock, 
  Package, 
  Truck, 
  MapPin, 
  ArrowRight, 
  X,
  AlertCircle,
  XCircle
} from 'lucide-vue-next'

interface OrderStatusData {
  order_id: string
  order_number: string
  old_status: string
  new_status: string
  delivery_type: 'courier' | 'pickup'
}

interface OrderStatusNotification {
  id: string
  type: 'order_status'
  title: string
  message: string
  data: OrderStatusData
  timestamp: string
  read: boolean
  priority: 'low' | 'medium' | 'high' | 'critical'
}

const props = defineProps<{
  notification: OrderStatusNotification
}>()

const emit = defineEmits<{
  dismiss: [id: string]
  viewOrder: [orderId: string]
  trackOrder: [orderId: string]
}>()

const isDismissed = ref(false)

const showStatusTransition = computed(() => {
  return props.notification.data.old_status && 
         props.notification.data.old_status !== props.notification.data.new_status
})

const formatTime = (timestamp: string): string => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  
  if (diffMins < 1) return 'Только что'
  if (diffMins < 60) return `${diffMins} мин. назад`
  if (diffMins < 1440) return `${Math.floor(diffMins / 60)} ч. назад`
  return date.toLocaleDateString('ru-RU')
}

const getOrderStatusMessage = (): string => {
  const { new_status, delivery_type } = props.notification.data
  const messages: Record<string, string> = {
    pending: 'Ваш заказ ожидает подтверждения',
    confirmed: 'Заказ подтверждён и передан в сборку',
    processing: 'Заказ в процессе сборки',
    ready: delivery_type === 'courier' ? 'Заказ передан курьеру' : 'Заказ готов к выдаче',
    delivering: 'Курьер в пути к вам',
    delivered: 'Заказ успешно доставлен',
    picked_up: 'Заказ получен',
    cancelled: 'Заказ отменён',
    failed: 'Произошла ошибка с заказом'
  }
  return messages[new_status] || 'Статус заказа обновлён'
}

const getStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    pending: 'Ожидает',
    confirmed: 'Подтверждён',
    processing: 'В сборке',
    ready: 'Готов',
    delivering: 'В пути',
    delivered: 'Доставлен',
    picked_up: 'Получен',
    cancelled: 'Отменён',
    failed: 'Ошибка'
  }
  return labels[status] || status
}

const getStatusIcon = (status: string) => {
  const icons: Record<string, any> = {
    pending: Clock,
    confirmed: CheckCircle,
    processing: Package,
    ready: Package,
    delivering: Truck,
    delivered: CheckCircle,
    picked_up: CheckCircle,
    cancelled: XCircle,
    failed: AlertCircle
  }
  return icons[status] || AlertCircle
}

const dismiss = () => {
  isDismissed.value = true
  setTimeout(() => {
    emit('dismiss', props.notification.id)
  }, 300)
}

const viewOrder = () => {
  emit('viewOrder', props.notification.data.order_id)
}

const trackOrder = () => {
  emit('trackOrder', props.notification.data.order_id)
}
</script>

<style scoped>
.order-status-notification {
  display: flex;
  gap: 1rem;
  padding: 1rem;
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-left: 4px solid;
  animation: slideInRight 0.3s ease-out;
  transition: all 0.3s ease;
  position: relative;
}

.order-status-notification.dismissed {
  opacity: 0;
  transform: translateX(100%);
  pointer-events: none;
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(100%);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.order-status-notification.status-pending {
  border-left-color: #fbbf24;
}

.order-status-notification.status-confirmed {
  border-left-color: #60a5fa;
}

.order-status-notification.status-processing {
  border-left-color: #818cf8;
}

.order-status-notification.status-ready {
  border-left-color: #34d399;
}

.order-status-notification.status-delivering {
  border-left-color: #38bdf8;
  animation: slideInRight 0.3s ease-out, deliveringPulse 2s ease-in-out infinite;
}

@keyframes deliveringPulse {
  0%, 100% {
    box-shadow: 0 4px 12px rgba(56, 189, 248, 0.1);
  }
  50% {
    box-shadow: 0 4px 20px rgba(56, 189, 248, 0.3);
  }
}

.order-status-notification.status-delivered,
.order-status-notification.status-picked-up {
  border-left-color: #22c55e;
}

.order-status-notification.status-cancelled,
.order-status-notification.status-failed {
  border-left-color: #ef4444;
}

.notification-icon {
  flex-shrink: 0;
  width: 3rem;
  height: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
}

.status-pending .notification-icon {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
}

.status-pending .notification-icon .icon {
  color: #d97706;
}

.status-confirmed .notification-icon {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}

.status-confirmed .notification-icon .icon {
  color: #2563eb;
}

.status-processing .notification-icon {
  background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
}

.status-processing .notification-icon .icon {
  color: #4f46e5;
}

.status-delivering .notification-icon {
  background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
}

.status-delivering .notification-icon .icon {
  color: #0284c7;
}

.status-delivered .notification-icon,
.status-picked-up .notification-icon {
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
}

.status-delivered .notification-icon .icon,
.status-picked-up .notification-icon .icon {
  color: #16a34a;
}

.status-cancelled .notification-icon,
.status-failed .notification-icon {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
}

.status-cancelled .notification-icon .icon,
.status-failed .notification-icon .icon {
  color: #dc2626;
}

.notification-icon .icon {
  width: 1.5rem;
  height: 1.5rem;
  color: #059669;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.notification-title {
  font-size: 1rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.notification-time {
  font-size: 0.75rem;
  color: #6b7280;
  white-space: nowrap;
}

.notification-message {
  font-size: 0.875rem;
  color: #4b5563;
  margin: 0 0 0.75rem 0;
  line-height: 1.5;
}

.order-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 0.75rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  margin-bottom: 0.75rem;
}

.order-number {
  font-weight: 600;
  color: #111827;
  font-size: 0.875rem;
}

.delivery-type {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  color: #6b7280;
}

.delivery-icon {
  width: 0.875rem;
  height: 0.875rem;
}

.status-transition {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border-radius: 0.5rem;
  margin-bottom: 0.75rem;
}

.old-status {
  font-size: 0.75rem;
  color: #6b7280;
  text-decoration: line-through;
}

.new-status {
  font-size: 0.75rem;
  font-weight: 600;
  color: #16a34a;
}

.arrow {
  width: 0.75rem;
  height: 0.75rem;
  color: #22c55e;
}

.notification-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  padding: 0.5rem 1rem;
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

.dismiss-btn {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  width: 1.75rem;
  height: 1.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  background: transparent;
  color: #9ca3af;
  cursor: pointer;
  border-radius: 50%;
  transition: all 0.2s ease;
}

.dismiss-btn:hover {
  background: #f3f4f6;
  color: #4b5563;
}

.dismiss-icon {
  width: 1rem;
  height: 1rem;
}

@media (max-width: 640px) {
  .notification-actions {
    flex-direction: column;
  }
  
  .action-btn {
    width: 100%;
  }
}
</style>
