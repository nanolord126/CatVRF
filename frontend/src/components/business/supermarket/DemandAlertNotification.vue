<template>
  <div 
    class="demand-alert-notification"
    :class="[`priority-${notification.priority}`, { dismissed: isDismissed }]"
  >
    <div class="notification-icon">
      <TrendingUp class="icon" />
    </div>
    
    <div class="notification-content">
      <div class="notification-header">
        <h4 class="notification-title">{{ notification.title }}</h4>
        <span class="notification-time">{{ formatTime(notification.timestamp) }}</span>
      </div>
      
      <p class="notification-message">{{ notification.message }}</p>
      
      <div v-if="notification.data" class="demand-details">
        <div class="demand-metric">
          <span class="metric-label">Товар:</span>
          <span class="metric-value">{{ notification.data.product_name }}</span>
        </div>
        <div class="demand-forecast">
          <div class="forecast-header">
            <span class="forecast-label">Прогноз спроса</span>
            <span class="forecast-value">{{ notification.data.predicted_demand }} ед.</span>
          </div>
          <div class="forecast-bar">
            <div 
              class="forecast-fill"
              :style="{ width: getDemandPercentage(notification.data.predicted_demand, notification.data.current_stock) + '%' }"
              :class="getDemandClass(notification.data.shortage_days)"
            ></div>
          </div>
        </div>
        <div class="shortage-warning">
          <AlertTriangle class="warning-icon" />
          <div>
            <span class="warning-label">Закончится через:</span>
            <span class="warning-value">{{ notification.data.shortage_days }} дн.</span>
          </div>
        </div>
      </div>
      
      <div class="notification-actions">
        <button @click="viewDetails" class="action-btn primary">
          Управление спросом
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
import { TrendingUp, X, AlertTriangle } from 'lucide-vue-next'

interface DemandAlertData {
  product_id: string
  product_name: string
  predicted_demand: number
  current_stock: number
  shortage_days: number
}

interface DemandAlertNotification {
  id: string
  type: 'demand_alert'
  title: string
  message: string
  data: DemandAlertData
  timestamp: string
  read: boolean
  priority: 'low' | 'medium' | 'high' | 'critical'
}

const props = defineProps<{
  notification: DemandAlertNotification
}>()

const emit = defineEmits<{
  dismiss: [id: string]
  viewDetails: [data: DemandAlertData]
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

const getDemandPercentage = (predicted: number, current: number): number => {
  if (predicted === 0) return 0
  return Math.min((current / predicted) * 100, 100)
}

const getDemandClass = (shortageDays: number): string => {
  if (shortageDays <= 3) return 'critical'
  if (shortageDays <= 7) return 'warning'
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
.demand-alert-notification {
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

.demand-alert-notification.dismissed {
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

.demand-alert-notification.priority-low {
  border-left-color: #6b7280;
}

.demand-alert-notification.priority-medium {
  border-left-color: #f59e0b;
}

.demand-alert-notification.priority-high {
  border-left-color: #f97316;
}

.demand-alert-notification.priority-critical {
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
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}

.notification-icon .icon {
  width: 1.5rem;
  height: 1.5rem;
  color: #2563eb;
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

.demand-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  margin-bottom: 0.75rem;
}

.demand-metric {
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

.demand-forecast {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.forecast-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.forecast-label {
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 500;
}

.forecast-value {
  font-size: 0.875rem;
  font-weight: 600;
}

.forecast-bar {
  height: 0.5rem;
  background: #e5e7eb;
  border-radius: 9999px;
  overflow: hidden;
}

.forecast-fill {
  height: 100%;
  border-radius: 9999px;
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

.forecast-fill.critical {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}

.forecast-fill.warning {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}

.forecast-fill.normal {
  background: linear-gradient(90deg, #22c55e, #16a34a);
}

.shortage-warning {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border-radius: 0.5rem;
}

.warning-icon {
  width: 1rem;
  height: 1rem;
  color: #d97706;
  flex-shrink: 0;
}

.shortage-warning > div {
  display: flex;
  flex-direction: column;
}

.warning-label {
  font-size: 0.75rem;
  color: #92400e;
}

.warning-value {
  font-size: 0.875rem;
  font-weight: 600;
  color: #b45309;
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
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
}

.action-btn.primary:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(59, 130, 246, 0.3);
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
