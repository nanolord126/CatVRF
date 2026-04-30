<template>
  <div 
    class="stock-alert-notification"
    :class="[`priority-${notification.data.priority}`, { dismissed: isDismissed }]"
  >
    <div class="notification-icon">
      <AlertTriangle class="icon" />
    </div>
    
    <div class="notification-content">
      <div class="notification-header">
        <h4 class="notification-title">{{ notification.title }}</h4>
        <span class="notification-time">{{ formatTime(notification.timestamp) }}</span>
      </div>
      
      <p class="notification-message">{{ notification.message }}</p>
      
      <div v-if="notification.data" class="stock-details">
        <div class="stock-metric">
          <span class="metric-label">Магазин:</span>
          <span class="metric-value">{{ notification.data.store_name }}</span>
        </div>
        <div class="stock-metric">
          <span class="metric-label">Товар:</span>
          <span class="metric-value">{{ notification.data.product_name }}</span>
        </div>
        <div class="stock-level">
          <div class="level-header">
            <span class="level-label">Уровень стока</span>
            <span 
              class="level-value" 
              :class="getStockLevelClass(notification.data.current_stock, notification.data.optimal_stock)"
            >
              {{ notification.data.current_stock }} / {{ notification.data.optimal_stock }}
            </span>
          </div>
          <div class="level-bar">
            <div 
              class="level-fill"
              :style="{ width: getStockPercentage(notification.data.current_stock, notification.data.optimal_stock) + '%' }"
              :class="getStockLevelClass(notification.data.current_stock, notification.data.optimal_stock)"
            ></div>
          </div>
        </div>
      </div>
      
      <div class="notification-actions">
        <button @click="viewDetails" class="action-btn primary">
          Управление стоком
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
import { AlertTriangle, X } from 'lucide-vue-next'

interface StockAlertData {
  store_id: string
  store_name: string
  product_id: string
  product_name: string
  current_stock: number
  optimal_stock: number
  priority: 'critical' | 'high' | 'medium'
}

interface StockAlertNotification {
  id: string
  type: 'stock_alert'
  title: string
  message: string
  data: StockAlertData
  timestamp: string
  read: boolean
  priority: 'low' | 'medium' | 'high' | 'critical'
}

const props = defineProps<{
  notification: StockAlertNotification
}>()

const emit = defineEmits<{
  dismiss: [id: string]
  viewDetails: [data: StockAlertData]
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

const getStockPercentage = (current: number, optimal: number): number => {
  if (optimal === 0) return 0
  return Math.min((current / optimal) * 100, 100)
}

const getStockLevelClass = (current: number, optimal: number): string => {
  const percentage = getStockPercentage(current, optimal)
  if (percentage <= 20) return 'critical'
  if (percentage <= 50) return 'warning'
  return 'normal'
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
.stock-alert-notification {
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

.stock-alert-notification.dismissed {
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

.stock-alert-notification.priority-low {
  border-left-color: #6b7280;
}

.stock-alert-notification.priority-medium {
  border-left-color: #f59e0b;
}

.stock-alert-notification.priority-high {
  border-left-color: #f97316;
}

.stock-alert-notification.priority-critical {
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
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
}

.notification-icon .icon {
  width: 1.5rem;
  height: 1.5rem;
  color: #d97706;
}

.priority-critical .notification-icon {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
}

.priority-critical .notification-icon .icon {
  color: #dc2626;
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

.stock-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  margin-bottom: 0.75rem;
}

.stock-metric {
  display: flex;
  justify-content: space-between;
}

.metric-label {
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 500;
}

.metric-value {
  font-size: 0.875rem;
  color: #111827;
  font-weight: 600;
}

.stock-level {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.level-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.level-label {
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 500;
}

.level-value {
  font-size: 0.875rem;
  font-weight: 600;
}

.level-value.critical {
  color: #dc2626;
}

.level-value.warning {
  color: #d97706;
}

.level-value.normal {
  color: #059669;
}

.level-bar {
  height: 0.5rem;
  background: #e5e7eb;
  border-radius: 9999px;
  overflow: hidden;
}

.level-fill {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.level-fill.critical {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}

.level-fill.warning {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}

.level-fill.normal {
  background: linear-gradient(90deg, #22c55e, #16a34a);
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
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.action-btn.primary:hover {
  background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(245, 158, 11, 0.3);
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
</style>
