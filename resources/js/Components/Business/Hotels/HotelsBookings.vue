<template>
  <div class="hotels-bookings">
    <div class="header">
      <h2>Hotel Bookings</h2>
      <button @click="addBooking" class="btn-primary">Add Booking</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="confirmed">Confirmed</option>
        <option value="checked_in">Checked In</option>
        <option value="checked_out">Checked Out</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <div class="bookings-grid">
      <div v-for="booking in filteredBookings" :key="booking.id" class="booking-card">
        <div class="booking-header">
          <span class="hotel-name">{{ booking.hotel }}</span>
          <span :class="['status-badge', booking.status]">{{ booking.status }}</span>
        </div>
        <div class="booking-details">
          <h3>Booking #{{ booking.id }}</h3>
          <p class="guest">Guest: {{ booking.guest }}</p>
          <div class="dates">
            <span>{{ formatDate(booking.check_in) }}</span>
            <span>→</span>
            <span>{{ formatDate(booking.check_out) }}</span>
          </div>
          <div class="room-info">{{ booking.room_type }} - {{ booking.rooms }} room(s)</div>
          <div class="price">{{ formatCurrency(booking.total) }}</div>
        </div>
        <div class="booking-actions">
          <button @click="viewBooking(booking)" class="btn-sm">View</button>
          <button @click="editBooking(booking)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Booking {
  id: number
  hotel: string
  guest: string
  check_in: string
  check_out: string
  room_type: string
  rooms: number
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

const editBooking = (booking: Booking) => {
  // Open edit modal
}

const fetchBookings = async () => {
  try {
    const response = await fetch('/api/hotels/bookings')
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
.hotels-bookings {
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

.bookings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.booking-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.booking-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.hotel-name {
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

.status-badge.checked_out {
  background: #e5e7eb;
  color: #374151;
}

.status-badge.cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.booking-details {
  padding: 16px;
}

.booking-details h3 {
  margin: 0 0 8px 0;
  font-size: 14px;
  font-weight: 600;
}

.guest {
  margin: 0 0 12px 0;
  font-size: 13px;
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

.room-info {
  margin-bottom: 12px;
  font-size: 13px;
  color: #6b7280;
}

.price {
  font-size: 18px;
  font-weight: 600;
  color: #059669;
}

.booking-actions {
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
