<template>
  <div class="tickets-bookings">
    <div class="header">
      <h2>Bookings</h2>
      <button @click="addBooking" class="btn-primary">Add Booking</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="confirmed">Confirmed</option>
        <option value="checked_in">Checked In</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <div class="bookings-table">
      <table>
        <thead>
          <tr>
            <th>Booking ID</th>
            <th>Customer</th>
            <th>Event</th>
            <th>Tickets</th>
            <th>Event Date</th>
            <th>Total</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="booking in filteredBookings" :key="booking.id">
            <td>#{{ booking.id }}</td>
            <td>{{ booking.customer }}</td>
            <td>{{ booking.event }}</td>
            <td>{{ booking.tickets }}</td>
            <td>{{ formatDate(booking.event_date) }}</td>
            <td>{{ formatCurrency(booking.total) }}</td>
            <td>
              <span :class="['status-badge', booking.status]">{{ booking.status }}</span>
            </td>
            <td>
              <button @click="viewBooking(booking)" class="btn-sm">View</button>
              <button @click="checkIn(booking)" class="btn-sm btn-primary">Check In</button>
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
  customer: string
  event: string
  tickets: number
  event_date: string
  total: number
  status: string
}

const bookings = ref<Booking[]>([])
const statusFilter = ref('')

const filteredBookings = computed(() => {
  if (!statusFilter.value) return bookings.value
  return bookings.value.filter(booking => booking.status === statusFilter.value)
})

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

const checkIn = (booking: Booking) => {
  // Process check-in
}

const fetchBookings = async () => {
  try {
    const response = await fetch('/api/tickets/bookings')
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
.tickets-bookings {
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

.status-badge.checked_in {
  background: #dbeafe;
  color: #1e40af;
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

.btn-primary {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}
</style>
