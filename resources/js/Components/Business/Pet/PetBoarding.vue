<template>
  <div class="pet-boarding">
    <div class="header">
      <h2>Pet Boarding</h2>
      <button @click="addBoarding" class="btn-primary">Add Boarding</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="booked">Booked</option>
        <option value="checked_in">Checked In</option>
        <option value="checked_out">Checked Out</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <div class="boarding-table">
      <table>
        <thead>
          <tr>
            <th>Booking ID</th>
            <th>Pet</th>
            <th>Owner</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Duration</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="boarding in filteredBoarding" :key="boarding.id">
            <td>#{{ boarding.id }}</td>
            <td>{{ boarding.pet }}</td>
            <td>{{ boarding.owner }}</td>
            <td>{{ formatDate(boarding.check_in) }}</td>
            <td>{{ formatDate(boarding.check_out) }}</td>
            <td>{{ boarding.duration }} nights</td>
            <td>
              <span :class="['status-badge', boarding.status]">{{ boarding.status }}</span>
            </td>
            <td>
              <button @click="viewBoarding(boarding)" class="btn-sm">View</button>
              <button @click="editBoarding(boarding)" class="btn-sm">Edit</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Boarding {
  id: number
  pet: string
  owner: string
  check_in: string
  check_out: string
  duration: number
  status: string
}

const boarding = ref<Boarding[]>([])
const statusFilter = ref('')

const filteredBoarding = computed(() => {
  if (!statusFilter.value) return boarding.value
  return boarding.value.filter(item => item.status === statusFilter.value)
})

const formatDate = (date: string): string => {
  return new Date(date).toLocaleDateString('ru-RU')
}

const addBoarding = () => {
  // Open modal to add new boarding
}

const viewBoarding = (item: Boarding) => {
  // Open boarding details
}

const editBoarding = (item: Boarding) => {
  // Open edit modal
}

const fetchBoarding = async () => {
  try {
    const response = await fetch('/api/pet/boarding')
    const data = await response.json()
    boarding.value = data
  } catch (error) {
    console.error('Failed to fetch boarding:', error)
  }
}

onMounted(() => {
  fetchBoarding()
})
</script>

<style scoped>
.pet-boarding {
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

.boarding-table {
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

.status-badge.booked {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.checked_in {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.checked_out {
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
