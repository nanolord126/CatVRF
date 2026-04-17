<template>
  <div class="event-planning-bookings">
    <div class="header">
      <h2>Bookings</h2>
      <button @click="addBooking" class="btn-primary">Add Booking</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="confirmed">Confirmed</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <select v-model="venueFilter">
        <option value="">All Venues</option>
      </select>
    </div>

    <div class="bookings-table">
      <table>
        <thead>
          <tr>
            <th>Booking ID</th>
            <th>Event</th>
            <th>Venue</th>
            <th>Date</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="booking in filteredBookings" :key="booking.id">
            <td>#{{ booking.id }}</td>
            <td>{{ booking.event }}</td>
            <td>{{ booking.venue }}</td>
            <td>{{ formatDate(booking.date) }}</td>
            <td>{{ formatCurrency(booking.amount) }}</td>
            <td>
              <span :class="['status-badge', booking.status]">{{ booking.status }}</span>
            </td>
            <td>
              <button @click="viewBooking(booking)" class="btn-sm">View</button>
              <button @click="editBooking(booking)" class="btn-sm">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Booking {
  id: number
  event: string
  venue: string
  date: string
  amount: number
  status: string
}

const bookings = ref<Booking[]>([])
const statusFilter = ref('')
const venueFilter = ref('')

const filteredBookings = computed(() => {
  return bookings.value.filter(booking => {
    if (statusFilter.value && booking.status !== statusFilter.value) return false
    if (venueFilter.value && booking.venue !== venueFilter.value) return false
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

const addBooking = () => {
  // Open modal to add new booking
}

const viewBooking = (booking: Booking) => {
  // Open booking details
}

const editBooking = (booking: Booking) => {
  // Open edit modal
}

const fetchBookings = async () => {
  try {
    const response = await fetch('/api/event-planning/bookings')
    const data = await response.json()
    bookings.value = data
  } catch (error) {
    console.error('Failed to fetch bookings:', error)
  }
}

onMounted(() => {
  fetchBookings()
})
</script>

<style scoped>
.event-planning-bookings {
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

.bookings-table {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 12px 16px;
  text-align: left;
  border-bottom: 1px solid #e5e7eb;
}

th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
}

.status-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.confirmed {
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

.btn-sm {
  padding: 6px 12px;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
  margin-right: 4px;
}
</style>
