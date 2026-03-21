<template>
  <div class="activity-feed">
    <div class="feed-header">
      <h3>Активность</h3>
      <button @click="refreshActivity" class="refresh-btn">
        ↻
      </button>
    </div>

    <div v-if="activities.length === 0" class="empty-state">
      Нет активности
    </div>

    <div v-else class="activities-list">
      <div
        v-for="activity in activities"
        :key="activity.id"
        :class="['activity-item', activity.type]"
      >
        <div class="activity-icon">
          {{ getActivityIcon(activity.type) }}
        </div>

        <div class="activity-content">
          <p class="activity-description">{{ activity.description }}</p>
          <span class="activity-time">{{ formatTime(activity.timestamp) }}</span>
        </div>

        <div class="activity-user" v-if="activity.user">
          <img
            :src="activity.user.avatar"
            :alt="activity.user.name"
            class="user-avatar"
          />
          <span>{{ activity.user.name }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const activities = ref([])
const refreshInterval = ref(null)

const getActivityIcon = (type) => {
  const icons = {
    order: '📦',
    payment: '💳',
    user: '👤',
    system: '⚙️',
  }
  return icons[type] || '•'
}

const formatTime = (timestamp) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now - date

  if (diff < 60000) return 'Только что'
  if (diff < 3600000) return `${Math.floor(diff / 60000)} мин назад`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)} ч назад`

  return date.toLocaleDateString('ru-RU')
}

const loadActivities = async () => {
  try {
    const response = await fetch('/api/v2/activities')
    const data = await response.json()
    activities.value = data.data || []
  } catch (error) {
    console.error('Failed to load activities:', error)
  }
}

const refreshActivity = () => {
  loadActivities()
}

onMounted(() => {
  loadActivities()

  // Auto-refresh every 10 seconds
  refreshInterval.value = setInterval(() => {
    loadActivities()
  }, 10000)
})

onBeforeUnmount(() => {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value)
  }
})
</script>

<style scoped>
.activity-feed {
  background: white;
  border-radius: 8px;
  padding: 16px;
}

.feed-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.refresh-btn {
  background: none;
  border: none;
  font-size: 18px;
  cursor: pointer;
  padding: 4px 8px;
}

.refresh-btn:hover {
  opacity: 0.7;
}

.empty-state {
  text-align: center;
  color: #9ca3af;
  padding: 24px;
}

.activities-list {
  display: grid;
  gap: 12px;
  max-height: 400px;
  overflow-y: auto;
}

.activity-item {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 12px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
  align-items: center;
}

.activity-icon {
  font-size: 20px;
}

.activity-content {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.activity-description {
  margin: 0;
  font-size: 14px;
  font-weight: 500;
  color: #111827;
}

.activity-time {
  font-size: 12px;
  color: #9ca3af;
}

.activity-user {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: #6b7280;
}

.user-avatar {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  object-fit: cover;
}
</style>
