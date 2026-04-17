<template>
  <div class="tickets-events">
    <div class="header">
      <h2>Events</h2>
      <button @click="addEvent" class="btn-primary">Add Event</button>
    </div>

    <div class="filters">
      <select v-model="categoryFilter">
        <option value="">All Categories</option>
        <option value="concert">Concert</option>
        <option value="sports">Sports</option>
        <option value="theater">Theater</option>
        <option value="cinema">Cinema</option>
      </select>
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="upcoming">Upcoming</option>
        <option value="on_sale">On Sale</option>
        <option value="sold_out">Sold Out</option>
        <option value="completed">Completed</option>
      </select>
    </div>

    <div class="events-grid">
      <div v-for="event in filteredEvents" :key="event.id" class="event-card">
        <div class="event-image">
          <img :src="event.image" :alt="event.name" />
          <span v-if="event.isHot" class="badge-hot">HOT</span>
        </div>
        <div class="event-details">
          <h3>{{ event.name }}</h3>
          <p class="category">{{ event.category }}</p>
          <p class="venue">{{ event.venue }}</p>
          <div class="dates">
            <span>{{ formatDate(event.date) }}</span>
            <span>{{ event.time }}</span>
          </div>
          <div class="price">{{ formatCurrency(event.price) }}</div>
          <div class="seats">{{ event.available_seats }} seats available</div>
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
  category: string
  venue: string
  date: string
  time: string
  price: number
  available_seats: number
  status: string
  isHot: boolean
  image: string
}

const events = ref<Event[]>([])
const categoryFilter = ref('')
const statusFilter = ref('')

const filteredEvents = computed(() => {
  return events.value.filter(event => {
    if (categoryFilter.value && event.category !== categoryFilter.value) return false
    if (statusFilter.value && event.status !== statusFilter.value) return false
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
    const response = await fetch('/api/tickets/events')
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
.tickets-events {
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
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.event-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.event-image {
  position: relative;
  width: 100%;
  height: 180px;
}

.event-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.badge-hot {
  position: absolute;
  top: 10px;
  right: 10px;
  background: #ef4444;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
}

.event-details {
  padding: 16px;
}

.event-details h3 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.category, .venue {
  margin: 0 0 8px 0;
  font-size: 12px;
  color: #6b7280;
}

.dates {
  display: flex;
  gap: 8px;
  margin-bottom: 8px;
  font-size: 13px;
  color: #374151;
}

.price {
  margin-bottom: 8px;
  font-size: 16px;
  font-weight: 600;
  color: #059669;
}

.seats {
  font-size: 12px;
  color: #6b7280;
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
