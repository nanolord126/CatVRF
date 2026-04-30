<template>
  <div class="supermarket-notification-center">
    <div class="notification-header">
      <h3 class="header-title">Уведомления</h3>
      <div class="header-actions">
        <button 
          v-if="unreadCount > 0" 
          @click="markAllAsRead" 
          class="mark-read-btn"
        >
          Прочитать все
        </button>
        <button @click="clearAll" class="clear-btn">
          Очистить
        </button>
      </div>
    </div>

    <div class="notification-filters">
      <button 
        v-for="filter in filters" 
        :key="filter.value"
        @click="selectFilter(filter.value)"
        :class="['filter-btn', { active: selectedFilter === filter.value }]"
      >
        <component :is="filter.icon" class="filter-icon" />
        {{ filter.label }}
        <span v-if="getUnreadCount(filter.value) > 0" class="filter-badge">
          {{ getUnreadCount(filter.value) }}
        </span>
      </button>
    </div>

    <div class="notification-list">
      <div v-if="filteredNotifications.length === 0" class="empty-state">
        <Bell class="empty-icon" />
        <p class="empty-text">Нет уведомлений</p>
      </div>

      <div 
        v-for="notification in filteredNotifications" 
        :key="notification.id"
        class="notification-wrapper"
      >
        <!-- Order Status Notification -->
        <OrderStatusNotification
          v-if="notification.type === 'order_status'"
          :notification="notification"
          @dismiss="onDismiss"
          @view-order="onViewOrder"
          @track-order="onTrackOrder"
        />

        <!-- Batch Arrival Notification -->
        <BatchArrivalNotification
          v-else-if="notification.type === 'batch_arrival'"
          :notification="notification"
          @dismiss="onDismiss"
          @view-details="onViewBatchDetails"
        />

        <!-- Stock Alert Notification -->
        <StockAlertNotification
          v-else-if="notification.type === 'stock_alert'"
          :notification="notification"
          @dismiss="onDismiss"
          @view-details="onViewStockDetails"
        />

        <!-- Demand Alert Notification -->
        <DemandAlertNotification
          v-else-if="notification.type === 'demand_alert'"
          :notification="notification"
          @dismiss="onDismiss"
          @view-details="onViewDemandDetails"
        />
      </div>
    </div>

    <div v-if="hasMore" class="load-more">
      <button @click="loadMore" class="load-more-btn">
        Загрузить ещё
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { 
  Bell, 
  Package, 
  ShoppingCart, 
  AlertTriangle,
  TrendingUp,
  Filter
} from 'lucide-vue-next'
import { useSupermarketNotifications } from '@/composables/useSupermarketNotifications'
import OrderStatusNotification from './OrderStatusNotification.vue'
import BatchArrivalNotification from './BatchArrivalNotification.vue'
import StockAlertNotification from './StockAlertNotification.vue'
import DemandAlertNotification from './DemandAlertNotification.vue'

const {
  notifications,
  unreadCount,
  markAsRead,
  markAllAsRead,
  clearNotifications,
  filterByType,
  getUnreadNotifications
} = useSupermarketNotifications()

const emit = defineEmits<{
  viewOrder: [orderId: string]
  trackOrder: [orderId: string]
  viewBatchDetails: [data: any]
  viewStockDetails: [data: any]
  viewDemandDetails: [data: any]
}>()

const selectedFilter = ref('all')
const hasMore = ref(false)

const filters = [
  { label: 'Все', value: 'all', icon: Filter },
  { label: 'Заказы', value: 'order_status', icon: ShoppingCart },
  { label: 'Партии', value: 'batch_arrival', icon: Package },
  { label: 'Склад', value: 'stock_alert', icon: AlertTriangle },
  { label: 'Спрос', value: 'demand_alert', icon: TrendingUp }
]

const filteredNotifications = computed(() => {
  if (selectedFilter.value === 'all') {
    return notifications.value
  }
  return filterByType(selectedFilter.value as any)
})

const getUnreadCount = (type: string): number => {
  if (type === 'all') {
    return unreadCount.value
  }
  return filterByType(type as any).filter(n => !n.read).length
}

const selectFilter = (filter: string) => {
  selectedFilter.value = filter
}

const onDismiss = (id: string) => {
  markAsRead(id)
  notifications.value = notifications.value.filter(n => n.id !== id)
}

const markAllAsReadHandler = () => {
  markAllAsRead()
}

const clearAllHandler = () => {
  clearNotifications()
}

const onViewOrder = (orderId: string) => {
  emit('viewOrder', orderId)
}

const onTrackOrder = (orderId: string) => {
  emit('trackOrder', orderId)
}

const onViewBatchDetails = (data: any) => {
  emit('viewBatchDetails', data)
}

const onViewStockDetails = (data: any) => {
  emit('viewStockDetails', data)
}

const onViewDemandDetails = (data: any) => {
  emit('viewDemandDetails', data)
}

const loadMore = () => {
  hasMore.value = false
}
</script>

<style scoped>
.supermarket-notification-center {
  max-width: 24rem;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  background: white;
  border-radius: 1rem;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  overflow: hidden;
}

.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.header-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 0.5rem;
}

.mark-read-btn,
.clear-btn {
  padding: 0.375rem 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

.mark-read-btn {
  background: #f3f4f6;
  color: #374151;
}

.mark-read-btn:hover {
  background: #e5e7eb;
}

.clear-btn {
  background: #fee2e2;
  color: #dc2626;
}

.clear-btn:hover {
  background: #fecaca;
}

.notification-filters {
  display: flex;
  gap: 0.5rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  overflow-x: auto;
}

.filter-btn {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid #e5e7eb;
  background: white;
  color: #6b7280;
  white-space: nowrap;
}

.filter-btn:hover {
  background: #f9fafb;
  border-color: #d1d5db;
}

.filter-btn.active {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  border-color: #22c55e;
  color: white;
}

.filter-icon {
  width: 0.875rem;
  height: 0.875rem;
}

.filter-badge {
  background: rgba(255, 255, 255, 0.2);
  padding: 0.125rem 0.375rem;
  border-radius: 9999px;
  font-size: 0.625rem;
  font-weight: 600;
}

.notification-list {
  flex: 1;
  overflow-y: auto;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.notification-wrapper {
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1rem;
  color: #9ca3af;
}

.empty-icon {
  width: 3rem;
  height: 3rem;
  margin-bottom: 1rem;
}

.empty-text {
  font-size: 0.875rem;
  margin: 0;
}

.load-more {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.load-more-btn {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  background: white;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s ease;
}

.load-more-btn:hover {
  background: #f9fafb;
  border-color: #d1d5db;
}
</style>
