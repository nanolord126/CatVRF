<template>
  <div class="travel-bookings">
    <div class="header">
      <h2>Travel Bookings</h2>
      <button @click="addBooking" class="btn-primary">Add Booking</button>
    </div>

    <div class="filters">
      <input 
        v-model="searchQuery" 
        type="text" 
        placeholder="Search by destination or customer..." 
        class="search-input"
      />
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="confirmed">Confirmed</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <select v-model="typeFilter">
        <option value="">All Types</option>
        <option value="flight">Flight</option>
        <option value="hotel">Hotel</option>
        <option value="tour">Tour</option>
        <option value="car_rental">Car Rental</option>
      </select>
      <div class="price-range">
        <input 
          v-model="minPrice" 
          type="number" 
          placeholder="Min Price" 
          class="price-input"
        />
        <span>-</span>
        <input 
          v-model="maxPrice" 
          type="number" 
          placeholder="Max Price" 
          class="price-input"
        />
      </div>
      <input 
        v-model="startDate" 
        type="date" 
        class="date-input"
      />
      <input 
        v-model="endDate" 
        type="date" 
        class="date-input"
      />
      <button @click="resetFilters" class="btn-reset">Reset</button>
    </div>

    <div class="bookings-grid">
      <div v-for="booking in filteredBookings" :key="booking.id" class="booking-card">
        <div class="booking-header">
          <span class="booking-type">{{ booking.type }}</span>
          <span :class="['status-badge', booking.status]">{{ booking.status }}</span>
        </div>
        <div class="booking-details">
          <h3>{{ booking.destination }}</h3>
          <p class="customer">Customer: {{ booking.customer }}</p>
          <div class="dates">
            <span>{{ formatDate(booking.start_date) }}</span>
            <span>→</span>
            <span>{{ formatDate(booking.end_date) }}</span>
          </div>
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
  type: string
  destination: string
  customer: string
  start_date: string
  end_date: string
  total: number
  status: string
}

const bookings = ref<Booking[]>([])
const statusFilter = ref('')
const typeFilter = ref('')
const searchQuery = ref('')
const minPrice = ref<number | null>(null)
const maxPrice = ref<number | null>(null)
const startDate = ref('')
const endDate = ref('')

const filteredBookings = computed(() => {
  return bookings.value.filter(booking => {
    if (statusFilter.value && booking.status !== statusFilter.value) return false
    if (typeFilter.value && booking.type !== typeFilter.value) return false
    
    if (searchQuery.value) {
      const query = searchQuery.value.toLowerCase()
      if (!booking.destination.toLowerCase().includes(query) && 
          !booking.customer.toLowerCase().includes(query)) {
        return false
      }
    }
    
    if (minPrice.value !== null && booking.total < minPrice.value) return false
    if (maxPrice.value !== null && booking.total > maxPrice.value) return false
    
    if (startDate.value && new Date(booking.start_date) < new Date(startDate.value)) return false
    if (endDate.value && new Date(booking.end_date) > new Date(endDate.value)) return false
    
    return true
  })
})

const resetFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
  typeFilter.value = ''
  minPrice.value = null
  maxPrice.value = null
  startDate.value = ''
  endDate.value = ''
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
    const response = await fetch('/api/travel/bookings')
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
.travel-bookings {
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
  flex-wrap: wrap;
  align-items: center;
}

.search-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  min-width: 250px;
}

.filters select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.price-range {
  display: flex;
  align-items: center;
  gap: 8px;
}

.price-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  width: 100px;
}

.date-input {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.btn-reset {
  padding: 8px 16px;
  background: #6b7280;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
}

.btn-reset:hover {
  background: #4b5563;
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

.booking-type {
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
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

.status-badge.completed {
  background: #dbeafe;
  color: #1e40af;
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
  font-size: 16px;
  font-weight: 600;
}

.customer {
  margin: 0 0 12px 0;
  font-size: 14px;
  color: #6b7280;
}

.dates {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  font-size: 13px;
  color: #374151;
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
