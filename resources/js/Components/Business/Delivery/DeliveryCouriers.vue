<template>
  <div class="delivery-couriers">
    <div class="header">
      <h2>Couriers</h2>
      <button @click="addCourier" class="btn-primary">Add Courier</button>
    </div>

    <div class="filters">
      <select v-model="statusFilter">
        <option value="">All Status</option>
        <option value="available">Available</option>
        <option value="busy">Busy</option>
        <option value="offline">Offline</option>
      </select>
      <select v-model="vehicleFilter">
        <option value="">All Vehicles</option>
        <option value="bike">Bike</option>
        <option value="car">Car</option>
        <option value="scooter">Scooter</option>
      </select>
    </div>

    <div class="couriers-grid">
      <div v-for="courier in filteredCouriers" :key="courier.id" class="courier-card">
        <div class="courier-header">
          <span class="name">{{ courier.name }}</span>
          <span :class="['status-badge', courier.status]">{{ courier.status }}</span>
        </div>
        <div class="courier-details">
          <p class="phone">{{ courier.phone }}</p>
          <p class="vehicle">Vehicle: {{ courier.vehicle }}</p>
          <p class="rating">Rating: {{ courier.rating }} ⭐</p>
          <div class="stats">
            <span>{{ courier.deliveries_today }} today</span>
            <span>{{ courier.total_deliveries }} total</span>
          </div>
        </div>
        <div class="courier-actions">
          <button @click="viewCourier(courier)" class="btn-sm">View</button>
          <button @click="editCourier(courier)" class="btn-sm">Edit</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

interface Courier {
  id: number
  name: string
  phone: string
  vehicle: string
  rating: number
  deliveries_today: number
  total_deliveries: number
  status: string
}

const couriers = ref<Courier[]>([])
const statusFilter = ref('')
const vehicleFilter = ref('')

const filteredCouriers = computed(() => {
  return couriers.value.filter(courier => {
    if (statusFilter.value && courier.status !== statusFilter.value) return false
    if (vehicleFilter.value && courier.vehicle !== vehicleFilter.value) return false
    return true
  })
}

const addCourier = () => {
  // Open modal to add new courier
}

const viewCourier = (courier: Courier) => {
  // Open courier details
}

const editCourier = (courier: Courier) => {
  // Open edit modal
}

const fetchCouriers = async () => {
  try {
    const response = await fetch('/api/delivery/couriers')
    const data = await response.json()
    couriers.value = data
  } catch (error) {
    console.error('Failed to fetch couriers:', error)
  }
}

onMounted(() => {
  fetchCouriers()
})
</script>

<style scoped>
.delivery-couriers {
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

.couriers-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.courier-card {
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.courier-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.name {
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

.status-badge.available {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.busy {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.offline {
  background: #e5e7eb;
  color: #374151;
}

.courier-details {
  padding: 16px;
}

.phone, .vehicle, .rating {
  margin: 0 0 8px 0;
  font-size: 13px;
  color: #6b7280;
}

.stats {
  display: flex;
  gap: 12px;
  font-size: 13px;
  color: #374151;
}

.courier-actions {
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
