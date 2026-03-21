<template>
  <div class="realtime-notifications">
    <div
      v-for="notification in notifications"
      :key="notification.id"
      :class="['notification-item', notification.type]"
      @click="dismissNotification(notification.id)"
    >
      <div class="notification-content">
        <p class="notification-title">{{ notification.title }}</p>
        <p class="notification-message">{{ notification.message }}</p>
      </div>
      <button class="notification-close">×</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const notifications = ref([])

const addNotification = (title, message, type = 'info') => {
  const id = Date.now()
  notifications.value.push({ id, title, message, type })

  // Auto-dismiss after 5 seconds
  setTimeout(() => dismissNotification(id), 5000)
}

const dismissNotification = (id) => {
  notifications.value = notifications.value.filter((n) => n.id !== id)
}

onMounted(() => {
  // Listen to WebSocket events
  if (window.Echo) {
    window.Echo.private(`tenant.${window.tenantId}`).listen('order.created', (event) => {
      addNotification('Новый заказ', `Заказ #${event.id} создан`, 'success')
    })

    window.Echo.private(`tenant.${window.tenantId}`).listen('order.status.changed', (event) => {
      addNotification('Статус заказа', `Заказ #${event.id}: ${event.new_status}`, 'info')
    })

    window.Echo.private(`tenant.${window.tenantId}`).listen('payment.processed', (event) => {
      addNotification('Платёж', `Платёж #${event.id} обработан`, 'success')
    })
  }
})

defineExpose({ addNotification, dismissNotification })
</script>

<style scoped>
.realtime-notifications {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
}

.notification-item {
  background: white;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  animation: slideInRight 0.3s ease-out;
  min-width: 300px;
  border-left: 4px solid;
}

.notification-item.success {
  border-left-color: #10b981;
}

.notification-item.error {
  border-left-color: #ef4444;
}

.notification-item.warning {
  border-left-color: #f59e0b;
}

.notification-item.info {
  border-left-color: #3b82f6;
}

.notification-content {
  flex: 1;
}

.notification-title {
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.notification-message {
  color: #6b7280;
  font-size: 14px;
  margin: 4px 0 0 0;
}

.notification-close {
  background: none;
  border: none;
  font-size: 24px;
  color: #9ca3af;
  cursor: pointer;
  padding: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.notification-close:hover {
  color: #6b7280;
}

@keyframes slideInRight {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}
</style>
