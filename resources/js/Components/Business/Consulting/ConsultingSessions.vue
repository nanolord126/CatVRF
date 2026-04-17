<template>
  <div class="consulting-sessions">
    <div class="header">
      <h2>Consulting Sessions</h2>
      <button @click="addSession" class="btn-primary">Add Session</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="scheduled">Scheduled</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <div class="sessions-grid">
      <div v-for="session in filteredSessions" :key="session.id" class="session-card">
        <div class="session-header">
          <span class="client">{{ session.client }}</span>
          <span :class="['status-badge', session.status]">{{ session.status }}</span>
        </div>
        <div class="session-details">
          <h3>{{ session.topic }}</h3>
          <p class="consultant">Consultant: {{ session.consultant }}</p>
          <div class="datetime">
            <span>{{ formatDate(session.date) }}</span>
            <span>{{ session.time }}</span>
          </div>
          <div class="duration">{{ session.duration }} min</div>
          <div class="notes">{{ session.notes }}</div>
        </div>
        <div class="session-actions">
          <button @click="viewSession(session)" class="btn-sm">View</button>
          <button @click="editSession(session)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Session {
  id: number
  client: string
  topic: string
  consultant: string
  date: string
  time: string
  duration: number
  notes: string
  status: string
}

const sessions = ref<Session[]>([])
const statusFilter = ref('')

const filteredSessions = computed(() => {
  if (!statusFilter.value) return sessions.value
  return sessions.value.filter(session => session.status === statusFilter.value)
})

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const addSession = () => {
  // Open modal to add new session
}

const viewSession = (session: Session) => {
  // Open session details
}

const editSession = (session: Session) => {
  // Open edit modal
}

const fetchSessions = async () => {
  try {
    const response = await fetch('/api/consulting/sessions')
    const data = await response.json()
    sessions.value = data
  } catch (error) {
    console.error('Failed to fetch sessions:', error)
  }
}

onMounted(() => {
  fetchSessions()
})
</script>

<style scoped>
.consulting-sessions {
  padding: 20px;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.filters {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

.filters select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.sessions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.session-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.session-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.client {
  font-size: 12px;
  font-weight: 600;
  color: #374151;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge.scheduled {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.in_progress {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.completed {
  background: #e5e7eb;
  color: #374151;
}

.status-badge.cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.session-details {
  padding: 16px;
}

.session-details h3 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.consultant {
  margin: 0 0 12px 0;
  font-size: 12px;
  color: #6b7280;
}

.datetime {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 13px;
  color: #374151;
}

.duration {
  margin-bottom: 8px;
  font-size: 13px;
  color: #6b7280;
}

.notes {
  margin-bottom: 12px;
  font-size: 12px;
  color: #4b5563;
  line-height: 1.5;
}

.session-actions {
  padding: 12px 16px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 8px;
}

.btn-sm {
  flex: 1;
  padding: 8px;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}
</style>
