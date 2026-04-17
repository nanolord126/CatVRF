<template>
  <div class="hotels-rooms">
    <div class="header">
      <h2>Room Management</h2>
      <button @click="addRoom" class="btn-primary">Add Room</button>
    </div>

    <div class="filters">
      <select v-model="typeFilter">
        <option value="">All Types</option>
        <option value="single">Single</option>
        <option value="double">Double</option>
        <option value="suite">Suite</option>
        <option value="deluxe">Deluxe</option>
      </select>
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="available">Available</option>
        <option value="occupied">Occupied</option>
        <option value="maintenance">Maintenance</option>
      </select>
    </div>

    <div class="rooms-grid">
      <div v-for="room in filteredRooms" :key="room.id" class="room-card">
        <div class="room-header">
          <span class="room-number">Room {{ room.number }}</span>
          <span :class="['status-badge', room.status]">{{ room.status }}</span>
        </div>
        <div class="room-details">
          <h3>{{ room.type }}</h3>
          <p class="floor">Floor: {{ room.floor }}</p>
          <div class="amenities">
            <span v-for="amenity in room.amenities" :key="amenity" class="amenity-tag">
              {{ amenity }}
            </span>
          </div>
          <div class="price">{{ formatCurrency(room.price) }}/night</div>
        </div>
        <div class="room-actions">
          <button @click="viewRoom(room)" class="btn-sm">View</button>
          <button @click="editRoom(room)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Room {
  id: number
  number: string
  type: string
  floor: number
  price: number
  status: string
  amenities: string[]
}

const rooms = ref<Room[]>([])
const typeFilter = ref('')
const statusFilter = ref('')

const filteredRooms = computed(() => {
  return rooms.value.filter(room => {
    if (typeFilter.value && room.type !== typeFilter.value) return false
    if (statusFilter.value && room.status !== statusFilter.value) return false
    return true
  })
})

const formatCurrency = (amount: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(amount)
}

const addRoom = () => {
  // Open modal to add new room
}

const viewRoom = (room: Room) => {
  // Open room details
}

const editRoom = (room: Room) => {
  // Open edit modal
}

const fetchRooms = async () => {
  try {
    const response = await fetch('/api/hotels/rooms')
    const data = await response.json()
    rooms.value = data
  } catch (error) {
    console.error('Failed to fetch rooms:', error)
  }
}

onMounted(() => {
  fetchRooms()
})
</script>

<style scoped>
.hotels-rooms {
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

.rooms-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
}

.room-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.room-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.room-number {
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

.status-badge.available {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.occupied {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.maintenance {
  background: #fef3c7;
  color: #92400e;
}

.room-details {
  padding: 16px;
}

.room-details h3 {
  margin: 0 0 4px 0;
  font-size: 14px;
  font-weight: 600;
}

.floor {
  margin: 0 0 12px 0;
  font-size: 12px;
  color: #6b7280;
}

.amenities {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 12px;
}

.amenity-tag {
  background: #f3f4f6;
  color: #4b5563;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 11px;
}

.price {
  font-size: 16px;
  font-weight: 600;
  color: #059669;
}

.room-actions {
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
