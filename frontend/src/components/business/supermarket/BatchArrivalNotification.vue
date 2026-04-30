<template>
  <div 
    class="batch-arrival-notification"
    :class="[`priority-${notification.priority}`, { dismissed: isDismissed }]"
  >
    <div class="notification-icon">
      <Package class="icon" />
    </div>
    
    <div class="notification-content">
      <div class="notification-header">
        <h4 class="notification-title">{{ notification.title }}</h4>
        <span class="notification-time">{{ formatTime(notification.timestamp) }}</span>
      </div>
      
      <p class="notification-message">{{ notification.message }}</p>
      
      <div v-if="notification.data" class="notification-details">
        <div class="detail-row">
          <span class="detail-label">ID партии:</span>
          <span class="detail-value">{{ notification.data.batch_id }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Склад:</span>
          <span class="detail-value">{{ notification.data.warehouse_id }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Товаров:</span>
          <span class="detail-value">{{ notification.data.product_count }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Количество:</span>
          <span class="detail-value">{{ notification.data.total_quantity }} ед.</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Оценка:</span>
          <span class="detail-value">{{ formatCurrency(notification.data.estimated_value) }}</span>
        </div>
      </div>
      
      <div class="notification-actions">
        <button @click="viewDetails" class="action-btn primary">
          Подробнее
        </button>
        <button @click="dismiss" class="action-btn secondary">
          Закрыть
        </button>
      </div>
    </div>
    
    <button @click="dismiss" class="dismiss-btn">
      <X class="dismiss-icon" />
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Package, X } from 'lucide-vue-next'

interface BatchArrivalData {
  batch_id: string
  warehouse_id: string
  product_count: number
  total_quantity: number
  estimated_value: number
}

interface BatchArrivalNotification {
  id: string
  type: 'batch_arrival'
  title: string
  message: string
  data: BatchArrivalData
  timestamp: string
  read: boolean
  priority: 'low' | 'medium' | 'high' | 'critical'
}

const props = defineProps<{
  notification: BatchArrivalNotification
}>()

const emit = defineEmits<{
  dismiss: [id: string]
  viewDetails: [data: BatchArrivalData]
}>()

const isDismissed = ref(false)

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

const formatCurrency = (value: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0
  }).format(value)
}

const dismiss = () => {
  isDismissed.value = true
  setTimeout(() => {
    emit('dismiss', props.notification.id)
  }, 300)
}

const viewDetails = () => {
  emit('viewDetails', props.notification.data)
}
</script>

<style scoped>
.batch-arrival-notification {
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

.batch-arrival-notification.dismissed {
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

.batch-arrival-notification.priority-low {
  border-left-color: #6b7280;
}

.batch-arrival-notification.priority-medium {
  border-left-color: #f59e0b;
}

.batch-arrival-notification.priority-high {
  border-left-color: #f97316;
}

.batch-arrival-notification.priority-critical {
  border-left-color: #ef4444;
  animation: slideInRight 0.3s ease-out, criticalPulse 2s ease-in-out infinite;
}

@keyframes criticalPulse {
  0%, 100% {
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);
  }
  50% {
    box-shadow: 0 4px 20px rgba(239, 68, 68, 0.3);
  }
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

.notification-details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  margin-bottom: 0.75rem;
}

.detail-row {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.detail-label {
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 500;
}

.detail-value {
  font-size: 0.875rem;
  color: #111827;
  font-weight: 600;
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
  .notification-details {
    grid-template-columns: 1fr;
  }
}
</style>
