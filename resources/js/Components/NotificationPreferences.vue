<template>
  <div class="notification-preferences">
    <div class="preferences-section">
      <h3>Каналы уведомлений</h3>
      
      <div class="channel-items">
        <div v-for="(channel, key) in channels" :key="key" class="channel-item">
          <label class="channel-label">
            <input
              type="checkbox"
              v-model="channel.enabled"
              @change="updateChannel(key)"
            />
            <span class="channel-name">{{ channel.label }}</span>
          </label>

          <div v-if="channel.enabled" class="categories">
            <label v-for="(enabled, category) in channel.categories" :key="category" class="category-checkbox">
              <input
                type="checkbox"
                v-model="channel.categories[category]"
                @change="updateChannel(key)"
              />
              <span>{{ getCategoryLabel(category) }}</span>
            </label>
          </div>
        </div>
      </div>
    </div>

    <div class="dnd-section">
      <h3>Не беспокоить</h3>
      
      <label class="dnd-toggle">
        <input
          type="checkbox"
          v-model="dndEnabled"
          @change="toggleDND"
        />
        <span>Включить режим "Не беспокоить"</span>
      </label>

      <div v-if="dndEnabled" class="dnd-times">
        <div class="time-input">
          <label>С:</label>
          <input
            type="time"
            v-model="dndStartTime"
            @change="saveDND"
          />
        </div>
        <div class="time-input">
          <label>По:</label>
          <input
            type="time"
            v-model="dndEndTime"
            @change="saveDND"
          />
        </div>
      </div>
    </div>

    <div v-if="saved" class="success-message">
      Предпочтения сохранены ✓
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { realtimeApi } from '@/api/realtime'

const channels = ref({
  email: {
    label: 'Email',
    enabled: true,
    categories: {
      orders: true,
      payments: true,
      promotions: false,
      system: true,
    },
  },
  sms: {
    label: 'SMS',
    enabled: true,
    categories: {
      orders: true,
      payments: true,
      promotions: false,
      system: true,
    },
  },
  push: {
    label: 'Push-уведомления',
    enabled: true,
    categories: {
      orders: true,
      payments: true,
      promotions: false,
      system: true,
    },
  },
  in_app: {
    label: 'В приложении',
    enabled: true,
    categories: {
      orders: true,
      payments: true,
      promotions: true,
      system: true,
    },
  },
})

const dndEnabled = ref(false)
const dndStartTime = ref('22:00')
const dndEndTime = ref('08:00')
const saved = ref(false)

const updateChannel = async (channel) => {
  try {
    await fetch(`/api/v2/notifications/preferences/${channel}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(channels.value[channel]),
    })
    showSaved()
  } catch (error) {
    console.error('Failed to update preferences:', error)
  }
}

const toggleDND = async () => {
  if (!dndEnabled.value) {
    await disableDND()
  }
}

const saveDND = async () => {
  try {
    await fetch('/api/v2/notifications/do-not-disturb', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        start_time: dndStartTime.value,
        end_time: dndEndTime.value,
      }),
    })
    showSaved()
  } catch (error) {
    console.error('Failed to save DND:', error)
  }
}

const disableDND = async () => {
  try {
    await fetch('/api/v2/notifications/do-not-disturb', {
      method: 'DELETE',
    })
    dndEnabled.value = false
  } catch (error) {
    console.error('Failed to disable DND:', error)
  }
}

const getCategoryLabel = (category) => {
  const labels = {
    orders: 'Заказы',
    payments: 'Платежи',
    promotions: 'Акции',
    system: 'Системные',
  }
  return labels[category] || category
}

const showSaved = () => {
  saved.value = true
  setTimeout(() => {
    saved.value = false
  }, 3000)
}

onMounted(async () => {
  try {
    const response = await fetch('/api/v2/notifications/preferences')
    const data = await response.json()
    
    Object.keys(data.data).forEach((channel) => {
      if (channels.value[channel]) {
        channels.value[channel] = { ...channels.value[channel], ...data.data[channel] }
      }
    })

    dndEnabled.value = data.data.do_not_disturb?.enabled || false
    dndStartTime.value = data.data.do_not_disturb?.start_time || '22:00'
    dndEndTime.value = data.data.do_not_disturb?.end_time || '08:00'
  } catch (error) {
    console.error('Failed to load preferences:', error)
  }
})
</script>

<style scoped>
.notification-preferences {
  display: grid;
  gap: 24px;
  padding: 20px;
  background: white;
  border-radius: 8px;
}

.preferences-section,
.dnd-section {
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 20px;
}

.preferences-section:last-child,
.dnd-section:last-child {
  border-bottom: none;
}

h3 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
  color: #111827;
}

.channel-items {
  display: grid;
  gap: 12px;
}

.channel-item {
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
}

.channel-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.categories {
  margin-top: 12px;
  margin-left: 24px;
  display: grid;
  gap: 8px;
}

.category-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
}

.dnd-times {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-top: 12px;
}

.time-input {
  display: flex;
  gap: 8px;
  align-items: center;
}

.success-message {
  padding: 12px;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  border-radius: 6px;
  color: #047857;
  font-size: 14px;
}

input[type='checkbox'] {
  cursor: pointer;
}

input[type='time'] {
  padding: 6px 8px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 14px;
}
</style>
