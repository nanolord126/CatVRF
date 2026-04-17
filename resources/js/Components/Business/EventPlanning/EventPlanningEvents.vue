<template>
  <div class="event-planning-events">
    <div class="header">
      <h2>Events</h2>
      <button @click="addEvent" class="btn-primary">Add Event</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="planning">Planning</option>
        <option value="confirmed">Confirmed</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
      </select>
      <select v-model="typeFilter">
        <option value="">All Types</option>
        <option value="wedding">Wedding</option>
        <option value="corporate">Corporate</option>
        <option value="birthday">Birthday</option>
        <option value="conference">Conference</option>
      </select>
    </div>

    <div class="events-grid">
      <div v-for="event in filteredEvents" :key="event.id" class="event-card">
        <div class="event-header">
          <span class="event-name">{{ event.name }}</span>
          <span :class="['status-badge', event.status]">{{ event.status }}</span>
        </div>
        <div class="event-details">
          <p class="type">{{ event.type }}</p>
          <p class="client">Client: {{ event.client }}</p>
          <div class="dates">
            <span>{{ formatDate(event.start_date) }}</span>
            <span>→</span>
            <span>{{ formatDate(event.end_date) }}</span>
          </div>
          <div class="guests">{{ event.guests }} guests</div>
          <div class="budget">Budget: {{ formatCurrency(event.budget) }}</div>
        </div>
        <div class="event-actions">
          <button @click="viewEvent(event)" class="btn-sm">View</button>
          <button @click="editEvent(event)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Event {
  id: number
  name: string
  type: string
  client: string
  start_date: string
  end_date: string
  guests: number
  budget: number
  status: string
}

const events = ref<Event[]>([])
const statusFilter = ref('')
const typeFilter = ref('')

const filteredEvents = computed(() => {
  return events.value.filter(event => {
    if (statusFilter.value && event.status !== statusFilter.value) return false
    if (typeFilter.value && event.type !== typeFilter.value) return false
    return true
  })
}

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const addEvent = () => {
  // Open modal to add new event
}

const viewEvent = (event: Event) => {
  // Open event details
}

const editEvent = (event: Event) => {
  // Open edit modal
}

const fetchEvents = async () => {
  try {
    const response = await fetch('/api/event-planning/events')
    const data = await response.json()
    events.value = data
  } catch (error) {
    console.error('Failed to fetch events:', error)
  }
}

onMounted(() => {
  fetchEvents()
})
</script>

<style scoped>
.event-planning-events {
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

.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.event-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.event-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.event-name {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
}

.status-badge.planning {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.confirmed {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.in_progress {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.completed {
  background: #e5e7eb;
  color: #374151;
}

.event-details {
  padding: 16px;
}

.type, .client {
  margin: 0 0 8px 0;
  font-size: 12px;
  color: #6b7280;
}

.dates {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 13px;
  color: #374151;
}

.guests {
  margin-bottom: 8px;
  font-size: 13px;
  color: #374151;
}

.budget {
  font-size: 14px;
  font-weight: 600;
  color: #059669;
}

.event-actions {
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
